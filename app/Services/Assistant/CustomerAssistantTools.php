<?php

namespace App\Services\Assistant;

use App\Http\Controllers\Api\V1\Presenter;
use App\Models\{FrontOrder, Product, SalonAppointment, Shop, User};
use App\Services\LoyaltyPoints;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * What the customer assistant can look at: public shops and products, and the signed-in customer's own orders,
 * points and bookings. It never sees other customers. Cards it chooses to show are collected in $cards.
 */
class CustomerAssistantTools
{
    public const TZ = 'Asia/Hebron';
    private const TYPES = ['restaurant', 'sweets', 'clothing', 'shoes', 'cosmetics', 'electronics', 'furniture_appliances', 'general',
        'advertising_services', 'real_estate', 'dental', 'krembo'];

    public function __construct(private LoyaltyPoints $loyalty) {}

    public function definitions(?Shop $shop): array
    {
        $type = ['type' => 'string', 'enum' => self::TYPES, 'description' => 'kind of shop'];

        return [
            ['name' => 'search_products', 'description' => 'Find products in shops on Ozman by keywords (Arabic, Hebrew or English names). Short keywords work best, e.g. "برغر" or "فستان". Filter by shop, kind of shop, price, or only discounted items. Returns id, name, price, discount price, shop, whether the shop is open now.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'query' => ['type' => 'string'],
                    'shop_id' => ['type' => 'integer'],
                    'shop_type' => $type,
                    'max_price' => ['type' => 'number', 'minimum' => 0],
                    'min_price' => ['type' => 'number', 'minimum' => 0],
                    'on_offer' => ['type' => 'boolean', 'description' => 'only products with a discount'],
                    'sort' => ['type' => 'string', 'enum' => ['relevance', 'price_asc', 'price_desc', 'popular']],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
                ]]],
            ['name' => 'product_details', 'description' => 'Full details of one product: description, sizes, colors, meal options, specs, shop.',
                'input_schema' => ['type' => 'object', 'required' => ['product_id'], 'properties' => ['product_id' => ['type' => 'integer']]]],
            ['name' => 'search_shops', 'description' => 'Find shops on Ozman by name, kind or city. Returns id, name, kind, city, hours, open now, accepting orders.',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'query' => ['type' => 'string'],
                    'shop_type' => $type,
                    'city' => ['type' => 'string'],
                    'open_now' => ['type' => 'boolean'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
                ]]],
            ['name' => 'shop_details', 'description' => 'One shop: description, address, city, hours, open now, accepting orders, payment method, WhatsApp, categories, whether it gives loyalty points.',
                'input_schema' => ['type' => 'object', 'required' => ['shop_id'], 'properties' => ['shop_id' => ['type' => 'integer']]]],
            ['name' => 'my_orders', 'description' => 'The signed-in customer\'s own recent orders with status, items, total, delivery time and driver.',
                'input_schema' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10]]]],
            ['name' => 'my_points', 'description' => 'The signed-in customer\'s loyalty points: balance, pending, per shop, and how points are earned.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []]],
            ['name' => 'my_appointments', 'description' => 'The signed-in customer\'s salon bookings.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []]],
            ['name' => 'show_products', 'description' => 'Show product cards under your answer (photo, price, open button, add to basket). Use it whenever you recommend products. Up to 6 ids from search results.',
                'input_schema' => ['type' => 'object', 'required' => ['product_ids'], 'properties' => ['product_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'maxItems' => 6]]]],
            ['name' => 'show_shops', 'description' => 'Show shop cards under your answer (logo, open now, open button). Up to 6 ids from search results.',
                'input_schema' => ['type' => 'object', 'required' => ['shop_ids'], 'properties' => ['shop_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'maxItems' => 6]]]],
        ];
    }

    /** @param  array{products: int[], shops: int[]}  $cards */
    public function run(string $name, array $input, ?User $user, array &$cards): array
    {
        try {
            return match ($name) {
                'search_products' => $this->searchProducts($input),
                'product_details' => $this->productDetails((int) ($input['product_id'] ?? 0)),
                'search_shops' => $this->searchShops($input),
                'shop_details' => $this->shopDetails((int) ($input['shop_id'] ?? 0)),
                'my_orders' => $this->myOrders($user, $input),
                'my_points' => $this->myPoints($user),
                'my_appointments' => $this->myAppointments($user),
                'show_products' => $this->show('products', $input['product_ids'] ?? [], $cards),
                'show_shops' => $this->show('shops', $input['shop_ids'] ?? [], $cards),
                default => ['error' => 'unknown tool'],
            };
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // ── Public catalogue ─────────────────────────────────────────────────────

    private function products(): Builder
    {
        return Product::query()->where('products.is_active', true)
            ->whereHas('shop', fn ($q) => $q->where('is_active', true));
    }

    private function searchProducts(array $input): array
    {
        $query = $this->products()->with('shop', 'category:id,name');
        $words = $this->words($input['query'] ?? '');
        if ($words) {
            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('name', 'like', $word)->orWhere('description', 'like', $word)
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $word));
                }
            });
        }
        if (! empty($input['shop_id'])) {
            $query->where('shop_id', $input['shop_id']);
        }
        if (! empty($input['shop_type'])) {
            $query->whereHas('shop', fn ($q) => $q->where('catalog_type', $input['shop_type']));
        }
        $effective = 'CASE WHEN discount_price IS NOT NULL AND discount_price > 0 AND discount_price < price THEN discount_price ELSE price END';
        if (isset($input['max_price'])) {
            $query->whereRaw("$effective <= CAST(? AS DECIMAL(10,2))", [(float) $input['max_price']]);
        }
        if (isset($input['min_price'])) {
            $query->whereRaw("$effective >= CAST(? AS DECIMAL(10,2))", [(float) $input['min_price']]);
        }
        if (! empty($input['on_offer'])) {
            $query->whereNotNull('discount_price')->where('discount_price', '>', 0)->whereColumn('discount_price', '<', 'price');
        }
        match ($input['sort'] ?? 'relevance') {
            'price_asc' => $query->orderByRaw("$effective asc"),
            'price_desc' => $query->orderByRaw("$effective desc"),
            default => $query->orderByDesc('is_featured')->orderByDesc('views'),
        };
        $limit = max(1, min(20, (int) ($input['limit'] ?? 10)));
        $total = (clone $query)->count();
        $products = $query->limit($limit)->get();
        // Rank by how many keywords each product matches, so "برغر لحم" puts beef burgers first.
        if (count($words) > 1) {
            $products = $products->sortByDesc(fn ($p) => collect($words)->filter(fn ($w) => Str::contains(mb_strtolower($p->name.' '.$p->description), mb_strtolower(trim($w, '%'))))->count())->values();
        }

        return ['count' => $total, 'products' => $products->map(fn (Product $p) => $this->productRow($p))->all(),
            'note' => $total === 0 ? 'Nothing matched. Try a shorter or different word (other spelling, singular), or search shops instead.' : null];
    }

    private function productDetails(int $id): array
    {
        $product = $this->products()->with('shop', 'category:id,name', 'variants')->find($id);
        if (! $product) {
            throw new \InvalidArgumentException('No active product with this id.');
        }
        $full = Presenter::product($product, true);

        return $this->productRow($product) + [
            'description' => Str::limit(strip_tags((string) ($full['description'] ?? '')), 600),
            'sizes' => $full['sizes'] ?? [],
            'colors' => collect($full['colors'] ?? [])->map(fn ($c) => is_array($c) ? ($c['name'] ?? $c['value'] ?? null) : $c)->filter()->values()->all(),
            'meal_options' => $full['meal'] ?? null,
            'specs' => collect($product->catalog_attributes ?? [])->filter(fn ($v, $k) => is_scalar($v) && filled($v) && ! str_starts_with((string) $k, 'meal_'))->take(15)->all(),
            'variants' => collect($full['variants'] ?? [])->map(fn ($v) => array_filter(['size' => $v['size'], 'color' => $v['color_name'] ?? $v['color'], 'storage' => $v['storage'], 'price' => $v['price']]))->take(15)->values()->all(),
        ];
    }

    private function searchShops(array $input): array
    {
        $query = Shop::query()->where('is_active', true);
        $words = $this->words($input['query'] ?? '');
        if ($words) {
            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('name', 'like', $word)->orWhere('description', 'like', $word);
                }
            });
        }
        if (! empty($input['shop_type'])) {
            $query->where('catalog_type', $input['shop_type']);
        }
        if (filled($input['city'] ?? null)) {
            $query->where('city', 'like', '%'.$this->escape($input['city']).'%');
        }
        $shops = $query->withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderByDesc('products_count')->limit(60)->get();
        if (! empty($input['open_now'])) {
            $shops = $shops->filter(fn (Shop $s) => $this->openNow($s) !== false && ($s->is_accepting_orders ?? true));
        }
        $limit = max(1, min(20, (int) ($input['limit'] ?? 10)));

        return ['count' => $shops->count(), 'shops' => $shops->take($limit)->map(fn (Shop $s) => $this->shopRow($s) + ['products' => $s->products_count])->values()->all()];
    }

    private function shopDetails(int $id): array
    {
        $shop = Shop::where('is_active', true)->find($id);
        if (! $shop) {
            throw new \InvalidArgumentException('No active shop with this id.');
        }

        return $this->shopRow($shop) + [
            'description' => Str::limit(strip_tags((string) $shop->description), 500),
            'address' => $shop->address,
            'payment' => $shop->payment_method,
            'whatsapp' => preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $shop->phone)) ?: null,
            'loyalty_points' => $this->loyalty->participates($shop),
            'categories' => $shop->categories()->where('is_active', true)->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('name')->limit(30)->get()->map(fn ($c) => ['name' => $c->name, 'products' => $c->products_count])->all(),
            'offers' => $this->products()->where('shop_id', $shop->id)->whereNotNull('discount_price')->where('discount_price', '>', 0)
                ->whereColumn('discount_price', '<', 'price')->limit(5)->get()->map(fn ($p) => $this->productRow($p, false))->all(),
        ];
    }

    // ── The customer's own data ──────────────────────────────────────────────

    private function needUser(?User $user): User
    {
        if (! $user) {
            throw new \InvalidArgumentException('The customer is not signed in. Ask them to sign in to see their orders, points or bookings.');
        }

        return $user;
    }

    private function myOrders(?User $user, array $input): array
    {
        $user = $this->needUser($user);
        $labels = FrontOrder::statusOptions();
        $orders = FrontOrder::with('shop:id,name,catalog_type,phone,whatsapp', 'restaurantDriver.user:id,name')
            ->where('user_id', $user->id)->latest('id')->limit(max(1, min(10, (int) ($input['limit'] ?? 5))))->get();

        return ['orders' => $orders->map(fn (FrontOrder $o) => [
            'number' => $o->order_number,
            'shop' => $o->shop?->name,
            'shop_whatsapp' => preg_replace('/\D+/', '', (string) ($o->shop?->whatsapp ?: $o->shop?->phone)) ?: null,
            'status' => $labels[$o->status] ?? $o->status,
            'type' => $o->order_type,
            'total' => (float) $o->total,
            'items' => collect($o->items ?? [])->map(fn ($i) => ($i['qty'] ?? 1).'× '.($i['name'] ?? ''))->implode('، '),
            'placed_at' => $o->created_at?->timezone(self::TZ)->format('Y-m-d H:i'),
            'preparation_minutes' => $o->estimated_preparation_minutes,
            'ready_by' => $o->preparationDueAt()?->timezone(self::TZ)->format('H:i'),
            'delivery_by' => $o->status === 'out_for_delivery' ? $o->estimated_delivery_at?->timezone(self::TZ)->format('H:i') : null,
            'driver' => in_array($o->status, ['ready', 'out_for_delivery'], true) ? $o->restaurantDriver?->user?->name : null,
        ])->all(), 'note' => $orders->isEmpty() ? 'This account has no orders. Orders placed as a guest are not linked to an account.' : null];
    }

    private function myPoints(?User $user): array
    {
        $user = $this->needUser($user);
        $settings = $this->loyalty->settings();
        if (! $settings['enabled'] || ! $user->earnsPoints()) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'rule' => "{$settings['points']} points for every {$settings['amount']} ₪ in participating shops",
            'balance' => $this->loyalty->balance($user),
            'pending' => $this->loyalty->pendingPoints($user),
            'by_shop' => $this->loyalty->shopBalances($user)->filter(fn ($r) => $r['balance'] > 0)->map(fn ($r) => ['shop' => $r['shop_name'] ?? 'Ozman', 'balance' => $r['balance']])->values()->all(),
        ];
    }

    private function myAppointments(?User $user): array
    {
        $user = $this->needUser($user);
        $labels = SalonAppointment::statusLabels();

        return ['appointments' => SalonAppointment::with('shop:id,name')->where('user_id', $user->id)->orderByDesc('appointment_at')->limit(10)->get()
            ->map(fn ($a) => ['shop' => $a->shop?->name, 'service' => $a->service, 'at' => $a->appointment_at->format('Y-m-d H:i'),
                'status' => $labels[$a->status] ?? $a->status, 'past' => $a->appointment_at->isPast()])->all()];
    }

    // ── Cards ────────────────────────────────────────────────────────────────

    private function show(string $kind, array $ids, array &$cards): array
    {
        $ids = array_values(array_unique(array_map('intval', array_slice((array) $ids, 0, 6))));
        $valid = $kind === 'products'
            ? $this->products()->whereIn('id', $ids)->pluck('id')->all()
            : Shop::where('is_active', true)->whereIn('id', $ids)->pluck('id')->all();
        $cards[$kind] = array_values(array_unique([...($cards[$kind] ?? []), ...array_values(array_intersect($ids, $valid))]));
        $cards[$kind] = array_slice($cards[$kind], 0, 6);

        return ['shown' => count($cards[$kind]), 'note' => 'The cards will appear under your reply. Now write the reply itself: a short friendly line, then each suggestion with its price and shop (one bullet each). Don\'t talk about the cards, buttons or links.'];
    }

    /** Cards for the client, in the order the assistant chose. */
    public static function cardsFor(?array $cards): array
    {
        $products = collect();
        if (! empty($cards['products'])) {
            $found = Product::with('shop')->whereIn('id', $cards['products'])->where('is_active', true)->withCount(['variants as active_variants_count' => fn ($q) => $q->where('is_active', true)])->get()->keyBy('id');
            $products = collect($cards['products'])->map(fn ($id) => $found[$id] ?? null)->filter()
                ->map(fn (Product $p) => Presenter::product($p) + ['shop' => $p->shop ? ['id' => $p->shop->id, 'name' => $p->shop->name, 'type' => $p->shop->catalog_type] : null,
                    'url' => route('share.product', $p)])->values();
        }
        $shops = collect();
        if (! empty($cards['shops'])) {
            $found = Shop::whereIn('id', $cards['shops'])->where('is_active', true)->get()->keyBy('id');
            $shops = collect($cards['shops'])->map(fn ($id) => $found[$id] ?? null)->filter()->map(fn (Shop $s) => Presenter::shop($s))->values();
        }

        return ['products' => $products->all(), 'shops' => $shops->all()];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function productRow(Product $p, bool $withShop = true): array
    {
        $price = (float) $p->price;
        $discount = $p->discount_price !== null && (float) $p->discount_price > 0 && (float) $p->discount_price < $price ? (float) $p->discount_price : null;

        return array_filter([
            'id' => $p->id, 'name' => $p->localized('name'), 'price' => $price, 'discount_price' => $discount,
            'category' => $p->category?->name,
            'shop' => $withShop && $p->shop ? ['id' => $p->shop->id, 'name' => $p->shop->name, 'type' => $p->shop->catalog_type, 'city' => $p->shop->city,
                'open_now' => $this->openNow($p->shop), 'accepting_orders' => (bool) ($p->shop->is_accepting_orders ?? true)] : null,
        ], fn ($v) => $v !== null);
    }

    private function shopRow(Shop $s): array
    {
        return [
            'id' => $s->id, 'name' => $s->name, 'type' => $s->catalog_type, 'city' => $s->city,
            'hours' => $s->open_time && $s->close_time ? substr($s->open_time, 0, 5).'–'.substr($s->close_time, 0, 5) : null,
            'open_now' => $this->openNow($s), 'accepting_orders' => (bool) ($s->is_accepting_orders ?? true),
        ];
    }

    /** True/false from the shop's hours (local time), null when the hours are not set. Handles hours past midnight. */
    private function openNow(Shop $s): ?bool
    {
        if (! $s->open_time || ! $s->close_time) {
            return null;
        }
        $now = now(self::TZ)->format('H:i');
        $open = substr($s->open_time, 0, 5);
        $close = substr($s->close_time, 0, 5);

        return $open <= $close ? ($now >= $open && $now < $close) : ($now >= $open || $now < $close);
    }

    /** LIKE patterns for the meaningful words of a search. */
    private function words(string $query): array
    {
        $words = preg_split('/[\s,،.]+/u', mb_strtolower(trim($query))) ?: [];
        $words = array_filter($words, fn ($w) => mb_strlen($w) >= 2 && ! in_array($w, ['بدي', 'في', 'من', 'على', 'عن', 'the', 'a', 'and', 'or', 'و'], true));

        return array_map(fn ($w) => '%'.$this->escape($w).'%', array_slice(array_values($words), 0, 6));
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
