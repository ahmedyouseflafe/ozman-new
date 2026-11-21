<?php

namespace App\Services;

use App\Models\{Product, PushDevice, Shop, ShopFollow, User};
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Customers following shops, and the "new offer" notification they get. */
class ShopFollows
{
    public function follows(User $user, Shop $shop): bool
    {
        return ShopFollow::where('shop_id', $shop->id)->where('user_id', $user->id)->exists();
    }

    public function set(User $user, Shop $shop, bool $follow): bool
    {
        if ($follow) {
            ShopFollow::firstOrCreate(['shop_id' => $shop->id, 'user_id' => $user->id]);
        } else {
            ShopFollow::where('shop_id', $shop->id)->where('user_id', $user->id)->delete();
        }

        return $follow;
    }

    public function count(Shop $shop): int
    {
        return ShopFollow::where('shop_id', $shop->id)->count();
    }

    /**
     * A product just went on offer: tell the shop's followers. At most one notification per shop every 6 hours,
     * so a shop putting ten products on offer doesn't send ten.
     */
    public function offerStarted(Product $product): void
    {
        $shop = $product->shop;
        if (! $shop || ! $shop->is_active || ! $product->is_active) {
            return;
        }
        if (! Cache::add('shop-offer-push:'.$shop->id, 1, now()->addHours(6))) {
            return;
        }
        $tokens = PushDevice::whereIn('user_id', ShopFollow::where('shop_id', $shop->id)->select('user_id'))->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }
        $price = rtrim(rtrim(number_format((float) $product->discount_price, 2, '.', ''), '0'), '.');
        try {
            app(\App\Services\FirebaseMessagingService::class)->sendToTokens(
                $tokens,
                __('عرض جديد عند :shop 🔥', ['shop' => $shop->name]),
                __(':product صار بـ :price ₪', ['product' => $product->name, 'price' => $price]),
                $shop->publicUrl(),
                ['type' => 'shop_offer', 'shop_id' => $shop->id, 'product_id' => $product->id],
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
