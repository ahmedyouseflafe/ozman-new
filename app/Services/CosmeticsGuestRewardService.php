<?php

namespace App\Services;

use App\Models\{FrontOrder, Shop, ShopSignupReward, VisitorRegistration};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CosmeticsGuestRewardService extends ShopSignupRewardService
{
    private function tokenHash(Shop $shop, string $token): string
    {
        return hash('sha256', 'cosmetics-guest:'.$shop->id.':'.$token);
    }

    private function reward(Shop $shop, string $token): ?ShopSignupReward
    {
        $reward = ShopSignupReward::where('shop_id', $shop->id)
            ->where('guest_token_hash', $this->tokenHash($shop, $token))->lockForUpdate()->first();
        if ($reward) return $reward;

        // Existing customers keep their previously issued/spun reward.
        $registration = VisitorRegistration::where('shop_id', $shop->id)->where('type', 'customer')
            ->where('marketing_source', 'restaurant_welcome')
            ->where('public_token', hash('sha256', 'restaurant-customer:'.$shop->id.':'.$token))->first();
        if ($registration) {
            return ShopSignupReward::where('visitor_registration_id', $registration->id)->lockForUpdate()->first();
        }
        return null;
    }

    public function state(Shop $shop, string $token, bool $spin = false): ?array
    {
        return DB::transaction(function () use ($shop, $token, $spin) {
            Shop::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $reward = $this->reward($shop, $token);
            if (! $reward) {
                $snapshot = $this->wheelSnapshot($shop);
                if (! $snapshot) return null;
                $reward = ShopSignupReward::create([
                    'shop_id' => $shop->id, 'guest_token_hash' => $this->tokenHash($shop, $token),
                    'phone_hash' => hash('sha256', 'unclaimed:'.$this->tokenHash($shop, $token)),
                    ...$snapshot,
                ]);
            }
            if ($spin && ! $reward->redeemed_at && $reward->selected_index === null) {
                $reward->update(['selected_index' => $this->drawIndex($reward), 'spun_at' => now()]);
            }
            $payload = $this->payload($reward);
            // A deliberate reset issues a new entitlement, including for browsers
            // that already dismissed the previous welcome.
            return $payload ? [...$payload, 'welcome_id' => (string) $reward->id] : null;
        });
    }

    public function checkout(Shop $shop, ?string $token, array $attributes, bool $skipReward = false): FrontOrder
    {
        return DB::transaction(function () use ($shop, $token, $attributes, $skipReward) {
            // Serialize phone claims so simultaneous orders cannot redeem two gifts.
            Shop::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $reward = $token ? $this->reward($shop, $token) : null;
            if ($reward && ! $reward->redeemed_at && ! $skipReward) {
                if ($reward->selected_index === null) {
                    throw ValidationException::withMessages(['signup_reward' => 'لف عجلة الترحيب قبل إرسال الطلب لتحصل على هديتك.']);
                }
                $phoneHash = self::phoneHash($attributes['customer_whatsapp']);
                $claimed = ShopSignupReward::where('shop_id', $shop->id)->where('phone_hash', $phoneHash)
                    ->where('id', '!=', $reward->id)->exists();
                if ($claimed || ($reward->visitor_registration_id && $reward->phone_hash !== $phoneHash)) {
                    throw ValidationException::withMessages(['welcome_reward' => 'هدية الترحيب غير متاحة لهذا الرقم. يمكنك إكمال الطلب بدون هدية الترحيب.']);
                }
                $reward->phone_hash = $phoneHash;
                $segment = $reward->segments[$reward->selected_index];
                $subtotal = (float) $attributes['subtotal'];
                $discount = match ($segment['discount_type']) {
                    'percent' => $subtotal * min(100, (int) $segment['discount_value']) / 100,
                    'amount' => (int) $segment['discount_value'],
                    default => 0,
                };
                $attributes['discount'] = round(min($subtotal, max(0, $discount)), 2);
                $attributes['total'] = round($subtotal - $attributes['discount'], 2);
                $attributes['reward_label'] = $segment['discount_type'] === 'none' ? null : $segment['label'];
                $attributes['reward_discount_type'] = $segment['discount_type'];
                $attributes['reward_discount_value'] = $segment['discount_value'];
                $attributes['reward_gift_image'] = $segment['gift_image'] ?? null;
                $attributes['reward_color'] = $segment['color'];
                $attributes['reward_won_at'] = $reward->spun_at;
            }
            $order = FrontOrder::create($attributes);
            if ($reward && ! $reward->redeemed_at) {
                $reward->redeemed_at = now();
                $reward->front_order_id = $order->id;
                $reward->save();
            }
            return $order;
        });
    }
}
