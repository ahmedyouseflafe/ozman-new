<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['super_admin', 'shop_owner', 'agent', 'distributor', 'marketer', 'employee'], true), 403);

        if ($user->isSuperAdmin()) {
            $this->rememberCurrentShop($request);

            return $next($request);
        }

        if ($user->isMarketer()) {
            $this->authorizeMarketerRoute($request);

            return $next($request);
        }

        if ($user->isEmployee()) {
            abort_unless($user->canAccessRouteName($request->route()?->getName()), 403);

            return $next($request);
        }

        if ($user->isAgent() || $user->isDistributor()) {
            if ($user->hasAssignedPermissions()) {
                abort_unless($user->canAccessRouteName($request->route()?->getName()), 403);
            } else {
                $this->authorizeAgentRoute($request);
            }
        }

        $ownedShopIds = $user->accessibleShopIds();
        abort_if(empty($ownedShopIds), 403);

        if ($request->filled('shop_id')) {
            abort_unless(in_array((int) $request->input('shop_id'), $ownedShopIds, true), 403);
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            $shopId = $parameter instanceof Shop ? $parameter->id : $parameter->getAttribute('shop_id');

            if ($shopId !== null) {
                abort_unless(in_array((int) $shopId, $ownedShopIds, true), 403);
            }
        }

        $this->rememberCurrentShop($request, $ownedShopIds);

        return $next($request);
    }

    private function authorizeAgentRoute(Request $request): void
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();
        $readOnlyProductRoutes = ['products', 'products.show', 'products.preview'];

        abort_unless(
            $routeName && (
                $routeName === 'dashboard'
                || $routeName === 'dashboard.main'
                || $routeName === 'translations.suggest'
                || str_starts_with($routeName, 'categories')
                || ($user?->isAgent() && str_starts_with($routeName, 'products'))
                || in_array($routeName, $readOnlyProductRoutes, true)
            ),
            403
        );
    }

    private function authorizeMarketerRoute(Request $request): void
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if ($user?->hasAssignedPermissions()) {
            abort_unless($user->canAccessRouteName($routeName), 403);

            return;
        }

        abort_unless(
            $routeName && (
                $routeName === 'dashboard'
                || $routeName === 'front-orders.index'
                || str_starts_with($routeName, 'reward-wheels.marketer.')
            ),
            403
        );
    }

    private function rememberCurrentShop(Request $request, array $allowedShopIds = []): void
    {
        $shopId = $request->integer('shop_id') ?: null;

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            $routeShopId = $parameter instanceof Shop ? $parameter->id : $parameter->getAttribute('shop_id');
            if ($routeShopId !== null) {
                $shopId = (int) $routeShopId;
                break;
            }
        }

        if (! $shopId) {
            return;
        }

        if ($allowedShopIds && ! in_array((int) $shopId, $allowedShopIds, true)) {
            return;
        }

        $request->session()->put('current_shop_id', (int) $shopId);
    }
}
