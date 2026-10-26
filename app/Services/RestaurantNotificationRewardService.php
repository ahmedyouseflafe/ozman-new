<?php

namespace App\Services;

use App\Models\{FrontOrder, OfferPushSubscription, Shop, ShopSignupReward, VisitorRegistration};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RestaurantNotificationRewardService extends ShopSignupRewardService
{
    public static function applies(Shop $shop): bool
    {
        return $shop->catalog_type === 'restaurant' && $shop->slug === 'bankai-sushi';
    }

    public function subscription(Request $request): ?string
    {
        $hash = $request->session()->get('offer_web_push_endpoint_hash');
        return is_string($hash) && OfferPushSubscription::where('endpoint_hash', $hash)->exists() ? $hash : null;
    }

    private function identity(Shop $shop, string $endpointHash): string
    {
        return hash('sha256', 'restaurant-push:'.$shop->id.':'.$endpointHash);
    }

    private function findReward(Shop $shop, string $endpointHash, ?string $legacyToken): ?ShopSignupReward
    {
        $identity = $this->identity($shop, $endpointHash);
        $reward = ShopSignupReward::where('shop_id', $shop->id)->where('guest_token_hash', $identity)->lockForUpdate()->first();
        if ($reward || ! $legacyToken) return $reward;
        $registration = VisitorRegistration::where('shop_id', $shop->id)->where('type', 'customer')
            ->where('public_token', hash('sha256', 'restaurant-customer:'.$shop->id.':'.$legacyToken))->first();
        if (! $registration) return null;
        $reward = ShopSignupReward::where('shop_id', $shop->id)->where('visitor_registration_id', $registration->id)->lockForUpdate()->first();
        if ($reward && ! $reward->guest_token_hash) $reward->update(['guest_token_hash' => $identity]);
        return $reward;
    }

    public function notificationState(Shop $shop, string $endpointHash, ?string $token, bool $spin): ?array
    {
        return DB::transaction(function () use ($shop, $endpointHash, $token, $spin) {
            Shop::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $reward = $this->findReward($shop, $endpointHash, $token);
            if (! $reward) {
                $snapshot = $this->wheelSnapshot($shop);
                if (! $snapshot) return null;
                $identity = $this->identity($shop, $endpointHash);
                $reward = ShopSignupReward::create(['shop_id' => $shop->id, 'guest_token_hash' => $identity,
                    'phone_hash' => hash('sha256', 'unclaimed:'.$identity), ...$snapshot]);
            }
            if ($spin && ! $reward->redeemed_at && $reward->selected_index === null) {
                $reward->update(['selected_index' => $this->drawIndex($reward), 'spun_at' => now()]);
            }
            $payload = $this->payload($reward);
            return $payload ? [...$payload, 'welcome_id' => (string) $reward->id] : null;
        });
    }

    public function checkout(Shop $shop, Request $request, array $attributes): FrontOrder
    {
        return DB::transaction(function () use ($shop, $request, $attributes) {
            Shop::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $subscription = $this->subscription($request);
            $reward = $subscription ? $this->findReward($shop, $subscription, $request->input('registration_token')) : null;
            $phone = $attributes['customer_phone'] ?? '';
            $phoneHash = filled($phone) ? self::phoneHash($phone) : null;
            if ($reward && ! $reward->redeemed_at && $reward->selected_index !== null) {
                if (! $phoneHash) throw ValidationException::withMessages(['customer_phone' => 'أدخل رقم الهاتف لحفظ هدية الإشعارات مع الطلب.']);
                $claimed = ShopSignupReward::where('shop_id', $shop->id)->where('phone_hash', $phoneHash)->where('id', '!=', $reward->id)->exists();
                if ($claimed || ($reward->visitor_registration_id && $reward->phone_hash !== $phoneHash)) {
                    if (! $request->boolean('skip_signup_reward')) {
                        throw ValidationException::withMessages(['signup_reward_used' => 'هدية الترحيب غير متاحة لهذا الرقم. يمكنك إرسال الطلب بدون الهدية.']);
                    }
                } else {
                    $reward->phone_hash = $phoneHash;
                    $segment = $reward->segments[$reward->selected_index];
                    $subtotal = (float) $attributes['subtotal'];
                    $discount = match ($segment['discount_type']) {
                        'percent' => $subtotal * min(100, (int) $segment['discount_value']) / 100,
                        'amount' => (int) $segment['discount_value'], default => 0,
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
            }
            $order = FrontOrder::create($attributes);
            // Phone identifies the dashboard entry, not permission to edit it.
            // Existing details are never overwritten by an unverified caller.
            $identity = $phoneHash ?? $request->session()->get('restaurant_checkout_identity');
            if (! $identity) { $identity = Str::random(64);$request->session()->put('restaurant_checkout_identity', $identity); }
            $registration = $reward?->visitor_registration_id && $reward->phone_hash === $phoneHash
                ? VisitorRegistration::find($reward->visitor_registration_id) : null;
            $registration ??= VisitorRegistration::firstOrCreate([
                'public_token' => hash('sha256', 'restaurant-checkout:'.$shop->id.':'.$identity),
            ], ['shop_id' => $shop->id, 'type' => 'customer', 'status' => 'approved', 'approved_at' => now(),
                'marketing_source' => 'restaurant_checkout', 'name' => $attributes['customer_name'], 'phone' => $phone ?: '',
                'residence_address' => $attributes['customer_address'] ?? '', 'latitude' => $attributes['latitude'] ?? null,
                'longitude' => $attributes['longitude'] ?? null, 'map_link' => $attributes['map_link'] ?? null]);
            if ($reward && ! $reward->redeemed_at) {
                // Even a skipped or unspun entitlement cannot be redeemed on a later order.
                if (! $reward->visitor_registration_id && $phoneHash && ! ShopSignupReward::where('shop_id', $shop->id)->where('phone_hash', $phoneHash)->where('id', '!=', $reward->id)->exists()) {
                    $reward->phone_hash = $phoneHash;
                }
                $reward->redeemed_at = now();$reward->front_order_id = $order->id;
                if (! $reward->visitor_registration_id && ! ShopSignupReward::where('visitor_registration_id', $registration->id)->exists()) {
                    $reward->visitor_registration_id = $registration->id;
                }
                $reward->save();
            }
            return $order;
        });
    }
}
