<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantDriverController;
use App\Models\{FrontOrder, RestaurantDriver, RestaurantTable, Shop, User};
use Illuminate\Http\{JsonResponse, Request};

/**
 * Restaurant tables (QR ordering) and delivery drivers in the app. Changes run through the
 * website's dashboard code, so rules and notifications stay the same.
 */
class RestaurantManageController extends Controller
{
    /** A scanned table QR: which restaurant and table it belongs to. */
    public function table(string $slug, string $code): JsonResponse
    {
        $shop = Shop::where('slug', $slug)->where('is_active', true)->where('catalog_type', 'restaurant')->firstOrFail();
        $table = $shop->restaurantTables()->where('code', $code)->where('is_active', true)->firstOrFail();

        return response()->json(['shop' => Presenter::shop($shop), 'table' => ['name' => $table->name, 'code' => $table->code]]);
    }

    public function tables(Request $request, Shop $shop): JsonResponse
    {
        $this->authorize($request->user(), $shop);

        return response()->json(['data' => $shop->restaurantTables()->latest()->get()->map(fn (RestaurantTable $table) => $this->presentTable($shop, $table))]);
    }

    public function storeTable(Request $request, Shop $shop, RestaurantController $site): JsonResponse
    {
        $this->authorize($request->user(), $shop);
        $site->storeTable($request, $shop);
        $table = $shop->restaurantTables()->latest('id')->first();

        return response()->json(['table' => $this->presentTable($shop, $table), 'message' => 'تمت إضافة الطاولة وإنشاء QR خاص بها.'], 201);
    }

    public function destroyTable(Request $request, RestaurantTable $table, RestaurantController $site): JsonResponse
    {
        $this->authorize($request->user(), $table->shop);
        $site->destroyTable($request, $table);

        return response()->json(['message' => 'تم حذف الطاولة.']);
    }

    public function drivers(Request $request, Shop $shop): JsonResponse
    {
        $this->authorize($request->user(), $shop);
        $drivers = $shop->restaurantDrivers()->with('user')->withCount(['orders as active_orders_count' => fn ($q) => $q->whereIn('status', ['ready', 'out_for_delivery'])])
            ->latest()->get();

        return response()->json(['data' => $drivers->map(fn (RestaurantDriver $driver) => $this->presentDriver($driver))]);
    }

    public function storeDriver(Request $request, Shop $shop, RestaurantDriverController $site): JsonResponse
    {
        $this->authorize($request->user(), $shop);
        $site->store($request, $shop);
        $driver = $shop->restaurantDrivers()->with('user')->latest('id')->first();

        return response()->json(['driver' => $this->presentDriver($driver), 'message' => 'تم إنشاء حساب المندوب وربطه بهذا المطعم فقط.'], 201);
    }

    public function toggleDriver(Request $request, RestaurantDriver $driver, RestaurantDriverController $site): JsonResponse
    {
        $this->authorize($request->user(), $driver->shop);
        $site->toggle($request, $driver);

        return response()->json(['driver' => $this->presentDriver($driver->fresh('user'))]);
    }

    public function assign(Request $request, FrontOrder $order): JsonResponse
    {
        abort_unless($order->shop, 404);
        $this->authorize($request->user(), $order->shop);
        app()->call([app(RestaurantDriverController::class), 'assign'], ['request' => $request, 'order' => $order]);

        return app()->call([app(MerchantController::class), 'show'], ['request' => $request, 'order' => $order->fresh()]);
    }

    private function presentTable(Shop $shop, RestaurantTable $table): array
    {
        return [
            'id' => $table->id, 'name' => $table->name, 'capacity' => $table->capacity, 'is_active' => (bool) $table->is_active,
            'link' => route('restaurant.table', [$shop, $table->code]),
            'qr' => route('restaurant.tables.qr', $table),
        ];
    }

    private function presentDriver(RestaurantDriver $driver): array
    {
        return [
            'id' => $driver->id, 'name' => $driver->user?->name, 'email' => $driver->user?->email, 'phone' => $driver->user?->phone,
            'is_active' => (bool) $driver->is_active && (bool) $driver->user?->is_active,
            'active_orders' => (int) ($driver->active_orders_count ?? 0),
        ];
    }

    private function authorize(User $user, ?Shop $shop): void
    {
        abort_unless($shop && $shop->catalog_type === 'restaurant', 404);
        abort_unless($user->isSuperAdmin() || in_array((int) $shop->id, $user->accessibleShopIds(), true), 403);
    }
}
