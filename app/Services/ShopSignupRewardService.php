<?php

namespace App\Services;

use App\Models\{Shop, RewardWheel, ShopSignupReward, ShopSignupWheelCycle, VisitorRegistration, FrontOrder};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopSignupRewardService
{
    public static function phoneHash(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', strtr($phone, array_combine(
            preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY), str_split('01234567890123456789')
        )));
        $phone = preg_replace('/^00/', '', $phone);
        if (str_starts_with($phone, '970') || str_starts_with($phone, '972')) $phone = '0'.substr($phone, 3);
        return hash('sha256', $phone);
    }

    public function issue(Shop $shop, VisitorRegistration $registration): void
    {
        // Called in the registration transaction, only for a newly created profile.
        $wheel = RewardWheel::where('key', 'shop_signup_'.$shop->id)->where('shop_id', $shop->id)
            ->where('is_active', true)->lockForUpdate()->first();
        if (! $wheel) return;
        $segments = $wheel->segments()->where('is_active', true)->get()->map(fn ($segment) => [
            'label' => $segment->label, 'color' => $segment->color,
            'discount_type' => $segment->discount_type, 'discount_value' => $segment->discount_value,
            'gift_image' => $segment->gift_image, 'win_quota' => (int) ($segment->win_quota ?? 1),
        ])->values()->all();
        if (count($segments) < 2) return;
        $quotas = array_column($segments, 'win_quota');
        if (array_sum($quotas) < 1) return;
        $cycleId = $wheel->spin_cycle['signup_cycle_id'] ?? null;
        if (! $cycleId) {
            $cycleId = ShopSignupWheelCycle::create(['shop_id' => $shop->id, 'quotas' => $quotas, 'remaining' => $quotas])->id;
            $wheel->update(['spin_cycle' => ['signup_cycle_id' => $cycleId]]);
        }
        ShopSignupReward::firstOrCreate(['shop_id' => $shop->id, 'phone_hash' => self::phoneHash($registration->phone)], [
            'visitor_registration_id' => $registration->id, 'title' => $wheel->title, 'segments' => $segments, 'cycle_id' => $cycleId,
        ]);
    }

    public function registration(Shop $shop, string $token): VisitorRegistration
    {
        return VisitorRegistration::where('shop_id', $shop->id)->where('type', 'customer')
            ->where('marketing_source', 'restaurant_welcome')
            ->where('public_token', hash('sha256', 'restaurant-customer:'.$shop->id.':'.$token))->firstOrFail();
    }

    public function payload(?ShopSignupReward $reward): ?array
    {
        if (! $reward || $reward->redeemed_at) return null;
        $segments = array_map(function ($segment) {
            if (! empty($segment['gift_image'])) $segment['gift_image'] = asset($segment['gift_image']);
            unset($segment['win_quota']);
            return $segment;
        }, $reward->segments);
        return ['title' => $reward->title, 'segments' => $segments, 'selected_index' => $reward->selected_index];
    }

    public function state(Shop $shop, string $token, bool $spin = false): ?array
    {
        $registration = $this->registration($shop, $token);
        return DB::transaction(function () use ($registration, $shop, $spin) {
            $reward = ShopSignupReward::where('shop_id', $shop->id)->where('visitor_registration_id', $registration->id)->lockForUpdate()->first();
            if ($spin && $reward && ! $reward->redeemed_at && $reward->selected_index === null) {
                $reward->update(['selected_index' => $this->drawIndex($reward), 'spun_at' => now()]);
            }
            return $this->payload($reward);
        });
    }

    private function drawIndex(ShopSignupReward $reward): int
    {
        // Old entitlements retain their original equal-chance rules. New ones
        // share a locked counter per settings version, including after edits.
        if (! $reward->cycle_id) return random_int(0, count($reward->segments) - 1);
        $cycle = ShopSignupWheelCycle::whereKey($reward->cycle_id)->where('shop_id', $reward->shop_id)->lockForUpdate()->firstOrFail();
        $remaining = $cycle->remaining;
        if (array_sum($remaining) === 0) $remaining = $cycle->quotas;
        $ticket = random_int(1, array_sum($remaining));
        foreach ($remaining as $index => $count) {
            $ticket -= $count;
            if ($ticket <= 0) {
                $remaining[$index]--;
                $cycle->update(['remaining' => $remaining]);
                return $index;
            }
        }
        throw new \LogicException('Invalid signup wheel quotas.');
    }

    public function placeOrder(Shop $shop, ?string $token, array $attributes): FrontOrder
    {
        if (! $token) return FrontOrder::create($attributes);
        $registration = $this->registration($shop, $token);
        return DB::transaction(function () use ($shop, $registration, $attributes) {
            $reward = ShopSignupReward::where('shop_id', $shop->id)->where('visitor_registration_id', $registration->id)->lockForUpdate()->first();
            if ($reward && ! $reward->redeemed_at) {
                if (self::phoneHash($attributes['customer_phone'] ?? '') !== $reward->phone_hash) {
                    throw ValidationException::withMessages(['customer_phone' => 'استخدم رقم الواتساب الذي حصل على جائزة التسجيل.']);
                }
                if ($reward->selected_index === null) {
                    throw ValidationException::withMessages(['signup_reward' => 'لف عجلة التسجيل قبل إرسال طلبك لتحصل على خصمك.']);
                }
                $segment = $reward->segments[$reward->selected_index];
                $subtotal = (float) $attributes['subtotal'];
                $discount = match ($segment['discount_type']) {
                    'percent' => $subtotal * min(100, (int) $segment['discount_value']) / 100,
                    'amount' => (int) $segment['discount_value'],
                    default => 0,
                };
                $attributes['discount'] = round(min($subtotal, max(0, $discount)), 2);
                $attributes['total'] = round($subtotal - $attributes['discount'], 2);
                $attributes['reward_label'] = $segment['label'];
                $attributes['reward_discount_type'] = $segment['discount_type'];
                $attributes['reward_discount_value'] = $segment['discount_value'];
                $attributes['reward_gift_image'] = $segment['gift_image'] ?? null;
                $attributes['reward_color'] = $segment['color'];
                $attributes['reward_won_at'] = $reward->spun_at;
            }
            $order = FrontOrder::create($attributes);
            if ($reward && ! $reward->redeemed_at) $reward->update(['redeemed_at' => now(), 'front_order_id' => $order->id]);
            return $order;
        });
    }
}
