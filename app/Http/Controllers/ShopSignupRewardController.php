<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\ShopSignupRewardService;
use Illuminate\Http\{Request, JsonResponse};

class ShopSignupRewardController extends Controller
{
    public function state(Request $request, Shop $shop, ShopSignupRewardService $service): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'restaurant', 404);
        $data = $request->validate(['registration_token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'], 'spin' => ['sometimes', 'boolean']]);
        return response()->json(['reward' => $service->state($shop, $data['registration_token'], (bool) ($data['spin'] ?? false))], 200, ['Cache-Control' => 'no-store']);
    }
}
