<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RaffleCard;
use App\Models\RewardWheel;
use App\Models\Shop;
use Illuminate\View\View;

class CosmeticsStoreController extends Controller
{
    public function index(Shop $shop): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'cosmetics', 404);

        $shop->loadMissing('social');
        $categories = $shop->categories()
            ->where('is_active', true)
            ->with(['products' => fn ($query) => $query->where('is_active', true)->latest()])
            ->orderBy('name')
            ->get();
        $uncategorizedProducts = Product::query()
            ->where('shop_id', $shop->id)
            ->whereNull('category_id')
            ->where('is_active', true)
            ->latest()
            ->get();
        $displayItems = $shop->advertisements()
            ->where('is_active', true)
            ->whereNotNull('media')
            ->where('media', '!=', '')
            ->orderBy('sort_order')
            ->latest()
            ->get();
        $purchaseRewardWheels = $shop->rewardWheels()
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->where('is_active', true)
            ->with(['segments' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')])
            ->orderBy('min_order_total')
            ->get()
            ->filter(fn ($wheel) => $wheel->segments->count() >= 2)
            ->map(fn ($wheel) => [
                'id' => $wheel->id,
                'title' => $wheel->title,
                'min_order_total' => (float) $wheel->min_order_total,
                'max_order_total' => $wheel->max_order_total !== null ? (float) $wheel->max_order_total : null,
                'segments' => $wheel->segments->map(fn ($segment) => [
                    'label' => $segment->label,
                    'discount_value' => $segment->discount_value,
                    'discount_type' => $segment->discount_type,
                    'gift_image' => $segment->discount_type === 'gift' && $segment->gift_image
                        ? asset($segment->gift_image)
                        : null,
                    'color' => $segment->color,
                ])->values()->all(),
            ])
            ->values()
            ->all();
        $raffleCardsAvailable = RaffleCard::query()->where('is_active', true)->exists();
        $ozmanLogo = Shop::query()->where('slug', 'ozman')->where('is_active', true)->value('logo');

        return view('front.cosmetics_store', compact(
            'shop', 'categories', 'uncategorizedProducts', 'displayItems', 'ozmanLogo',
            'purchaseRewardWheels', 'raffleCardsAvailable'
        ));
    }
}
