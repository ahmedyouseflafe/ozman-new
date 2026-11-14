<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RestaurantDriverController;
use App\Models\{FrontOrder, RestaurantDeliveryOffer, RestaurantDriver, User};
use Illuminate\Http\{JsonResponse, Request};

/**
 * The delivery driver's screen. Reading is done here; every action (accept, reject, start, deliver,
 * new arrival time) runs the website's own driver code, so its checks and customer pushes are the same.
 */
class DriverController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request->user());
        $orders = fn () => FrontOrder::query()->where('restaurant_driver_id', $driver->id)
            ->where('shop_id', $driver->shop_id)->where('order_type', 'delivery');
        $offers = $this->openOffers($driver);

        return response()->json([
            'shop' => ['id' => $driver->shop->id, 'name' => $driver->shop->name],
            'stats' => [
                'available' => (clone $offers)->count(),
                'assigned' => $orders()->whereIn('status', ['new', 'preparing', 'ready'])->count(),
                'on_the_way' => $orders()->where('status', 'out_for_delivery')->count(),
                'delivered_today' => $orders()->where('status', 'completed')->whereDate('delivered_at', today())->count(),
            ],
            'offers' => (clone $offers)->latest('offered_at')->limit(30)->get()
                ->map(fn (RestaurantDeliveryOffer $offer) => ['offer_id' => $offer->id] + $this->present($offer->order, false)),
            // Work in hand first, then what was delivered.
            'orders' => $orders()->orderByRaw("CASE status WHEN 'out_for_delivery' THEN 0 WHEN 'ready' THEN 1 WHEN 'preparing' THEN 2 WHEN 'new' THEN 3 ELSE 4 END")
                ->latest()->limit(50)->get()->map(fn (FrontOrder $order) => $this->present($order, true)),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function accept(Request $request, RestaurantDeliveryOffer $offer, RestaurantDriverController $site): JsonResponse
    {
        app()->call([$site, 'acceptOffer'], ['request' => $request, 'offer' => $offer]);

        return response()->json(['message' => 'تم استلام الطلب وإسناده إلك.']);
    }

    public function reject(Request $request, RestaurantDeliveryOffer $offer, RestaurantDriverController $site): JsonResponse
    {
        app()->call([$site, 'rejectOffer'], ['request' => $request, 'offer' => $offer]);

        return response()->json(['message' => 'تم رفض الطلب.']);
    }

    /** status = out_for_delivery (with estimated_delivery_minutes) or completed. */
    public function status(Request $request, FrontOrder $order, RestaurantDriverController $site): JsonResponse
    {
        app()->call([$site, 'updateStatus'], ['request' => $request, 'order' => $order]);

        return response()->json(['order' => $this->present($order->fresh(), true), 'message' => $request->input('status') === 'completed' ? 'تم تأكيد التسليم.' : 'بدأ التوصيل، والزبون وصله إشعار.']);
    }

    public function deliveryTime(Request $request, FrontOrder $order, RestaurantDriverController $site): JsonResponse
    {
        app()->call([$site, 'updateDeliveryTime'], ['request' => $request, 'order' => $order]);

        return response()->json(['order' => $this->present($order->fresh(), true), 'message' => 'تم تحديث وقت الوصول.']);
    }

    private function driver(User $user): RestaurantDriver
    {
        abort_unless($user->isRestaurantDriver() && $user->is_active, 403, 'هذا الحساب مش حساب مندوب.');

        return RestaurantDriver::query()->with('shop')->where('user_id', $user->id)->where('is_active', true)
            ->whereHas('shop', fn ($query) => $query->where('catalog_type', 'restaurant')->where('is_active', true))
            ->firstOrFail();
    }

    private function openOffers(RestaurantDriver $driver)
    {
        return RestaurantDeliveryOffer::query()->with('order')
            ->where('restaurant_driver_id', $driver->id)->where('status', 'offered')
            ->whereHas('order', fn ($query) => $query->where('shop_id', $driver->shop_id)->where('order_type', 'delivery')
                ->whereNull('restaurant_driver_id')->whereIn('status', ['preparing', 'ready']));
    }

    /** An offer shows the area and the food, but the customer's phone only once the driver took the order. */
    private function present(FrontOrder $order, bool $assigned): array
    {
        $due = $order->preparationDueAt();

        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'total' => Presenter::price($order->total),
            'created_at' => $order->created_at?->toIso8601String(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $assigned ? $order->customer_phone : null,
            'customer_address' => $order->customer_address,
            'customer_notes' => $assigned ? $order->customer_notes : null,
            'map_link' => $assigned ? $order->map_link : null,
            'preparation_due_at' => $due?->toIso8601String(),
            'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
            'items' => collect($order->items ?? [])->map(fn ($item) => trim(($item['qty'] ?? 1).'× '.($item['name'] ?? '').' '.($item['size'] ?? '')))->values(),
        ];
    }
}
