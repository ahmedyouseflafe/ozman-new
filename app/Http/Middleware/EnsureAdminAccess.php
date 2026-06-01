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

        abort_unless($user && in_array($user->role, ['super_admin', 'shop_owner'], true), 403);

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $ownedShopIds = $user->shops()->pluck('id')->map(fn($id) => (int) $id)->all();
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

        return $next($request);
    }
}
