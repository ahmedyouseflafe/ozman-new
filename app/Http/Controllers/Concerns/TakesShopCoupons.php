<?php

namespace App\Http\Controllers\Concerns;

use App\Models\{FrontOrder, Shop, ShopCoupon};
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The website's checkouts take the same discount codes the owner makes in the app.
 * The code is checked against the basket before the order is saved (so a wrong code never leaves a half-made order),
 * then taken off the saved order once, with its use counted.
 */
trait TakesShopCoupons
{
    /** The coupon in the request, checked for this basket; null when no code was typed. */
    protected function couponForBasket(Request $request, Shop $shop, float $subtotal): ?ShopCoupon
    {
        $code = trim((string) $request->input('coupon_code', ''));
        if ($code === '') {
            return null;
        }
        if (mb_strlen($code) > 40) {
            throw ValidationException::withMessages(['coupon_code' => __('الكود غير صحيح أو انتهى.')]);
        }
        $coupons = app(CouponService::class);
        $coupon = $coupons->find($shop, $code, $request->user());
        $coupons->discount($coupon, $subtotal);

        return $coupon;
    }

    /**
     * Takes the coupon off the saved order. If it can't be used any more (someone took its last use a moment ago)
     * the order stays as it is and the reason comes back, so the customer isn't asked to order twice.
     *
     * @return array{coupon_discount: float, coupon_error: ?string}
     */
    protected function takeCoupon(?ShopCoupon $coupon, FrontOrder $order, Shop $shop, Request $request): array
    {
        if (! $coupon || $order->shop_coupon_id || ! $order->wasRecentlyCreated) {
            return ['coupon_discount' => (float) ($order->coupon_discount ?? 0), 'coupon_error' => null];
        }
        try {
            $off = app(CouponService::class)->apply($order, $shop, $coupon->code, $request->user());
            $order->refresh();

            return ['coupon_discount' => $off, 'coupon_error' => null];
        } catch (ValidationException $e) {
            return ['coupon_discount' => 0.0, 'coupon_error' => collect($e->errors())->flatten()->first()];
        } catch (Throwable $e) {
            report($e);

            return ['coupon_discount' => 0.0, 'coupon_error' => __('ما قدرنا نطبّق الكود، والطلب انبعت بدونه.')];
        }
    }

    /** What the checkout shows before ordering: "10% off" and the minimum, or why the code can't be used. */
    public static function couponSummary(ShopCoupon $coupon): array
    {
        $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        $label = $coupon->type === 'percent'
            ? __('خصم :value%', ['value' => $number($coupon->value)])
            : __('خصم :value ₪', ['value' => $number($coupon->value)]);

        return [
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'min_total' => $coupon->min_total !== null ? (float) $coupon->min_total : null,
            'label' => $coupon->min_total !== null
                ? $label.' · '.__('على طلب من :min ₪ وفوق', ['min' => $number($coupon->min_total)])
                : $label,
        ];
    }
}
