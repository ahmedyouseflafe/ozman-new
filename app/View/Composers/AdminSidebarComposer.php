<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * The admin sidebar's menu data: who the user is (super admin, shop owner, agent, distributor, employee…), which
 * shop the menu is about, and which sections they may see. Moved here as-is from the sidebar view.
 */
class AdminSidebarComposer
{
    public function compose(View $view): void
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser?->isSuperAdmin();
        $isAgent = $currentUser?->isAgent();
        $isDistributor = $currentUser?->isDistributor();
        $isMarketer = $currentUser?->isMarketer();
        $isEmployee = $currentUser?->isEmployee();
        $isShopOwner = $currentUser?->isShopOwner();
        $hasAssignedPermissions = $currentUser?->hasAssignedPermissions() ?? false;
        $isPermissionManaged = $isEmployee || (($isShopOwner || $isAgent || $isDistributor || $isMarketer) && $hasAssignedPermissions);
        $isCatalogOnlyUser = ($isAgent || $isDistributor) && ! $hasAssignedPermissions;
        $previewShopId = request()->integer('shop_id') ?: session('current_shop_id');
        $canSee = fn(array $routes) => ! $isPermissionManaged || $currentUser?->canAccessAnyRoute($routes);

        foreach (request()->route()?->parameters() ?? [] as $parameter) {
            if (! $parameter instanceof \Illuminate\Database\Eloquent\Model) {
                continue;
            }

            $routeShopId = $parameter instanceof \App\Models\Shop
                ? $parameter->getKey()
                : $parameter->getAttribute('shop_id');

            if ($routeShopId) {
                $previewShopId = (int) $routeShopId;
                break;
            }
        }

        if (! $isSuperAdmin) {
            $ownedPreviewShopIds = auth()->user()?->accessibleShopIds() ?? [];
            if ($previewShopId && ! in_array((int) $previewShopId, $ownedPreviewShopIds, true)) {
                $previewShopId = null;
            }
        }

        if (! $previewShopId) {
            $previewShopId = auth()->user()?->accessibleShopIds()[0] ?? null;
        }
        $catalogNavShop = $previewShopId
            ? \App\Models\Shop::query()->whereKey($previewShopId)->first()
            : null;
        $restaurantNavShop = $catalogNavShop?->catalog_type === 'restaurant' ? $catalogNavShop : null;
        $realEstateNavShop = $catalogNavShop?->catalog_type === 'real_estate' ? $catalogNavShop : null;
        $cosmeticsNavShop = $catalogNavShop?->catalog_type === 'cosmetics' ? $catalogNavShop : null;
        $canSeeSalonAppointments = $cosmeticsNavShop && ($isShopOwner || $canSee(['salon-appointments.index']));
        $kremboNavShop = $catalogNavShop?->catalog_type === 'krembo' ? $catalogNavShop : null;
        $canSeeShopOutlets = $kremboNavShop && ($isShopOwner || $canSee(['shop-outlets.index']));
        $catalogTerms = $catalogNavShop?->catalogTerms() ?? config('catalog_terminology.general', []);
        $catalogItemsLabel = $catalogTerms['item_plural'] ?? 'المنتجات';
        $catalogCategoriesLabel = $catalogTerms['category_plural'] ?? 'الفئات';

        $previewDistributor = null;
        if ($isDistributor) {
            $previewDistributor = \App\Models\Distributor::query()
                ->where(function ($query) use ($currentUser) {
                    $query->where('user_id', $currentUser?->id);

                    if ($currentUser?->email) {
                        $query->orWhere('email', $currentUser->email);
                    }
                })
                ->where('is_active', true)
                ->first();
        }

        $previewUrl = $previewDistributor
            ? route('front.distributor', $previewDistributor)
            : ($catalogNavShop?->publicUrl()
                ?? route('products.preview', $previewShopId ? ['shop_id' => $previewShopId] : []));
        // Shop owners get a menu built around their day: orders first, then products, rewards, marketing.
        $useShopOwnerMenu = $isShopOwner && ! $isSuperAdmin && $catalogNavShop !== null;

        // Promoter sign-ups waiting for the super admin (the count badge next to "طلبات المروّجين").
        $pendingPromoters = $isSuperAdmin ? \App\Models\PromoterProfile::pending()->count() : 0;

        $view->with(compact('pendingPromoters', 'canSee', 'canSeeSalonAppointments', 'canSeeShopOutlets', 'catalogCategoriesLabel', 'catalogItemsLabel', 'catalogNavShop', 'catalogTerms', 'cosmeticsNavShop', 'hasAssignedPermissions', 'isCatalogOnlyUser', 'isMarketer', 'isPermissionManaged', 'isShopOwner', 'isSuperAdmin', 'kremboNavShop', 'previewShopId', 'previewUrl', 'realEstateNavShop', 'restaurantNavShop', 'useShopOwnerMenu'));
    }
}
