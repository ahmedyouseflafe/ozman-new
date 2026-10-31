<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\View\View;

class ClothingStoreController extends Controller
{
    public function index(Shop $shop): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'clothing', 404);

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

        $allProducts = $categories->flatMap(fn ($category) => $category->products)
            ->merge($uncategorizedProducts)
            ->unique('id')
            ->values();

        $displayItems = $shop->advertisements()
            ->where('is_active', true)
            ->whereNotNull('media')
            ->where('media', '!=', '')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $user = auth()->user();
        $canManage = $user && ((int) $shop->user_id === (int) $user->id || $user->isSuperAdmin());

        $openTime = filled($shop->open_time) ? substr((string) $shop->open_time, 0, 5) : null;
        $closeTime = filled($shop->close_time) ? substr((string) $shop->close_time, 0, 5) : null;
        $isOpenNow = $this->isOpenNow($openTime, $closeTime);

        return view('front.clothing_store', compact(
            'shop', 'categories', 'uncategorizedProducts', 'allProducts', 'displayItems',
            'canManage', 'openTime', 'closeTime', 'isOpenNow'
        ));
    }

    private function isOpenNow(?string $openTime, ?string $closeTime): ?bool
    {
        if (! $openTime || ! $closeTime) {
            return null;
        }

        $now = now('Asia/Jerusalem')->format('H:i');

        if ($openTime === $closeTime) {
            return true;
        }

        // Closing after midnight (e.g. 18:00 → 02:00) wraps into the next day.
        return $openTime < $closeTime
            ? $now >= $openTime && $now < $closeTime
            : $now >= $openTime || $now < $closeTime;
    }
}
