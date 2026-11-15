<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Product, Shop};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

/**
 * Turns a website link (shared on WhatsApp, a QR on a table, a promoter's page…) into the app
 * screen that shows the same thing. Anything the app doesn't have opens in the browser.
 */
class LinkController extends Controller
{
    private const SHOP_ROUTES = ['restaurant.menu', 'electronics.store', 'clothing.store', 'cosmetics.store', 'sweets.store', 'krembo.store',
        'dental.clinic', 'furniture.store', 'advertising.store', 'front.shop.slug', 'real-estate.company', 'krembo.catalog'];

    public function resolve(Request $request): JsonResponse
    {
        $url = (string) $request->validate(['url' => ['required', 'string', 'max:2000']])['url'];
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $known = collect([parse_url(config('app.url'), PHP_URL_HOST), 'ozman.online', 'www.ozman.online', 'staging.ozman.online'])->filter()->map('strtolower');
        if (! $known->contains($host)) {
            return response()->json(['target' => ['type' => 'web', 'url' => $url]]);
        }
        $path = '/'.ltrim((string) ($parts['path'] ?? '/'), '/');
        parse_str((string) ($parts['query'] ?? ''), $query);

        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (\Throwable) {
            return response()->json(['target' => ['type' => 'web', 'url' => $url]]);
        }
        $name = (string) $route->getName();
        $param = fn (string $key) => is_object($route->parameter($key)) ? $route->parameter($key)->getRouteKey() : (string) $route->parameter($key);

        $target = match (true) {
            $path === '/' => ['type' => 'home'],
            in_array($name, self::SHOP_ROUTES, true) => ($shop = $this->shop($param('shop'))) ? ['type' => 'shop', 'id' => $shop->id, 'name' => $shop->name] : null,
            $name === 'cosmetics.booking' => ($shop = $this->shop($param('shop'))) ? ['type' => 'booking', 'id' => $shop->id, 'name' => $shop->name] : null,
            $name === 'restaurant.table' => ['type' => 'table', 'slug' => $param('shop'), 'code' => $param('tableCode')],
            $name === 'share.product', $name === 'electronics.product' => ($product = $this->product($param('product'))) ? ['type' => 'product', 'id' => $product->id] : null,
            $name === 'promoters.show' => ['type' => 'promoter', 'slug' => $param('slug')],
            $name === 'market.show' => ['type' => 'market_item', 'id' => (int) $param('listing')],
            $name === 'market.index' => ['type' => 'market'],
            $name === 'promoters.index' => ['type' => 'promoters'],
            $name === 'real-estate.property' => ($id = DB::table('real_estate_properties')->where('slug', $param('realEstateProperty'))->value('id')) ? ['type' => 'property', 'id' => $id] : null,
            $name === 'real-estate.index' => ['type' => 'properties'],
            $name === 'front.raffle-card.open' => ['type' => 'raffle_card', 'number' => $param('cardNumber')],
            $name === 'front.marketer.direct-wheel', $name === 'front.marketer' => ['type' => 'marketer_wheel', 'code' => $param('marketer')],
            default => null,
        };
        // A product opened from a shop page (?product=…) goes straight to the product.
        if (($target['type'] ?? null) === 'shop' && ctype_digit((string) ($query['product'] ?? ''))) {
            $product = Product::where('shop_id', $target['id'])->where('is_active', true)->find((int) $query['product']);
            $target = $product ? ['type' => 'product', 'id' => $product->id] : $target;
        }

        return response()->json(['target' => $target ?? ['type' => 'web', 'url' => $url]]);
    }

    private function shop(string $slug): ?Shop
    {
        return Shop::where('is_active', true)->where(fn ($q) => $q->where('slug', $slug)->orWhere('id', ctype_digit($slug) ? (int) $slug : 0))->first()
            ?? Shop::where('is_active', true)->whereHas('slugRedirects', fn ($q) => $q->where('slug', $slug))->first();
    }

    private function product(string $key): ?Product
    {
        return Product::where('is_active', true)->where(fn ($q) => $q->where('slug', $key)->orWhere('id', ctype_digit($key) ? (int) $key : 0))->first();
    }
}
