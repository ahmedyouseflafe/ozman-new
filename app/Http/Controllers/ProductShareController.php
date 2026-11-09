<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductShareController extends Controller
{
    /**
     * A product link for WhatsApp and social apps: crawlers read the product's own title and photo,
     * people are sent straight to the store with that product opened.
     */
    public function show(Product $product): View
    {
        $shop = $product->shop;
        abort_unless($product->is_active && $shop?->is_active, 404);
        Product::whereKey($product->id)->increment('share_opens'); // Counted in the analytics.

        $target = $shop->catalog_type === 'electronics'
            ? route('electronics.product', [$shop, $product])
            : $shop->publicUrl().'?'.http_build_query(['product' => $product->id]);

        return view('front.share_product', [
            'product' => $product,
            'shop' => $shop,
            'target' => $target,
            'image' => $this->imageUrl($product->main_image) ?? $this->imageUrl($shop->logo ?? null) ?? asset('images/logo.svg'),
        ]);
    }

    private function imageUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // Same as the storefronts: uploads are saved as "storage/products/…" and served with asset().
        return asset(ltrim($path, '/'));
    }
}
