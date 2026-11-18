<?php

namespace App\Services;

use App\Models\{FrontOrder, Shop, ShopCoupon, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A shop's discount codes: checking one against a basket, and taking it off an order once (with the use counted).
 * The discount never goes below zero and comes after any other discount already on the order.
 */
class CouponService
{
    /** The coupon if it can be used now by this customer, or a validation error saying why not. */
    public function find(Shop $shop, string $code, ?User $user): ShopCoupon
    {
        $coupon = ShopCoupon::query()->where('shop_id', $shop->id)->where('code', ShopCoupon::normalize($code))->first();
        $fail = fn (string $message) => throw ValidationException::withMessages(['coupon_code' => __($message)]);
        if (! $coupon || ! $coupon->is_active) {
            $fail('الكود غير صحيح أو انتهى.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            $fail('الكود لسا ما بلّش.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            $fail('الكود غير صحيح أو انتهى.');
        }
        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            $fail('الكود خلص استخدامه.');
        }
        if ($user && $coupon->per_customer !== null
            && FrontOrder::where('shop_coupon_id', $coupon->id)->where('user_id', $user->id)->where('status', '!=', 'cancelled')->count() >= $coupon->per_customer) {
            $fail('استخدمت هالكود قبل.');
        }

        return $coupon;
    }

    /** How much the coupon takes off this amount, or a validation error when the basket is too small. */
    public function discount(ShopCoupon $coupon, float $amount): float
    {
        if ($coupon->min_total !== null && $amount < $coupon->min_total) {
            throw ValidationException::withMessages(['coupon_code' => __('الكود بيشتغل على طلبات من :min ₪ وفوق.', ['min' => rtrim(rtrim(number_format($coupon->min_total, 2, '.', ''), '0'), '.')])]);
        }
        $off = $coupon->type === 'percent' ? $amount * min(100, $coupon->value) / 100 : $coupon->value;

        return round(max(0, min($amount, $off)), 2);
    }

    /**
     * Counts one use of the coupon (locked, so two orders can't take its last use) and returns what it takes off
     * this amount. Call inside the order's transaction.
     */
    public function redeem(ShopCoupon $coupon, float $amount): float
    {
        $locked = ShopCoupon::query()->lockForUpdate()->findOrFail($coupon->id);
        if (! $locked->is_active || ($locked->max_uses !== null && $locked->used_count >= $locked->max_uses)) {
            throw ValidationException::withMessages(['coupon_code' => __('الكود خلص استخدامه.')]);
        }
        $off = $this->discount($locked, $amount);
        $locked->increment('used_count');

        return $off;
    }

    /** Takes the coupon off an order that was already placed (restaurant orders go through the website's checkout). */
    public function apply(FrontOrder $order, Shop $shop, string $code, ?User $user): float
    {
        return DB::transaction(function () use ($order, $shop, $code, $user) {
            $coupon = $this->find($shop, $code, $user);
            $order->refresh();
            $off = $this->redeem($coupon, (float) $order->total);
            $order->forceFill([
                'shop_coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
                'coupon_discount' => $off,
                'discount' => round((float) $order->discount + $off, 2),
                'total' => round(max(0, (float) $order->total - $off), 2),
            ])->save();

            return $off;
        });
    }
}
