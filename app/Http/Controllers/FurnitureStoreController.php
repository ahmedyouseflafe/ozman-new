<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\View\View;

class FurnitureStoreController extends Controller
{
    public function directory(): View
    {
        $shops = Shop::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('catalog_type', 'furniture_appliances')
                ->orWhere('home_section', 'home_furniture'))
            ->with([
                'categories' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
                'products' => fn ($query) => $query->where('is_active', true)->latest()->limit(4),
            ])
            ->latest()
            ->get();

        return view('front.furniture_directory', compact('shops'));
    }

    public function index(Shop $shop): View
    {
        abort_unless(
            $shop->is_active && ($shop->catalog_type === 'furniture_appliances' || $shop->home_section === 'home_furniture'),
            404
        );

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

        $showroomProducts = $categories->flatMap(fn ($category) => $category->products)
            ->merge($uncategorizedProducts)
            ->unique('id')
            ->take(5)
            ->values();

        $displayItems = $shop->advertisements()
            ->where('is_active', true)
            ->whereNotNull('media')
            ->where('media', '!=', '')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return view('front.furniture_store', compact(
            'shop', 'categories', 'uncategorizedProducts', 'showroomProducts', 'displayItems'
        ));
    }
}
