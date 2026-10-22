<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Services\ShopActivityRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordShopVisit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $request->expectsJson()
            || ! $request->routeIs('home', 'front.home', 'front.shop', 'front.shop.slug', 'restaurant.menu', 'restaurant.table',
                'cosmetics.store', 'sweets.store', 'furniture.store', 'clothing.store', 'electronics.store',
                'advertising.store', 'real-estate.company')
            || preg_match('/bot|crawler|spider|preview/i', $request->userAgent() ?? '')
            || $request->user()?->isSuperAdmin()) return $response;

        $shop = $request->route('shop');
        if (! $shop instanceof Shop && method_exists($response, 'getOriginalContent')) {
            $view = $response->getOriginalContent();
            if ($view instanceof \Illuminate\View\View) $shop = $view->getData()['shop'] ?? null;
        }
        if (! $shop instanceof Shop || ! $shop->is_active) return $response;
        try {
            if (! app(ShopActivityRecorder::class)->available()) return $response;
            $visitor = $request->session()->get('analytics_visitor_id');
            if (! $visitor) {
                $visitor = (string) \Illuminate\Support\Str::uuid();
                $request->session()->put('analytics_visitor_id', $visitor);
            }
            $key = ['shop_id' => $shop->id, 'day' => now()->toDateString(),
                'visitor_hash' => hash_hmac('sha256', $visitor, config('app.key'))];
            DB::table('shop_visit_days')->insertOrIgnore([...$key, 'page_views' => 0]);
            DB::table('shop_visit_days')->where($key)->increment('page_views');
        } catch (\Throwable $e) { report($e); }
        return $response;
    }
}
