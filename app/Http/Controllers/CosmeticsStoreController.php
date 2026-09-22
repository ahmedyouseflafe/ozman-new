<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
        $ozmanLogo = Shop::query()->where('slug', 'ozman')->where('is_active', true)->value('logo');

        return view('front.cosmetics_store', compact(
            'shop', 'categories', 'uncategorizedProducts', 'displayItems', 'ozmanLogo'
        ));
    }
}
