<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Product, Shop, SocialPost, User};
use App\Services\Social\{ShopSocialManager, Zernio};
use Illuminate\Http\{JsonResponse, Request};
use RuntimeException;

/** The shop's social media in the app: connect Instagram/Facebook/TikTok, Claude writes the post, publish now or schedule. */
class SocialController extends Controller
{
    public function __construct(private ShopSocialManager $social) {}

    public function index(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        $accounts = $this->social->accounts($shop);

        return response()->json([
            'configured' => $this->social->configured(),
            'writer_ready' => $this->social->writerReady(),
            'platforms' => Zernio::PLATFORMS,
            'accounts' => $accounts['accounts'],
            'suggestions' => $accounts['suggestions'],
            'error' => $accounts['error'],
            'timezone' => config('services.zernio.timezone'),
            'products' => Product::query()->where('shop_id', $shop->id)->where('is_active', true)->latest()->limit(300)->get()
                ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'price' => Presenter::price($p->price), 'image' => $this->social->imageUrl($p->main_image)])->values(),
            'posts' => $this->social->posts($shop)->map(fn (SocialPost $post) => $this->present($post))->values(),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function connect(Request $request, Shop $shop, string $platform): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        abort_unless(in_array($platform, Zernio::PLATFORMS, true), 404);
        try {
            $url = $this->social->connectUrl($shop, $platform, route('social.app-callback'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => __('ما قدرنا نفتح الربط: :error', ['error' => $e->getMessage()])], 502);
        }

        return response()->json(['url' => $url]);
    }

    public function caption(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        try {
            $caption = $this->social->caption($shop, $request);
        } catch (RuntimeException $e) {
            return response()->json(['message' => __('الكاتب الذكي مش متاح هلقيت: :error', ['error' => $e->getMessage()])], 503);
        }

        return response()->json(['caption' => $caption]);
    }

    public function store(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        try {
            [$post, $when] = $this->social->publish($shop, $request->user(), $request);
        } catch (RuntimeException $e) {
            return response()->json(['message' => __('ما انتشر المنشور: :error', ['error' => $e->getMessage()])], 502);
        }

        return response()->json([
            'post' => $this->present($post->fresh('product')),
            'message' => $when ? __('تمام! المنشور مجدول :time.', ['time' => $when->translatedFormat('l j/n H:i')]) : __('تم إرسال المنشور للنشر 🚀'),
        ], 201);
    }

    public function destroy(Request $request, SocialPost $post): JsonResponse
    {
        $shop = $post->shop;
        abort_unless($shop, 404);
        $this->authorizeShop($request->user(), $shop);
        try {
            $this->social->cancel($shop, $post);
        } catch (RuntimeException $e) {
            return response()->json(['message' => __('ما قدرنا نلغي المنشور: :error', ['error' => $e->getMessage()])], 502);
        }

        return response()->json(['post' => $this->present($post->fresh('product')), 'message' => __('انلغى المنشور.')]);
    }

    private function present(SocialPost $post): array
    {
        return [
            'id' => $post->id,
            'caption' => $post->caption,
            'media_url' => $post->media_url,
            'status' => $post->status,
            'targets' => $post->targets ?? [],
            'product' => $post->product?->name,
            'scheduled_for' => $post->scheduled_for?->toIso8601String(),
            'created_at' => $post->created_at?->toIso8601String(),
            'error' => $post->error,
        ];
    }

    private function authorizeShop(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        abort_unless($user->isSuperAdmin() || $user->canAccessRouteName('social.index'), 403);
    }
}
