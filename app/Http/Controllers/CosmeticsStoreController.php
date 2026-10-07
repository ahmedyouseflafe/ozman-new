<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RaffleCard;
use App\Models\RewardWheel;
use App\Models\Shop;
use Illuminate\View\View;

class CosmeticsStoreController extends Controller
{
    public function booking(Shop $shop): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'cosmetics', 404);

        $shop->loadMissing('social');

        return view('front.cosmetics_booking', compact('shop'));
    }

    public function index(Shop $shop): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'cosmetics', 404);

        $shop->loadMissing('social');
        $categoriesQuery = $shop->categories()
            ->where('is_active', true)
            ->with(['products' => fn ($query) => $query->where('is_active', true)->latest()]);

        // Curly Waves asked for a deliberate storefront order: curly first,
        // cosmetics second and perfumes last. Other sections keep their
        // existing alphabetical order.
        if ($shop->slug === 'curly-waves' || strtolower(trim($shop->name)) === 'curly waves') {
            $categoriesQuery->orderByRaw("
                CASE
                    WHEN name LIKE '%كيرلي%' OR LOWER(name) LIKE '%curly%' THEN 0
                    WHEN name LIKE '%كوزمت%' OR LOWER(name) LIKE '%cosmetic%' THEN 1
                    WHEN (name LIKE '%عطر%' AND name NOT LIKE '%معطر%') OR LOWER(name) LIKE '%perfume%' THEN 3
                    ELSE 2
                END
            ");
        } elseif ($shop->slug === 'elegance-perfumes' || strtolower(trim($shop->name)) === 'elegance perfumes') {
            $categoriesQuery->orderByRaw("
                CASE
                    WHEN (name LIKE '%عطور%' OR name LIKE '%عطر%' OR LOWER(name) LIKE '%perfume%')
                        AND (name LIKE '%رجال%' OR (LOWER(name) LIKE '%men%' AND LOWER(name) NOT LIKE '%women%')) THEN 0
                    WHEN (name LIKE '%عطور%' OR name LIKE '%عطر%' OR LOWER(name) LIKE '%perfume%')
                        AND (name LIKE '%عرب%' OR LOWER(name) LIKE '%arab%') THEN 1
                    WHEN (name LIKE '%عطور%' OR name LIKE '%عطر%' OR LOWER(name) LIKE '%perfume%')
                        AND (name LIKE '%نسائ%' OR LOWER(name) LIKE '%women%' OR LOWER(name) LIKE '%female%') THEN 2
                    WHEN name LIKE '%يون%سكس%' OR LOWER(name) LIKE '%unisex%' THEN 3
                    WHEN (name LIKE '%ساع%' OR LOWER(name) LIKE '%watch%')
                        AND (name LIKE '%رجال%' OR (LOWER(name) LIKE '%men%' AND LOWER(name) NOT LIKE '%women%')) THEN 4
                    WHEN (name LIKE '%ساع%' OR LOWER(name) LIKE '%watch%')
                        AND (name LIKE '%نسائ%' OR LOWER(name) LIKE '%women%' OR LOWER(name) LIKE '%female%') THEN 5
                    ELSE 6
                END
            ");
        }

        $categories = $categoriesQuery
            ->orderBy('name')
            ->get();
        if ($shop->slug === 'elegance-perfumes' || strtolower(trim($shop->name)) === 'elegance perfumes') {
            $sorter = app(\App\Services\PerfumeProductSorter::class);
            foreach ($categories as $category) {
                if (preg_match('/عطور|عطر|perfume|fragrance|يون.*سكس|unisex/ui', $category->name)) {
                    $category->setRelation('products', $sorter->sort($category->products));
                }
            }
        }
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
