<?php

namespace App\Services;

use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopActivityEvent;
use App\Models\VisitorRegistration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShopAnalytics
{
    public function report(Carbon $from, Carbon $to, ?int $shopId): array
    {
        $days = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;
        $orders = FrontOrder::query()->when($shopId, fn ($q) => $q->where('shop_id', $shopId));
        $sales = (clone $orders)->whereIn('status', ['completed', 'delivered'])->whereBetween('sale_confirmed_at', [$from, $to]);
        $placed = (clone $orders)->whereBetween('created_at', [$from, $to]);
        $previous = (clone $orders)->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('sale_confirmed_at', [$from->copy()->subDays($days), $from->copy()->subSecond()]);
        $totals = (clone $sales)->selectRaw('COUNT(*) AS sales, COALESCE(SUM(total),0) AS revenue, COALESCE(SUM(subtotal),0) AS gross, COALESCE(SUM(discount),0) AS discounts')->first()->toArray();
        $priorRevenue = (float) $previous->sum('total');
        $visits = DB::table('shop_visit_days')->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->whereBetween('day', [$from->toDateString(), $to->toDateString()]);
        $visitGroups = (clone $visits)->selectRaw('shop_id, SUM(page_views) AS views, COUNT(DISTINCT visitor_hash) AS visitors')->groupBy('shop_id')->get()->keyBy('shop_id');
        $orderGroups = (clone $placed)->selectRaw('shop_id, COUNT(*) AS orders')->groupBy('shop_id')->pluck('orders', 'shop_id');
        $saleGroups = (clone $sales)->selectRaw('shop_id, COUNT(*) AS sales, SUM(total) AS revenue')->groupBy('shop_id')->get()->keyBy('shop_id');
        $registrations = VisitorRegistration::where('type', 'customer')->when($shopId, fn ($q) => $q->where('shop_id', $shopId))->whereBetween('created_at', [$from, $to]);
        $registrationGroups = (clone $registrations)->selectRaw('shop_id, COUNT(*) AS registrations')->groupBy('shop_id')->pluck('registrations', 'shop_id');
        $loginGroups = ShopActivityEvent::where('kind', 'owner_login')->whereBetween('created_at', [$from, $to])
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))->selectRaw('shop_id, COUNT(*) AS logins')->groupBy('shop_id')->pluck('logins', 'shop_id');
        $shops = Shop::when($shopId, fn ($q) => $q->whereKey($shopId))->orderBy('name')->get(['id', 'name', 'is_active', 'is_accepting_orders', 'catalog_type']);
        $rows = $shops->map(fn ($shop) => [
            'id' => $shop->id, 'name' => $shop->name, 'active' => $shop->is_active, 'accepting_orders' => $shop->is_accepting_orders,
            'orders' => (int) ($orderGroups[$shop->id] ?? 0), 'sales' => (int) ($saleGroups[$shop->id]->sales ?? 0),
            'revenue' => (float) ($saleGroups[$shop->id]->revenue ?? 0),
            'views' => (int) ($visitGroups[$shop->id]->views ?? 0), 'visitors' => (int) ($visitGroups[$shop->id]->visitors ?? 0),
            'registrations' => (int) ($registrationGroups[$shop->id] ?? 0), 'logins' => (int) ($loginGroups[$shop->id] ?? 0),
        ])->sortByDesc('revenue')->values();

        $daily = (clone $sales)->selectRaw('DATE(sale_confirmed_at) AS day, SUM(total) AS revenue, COUNT(*) AS sales')->groupByRaw('DATE(sale_confirmed_at)')->get()->keyBy('day');
        $chart = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $chart[] = ['day' => $key, 'revenue' => (float) ($daily[$key]->revenue ?? 0), 'sales' => (int) ($daily[$key]->sales ?? 0)];
        }
        $units = 0;
        $products = [];
        $buyers = [];
        // Stream item snapshots: deleting or renaming a product must not erase its sales.
        foreach ((clone $sales)->select(['id', 'shop_id', 'items', 'customer_phone', 'customer_whatsapp'])->lazyById(250) as $order) {
            $phone = preg_replace('/\D/', '', $order->customer_phone ?: $order->customer_whatsapp ?: '');
            if ($phone !== '') $buyers[$order->shop_id.':'.$phone] = true;
            foreach ($order->items ?? [] as $item) {
                $qty = max(0, (int) ($item['qty'] ?? $item['quantity'] ?? 1));
                $units += $qty;
                $key = $order->shop_id.':'.($item['product_id'] ?? $item['id'] ?? $item['name'] ?? 'unknown');
                $products[$key] ??= ['name' => $item['name'] ?? 'منتج', 'shop_id' => $order->shop_id, 'units' => 0, 'gross' => 0];
                $products[$key]['units'] += $qty;
                $products[$key]['gross'] += $qty * (float) ($item['price'] ?? 0);
            }
        }
        $returning = [];
        if ($buyers) {
            foreach ((clone $orders)->whereIn('status', ['completed', 'delivered'])->where('sale_confirmed_at', '<', $from)
                ->select(['id', 'shop_id', 'customer_phone', 'customer_whatsapp'])->lazyById(500) as $order) {
                $phone = preg_replace('/\D/', '', $order->customer_phone ?: $order->customer_whatsapp ?: '');
                $key = $order->shop_id.':'.$phone;
                if (isset($buyers[$key])) $returning[$key] = true;
            }
        }
        $shopNames = $shops->pluck('name', 'id');
        $top = collect($products)->sortByDesc('units')->take(10)->values()->map(fn ($p) => [...$p, 'shop' => $shopNames[$p['shop_id']] ?? 'متجر محذوف'])->all();
        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'tracked_since' => DB::table('shop_analytics_state')->value('started_at'),
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                ...$totals, 'previous_revenue' => $priorRevenue,
                'revenue_change' => $priorRevenue > 0 ? round(((float) $totals['revenue'] - $priorRevenue) / $priorRevenue * 100, 1) : null,
                'average_order' => $totals['sales'] ? round($totals['revenue'] / $totals['sales'], 2) : 0,
                'orders' => (clone $placed)->count(), 'cancelled' => (clone $placed)->where('status', 'cancelled')->count(),
                'pending' => (clone $placed)->whereNotIn('status', ['completed', 'delivered', 'cancelled'])->count(),
                'units' => $units, 'buyers' => count($buyers), 'returning_buyers' => count($returning),
                'new_buyers' => count($buyers) - count($returning),
                'views' => (int) (clone $visits)->sum('page_views'),
                'visitors' => (clone $visits)->distinct()->count('visitor_hash'),
                'registrations' => (clone $registrations)->count(), 'owner_logins' => $loginGroups->sum(),
                'shops' => $shops->count(), 'active_shops' => $shops->where('is_active', true)->count(),
                'products' => Product::when($shopId, fn ($q) => $q->where('shop_id', $shopId))->count(),
                'inactive_products' => Product::where('is_active', false)->when($shopId, fn ($q) => $q->where('shop_id', $shopId))->count(),
                'estimated_sales_dates' => (clone $sales)->where('sale_date_estimated', true)->count(),
                'unassigned_orders' => (clone $placed)->whereNull('shop_id')->count(),
            ],
            'shops' => $rows, 'chart' => $chart, 'top_products' => $top,
            'statuses' => (clone $placed)->selectRaw('status, COUNT(*) AS count')->groupBy('status')->get()
                ->map(fn ($o) => ['name' => $o->statusLabel(), 'count' => $o->count]),
            'orders' => (clone $placed)->with('shop:id,name')->latest()->limit(20)->get()->map(fn ($o) => [
                'id' => $o->id, 'number' => $o->order_number, 'shop' => $o->shop?->name ?? 'غير مربوط بمتجر',
                'customer' => $o->customer_name, 'total' => $o->total, 'status' => $o->statusLabel(), 'created_at' => $o->created_at->toIso8601String(),
            ]),
        ];
    }
}
