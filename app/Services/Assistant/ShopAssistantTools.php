<?php

namespace App\Services\Assistant;

use App\Models\{AssistantAction, AssistantConversation, Category, FrontOrder, Product, SalonAppointment, Shop, User};
use App\Services\ShopAnalytics;
use Carbon\Carbon;
use Illuminate\Support\{Arr, Str};

/**
 * What the shop owner's assistant can look at and propose. Every tool is limited to one shop; reads never return
 * customer phone numbers; tools that would change something only record a proposal the owner must confirm.
 */
class ShopAssistantTools
{
    public const TZ = 'Asia/Hebron';

    /** Order statuses the owner may set, per kind of order (the dashboards' own lists). */
    private const SHOP_STATUSES = ['new', 'preparing', 'ready', 'out_for_delivery', 'completed', 'delivered', 'not_delivered', 'cancelled'];
    private const RESTAURANT_STATUSES = ['new', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];

    public function __construct(private ShopAnalytics $analytics) {}

    /** The tool definitions sent to Claude. */
    public function definitions(Shop $shop): array
    {
        $date = ['type' => 'string', 'description' => 'YYYY-MM-DD (local date)'];
        $tools = [
            ['name' => 'shop_info', 'description' => 'The shop: name, type, hours, whether it accepts orders now, counts of products and categories.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []]],
            ['name' => 'sales_report', 'description' => 'Sales for a period: confirmed sales (completed/delivered) and revenue, orders placed, visitors, comparison with the previous period of the same length, top products, statuses, daily chart and customer insights. Defaults to this month.',
                'input_schema' => ['type' => 'object', 'properties' => ['from' => $date, 'to' => $date]]],
            ['name' => 'list_orders', 'description' => 'The shop\'s orders, newest first: number, status, total, customer name, items, age. Filter by status, dates or a search on the customer name or order number.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'status' => ['type' => 'string', 'enum' => self::SHOP_STATUSES],
                    'from' => $date, 'to' => $date,
                    'search' => ['type' => 'string'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30],
                ]]],
            ['name' => 'search_products', 'description' => 'Products of the shop with id, price, discount price, active flag, category and views. Search by name, filter by category or active flag.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'query' => ['type' => 'string'],
                    'category_id' => ['type' => 'integer'],
                    'active' => ['type' => 'boolean'],
                    'sort' => ['type' => 'string', 'enum' => ['newest', 'price_asc', 'price_desc', 'views']],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 40],
                ]]],
            ['name' => 'product_sales', 'description' => 'Units and revenue per product over a period, from confirmed sales. order=best for best sellers, order=worst for active products that sold least (including zero). Defaults to the last 30 days.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'from' => $date, 'to' => $date,
                    'order' => ['type' => 'string', 'enum' => ['best', 'worst']],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30],
                ]]],
            ['name' => 'list_categories', 'description' => 'The shop\'s categories with id and number of products.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []]],
            ['name' => 'customers', 'description' => 'Customers who bought (confirmed sales): name, number of orders, total spent, last order date. sort=top for best customers, sort=lapsed for customers who bought before but not in the last `days` days (default 30). No phone numbers.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'sort' => ['type' => 'string', 'enum' => ['top', 'lapsed']],
                    'days' => ['type' => 'integer', 'minimum' => 7, 'maximum' => 365],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30],
                ]]],
            ['name' => 'propose_product_change', 'description' => 'Propose changing a product: price, discount price (0 removes the discount) or whether it is shown (hide a product that is unavailable). Only records a proposal; the owner must press confirm. Look the product up first.',
                'input_schema' => ['type' => 'object', 'required' => ['product_id'], 'properties' => [
                    'product_id' => ['type' => 'integer'],
                    'price' => ['type' => 'number', 'minimum' => 0],
                    'discount_price' => ['type' => 'number', 'minimum' => 0],
                    'is_active' => ['type' => 'boolean'],
                ]]],
            ['name' => 'propose_order_status', 'description' => 'Propose moving an order to another status. The customer is notified when it is confirmed. For restaurant orders moving to preparing, give estimated_preparation_minutes. Only records a proposal; the owner must press confirm.',
                'input_schema' => ['type' => 'object', 'required' => ['order_id', 'status'], 'properties' => [
                    'order_id' => ['type' => 'integer', 'description' => 'the order id from list_orders (not the order number)'],
                    'status' => ['type' => 'string', 'enum' => self::SHOP_STATUSES],
                    'estimated_preparation_minutes' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 1440],
                ]]],
            ['name' => 'propose_accepting_orders', 'description' => 'Propose pausing or resuming new orders for the shop. Only records a proposal; the owner must press confirm.',
                'input_schema' => ['type' => 'object', 'required' => ['open'], 'properties' => ['open' => ['type' => 'boolean']]]],
        ];
        if ($shop->catalog_type === 'cosmetics') {
            $tools[] = ['name' => 'salon_appointments', 'description' => 'Salon bookings: a given date, or upcoming/past. Service, time, status, customer name.',
                'input_schema' => ['type' => 'object', 'properties' => ['date' => $date, 'when' => ['type' => 'string', 'enum' => ['upcoming', 'past']]]]];
        }

        return $tools;
    }

    /** Runs one tool and returns data for Claude. Errors come back as ['error' => ...] so the model can correct itself. */
    public function run(string $name, array $input, Shop $shop, User $user, AssistantConversation $conversation, array &$proposed): array
    {
        try {
            return match ($name) {
                'shop_info' => $this->shopInfo($shop),
                'sales_report' => $this->salesReport($shop, $input),
                'list_orders' => $this->listOrders($shop, $input),
                'search_products' => $this->searchProducts($shop, $input),
                'product_sales' => $this->productSales($shop, $input),
                'list_categories' => $this->categories($shop),
                'customers' => $this->customers($shop, $input),
                'salon_appointments' => $this->appointments($shop, $input),
                'propose_product_change' => $this->proposeProduct($shop, $user, $conversation, $input, $proposed),
                'propose_order_status' => $this->proposeOrderStatus($shop, $user, $conversation, $input, $proposed),
                'propose_accepting_orders' => $this->proposeAccepting($shop, $user, $conversation, $input, $proposed),
                default => ['error' => 'unknown tool'],
            };
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ── Reads ────────────────────────────────────────────────────────────────

    private function shopInfo(Shop $shop): array
    {
        return [
            'name' => $shop->name,
            'type' => $shop->catalog_type,
            'city' => $shop->city,
            'open_time' => $shop->open_time ? substr($shop->open_time, 0, 5) : null,
            'close_time' => $shop->close_time ? substr($shop->close_time, 0, 5) : null,
            'accepting_orders' => (bool) ($shop->is_accepting_orders ?? true),
            'active' => (bool) $shop->is_active,
            'loyalty_points' => (bool) $shop->loyalty_enabled,
            'products' => Product::where('shop_id', $shop->id)->count(),
            'active_products' => Product::where('shop_id', $shop->id)->where('is_active', true)->count(),
            'categories' => Category::where('shop_id', $shop->id)->count(),
            'public_url' => $shop->publicUrl(),
        ];
    }

    private function salesReport(Shop $shop, array $input): array
    {
        [$from, $to] = $this->period($input, now(self::TZ)->startOfMonth());
        $report = $this->analytics->report($from->copy()->utc(), $to->copy()->utc(), $shop->id);
        $data = Arr::only($report, ['summary', 'top_products', 'statuses', 'insights']);
        $data['period'] = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
        $data['top_products'] = collect($data['top_products'] ?? [])->map(fn ($p) => Arr::only((array) $p, ['name', 'units', 'gross']))->all();
        if ($from->diffInDays($to) <= 62) {
            $data['daily'] = collect($report['chart'] ?? [])->filter(fn ($d) => $d['sales'] > 0)->values()->all();
        }
        if (isset($data['insights']['customers'])) {
            $data['insights']['customers'] = collect($data['insights']['customers'])->map(fn ($c) => Arr::except((array) $c, ['phone', 'whatsapp']))->all();
        }

        return $data;
    }

    private function listOrders(Shop $shop, array $input): array
    {
        $query = $this->ordersOf($shop)->latest();
        if (! empty($input['status'])) {
            $query->where('status', $input['status']);
        }
        if (! empty($input['from']) || ! empty($input['to'])) {
            [$from, $to] = $this->period($input, now(self::TZ)->subDays(30));
            $query->whereBetween('created_at', [$from->copy()->utc(), $to->copy()->utc()]);
        }
        if (filled($input['search'] ?? null)) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $input['search']).'%';
            $query->where(fn ($q) => $q->where('customer_name', 'like', $term)->orWhere('order_number', 'like', $term));
        }
        $total = (clone $query)->count();
        $orders = $query->with('restaurantTable')->limit($this->limit($input, 15, 30))->get();
        $labels = FrontOrder::statusOptions();

        return ['count' => $total, 'orders' => $orders->map(fn (FrontOrder $order) => [
            'id' => $order->id,
            'number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $labels[$order->status] ?? $order->status,
            'total' => (float) $order->total,
            'customer' => $order->customer_name,
            'type' => $order->order_type ?: (filled($order->customer_address) ? 'delivery' : 'pickup'),
            'table' => $order->restaurantTable?->name,
            'items' => collect($order->items ?? [])->map(fn ($i) => ($i['qty'] ?? $i['quantity'] ?? 1).'× '.($i['name'] ?? 'منتج'))->implode('، '),
            'notes' => Str::limit((string) $order->customer_notes, 160),
            'placed_at' => $order->created_at?->timezone(self::TZ)->format('Y-m-d H:i'),
            'minutes_ago' => $order->created_at ? (int) $order->created_at->diffInMinutes(now()) : null,
        ])->all()];
    }

    private function searchProducts(Shop $shop, array $input): array
    {
        $query = Product::with('category:id,name')->where('shop_id', $shop->id);
        if (filled($input['query'] ?? null)) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $input['query']).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('barcode', 'like', $term));
        }
        if (! empty($input['category_id'])) {
            $query->where('category_id', $input['category_id']);
        }
        if (array_key_exists('active', $input)) {
            $query->where('is_active', (bool) $input['active']);
        }
        match ($input['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'views' => $query->orderByDesc('views'),
            default => $query->latest('id'),
        };
        $total = (clone $query)->count();

        return ['count' => $total, 'products' => $query->limit($this->limit($input, 20, 40))->get()->map(fn (Product $p) => $this->product($p))->all()];
    }

    private function productSales(Shop $shop, array $input): array
    {
        [$from, $to] = $this->period($input, now(self::TZ)->subDays(29));
        $sold = [];
        $orders = FrontOrder::where('shop_id', $shop->id)->whereIn('status', ['completed', 'delivered'])
            ->whereBetween('sale_confirmed_at', [$from->copy()->utc(), $to->copy()->utc()])->select(['id', 'items']);
        foreach ($orders->lazyById(250) as $order) {
            foreach ($order->items ?? [] as $item) {
                $key = (string) ($item['product_id'] ?? $item['id'] ?? $item['name'] ?? 'x');
                $qty = max(0, (int) ($item['qty'] ?? $item['quantity'] ?? 1));
                $sold[$key] ??= ['product_id' => isset($item['product_id']) || isset($item['id']) ? (int) ($item['product_id'] ?? $item['id']) : null, 'name' => $item['name'] ?? 'منتج', 'units' => 0, 'revenue' => 0.0];
                $sold[$key]['units'] += $qty;
                $sold[$key]['revenue'] += $qty * (float) ($item['price'] ?? 0);
            }
        }
        $limit = $this->limit($input, 10, 30);
        if (($input['order'] ?? 'best') === 'worst') {
            $byId = collect($sold)->whereNotNull('product_id')->keyBy('product_id');
            $rows = Product::where('shop_id', $shop->id)->where('is_active', true)->get(['id', 'name', 'price', 'views'])
                ->map(fn ($p) => ['product_id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price, 'views' => (int) $p->views,
                    'units' => (int) ($byId[$p->id]['units'] ?? 0), 'revenue' => round((float) ($byId[$p->id]['revenue'] ?? 0), 2)])
                ->sortBy([['units', 'asc'], ['views', 'desc']])->take($limit)->values();
        } else {
            $rows = collect($sold)->sortByDesc('units')->take($limit)->map(fn ($r) => [...$r, 'revenue' => round($r['revenue'], 2)])->values();
        }

        return ['period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'products' => $rows->all()];
    }

    private function categories(Shop $shop): array
    {
        return ['categories' => Category::where('shop_id', $shop->id)->withCount('products')->orderBy('name')->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'active' => (bool) $c->is_active, 'products' => $c->products_count])->all()];
    }

    private function customers(Shop $shop, array $input): array
    {
        $buyers = [];
        $orders = FrontOrder::where('shop_id', $shop->id)->whereIn('status', ['completed', 'delivered'])
            ->select(['id', 'customer_name', 'customer_phone', 'customer_whatsapp', 'total', 'created_at']);
        foreach ($orders->lazyById(500) as $order) {
            $key = preg_replace('/\D/', '', (string) ($order->customer_phone ?: $order->customer_whatsapp)) ?: 'name:'.mb_strtolower(trim((string) $order->customer_name));
            if ($key === 'name:') {
                continue;
            }
            $buyers[$key] ??= ['name' => $order->customer_name, 'orders' => 0, 'spent' => 0.0, 'last' => null];
            $buyers[$key]['orders']++;
            $buyers[$key]['spent'] += (float) $order->total;
            if (! $buyers[$key]['last'] || $order->created_at?->gt($buyers[$key]['last'])) {
                $buyers[$key]['last'] = $order->created_at;
                $buyers[$key]['name'] = $order->customer_name ?: $buyers[$key]['name'];
            }
        }
        $rows = collect($buyers);
        $days = (int) ($input['days'] ?? 30);
        if (($input['sort'] ?? 'top') === 'lapsed') {
            $rows = $rows->filter(fn ($b) => $b['last'] && $b['last']->lt(now()->subDays($days)))->sortByDesc('spent');
        } else {
            $rows = $rows->sortByDesc('spent');
        }

        return ['customers_total' => count($buyers), 'customers' => $rows->take($this->limit($input, 15, 30))->map(fn ($b) => [
            'name' => $b['name'], 'orders' => $b['orders'], 'spent' => round($b['spent'], 2),
            'last_order' => $b['last']?->timezone(self::TZ)->toDateString(),
        ])->values()->all()];
    }

    private function appointments(Shop $shop, array $input): array
    {
        if ($shop->catalog_type !== 'cosmetics') {
            throw new \InvalidArgumentException('This shop has no salon bookings.');
        }
        $query = $shop->salonAppointments();
        if (! empty($input['date'])) {
            $day = $this->date($input['date']);
            $query->whereBetween('appointment_at', [$day->copy()->startOfDay()->utc(), $day->copy()->endOfDay()->utc()])->orderBy('appointment_at');
        } elseif (($input['when'] ?? 'upcoming') === 'past') {
            $query->where('appointment_at', '<', now())->orderByDesc('appointment_at');
        } else {
            $query->where('appointment_at', '>=', now()->startOfDay())->orderBy('appointment_at');
        }
        $labels = SalonAppointment::statusLabels();

        return ['appointments' => $query->limit(40)->get()->map(fn ($a) => [
            'id' => $a->id, 'service' => $a->service, 'at' => $a->appointment_at->format('Y-m-d H:i'),
            'status' => $labels[$a->status] ?? $a->status, 'customer' => $a->customer_name, 'notes' => Str::limit((string) $a->notes, 120),
        ])->all()];
    }

    // ── Proposals ────────────────────────────────────────────────────────────

    private function proposeProduct(Shop $shop, User $user, AssistantConversation $conversation, array $input, array &$proposed): array
    {
        $product = Product::where('shop_id', $shop->id)->find((int) ($input['product_id'] ?? 0));
        if (! $product) {
            throw new \InvalidArgumentException('No product with this id in this shop. Use search_products first.');
        }
        $changes = Arr::only($input, ['price', 'discount_price', 'is_active']);
        if ($changes === []) {
            throw new \InvalidArgumentException('Nothing to change.');
        }
        $price = (float) ($changes['price'] ?? $product->price);
        if (isset($changes['discount_price']) && (float) $changes['discount_price'] > 0 && (float) $changes['discount_price'] >= $price) {
            throw new \InvalidArgumentException('The discount price must be lower than the price ('.$price.').');
        }
        $parts = [];
        if (isset($changes['price'])) {
            $parts[] = __('السعر: :from ← :to ₪', ['from' => $this->money($product->price), 'to' => $this->money($changes['price'])]);
        }
        if (isset($changes['discount_price'])) {
            $parts[] = (float) $changes['discount_price'] > 0
                ? __('سعر الخصم: :price ₪', ['price' => $this->money($changes['discount_price'])])
                : __('إلغاء الخصم');
        }
        if (isset($changes['is_active'])) {
            $parts[] = $changes['is_active'] ? __('إظهار المنتج') : __('إخفاء المنتج');
        }

        return $this->propose($shop, $user, $conversation, 'product_change', ['product_id' => $product->id] + $changes,
            $product->name.': '.implode('، ', $parts), $proposed);
    }

    private function proposeOrderStatus(Shop $shop, User $user, AssistantConversation $conversation, array $input, array &$proposed): array
    {
        $order = FrontOrder::where('shop_id', $shop->id)->find((int) ($input['order_id'] ?? 0));
        if (! $order) {
            throw new \InvalidArgumentException('No order with this id in this shop. Use list_orders and pass its id.');
        }
        $restaurant = filled($order->order_type);
        $status = (string) ($input['status'] ?? '');
        if (! in_array($status, $restaurant ? self::RESTAURANT_STATUSES : self::SHOP_STATUSES, true)) {
            throw new \InvalidArgumentException('This status is not allowed for this order. Allowed: '.implode(', ', $restaurant ? self::RESTAURANT_STATUSES : self::SHOP_STATUSES));
        }
        if ($status === $order->status) {
            throw new \InvalidArgumentException('The order is already in this status.');
        }
        if ($restaurant && $status === 'out_for_delivery' && $order->order_type !== 'delivery') {
            throw new \InvalidArgumentException('Only delivery orders can go out for delivery.');
        }
        $payload = ['order_id' => $order->id, 'status' => $status];
        if ($restaurant && $status === 'preparing' && ! $order->estimated_preparation_minutes) {
            if (empty($input['estimated_preparation_minutes'])) {
                throw new \InvalidArgumentException('Ask the owner how many minutes the preparation will take, then propose again with estimated_preparation_minutes.');
            }
            $payload['estimated_preparation_minutes'] = (int) $input['estimated_preparation_minutes'];
        }
        $labels = FrontOrder::statusOptions();
        $summary = __('الطلب :number (:customer): :from ← :to', ['number' => $order->order_number, 'customer' => $order->customer_name ?: '—',
            'from' => $labels[$order->status] ?? $order->status, 'to' => $labels[$status] ?? $status]);

        return $this->propose($shop, $user, $conversation, 'order_status', $payload, $summary, $proposed);
    }

    private function proposeAccepting(Shop $shop, User $user, AssistantConversation $conversation, array $input, array &$proposed): array
    {
        $open = (bool) ($input['open'] ?? true);
        if ($open === (bool) ($shop->is_accepting_orders ?? true)) {
            throw new \InvalidArgumentException($open ? 'The shop is already accepting orders.' : 'Orders are already paused.');
        }

        return $this->propose($shop, $user, $conversation, 'accepting_orders', ['open' => $open],
            $open ? __('استقبال الطلبات من جديد') : __('إيقاف استقبال الطلبات مؤقتاً'), $proposed);
    }

    private function propose(Shop $shop, User $user, AssistantConversation $conversation, string $type, array $payload, string $summary, array &$proposed): array
    {
        $action = AssistantAction::create([
            'assistant_conversation_id' => $conversation->id, 'shop_id' => $shop->id, 'user_id' => $user->id,
            'type' => $type, 'payload' => $payload, 'summary' => Str::limit($summary, 490), 'status' => AssistantAction::PENDING,
        ]);
        $proposed[] = $action->id;

        return ['proposed' => true, 'summary' => $action->summary,
            'note' => 'Not done yet. A confirm button is shown under your reply; tell the owner to press it. Do not say it is done.'];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function product(Product $p): array
    {
        return [
            'id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price,
            'discount_price' => $p->discount_price !== null && (float) $p->discount_price > 0 ? (float) $p->discount_price : null,
            'active' => (bool) $p->is_active, 'category' => $p->category?->name,
            'views' => (int) $p->views, 'has_image' => filled($p->main_image),
            'description' => Str::limit(strip_tags((string) $p->description), 200),
        ];
    }

    private function ordersOf(Shop $shop)
    {
        $query = FrontOrder::query()->where('shop_id', $shop->id);

        return $shop->catalog_type === 'restaurant'
            ? $query->whereNotNull('order_type')
            : $query->whereNull('order_type')->where('order_number', 'not like', 'ORD-%');
    }

    /** @return array{0: Carbon, 1: Carbon} local start and end of the requested days */
    private function period(array $input, Carbon $defaultFrom): array
    {
        $from = ! empty($input['from']) ? $this->date($input['from']) : $defaultFrom;
        $to = ! empty($input['to']) ? $this->date($input['to']) : now(self::TZ);
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            throw new \InvalidArgumentException('The period may be at most one year.');
        }

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    private function date(string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value, self::TZ);
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Dates must be YYYY-MM-DD.');
        }
    }

    private function limit(array $input, int $default, int $max): int
    {
        return max(1, min($max, (int) ($input['limit'] ?? $default)));
    }

    private function money($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
