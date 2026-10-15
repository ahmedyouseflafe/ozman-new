<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\View\View;

class SweetsStoreController extends Controller
{
    public function index(Shop $shop): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'sweets', 404);

        $shop->loadMissing('social');

        $categories = $shop->categories()
            ->where('is_active', true)
            ->with(['products' => fn ($query) => $query->where('is_active', true)->latest()])
            ->orderBy('name')
            ->get();

        // Keep each row's original order, regardless of the current display language.
        [$cupCategories, $plateCategories] = $categories->partition(function ($category) {
            $names = implode(' ', array_merge(
                [$category->name, $category->slug],
                array_values($category->name_translations ?? [])
            ));

            return (bool) preg_match('/مشروبات|عصائر|عصير|قهوة|قهوه|كوفي|كابتشينو|لاتيه|ميلك\s*شيك|سموثي|كاسات|كؤوس|كوب|ايس\s*كريم|آيس\s*كريم|بوظة|بوظه|(?:^|[\s-])(?:drinks?|beverages?|coffee|iced?[-\s]*coffee|juices?|milkshakes?|smoothies?|cups?|tea|ice[-\s]*cream)(?:$|[\s-])|שתייה|משקאות|קפה|מיצים|גלידה/iu', $names);
        });
        $categories = $cupCategories->concat($plateCategories)->values();

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

        return view('front.sweets_store', compact(
            'shop', 'categories', 'cupCategories', 'plateCategories', 'uncategorizedProducts', 'displayItems'
        ));
    }
}
