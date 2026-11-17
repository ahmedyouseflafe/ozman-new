<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\{Controller, DistributorController, ShopOutletController};
use App\Models\{Distributor, DistributorMarketer, Shop, ShopOutlet, User};
use Illuminate\Http\{JsonResponse, Request};

/**
 * Krembo's network in the app: the company keeps its list of points of sale, and a distributor adds and manages its
 * marketers. Every change runs through the dashboard's own code.
 */
class KremboManageController extends Controller
{
    // ── Points of sale (the company) ─────────────────────────────────────────

    public function outlets(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop, 'shop-outlets.index');

        return response()->json([
            'data' => $shop->outlets()->orderBy('region')->orderBy('sort_order')->orderBy('name')->get()->map(fn ($o) => $this->outlet($o))->values(),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function storeOutlet(Request $request, Shop $shop, ShopOutletController $site): JsonResponse
    {
        $this->authorizeCompany($request->user(), $shop, 'shop-outlets.store');
        $before = (int) ShopOutlet::max('id');
        app()->call([$site, 'store'], ['request' => $request, 'shop' => $shop]);
        $outlet = $shop->outlets()->where('id', '>', $before)->latest('id')->firstOrFail();

        return response()->json(['outlet' => $this->outlet($outlet), 'message' => __('تمت إضافة نقطة البيع.')], 201);
    }

    public function updateOutlet(Request $request, ShopOutlet $outlet, ShopOutletController $site): JsonResponse
    {
        $shop = $outlet->shop;
        $this->authorizeCompany($request->user(), $shop, 'shop-outlets.update');
        app()->call([$site, 'update'], ['request' => $request, 'shop' => $shop, 'outlet' => $outlet]);

        return response()->json(['outlet' => $this->outlet($outlet->fresh()), 'message' => __('تم تحديث نقطة البيع.')]);
    }

    public function destroyOutlet(Request $request, ShopOutlet $outlet, ShopOutletController $site): JsonResponse
    {
        $shop = $outlet->shop;
        $this->authorizeCompany($request->user(), $shop, 'shop-outlets.destroy');
        app()->call([$site, 'destroy'], ['shop' => $shop, 'outlet' => $outlet]);

        return response()->json(['message' => __('تم حذف نقطة البيع.')]);
    }

    // ── Marketers (the distributor) ──────────────────────────────────────────

    public function storeMarketer(Request $request, Distributor $distributor, DistributorController $site): JsonResponse
    {
        abort_unless($this->can($request->user(), 'distributors.marketers.store'), 403);
        $before = (int) DistributorMarketer::max('id');
        app()->call([$site, 'storeMarketer'], ['request' => $request, 'distributor' => $distributor]);
        $marketer = $distributor->marketers()->where('id', '>', $before)->latest('id')->firstOrFail();

        return response()->json(['marketer' => $this->marketer($marketer), 'message' => __('تمت إضافة المسوق بنجاح.')], 201);
    }

    public function updateMarketer(Request $request, DistributorMarketer $marketer, DistributorController $site): JsonResponse
    {
        abort_unless($this->can($request->user(), 'distributors.marketers.update'), 403);
        // The app sends only what it shows; keep the rest as it is.
        $request->merge([
            'name' => $request->input('name', $marketer->name),
            'commission_rate' => $request->input('commission_rate', $marketer->commission_rate ?? 0),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : (bool) $marketer->is_active,
        ] + ($request->has('email') ? [] : ['email' => $marketer->email]));
        app()->call([$site, 'updateMarketer'], ['request' => $request, 'marketer' => $marketer]);

        return response()->json(['marketer' => $this->marketer($marketer->fresh()), 'message' => __('تم تحديث بيانات المسوق بنجاح.')]);
    }

    public function destroyMarketer(Request $request, DistributorMarketer $marketer, DistributorController $site): JsonResponse
    {
        abort_unless($this->can($request->user(), 'distributors.marketers.destroy'), 403);
        app()->call([$site, 'destroyMarketer'], ['marketer' => $marketer]);

        return response()->json(['message' => __('تم حذف المسوق بنجاح.')]);
    }

    private function outlet(ShopOutlet $o): array
    {
        return [
            'id' => $o->id, 'name' => $o->name, 'region' => $o->region, 'address' => $o->address, 'phone' => $o->phone,
            'map_url' => $o->map_url, 'latitude' => $o->latitude !== null ? (float) $o->latitude : null,
            'longitude' => $o->longitude !== null ? (float) $o->longitude : null, 'sort_order' => (int) $o->sort_order, 'is_active' => (bool) $o->is_active,
        ];
    }

    private function marketer(DistributorMarketer $m): array
    {
        return [
            'id' => $m->id, 'name' => $m->name, 'phone' => $m->phone, 'whatsapp' => $m->whatsapp, 'email' => $m->email,
            'is_active' => (bool) $m->is_active, 'code' => $m->tracking_code,
            'commission_rate' => $m->commission_rate !== null ? (float) $m->commission_rate : null,
            'store_url' => route('front.marketer', ['marketer' => $m->tracking_code]),
        ];
    }

    private function can(User $user, string $route): bool
    {
        return $user->isSuperAdmin() || $user->canAccessRouteName($route);
    }

    private function authorizeCompany(User $user, ?Shop $shop, string $route): void
    {
        abort_unless($shop && $shop->catalog_type === 'krembo', 404);
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        abort_unless($this->can($user, $route), 403);
    }
}
