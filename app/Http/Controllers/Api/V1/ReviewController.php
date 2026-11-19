<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{FrontOrder, PushDevice, Shop, ShopReview};
use App\Services\FirebaseMessagingService;
use Illuminate\Http\{JsonResponse, Request};
use Throwable;

/**
 * Reviews: a customer rates a finished order once (stars and an optional comment), everyone sees the shop's
 * reviews, and the owner replies. Only reviews from real, finished orders exist.
 */
class ReviewController extends Controller
{
    public function store(Request $request, FrontOrder $order, FirebaseMessagingService $firebase): JsonResponse
    {
        $user = $request->user();
        abort_unless((int) $order->user_id === (int) $user->id, 404);
        abort_unless(in_array($order->status, ['completed', 'delivered'], true), 422, __('بتقدر تقيّم الطلب بعد ما يوصلك.'));
        abort_if(ShopReview::where('front_order_id', $order->id)->exists(), 422, __('قيّمت هالطلب قبل.'));
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
        $review = ShopReview::create([
            'shop_id' => $order->shop_id, 'user_id' => $user->id, 'front_order_id' => $order->id,
            'rating' => $data['rating'], 'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
        ]);

        // The owner hears about it on the phone.
        $shop = $order->shop;
        if ($shop?->user_id) {
            try {
                $tokens = PushDevice::where('user_id', $shop->user_id)->pluck('token');
                if ($tokens->isNotEmpty()) {
                    $firebase->sendToTokens($tokens, __('تقييم جديد ⭐ :stars', ['stars' => $review->rating.'/5']),
                        $review->comment ? mb_strimwidth($review->comment, 0, 120, '…') : __('على الطلب :number', ['number' => $order->order_number]),
                        $shop->publicUrl(), ['type' => 'shop_review', 'shop_id' => $shop->id, 'review_id' => $review->id]);
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json(['review' => $this->present($review), 'message' => __('شكراً على تقييمك 🙏')], 201);
    }

    /** The shop's visible reviews, newest first, with the average. */
    public function index(Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active, 404);
        $page = ShopReview::where('shop_id', $shop->id)->where('is_visible', true)->with('user:id,name')->latest()->orderByDesc('id')->paginate(20);

        return response()->json([
            'summary' => ShopReview::summaryFor($shop->id) + ['stars' => $this->stars($shop->id)],
            'data' => collect($page->items())->map(fn ($r) => $this->present($r))->values(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }

    public function merchant(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request, $shop);
        $page = ShopReview::where('shop_id', $shop->id)->with(['user:id,name', 'order:id,order_number'])->latest()->orderByDesc('id')->paginate(30);

        return response()->json([
            'summary' => ShopReview::summaryFor($shop->id) + ['stars' => $this->stars($shop->id)],
            'data' => collect($page->items())->map(fn ($r) => $this->present($r, true))->values(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function reply(Request $request, ShopReview $review): JsonResponse
    {
        $this->authorizeShop($request, $review->shop);
        $data = $request->validate(['reply' => ['nullable', 'string', 'max:1000']]);
        $reply = trim((string) ($data['reply'] ?? ''));
        $review->update(['reply' => $reply !== '' ? $reply : null, 'replied_at' => $reply !== '' ? now() : null]);

        return response()->json(['review' => $this->present($review->fresh(['user:id,name', 'order:id,order_number']), true), 'message' => __('انحفظ الرد.')]);
    }

    private function present(ShopReview $review, bool $forOwner = false): array
    {
        $name = trim((string) $review->user?->name);
        // Customers see a first name and an initial only.
        $shown = $review->publicName();

        return [
            'id' => $review->id, 'rating' => $review->rating, 'comment' => $review->comment,
            'name' => $forOwner ? ($name ?: __('زبون')) : $shown,
            'reply' => $review->reply, 'created_at' => $review->created_at?->toIso8601String(),
        ] + ($forOwner ? ['order_number' => $review->order?->order_number] : []);
    }

    /** How many reviews gave each number of stars (5 to 1). */
    private function stars(int $shopId): array
    {
        return ShopReview::starsFor($shopId);
    }

    private function authorizeShop(Request $request, ?Shop $shop): void
    {
        $user = $request->user();
        abort_unless($shop, 404);
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
    }
}
