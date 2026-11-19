<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TakesShopCoupons;
use App\Models\Shop;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The website checkout's "apply" button: says what a code gives before the order is sent. */
class WebCouponController extends Controller
{
    use TakesShopCoupons;

    public function check(Request $request, Shop $shop, CouponService $coupons): JsonResponse
    {
        abort_unless($shop->is_active, 404);
        $data = $request->validate(['coupon_code' => ['required', 'string', 'max:40']]);
        $coupon = $coupons->find($shop, $data['coupon_code'], $request->user());

        return response()->json(['coupon' => self::couponSummary($coupon)])->header('Cache-Control', 'private, no-store');
    }
}
