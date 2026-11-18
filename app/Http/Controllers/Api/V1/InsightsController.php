<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{FrontOrder, Shop, ShopReview};
use App\Services\ShopAnalytics;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Arr;

/** The shop owner's numbers in the app: sales for today, this week or this month, best sellers, busy hours and the daily chart. */
class InsightsController extends Controller
{
    private const TZ = 'Asia/Hebron';

    public function show(Request $request, Shop $shop, ShopAnalytics $analytics): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        $period = in_array($request->query('period'), ['today', 'week', 'month'], true) ? $request->query('period') : 'week';
        $now = now(self::TZ);
        $from = match ($period) {
            'today' => $now->copy()->startOfDay(),
            'month' => $now->copy()->startOfMonth(),
            default => $now->copy()->subDays(6)->startOfDay(),
        };
        $to = $now->copy()->endOfDay();
        $report = $analytics->report($from->copy()->utc(), $to->copy()->utc(), $shop->id);
        $summary = Arr::only($report['summary'], ['sales', 'revenue', 'orders', 'cancelled', 'pending', 'average_order', 'units',
            'buyers', 'returning_buyers', 'new_buyers', 'views', 'visitors', 'revenue_change', 'previous_revenue']);

        // Busy hours: when orders come in, in local time.
        $hours = array_fill(0, 24, 0);
        FrontOrder::where('shop_id', $shop->id)->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$from->copy()->utc(), $to->copy()->utc()])->select(['id', 'created_at'])
            ->lazyById(500)->each(function ($order) use (&$hours) {
                $hours[(int) $order->created_at->timezone(self::TZ)->format('G')]++;
            });

        return response()->json([
            'period' => $period,
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'summary' => array_map(fn ($v) => is_numeric($v) ? $v + 0 : $v, $summary),
            'daily' => collect($report['chart'])->map(fn ($d) => ['day' => $d['day'], 'revenue' => round($d['revenue'], 2), 'sales' => $d['sales']])->values(),
            'hours' => collect($hours)->map(fn ($count, $hour) => ['hour' => $hour, 'orders' => $count])->values(),
            'top_products' => collect($report['top_products'])->take(5)->map(fn ($p) => ['name' => $p['name'], 'units' => $p['units'], 'gross' => round($p['gross'], 2)])->values(),
            'rating' => ShopReview::summaryFor($shop->id),
        ], 200, ['Cache-Control' => 'no-store']);
    }
}
