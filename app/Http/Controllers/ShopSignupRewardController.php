<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\ShopSignupRewardService;
use Illuminate\Http\{Request, JsonResponse};

class ShopSignupRewardController extends Controller
{
    public function state(Request $request, Shop $shop, ShopSignupRewardService $service): JsonResponse
    {
        abort_unless($shop->is_active && ($shop->catalog_type === 'restaurant' || ($shop->catalog_type === 'cosmetics' && $shop->slug === 'elegance-perfumes')), 404);
        $data = $request->validate(['registration_token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'], 'spin' => ['sometimes', 'boolean']]);
        if (\App\Services\RestaurantNotificationRewardService::applies($shop)) {
            $notifications = app(\App\Services\RestaurantNotificationRewardService::class);
            $subscription = $notifications->subscription($request);
            abort_unless($subscription, 403, 'فعّل الإشعارات أولًا لفتح عجلة الهدية.');
            return response()->json(['reward' => $notifications->notificationState($shop, $subscription, $data['registration_token'], (bool) ($data['spin'] ?? false))])
                ->header('Cache-Control', 'no-store');
        }
        if ($shop->catalog_type === 'cosmetics') $service = app(\App\Services\CosmeticsGuestRewardService::class);
        return response()->json(['reward' => $service->state($shop, $data['registration_token'], (bool) ($data['spin'] ?? false))], 200, ['Cache-Control' => 'no-store']);
    }
}
