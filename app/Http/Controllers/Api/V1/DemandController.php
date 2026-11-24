<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\Demand\LostDemand;
use Illuminate\Http\{JsonResponse, Request};

/** "What customers looked for and didn't find": the website's search box reports misses, shop owners read them. */
class DemandController extends Controller
{
    /** The website's search box tells us when nothing matched. Anonymous; throttled per visitor. */
    public function miss(Request $request, LostDemand $demand): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'max:100'], 'shop_id' => ['nullable', 'integer']]);
        $shop = isset($data['shop_id']) ? Shop::where('is_active', true)->find($data['shop_id']) : null;
        $demand->record($data['q'], 'web', $shop);

        return response()->json(['ok' => true]);
    }

    public function forShop(Request $request, Shop $shop, LostDemand $demand): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        $days = (int) $request->query('days', 7) === 30 ? 30 : 7;

        return response()->json($demand->forShop($shop, $days));
    }
}
