<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{MarketListing, MarketOrder, User};
use App\Services\FreeMarket;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Cache;

/** "المتجر الحر" in the app, on the same rules as the website (App\Services\FreeMarket). */
class MarketController extends Controller
{
    public function __construct(private FreeMarket $market) {}

    public function meta(): JsonResponse
    {
        $pairs = fn (array $items) => collect($items)->map(fn ($label, $key) => ['key' => $key, 'label' => __($label)])->values();

        return response()->json([
            'categories' => $pairs(config('market.categories')),
            'conditions' => $pairs(config('market.conditions')),
            'cities' => config('market.cities'),
            'max_images' => (int) config('market.max_images', 6),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:40'],
            'condition' => ['nullable', 'string', 'max:20'], 'city' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'in:latest,price_asc,price_desc'], 'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $like = fn (string $value) => '%'.addcslashes($value, '%_\\').'%';
        $query = MarketListing::visible()->with(['images', 'seller:id,name,username'])
            ->when(trim((string) ($data['q'] ?? '')) !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', $like(trim($data['q'])))
                ->orWhere('description', 'like', $like(trim($data['q'])))))
            ->when(array_key_exists((string) ($data['category'] ?? ''), config('market.categories')), fn ($q) => $q->where('category', $data['category']))
            ->when(array_key_exists((string) ($data['condition'] ?? ''), config('market.conditions')), fn ($q) => $q->where('condition', $data['condition']))
            ->when(trim((string) ($data['city'] ?? '')) !== '', fn ($q) => $q->where('city', 'like', $like(trim($data['city']))));
        $query = match ($data['sort'] ?? 'latest') {
            'price_asc' => $query->orderBy('price')->latest('id'),
            'price_desc' => $query->orderByDesc('price')->latest('id'),
            default => $query->latest('id'),
        };
        $page = $query->paginate(24);

        return response()->json([
            'data' => collect($page->items())->map(fn (MarketListing $listing) => $this->summary($listing)),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, MarketListing $listing): JsonResponse
    {
        $user = $request->user();
        $listing->load(['images', 'seller']);
        $mine = $listing->canBeManagedBy($user);
        abort_unless($listing->isVisible() || $mine, 404);
        // One view per person (or phone) per day; the seller's own visits don't count.
        if (! $mine && Cache::add('market_view:'.$listing->id.':'.($user?->id ?? $request->ip()).':'.today()->toDateString(), 1, now()->endOfDay())) {
            MarketListing::query()->whereKey($listing->id)->increment('views');
        }
        $more = MarketListing::visible()->with('images')->where('user_id', $listing->user_id)->whereKeyNot($listing->id)->latest('id')->limit(6)->get();

        return response()->json(['listing' => $this->summary($listing) + [
            'description' => $listing->description,
            'images' => $listing->images->map(fn ($image) => ['id' => $image->id, 'url' => route('market.image', $image)])->values(),
            'views' => (int) $listing->views,
            'status' => $listing->status, 'status_label' => $listing->statusLabel(),
            'mine' => $mine,
            'can_order' => ! $mine && $this->market->isMember($user) && $listing->isVisible(),
            'seller_since' => $listing->seller?->created_at?->toIso8601String(),
        ], 'more' => $more->map(fn (MarketListing $other) => $this->summary($other))]);
    }

    public function order(Request $request, MarketListing $listing): JsonResponse
    {
        $order = $this->market->placeOrder($request->user(), $listing, $request->all());

        return response()->json(['order' => $this->presentOrder($order->load('listing.images'), $request->user()),
            'message' => __('وصل طلبك للبائع ✅ رقم الطلب :number.', ['number' => $order->order_number])], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $this->member($request->user());
        $listings = MarketListing::where('user_id', $request->user()->id)->with('images')
            ->withCount(['orders as open_orders_count' => fn ($q) => $q->where('status', 'pending')])->latest('id')->limit(100)->get();

        return response()->json(['data' => $listings->map(fn (MarketListing $listing) => $this->summary($listing) + [
            'status' => $listing->status, 'status_label' => $listing->statusLabel(),
            'views' => (int) $listing->views, 'open_orders' => (int) $listing->open_orders_count,
        ])]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->member($request->user());
        $listing = $this->market->createListing($request->user(), $this->market->validateListing($request->all(), true), $request->file('images', []));

        return response()->json(['listing' => $this->summary($listing->load('images')), 'message' => __('تم نشر منتجك بالمتجر الحر 🎉')], 201);
    }

    /** POST, because phones send photos as multipart form data. */
    public function update(Request $request, MarketListing $listing): JsonResponse
    {
        $this->owner($request->user(), $listing);
        $data = $this->market->validateListing($request->all(), false);
        $listing = $this->market->updateListing($listing, $data, $request->file('images', []), (array) $request->input('remove_images', []));

        return response()->json(['listing' => $this->summary($listing), 'message' => __('تم حفظ التعديلات.')]);
    }

    public function toggle(Request $request, MarketListing $listing): JsonResponse
    {
        $this->owner($request->user(), $listing);
        $listing = $this->market->toggleListing($listing);

        return response()->json(['status' => $listing->status, 'status_label' => $listing->statusLabel(),
            'message' => $listing->status === MarketListing::ACTIVE ? __('المنتج رجع معروض.') : __('تم إخفاء المنتج.')]);
    }

    public function destroy(Request $request, MarketListing $listing): JsonResponse
    {
        $this->owner($request->user(), $listing);
        $this->market->deleteListing($listing);

        return response()->json(['message' => __('تم حذف المنتج.')]);
    }

    public function purchases(Request $request): JsonResponse
    {
        $this->member($request->user());
        $orders = MarketOrder::where('buyer_id', $request->user()->id)->with(['seller:id,name,username,phone', 'listing.images'])->latest('id')->limit(100)->get();

        return response()->json(['data' => $orders->map(fn (MarketOrder $order) => $this->presentOrder($order, $request->user()))]);
    }

    public function sales(Request $request): JsonResponse
    {
        $this->member($request->user());
        $orders = MarketOrder::where('seller_id', $request->user()->id)->with(['buyer:id,name,username', 'listing.images'])
            ->orderByRaw("case status when 'pending' then 0 when 'accepted' then 1 else 2 end")->latest('id')->limit(100)->get();

        return response()->json(['data' => $orders->map(fn (MarketOrder $order) => $this->presentOrder($order, $request->user()))]);
    }

    /** action: accept / reject / complete / cancel (seller), or buyer_cancel (buyer). */
    public function updateOrder(Request $request, MarketOrder $order): JsonResponse
    {
        $action = $request->validate(['action' => ['required', 'in:accept,reject,complete,cancel,buyer_cancel']])['action'];
        $order = $action === 'buyer_cancel'
            ? $this->market->buyerCancel($request->user(), $order)
            : $this->market->sellerAction($request->user(), $order, $action);

        return response()->json(['order' => $this->presentOrder($order->load('listing.images'), $request->user()), 'message' => $order->statusLabel()]);
    }

    private function summary(MarketListing $listing): array
    {
        return [
            'id' => $listing->id,
            'title' => $listing->title,
            'price' => Presenter::price($listing->price),
            'category' => $listing->category, 'category_label' => $listing->categoryLabel(),
            'condition' => $listing->condition, 'condition_label' => $listing->conditionLabel(),
            'city' => $listing->city,
            'quantity' => (int) $listing->quantity,
            'cover' => $listing->coverUrl(),
            'seller' => $listing->relationLoaded('seller') && $listing->seller ? ['id' => $listing->seller->id, 'name' => $listing->seller->name] : null,
            'created_at' => $listing->created_at?->toIso8601String(),
            'share_url' => $listing->url(),
        ];
    }

    /** Each side sees the other's contact once it matters: the buyer's details always, the seller's phone after accepting. */
    private function presentOrder(MarketOrder $order, User $viewer): array
    {
        $selling = (int) $order->seller_id === (int) $viewer->id;

        return [
            'id' => $order->id, 'number' => $order->order_number,
            'status' => $order->status, 'status_label' => $order->statusLabel(),
            'title' => $order->listing_title, 'listing_id' => $order->market_listing_id,
            'cover' => $order->listing?->coverUrl(),
            'quantity' => (int) $order->quantity, 'unit_price' => Presenter::price($order->unit_price), 'total' => Presenter::price($order->total),
            'created_at' => $order->created_at?->toIso8601String(),
            'selling' => $selling,
            'buyer' => $selling ? ['name' => $order->buyer_name, 'phone' => $order->buyer_phone, 'city' => $order->city, 'address' => $order->address, 'note' => $order->note] : null,
            'seller' => ! $selling && $order->seller ? [
                'name' => $order->seller->name,
                'phone' => in_array($order->status, [MarketOrder::ACCEPTED, MarketOrder::COMPLETED], true) ? $order->seller->phone : null,
            ] : null,
        ];
    }

    private function member(User $user): void
    {
        abort_unless($this->market->isMember($user), 403, __('البيع والطلب بالمتجر الحر لحسابات العملاء والمروّجين.'));
    }

    private function owner(User $user, MarketListing $listing): void
    {
        abort_unless($listing->canBeManagedBy($user), 403);
    }
}
