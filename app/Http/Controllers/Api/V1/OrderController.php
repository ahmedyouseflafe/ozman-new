<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RestaurantController;
use App\Models\FrontOrder;
use App\Models\PushDevice;
use App\Models\Shop;
use App\Services\FirebaseMessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Orders from the app. Restaurant orders go through exactly the same code as the website
 * (prices, meal options, rewards, the kitchen alert), signed in as the app user.
 */
class OrderController extends Controller
{
    public function storeRestaurant(Request $request, Shop $shop, RestaurantController $restaurant, FirebaseMessagingService $firebase): JsonResponse
    {
        $user = $request->user();
        $request->validate(['device_token' => ['nullable', 'string', 'max:512']]);
        // The website's checkout fields, filled from the account when the app leaves them out.
        $request->merge([
            'customer_name' => $request->input('customer_name') ?: $user->name,
            'customer_phone' => $request->input('customer_phone') ?: $user->phone,
        ]);
        // Status updates reach the customer's phone through the app's push token.
        $token = $request->input('device_token');
        if ($token && PushDevice::where('token', $token)->exists()) {
            $request->session()->put('app_push_token', $token);
        }

        $response = $restaurant->storeOrder($request, $shop, $firebase);
        $orderId = $response->getData(true)['order_id'] ?? null;
        if ($orderId) {
            FrontOrder::whereKey($orderId)->whereNull('user_id')->update(['user_id' => $user->id]);
        }

        return $response;
    }

    public function index(Request $request): JsonResponse
    {
        $orders = FrontOrder::with('shop:id,name,slug,catalog_type,logo')
            ->where('user_id', $request->user()->id)
            ->latest('id')->limit(50)->get();

        return response()->json(['data' => $orders->map(fn ($order) => $this->summary($order))]);
    }

    public function show(Request $request, FrontOrder $order): JsonResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);
        $order->load('shop:id,name,slug,catalog_type,logo,phone,whatsapp');
        $due = $order->preparationDueAt();

        return response()->json(['order' => $this->summary($order) + [
            'items' => collect($order->items ?? [])->map(fn ($item) => [
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'] ?? '',
                'qty' => (int) ($item['qty'] ?? 1),
                'price' => Presenter::price($item['price'] ?? 0),
                'size' => $item['size'] ?? null,
                'notes' => $item['notes'] ?? null,
            ])->values(),
            'subtotal' => Presenter::price($order->subtotal),
            'discount' => Presenter::price($order->discount),
            'customer_address' => $order->customer_address,
            'customer_notes' => $order->customer_notes,
            'estimated_preparation_minutes' => $order->estimated_preparation_minutes,
            'preparation_due_at' => $due?->toIso8601String(),
            'server_time' => now()->toIso8601String(),
            'tracking_url' => $order->shop?->catalog_type === 'restaurant' ? URL::signedRoute('restaurant.orders.track', $order) : null,
            'shop_phone' => $order->shop?->phone,
            'shop_whatsapp' => $order->shop?->whatsapp,
        ]]);
    }

    /** Saves the app's push token for this user, so order updates and offers reach the phone. */
    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:512'], 'platform' => ['nullable', 'in:android,ios']]);
        PushDevice::updateOrCreate(['token' => $data['token']], [
            'user_id' => $request->user()?->id,
            'platform' => $data['platform'] ?? 'android',
            'last_seen_at' => now(),
        ]);

        return response()->json(['registered' => true]);
    }

    private function summary(FrontOrder $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'type' => $order->order_type,
            'total' => Presenter::price($order->total),
            'items_count' => (int) collect($order->items ?? [])->sum(fn ($item) => (int) ($item['qty'] ?? 1)),
            'created_at' => $order->created_at?->toIso8601String(),
            'shop' => $order->shop ? ['id' => $order->shop->id, 'name' => $order->shop->name, 'type' => $order->shop->catalog_type, 'logo' => Presenter::media($order->shop->logo)] : null,
        ];
    }
}
