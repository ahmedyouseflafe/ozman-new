<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\PushDevice;
use App\Models\RestaurantDriver;
use App\Models\Shop;
use App\Models\User;
use App\Rules\ValidPhoneNumber;
use App\Services\FirebaseMessagingService;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class RestaurantDriverController extends Controller
{
    public function store(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeRestaurantManagement($request, $shop);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:60', new ValidPhoneNumber],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        DB::transaction(function () use ($shop, $data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'restaurant_driver',
                'is_active' => true,
            ]);

            $shop->restaurantDrivers()->create([
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        });

        return back()->with('status', 'تم إنشاء حساب المندوب وربطه بهذا المطعم فقط.');
    }

    public function toggle(Request $request, RestaurantDriver $driver): RedirectResponse
    {
        $driver->loadMissing('shop', 'user');
        $this->authorizeRestaurantManagement($request, $driver->shop);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        DB::transaction(function () use ($driver, $data): void {
            $active = (bool) $data['is_active'];
            $driver->update(['is_active' => $active]);
            $driver->user->update(['is_active' => $active]);
        });

        return back()->with('status', $driver->fresh()->is_active ? 'تم تفعيل المندوب.' : 'تم إيقاف المندوب ومنع دخوله.');
    }

    public function assign(
        Request $request,
        FrontOrder $order,
        FirebaseMessagingService $firebase,
        WebPushService $webPush,
    ): RedirectResponse {
        $order->loadMissing('shop', 'restaurantDriver.user');
        abort_unless($order->shop?->catalog_type === 'restaurant' && $order->order_type === 'delivery', 404);
        $this->authorizeRestaurantManagement($request, $order->shop);
        abort_if(in_array($order->status, ['completed', 'cancelled'], true), 422, 'لا يمكن تعديل مندوب طلب منتهٍ.');

        $data = $request->validate([
            'restaurant_driver_id' => [
                'nullable',
                'integer',
                Rule::exists('restaurant_drivers', 'id')->where(fn ($query) => $query
                    ->where('shop_id', $order->shop_id)
                    ->where('is_active', true)),
            ],
        ]);
        $driverId = isset($data['restaurant_driver_id']) ? (int) $data['restaurant_driver_id'] : null;
        if ($order->status === 'out_for_delivery' && ! $driverId) {
            throw ValidationException::withMessages(['restaurant_driver_id' => 'لا يمكن إزالة المندوب بعد بدء التوصيل. اختر مندوبًا بديلًا.']);
        }
        if ($driverId && ! RestaurantDriver::query()->whereKey($driverId)->whereHas('user', fn ($query) => $query->where('is_active', true))->exists()) {
            throw ValidationException::withMessages(['restaurant_driver_id' => 'حساب المندوب المحدد غير فعال.']);
        }
        $changed = (int) $order->restaurant_driver_id !== (int) $driverId;

        $order->update([
            'restaurant_driver_id' => $driverId,
            'driver_assigned_at' => $driverId ? now() : null,
            ...($changed ? [
                'driver_latitude' => null,
                'driver_longitude' => null,
                'driver_location_accuracy_meters' => null,
                'driver_location_at' => null,
            ] : []),
        ]);
        $order->refresh()->load('shop', 'restaurantDriver.user');

        if ($changed && $order->restaurantDriver) {
            $this->sendAssignmentNotifications($order, $firebase, $webPush);
        }

        return back()->with('status', $driverId
            ? ($changed ? 'تم تعيين المندوب. ظهر الطلب في لوحته وسيصله إشعار إذا فعّله على جهازه.' : 'المندوب معيّن لهذا الطلب مسبقًا.')
            : 'تم إلغاء تعيين المندوب.');
    }

    public function dashboard(Request $request): View
    {
        $driver = $this->activeDriver($request);
        $ordersQuery = FrontOrder::query()
            ->with('shop')
            ->where('restaurant_driver_id', $driver->id)
            ->where('shop_id', $driver->shop_id)
            ->where('order_type', 'delivery');

        return view('driver.dashboard', [
            'driver' => $driver->loadMissing('shop', 'user'),
            'orders' => (clone $ordersQuery)->latest()->limit(50)->get(),
            'stats' => [
                'assigned' => (clone $ordersQuery)->whereIn('status', ['new', 'preparing', 'ready'])->count(),
                'on_the_way' => (clone $ordersQuery)->where('status', 'out_for_delivery')->count(),
                'delivered_today' => (clone $ordersQuery)->where('status', 'completed')->whereDate('delivered_at', today())->count(),
            ],
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $driver = $this->activeDriver($request);
        $ordersQuery = FrontOrder::query()
            ->with('shop')
            ->where('restaurant_driver_id', $driver->id)
            ->where('shop_id', $driver->shop_id)
            ->where('order_type', 'delivery');

        return response()->json([
            'stats' => [
                'assigned' => (clone $ordersQuery)->whereIn('status', ['new', 'preparing', 'ready'])->count(),
                'on_the_way' => (clone $ordersQuery)->where('status', 'out_for_delivery')->count(),
                'delivered_today' => (clone $ordersQuery)->where('status', 'completed')->whereDate('delivered_at', today())->count(),
            ],
            'html' => view('driver.partials.orders', [
                'orders' => (clone $ordersQuery)->latest()->limit(50)->get(),
            ])->render(),
        ]);
    }

    public function updateStatus(
        Request $request,
        FrontOrder $order,
        FirebaseMessagingService $firebase,
    ): RedirectResponse {
        $driver = $this->activeDriver($request);
        abort_unless(
            (int) $order->restaurant_driver_id === (int) $driver->id
            && (int) $order->shop_id === (int) $driver->shop_id
            && $order->order_type === 'delivery',
            403,
        );

        $data = $request->validate(['status' => ['required', Rule::in(['out_for_delivery', 'completed'])]]);
        $allowed = [
            'ready' => ['out_for_delivery'],
            'out_for_delivery' => ['completed'],
        ];
        abort_unless(in_array($data['status'], $allowed[$order->status] ?? [], true), 422, 'لا يمكن تنفيذ هذه الخطوة الآن.');

        $attributes = ['status' => $data['status']];
        if ($data['status'] === 'out_for_delivery') {
            $attributes['picked_up_at'] = now();
        } else {
            $attributes['delivered_at'] = now();
            $attributes['driver_latitude'] = null;
            $attributes['driver_longitude'] = null;
            $attributes['driver_location_accuracy_meters'] = null;
            $attributes['driver_location_at'] = null;
        }
        $order->update($attributes);
        $this->sendCustomerDeliveryStatus($order->fresh('shop'), $firebase);

        return back()->with('status', $data['status'] === 'completed'
            ? 'تم تأكيد تسليم الطلب وإبلاغ العميل.'
            : 'تم بدء التوصيل وإبلاغ العميل أن طلبه في الطريق.');
    }

    public function updateLocation(Request $request, FrontOrder $order): JsonResponse
    {
        $this->authorizeLiveLocation($request, $order);
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0', 'max:250'],
        ]);

        if ($order->driver_location_at?->isAfter(now()->subSeconds(8))) {
            return response()->json(['accepted' => false]);
        }

        $updated = FrontOrder::query()->whereKey($order->id)
            ->where('restaurant_driver_id', $order->restaurant_driver_id)
            ->where('status', 'out_for_delivery')->update([
            'driver_latitude' => $data['latitude'],
            'driver_longitude' => $data['longitude'],
            'driver_location_accuracy_meters' => (int) round($data['accuracy']),
            'driver_location_at' => now(),
        ]);

        abort_unless($updated, 422, 'انتهى التوصيل أو تغيّر المندوب.');

        return response()->json(['accepted' => true]);
    }

    public function clearLocation(Request $request, FrontOrder $order): JsonResponse
    {
        $this->authorizeLiveLocation($request, $order);
        FrontOrder::query()->whereKey($order->id)
            ->where('restaurant_driver_id', $order->restaurant_driver_id)
            ->where('status', 'out_for_delivery')->update([
            'driver_latitude' => null,
            'driver_longitude' => null,
            'driver_location_accuracy_meters' => null,
            'driver_location_at' => null,
        ]);

        return response()->json(['cleared' => true]);
    }

    private function authorizeLiveLocation(Request $request, FrontOrder $order): void
    {
        $driver = $this->activeDriver($request);
        abort_unless(
            (int) $order->restaurant_driver_id === (int) $driver->id
            && (int) $order->shop_id === (int) $driver->shop_id
            && $order->order_type === 'delivery',
            403,
        );
        abort_unless($order->status === 'out_for_delivery', 422, 'مشاركة الموقع متاحة أثناء التوصيل فقط.');
    }

    private function activeDriver(Request $request): RestaurantDriver
    {
        $user = $request->user();
        abort_unless($user?->isRestaurantDriver() && $user->is_active, 403);

        return RestaurantDriver::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('shop', fn ($query) => $query->where('catalog_type', 'restaurant')->where('is_active', true))
            ->firstOrFail();
    }

    private function authorizeRestaurantManagement(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless(
            $shop->catalog_type === 'restaurant'
            && $user
            && ($user->isSuperAdmin() || in_array((int) $shop->id, $user->accessibleShopIds(), true)),
            403,
        );
    }

    private function sendAssignmentNotifications(FrontOrder $order, FirebaseMessagingService $firebase, WebPushService $webPush): void
    {
        $driver = $order->restaurantDriver;
        $tokens = PushDevice::query()->where('user_id', $driver->user_id)->pluck('token');

        try {
            if ($tokens->isNotEmpty()) {
                $firebase->sendToTokens(
                    $tokens,
                    'طلب توصيل جديد · '.$order->shop->name,
                    "تم تعيين الطلب {$order->order_number} لك بقيمة {$order->total} شيكل",
                    route('driver.dashboard').'#delivery-order-'.$order->id,
                    ['type' => 'driver_order_assignment', 'order_id' => $order->id, 'shop_id' => $order->shop_id],
                );
            }

            if (filled($order->customer_push_token)) {
                $firebase->sendToTokens(
                    [$order->customer_push_token],
                    'تم تعيين مندوب لطلبك 🛵',
                    "المندوب {$driver->user->name} سيتولى توصيل طلبك {$order->order_number}.",
                    URL::signedRoute('restaurant.orders.track', $order),
                    ['type' => 'restaurant_order_status', 'status' => $order->status, 'order_id' => $order->id],
                );
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $webPush->sendToDriver(
                $driver,
                'طلب توصيل جديد · '.$order->shop->name,
                "تم تعيين الطلب {$order->order_number} لك بقيمة {$order->total} شيكل",
                route('driver.dashboard').'#delivery-order-'.$order->id,
                ['type' => 'driver_order_assignment', 'order_id' => $order->id, 'shop_id' => $order->shop_id],
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sendCustomerDeliveryStatus(FrontOrder $order, FirebaseMessagingService $firebase): void
    {
        if (! filled($order->customer_push_token) || ! $order->shop) {
            return;
        }

        $title = $order->status === 'completed' ? 'تم تسليم طلبك ✅' : 'طلبك في الطريق إليك 🛵';
        $body = $order->status === 'completed'
            ? "تم تسليم طلبك {$order->order_number} من {$order->shop->name}. نتمنى لك وجبة شهية."
            : "غادر طلبك {$order->order_number} المطعم وهو الآن في الطريق إليك.";

        try {
            $firebase->sendToTokens(
                [$order->customer_push_token],
                $title,
                $body,
                URL::signedRoute('restaurant.orders.track', $order),
                ['type' => 'restaurant_order_status', 'status' => $order->status, 'order_id' => $order->id],
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
