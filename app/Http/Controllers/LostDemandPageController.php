<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\Demand\LostDemand;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "What customers looked for": a shop sees its own misses; the Ozman admin also sees all of Ozman. */
class LostDemandPageController extends Controller
{
    public function index(Request $request, LostDemand $demand): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        $user = $request->user();
        $days = (int) $request->query('days', 7) === 30 ? 30 : 7;
        $shops = $user->isSuperAdmin()
            ? Shop::orderBy('name')->get(['id', 'name', 'catalog_type', 'slug'])
            : Shop::whereIn('id', $user->accessibleShopIds())->orderBy('name')->get(['id', 'name', 'catalog_type', 'slug']);
        $shop = $shops->firstWhere('id', $request->integer('shop_id'))
            ?? ($user->isSuperAdmin() ? null : $shops->first());

        return view('admin.demand.index', [
            'days' => $days,
            'shops' => $shops,
            'shop' => $shop,
            'report' => $shop ? $demand->forShop($shop, $days, 30) : null,
            'overall' => $user->isSuperAdmin() && ! $shop ? $demand->overall($days, 50) : null,
            'byType' => $user->isSuperAdmin() && ! $shop ? $demand->byType($days) : null,
            'types' => collect(config('catalog_types'))->map(fn ($type) => $type['label'] ?? null)->filter(),
        ]);
    }
}
