<?php

namespace App\Services;

use App\Models\FrontOrder;
use App\Models\Shop;
use App\Models\ShopActivityEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Deeper readings for the analytics centre: funnel, kitchen, alerts, engagement, peak hours, trends, customers. */
class AnalyticsInsights
{
    private const SALE = ['completed', 'delivered'];

    public function build(Carbon $from, Carbon $to, ?int $shopId, Collection $shopRows): array
    {
        $names = Shop::pluck('name', 'id');
        $blocks = [];
        foreach (['funnel', 'kitchen', 'alerts', 'engagement', 'growth', 'heatmap', 'trends', 'customers'] as $block) {
            try {
                $blocks[$block] = $this->{$block}($from, $to, $shopId, $shopRows, $names);
            } catch (Throwable $e) {
                report($e); // One missing table must not take the whole page down.
                $blocks[$block] = null;
            }
        }

        return $blocks;
    }

    private function orders(?int $shopId)
    {
        return FrontOrder::query()->when($shopId, fn ($q) => $q->where('shop_id', $shopId));
    }

    /** Visitors → orders → confirmed sales, per shop. */
    private function funnel(Carbon $from, Carbon $to, ?int $shopId, Collection $rows): array
    {
        $rate = fn ($a, $b) => $b > 0 ? round($a / $b * 100, 1) : null;
        $shops = $rows->map(fn ($r) => [
            'id' => $r['id'], 'name' => $r['name'], 'visitors' => $r['visitors'], 'orders' => $r['orders'], 'sales' => $r['sales'],
            'visit_to_order' => $rate($r['orders'], $r['visitors']), 'order_to_sale' => $rate($r['sales'], $r['orders']),
        ])->filter(fn ($r) => $r['visitors'] || $r['orders'])->sortByDesc('visitors')->values();
        $v = $rows->sum('visitors'); $o = $rows->sum('orders'); $s = $rows->sum('sales');

        return ['total' => ['visitors' => $v, 'orders' => $o, 'sales' => $s, 'visit_to_order' => $rate($o, $v), 'order_to_sale' => $rate($s, $o)],
            'shops' => $shops->take(30)->all()];
    }

    /** Real preparation time against the time promised to the customer. */
    private function kitchen(Carbon $from, Carbon $to, ?int $shopId, Collection $rows, Collection $names): array
    {
        $timed = $this->orders($shopId)->whereBetween('created_at', [$from, $to])
            ->whereNotNull('preparation_started_at')->whereNotNull('ready_at')->whereNotNull('estimated_preparation_minutes')
            ->get(['shop_id', 'preparation_started_at', 'ready_at', 'estimated_preparation_minutes']);
        $cancelled = $this->orders($shopId)->whereBetween('created_at', [$from, $to])->where('status', 'cancelled')
            ->selectRaw('shop_id, COUNT(*) AS c')->groupBy('shop_id')->pluck('c', 'shop_id');
        $all = $this->orders($shopId)->whereBetween('created_at', [$from, $to])->selectRaw('shop_id, COUNT(*) AS c')->groupBy('shop_id')->pluck('c', 'shop_id');
        $stats = function (Collection $group) {
            $actual = $group->map(fn ($o) => max(0, $o->preparation_started_at->diffInSeconds($o->ready_at, false)) / 60);
            $late = $group->filter(fn ($o) => $o->preparation_started_at->diffInSeconds($o->ready_at, false) / 60 > $o->estimated_preparation_minutes + 1);

            return ['timed' => $group->count(), 'avg_actual' => $group->count() ? round($actual->avg(), 1) : null,
                'avg_promised' => $group->count() ? round($group->avg('estimated_preparation_minutes'), 1) : null,
                'late_pct' => $group->count() ? round($late->count() / $group->count() * 100) : null];
        };
        $shops = $timed->groupBy('shop_id')->map(fn ($g, $id) => ['id' => (int) $id, 'name' => $names[$id] ?? 'متجر محذوف', ...$stats($g),
            'cancelled' => (int) ($cancelled[$id] ?? 0), 'cancel_pct' => ($all[$id] ?? 0) ? round(($cancelled[$id] ?? 0) / $all[$id] * 100) : 0]);
        foreach ($cancelled as $id => $c) {
            if (! $shops->has($id)) {
                $shops[$id] = ['id' => (int) $id, 'name' => $names[$id] ?? 'متجر محذوف', 'timed' => 0, 'avg_actual' => null, 'avg_promised' => null,
                    'late_pct' => null, 'cancelled' => (int) $c, 'cancel_pct' => ($all[$id] ?? 0) ? round($c / $all[$id] * 100) : 0];
            }
        }

        return ['total' => [...$stats($timed), 'cancelled' => (int) $cancelled->sum()], 'shops' => $shops->sortByDesc('late_pct')->values()->all()];
    }

    /** Things that need attention now, whatever period is selected. */
    private function alerts(Carbon $from, Carbon $to, ?int $shopId, Collection $rows, Collection $names): array
    {
        $alerts = [];
        $shops = Shop::query()->where('is_active', true)->when($shopId, fn ($q) => $q->whereKey($shopId))->get(['id', 'name', 'is_accepting_orders', 'catalog_type']);
        // Orders nobody answered.
        $waiting = $this->orders($shopId)->where('status', 'new')->where('created_at', '<=', now()->subMinutes(10))->where('created_at', '>=', now()->subDay())
            ->with('shop:id,name')->latest()->limit(10)->get();
        foreach ($waiting as $order) {
            $alerts[] = ['level' => 'danger', 'icon' => '⏰', 'shop_id' => $order->shop_id, 'order_id' => $order->id,
                'text' => 'طلب '.$order->order_number.' بـ'.($order->shop?->name ?? 'متجر').' ما انرد عليه من '.$this->ago($order->created_at)];
        }
        $lastOrder = FrontOrder::query()->whereIn('shop_id', $shops->pluck('id'))->selectRaw('shop_id, MAX(created_at) AS last')->groupBy('shop_id')->pluck('last', 'shop_id');
        foreach ($shops->where('is_accepting_orders', false) as $shop) {
            $last = isset($lastOrder[$shop->id]) ? Carbon::parse($lastOrder[$shop->id]) : null;
            if (! $last || $last->lt(now()->subDays(3))) {
                $alerts[] = ['level' => 'warn', 'icon' => '⛔', 'shop_id' => $shop->id,
                    'text' => $shop->name.': استقبال الطلبات متوقف'.($last ? '، وآخر طلب قبل '.((int) $last->diffInDays(now())).' يوم' : '، وما وصلها طلبات لسا')];
            }
        }
        // Owners who stopped logging in (only once there is a week of tracking).
        $trackedSince = DB::table('shop_analytics_state')->value('started_at');
        if ($trackedSince && Carbon::parse($trackedSince)->lt(now()->subDays(7))) {
            $logins = ShopActivityEvent::where('kind', 'owner_login')->whereIn('shop_id', $shops->pluck('id'))
                ->selectRaw('shop_id, MAX(created_at) AS last')->groupBy('shop_id')->pluck('last', 'shop_id');
            foreach ($shops as $shop) {
                $last = isset($logins[$shop->id]) ? Carbon::parse($logins[$shop->id]) : null;
                if (! $last || $last->lt(now()->subDays(7))) {
                    $alerts[] = ['level' => 'info', 'icon' => '💤', 'shop_id' => $shop->id,
                        'text' => $shop->name.': صاحب المحل ما دخل على لوحته'.($last ? ' من '.((int) $last->diffInDays(now())).' يوم' : ' من أسبوع أو أكثر')];
                }
            }
        }
        // Sales drop: last 7 days against the 7 before.
        $week = fn (Carbon $a, Carbon $b) => FrontOrder::query()->whereIn('shop_id', $shops->pluck('id'))->whereIn('status', self::SALE)
            ->whereBetween('sale_confirmed_at', [$a, $b])->selectRaw('shop_id, SUM(total) AS r')->groupBy('shop_id')->pluck('r', 'shop_id');
        $now = $week(now()->subDays(7), now());
        $before = $week(now()->subDays(14), now()->subDays(7));
        foreach ($before as $id => $prev) {
            $cur = (float) ($now[$id] ?? 0);
            if ((float) $prev >= 100 && $cur <= (float) $prev * 0.6) {
                $alerts[] = ['level' => 'warn', 'icon' => '📉', 'shop_id' => (int) $id,
                    'text' => ($names[$id] ?? 'متجر').': المبيعات نزلت '.round((1 - $cur / (float) $prev) * 100).'% عن الأسبوع الماضي ('.number_format($cur, 0).' ₪ مقابل '.number_format((float) $prev, 0).' ₪)'];
            }
        }
        $order = ['danger' => 0, 'warn' => 1, 'info' => 2];

        return collect($alerts)->sortBy(fn ($a) => $order[$a['level']])->values()->take(40)->all();
    }

    private function ago(Carbon $at): string
    {
        $minutes = (int) $at->diffInMinutes(now());

        return $minutes < 60 ? $minutes.' دقيقة' : ((int) floor($minutes / 60)).' ساعة';
    }

    /** Likes, shares and social posts. */
    private function engagement(Carbon $from, Carbon $to, ?int $shopId, Collection $rows, Collection $names): array
    {
        $products = DB::table('products')->when($shopId, fn ($q) => $q->where('shop_id', $shopId));
        $map = fn ($p) => ['name' => $p->name, 'shop' => $names[$p->shop_id] ?? 'متجر', 'value' => (int) $p->value];
        $out = [
            'likes_total' => Schema::hasColumn('products', 'likes_count') ? (int) (clone $products)->sum('likes_count') : 0,
            'shares_total' => Schema::hasColumn('products', 'share_opens') ? (int) (clone $products)->sum('share_opens') : 0,
            'top_liked' => Schema::hasColumn('products', 'likes_count') ? (clone $products)->where('likes_count', '>', 0)->orderByDesc('likes_count')->limit(8)
                ->get(['name', 'shop_id', 'likes_count as value'])->map($map)->all() : [],
            'top_shared' => Schema::hasColumn('products', 'share_opens') ? (clone $products)->where('share_opens', '>', 0)->orderByDesc('share_opens')->limit(8)
                ->get(['name', 'shop_id', 'share_opens as value'])->map($map)->all() : [],
            'social' => null,
        ];
        if (Schema::hasTable('social_posts')) {
            $posts = DB::table('social_posts')->when($shopId, fn ($q) => $q->where('shop_id', $shopId))->whereBetween('created_at', [$from, $to]);
            $out['social'] = ['total' => (clone $posts)->count(), 'published' => (clone $posts)->whereIn('status', ['published', 'partial'])->count(),
                'scheduled' => (clone $posts)->where('status', 'scheduled')->count(), 'failed' => (clone $posts)->where('status', 'failed')->count(),
                'shops' => (clone $posts)->selectRaw('shop_id, COUNT(*) AS c')->groupBy('shop_id')->orderByDesc('c')->limit(8)->get()
                    ->map(fn ($r) => ['name' => $names[$r->shop_id] ?? 'متجر', 'value' => (int) $r->c])->all()];
        }

        return $out;
    }

    /** Wheels, loyalty points, promoters and the free market. */
    private function growth(Carbon $from, Carbon $to, ?int $shopId): array
    {
        $events = ShopActivityEvent::whereBetween('created_at', [$from, $to])->when($shopId, fn ($q) => $q->where('shop_id', $shopId));
        $out = ['wheel_spins' => (clone $events)->where('kind', 'reward_spun')->count(),
            'wheel_redeemed' => (clone $events)->where('kind', 'reward_redeemed')->count(),
            'purchase_prizes' => $this->orders($shopId)->whereBetween('created_at', [$from, $to])->whereNotNull('reward_won_at')->count()];
        if (Schema::hasTable('loyalty_point_entries')) {
            $points = DB::table('loyalty_point_entries')->whereBetween('created_at', [$from, $to])->where('status', 'confirmed')->when($shopId, fn ($q) => $q->where('shop_id', $shopId));
            $out['points_earned'] = (int) (clone $points)->where('type', 'earn')->sum('points');
            $out['points_spent'] = (int) abs((clone $points)->whereIn('type', ['redeem', 'gift'])->sum('points'));
        }
        if (Schema::hasTable('promoter_profiles')) {
            $out['promoters'] = DB::table('promoter_profiles')->whereNotNull('approved_at')->count();
            $out['promoter_posts'] = DB::table('promoter_posts')->whereBetween('created_at', [$from, $to])->when($shopId, fn ($q) => $q->where('shop_id', $shopId))->count();
            $clicks = DB::table('promoter_posts')->selectRaw('promoter_profile_id, SUM(shop_clicks) AS c')->when($shopId, fn ($q) => $q->where('shop_id', $shopId))->groupBy('promoter_profile_id');
            $out['top_promoters'] = DB::table('promoter_profiles')->joinSub($clicks, 'k', 'k.promoter_profile_id', '=', 'promoter_profiles.id')
                ->orderByDesc('k.c')->limit(5)->get(['display_name', 'k.c'])->map(fn ($r) => ['name' => $r->display_name, 'value' => (int) $r->c])->filter(fn ($r) => $r['value'] > 0)->values()->all();
        }
        if (Schema::hasTable('market_listings') && ! $shopId) {
            $out['market_listings'] = DB::table('market_listings')->whereBetween('created_at', [$from, $to])->count();
            $out['market_orders'] = DB::table('market_orders')->whereBetween('created_at', [$from, $to])->count();
            $out['market_sales'] = (float) DB::table('market_orders')->whereBetween('created_at', [$from, $to])->where('status', 'completed')->sum('total');
        }

        return $out;
    }

    /** Orders by weekday and hour, in the shops' local time. */
    private function heatmap(Carbon $from, Carbon $to, ?int $shopId): array
    {
        $tz = config('services.zernio.timezone', 'Asia/Jerusalem');
        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        foreach ($this->orders($shopId)->whereBetween('created_at', [$from, $to])->select(['id', 'created_at'])->lazyById(1000) as $o) {
            $at = $o->created_at->copy()->timezone($tz);
            $grid[$at->dayOfWeek][$at->hour]++;
        }
        $max = max(array_map('max', $grid));
        $best = [];
        foreach ($grid as $day => $hours) {
            foreach ($hours as $hour => $count) {
                if ($count) $best[] = ['day' => $day, 'hour' => $hour, 'count' => $count];
            }
        }

        return ['grid' => $grid, 'max' => $max, 'timezone' => $tz, 'peak' => collect($best)->sortByDesc('count')->take(3)->values()->all()];
    }

    /** Which shops grew or dropped against the previous period of the same length. */
    private function trends(Carbon $from, Carbon $to, ?int $shopId, Collection $rows, Collection $names): array
    {
        $days = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;
        $prev = $this->orders($shopId)->whereIn('status', self::SALE)->whereBetween('sale_confirmed_at', [$from->copy()->subDays($days), $from->copy()->subSecond()])
            ->selectRaw('shop_id, SUM(total) AS r')->groupBy('shop_id')->pluck('r', 'shop_id');
        $list = $rows->map(function ($r) use ($prev) {
            $before = (float) ($prev[$r['id']] ?? 0);

            return ['id' => $r['id'], 'name' => $r['name'], 'revenue' => $r['revenue'], 'previous' => $before,
                'change' => $before > 0 ? round(($r['revenue'] - $before) / $before * 100, 1) : ($r['revenue'] > 0 ? null : 0)];
        })->filter(fn ($r) => $r['revenue'] > 0 || $r['previous'] > 0);

        return ['rising' => $list->filter(fn ($r) => $r['revenue'] > $r['previous'])->sortByDesc(fn ($r) => $r['revenue'] - $r['previous'])->take(5)->values()->all(),
            'falling' => $list->filter(fn ($r) => $r['revenue'] < $r['previous'])->sortBy(fn ($r) => $r['revenue'] - $r['previous'])->take(5)->values()->all()];
    }

    /** Best customers by confirmed purchases in the period. */
    private function customers(Carbon $from, Carbon $to, ?int $shopId, Collection $rows, Collection $names): array
    {
        $buyers = [];
        foreach ($this->orders($shopId)->whereIn('status', self::SALE)->whereBetween('sale_confirmed_at', [$from, $to])
            ->select(['id', 'shop_id', 'customer_name', 'customer_phone', 'customer_whatsapp', 'total'])->lazyById(500) as $o) {
            $phone = preg_replace('/\D/', '', $o->customer_phone ?: $o->customer_whatsapp ?: '');
            if ($phone === '') continue;
            $key = $o->shop_id.':'.$phone;
            $buyers[$key] ??= ['name' => $o->customer_name ?: 'زبون', 'phone' => $phone, 'shop' => $names[$o->shop_id] ?? 'متجر', 'orders' => 0, 'spent' => 0];
            $buyers[$key]['orders']++;
            $buyers[$key]['spent'] += (float) $o->total;
        }

        return collect($buyers)->sortByDesc('spent')->take(10)->values()->all();
    }
}
