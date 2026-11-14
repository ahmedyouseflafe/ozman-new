<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\ShopOrdersController;
use App\Models\FrontOrder;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The shop owner's side of the app: their shops, live orders and status changes. Status changes
 * run through the website's own dashboards, so the customer push, drivers and timers stay identical.
 */
class MerchantController extends Controller
{
    private const RESTAURANT_STATUSES = [
        'new' => 'جديد', 'preparing' => 'قيد التحضير', 'ready' => 'جاهز',
        'out_for_delivery' => 'خرج للتوصيل', 'completed' => 'مكتمل', 'cancelled' => 'ملغي',
    ];

    private const ACTIVE_FIRST = "CASE status WHEN 'new' THEN 0 WHEN 'preparing' THEN 1 WHEN 'ready' THEN 2 WHEN 'out_for_delivery' THEN 3 ELSE 4 END";

    public function shops(Request $request): JsonResponse
    {
        $user = $request->user();
        $shops = Shop::query()
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereIn('id', $user->accessibleShopIds() ?: [0]))
            ->where('is_active', true)->latest('id')->limit(50)->get();

        return response()->json(['data' => $shops->map(fn (Shop $shop) => Presenter::shop($shop) + [
            'new_orders' => $this->ordersOf($shop)->where('status', 'new')->count(),
        ])]);
    }

    public function orders(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'period' => ['nullable', 'in:today,week,month,all'],
        ]);
        $restaurant = $shop->catalog_type === 'restaurant';
        $period = $data['period'] ?? ($restaurant ? 'today' : 'all');
        $periodOrders = $this->inPeriod($this->ordersOf($shop), $period);
        $orders = (clone $periodOrders)
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByRaw(self::ACTIVE_FIRST)->latest()->limit(80)->get();

        return response()->json([
            'shop' => Presenter::shop($shop),
            'period' => $period,
            'stats' => [
                'orders' => (clone $periodOrders)->count(),
                'new' => (clone $periodOrders)->where('status', 'new')->count(),
                'preparing' => (clone $periodOrders)->where('status', 'preparing')->count(),
                'ready' => (clone $periodOrders)->whereIn('status', ['ready', 'out_for_delivery'])->count(),
                'sales_total' => Presenter::price((clone $periodOrders)->whereIn('status', ['completed', 'delivered'])->sum('total')) ?? 0,
            ],
            'data' => $orders->map(fn (FrontOrder $order) => $this->summary($order, $shop)),
        ]);
    }

    public function show(Request $request, FrontOrder $order): JsonResponse
    {
        $shop = $order->shop;
        abort_unless($shop, 404);
        $this->authorizeShop($request->user(), $shop);

        return response()->json(['order' => $this->detail($order, $shop)]);
    }

    public function status(Request $request, FrontOrder $order): JsonResponse
    {
        $shop = $order->shop;
        abort_unless($shop, 404);
        $user = $request->user();
        $this->authorizeShop($user, $shop);
        $restaurant = filled($order->order_type);
        abort_unless($user->isSuperAdmin() || $user->canAccessRouteName($restaurant ? 'restaurant.orders.status' : 'shop-orders.status'), 403);

        // The same code the website dashboards run (validation, customer push, drivers, timers).
        $restaurant
            ? app()->call([app(RestaurantController::class), 'status'], ['request' => $request, 'order' => $order])
            : app()->call([app(ShopOrdersController::class), 'status'], ['request' => $request, 'order' => $order]);

        return response()->json(['order' => $this->detail($order->fresh(), $shop)]);
    }

    public function availability(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        $open = (bool) $request->validate(['is_accepting_orders' => ['required', 'boolean']])['is_accepting_orders'];
        $shop->update(['is_accepting_orders' => $open]);

        return response()->json(['shop' => Presenter::shop($shop->fresh())]);
    }

    private function ordersOf(Shop $shop)
    {
        $query = FrontOrder::query()->where('shop_id', $shop->id);

        return $shop->catalog_type === 'restaurant'
            ? $query->whereNotNull('order_type')
            : $query->whereNull('order_type')->where('order_number', 'not like', 'ORD-%');
    }

    private function summary(FrontOrder $order, Shop $shop): array
    {
        $items = collect($order->items ?? []);
        $delivery = $order->order_type === 'delivery'
            || (! $order->order_type && (filled($order->customer_address) || str_contains((string) $order->customer_notes, 'توصيل')));

        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'short_number' => \Illuminate\Support\Str::afterLast($order->order_number, '-'),
            'status' => $order->status,
            'status_label' => $this->statuses($order, $shop)[$order->status] ?? $order->statusLabel(),
            'type' => $order->order_type ?: ($delivery ? 'delivery' : 'pickup'),
            'is_restaurant' => filled($order->order_type),
            'table' => $order->restaurantTable?->name,
            'channel' => $order->order_channel,
            'customer_name' => $order->customer_name,
            'items_count' => (int) $items->sum(fn ($item) => (int) ($item['qty'] ?? 1)),
            'total' => Presenter::price($order->total),
            'created_at' => $order->created_at?->toIso8601String(),
            'preparation_due_at' => $order->status === 'preparing' ? $order->preparationDueAt()?->toIso8601String() : null,
        ];
    }

    private function detail(FrontOrder $order, Shop $shop): array
    {
        $statuses = $this->statuses($order, $shop);
        $summary = $this->summary($order, $shop);
        if ($summary['type'] !== 'delivery') {
            unset($statuses['out_for_delivery']);
        }

        return $summary + [
            'customer_phone' => $order->customer_phone ?: $order->customer_whatsapp,
            'customer_address' => $order->customer_address,
            'customer_notes' => $order->customer_notes,
            'map_link' => $order->map_link,
            'estimated_preparation_minutes' => $order->estimated_preparation_minutes,
            'items' => collect($order->items ?? [])->map(fn ($item) => [
                'name' => trim(($item['name'] ?? '').' '.($item['size'] ?? '')),
                'qty' => (int) ($item['qty'] ?? 1),
                'price' => Presenter::price($item['price'] ?? 0),
                'notes' => collect([
                    ! empty($item['choices']) ? \App\Services\MealChoiceService::summary($item['choices']) : null,
                    ! empty($item['addons']) ? 'إضافات: '.implode('، ', (array) $item['addons']) : null,
                    ! empty($item['excluded']) ? 'بدون: '.implode('، ', (array) $item['excluded']) : null,
                    $item['notes'] ?? null,
                ])->filter()->implode(' · ') ?: null,
            ])->values(),
            'statuses' => collect($statuses)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'server_time' => now()->toIso8601String(),
        ] + ($order->order_type === 'delivery' ? [
            // Who's delivering, and the restaurant's active drivers to pick from.
            'driver' => $order->restaurantDriver ? ['id' => $order->restaurantDriver->id, 'name' => $order->restaurantDriver->user?->name,
                'phone' => $order->restaurantDriver->user?->phone] : null,
            'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
            'drivers' => $shop->restaurantDrivers()->where('is_active', true)->whereHas('user', fn ($q) => $q->where('is_active', true))->with('user')->get()
                ->map(fn ($driver) => ['id' => $driver->id, 'name' => $driver->user->name])->values(),
            'can_assign_driver' => ! in_array($order->status, ['completed', 'cancelled'], true),
        ] : []);
    }

    private function statuses(FrontOrder $order, Shop $shop): array
    {
        return filled($order->order_type) ? self::RESTAURANT_STATUSES : ShopOrdersController::STATUSES;
    }

    private function inPeriod($query, string $period)
    {
        return match ($period) {
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'all' => $query,
            default => $query->whereDate('created_at', today()),
        };
    }

    private function authorizeShop(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
    }
}
