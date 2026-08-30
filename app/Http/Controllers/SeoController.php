<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RealEstateProperty;
use App\Models\Shop;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /login',
            'Disallow: /merchant-',
            'Disallow: /customer-login',
            'Disallow: /csrf-token',
            'Disallow: /front-orders',
            'Disallow: /raffle-card',
            'Disallow: /reward-wheels',
            'Sitemap: '.route('seo.sitemap'),
            '',
        ]);

        return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $urls = collect([[
            'loc' => route('home'),
            'lastmod' => Carbon::now()->toDateString(),
            'priority' => '1.0',
        ]]);

        Shop::query()->where('is_active', true)->get(['id', 'slug', 'catalog_type', 'updated_at'])
            ->each(function (Shop $shop) use ($urls): void {
                $urls->push([
                    'loc' => $shop->publicUrl(),
                    'lastmod' => $shop->updated_at?->toDateString(),
                    'priority' => '0.8',
                ]);
            });

        Product::query()
            ->where('is_active', true)
            ->whereHas('shop', fn ($query) => $query->where('is_active', true)->where('catalog_type', 'electronics'))
            ->with('shop:id,slug')
            ->get(['id', 'shop_id', 'slug', 'updated_at'])
            ->each(fn (Product $product) => $urls->push([
                'loc' => route('electronics.product', [$product->shop, $product]),
                'lastmod' => $product->updated_at?->toDateString(),
                'priority' => '0.7',
            ]));

        RealEstateProperty::query()
            ->published()
            ->whereHas('shop', fn ($query) => $query->where('is_active', true)->where('catalog_type', 'real_estate'))
            ->with('shop:id,slug')
            ->get(['id', 'shop_id', 'slug', 'updated_at'])
            ->each(fn (RealEstateProperty $property) => $urls->push([
                'loc' => $property->publicUrl(),
                'lastmod' => $property->updated_at?->toDateString(),
                'priority' => '0.7',
            ]));

        return response()
            ->view('seo.sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
