<?php

namespace App\Services\Assistant;

use App\Http\Controllers\Api\V1\Presenter;
use App\Http\Controllers\SalonAppointmentController;
use App\Models\{AssistantAction, AssistantConversation, FrontOrder, Product, SalonAppointment, Shop, User};
use App\Services\CouponService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
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

    /**
     * Places in the app that Fulla the robot can fly to and point at, with what each one is.
     * The app marks the same names on its buttons.
     */
    public const SPOTS = [
        'nav.home' => 'bottom bar: home tab',
        'nav.shops' => 'bottom bar: shops tab (all shops, by kind)',
        'nav.cart' => 'bottom bar: basket tab',
        'nav.more' => 'bottom bar: "more" (free market, promoters, raffle cards)',
        'nav.account' => 'bottom bar: account tab (profile, orders, points, favourites, language, sign in)',
        'home.search' => 'home: search button (shops and products, also by voice)',
        'home.qr' => 'home: scan a restaurant table QR to order to the table',
        'home.order' => 'home: the card of the customer\'s order on its way (tap to follow it)',
        'home.offers' => 'home: today\'s offers',
        'account.points' => 'account tab: "my points"',
        'product.add' => 'product page: the "add to basket" button',
        'cart.checkout' => 'basket tab: the "continue" button that goes to sending the order',
        'checkout.send' => 'order page: the "send order" button',
    ];

    /** The question was spoken to Fulla the robot in the app: she adds to the basket and opens pages herself. */
    public bool $robot = false;

    /** Pages the robot can open on the customer's screen. */
    public const PAGES = ['product', 'shop', 'basket', 'orders', 'points'];

    public function definitions(?Shop $shop, bool $robot = false): array
    {
        $type = ['type' => 'string', 'enum' => self::TYPES, 'description' => 'kind of shop'];
        $guide = $robot ? [['name' => 'guide_to', 'description' => 'Fly the robot to buttons in the app, one step after another, pointing at each and saying a short line, so the customer sees where to tap. Use it when they ask how or where to do something in the app. Steps run in order; each next step waits until the customer tapped and that button is on screen. Spots: '
            .collect(self::SPOTS)->map(fn ($what, $spot) => "$spot = $what")->implode('; ').'.',
            'input_schema' => ['type' => 'object', 'required' => ['steps'], 'properties' => [
                'steps' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 5, 'items' => ['type' => 'object', 'required' => ['spot', 'say'], 'properties' => [
                    'spot' => ['type' => 'string', 'enum' => array_keys(self::SPOTS)],
                    'say' => ['type' => 'string', 'maxLength' => 90, 'description' => 'a few words shown next to the button, in the customer\'s language, e.g. "اكبس هون عشان تفتح السلة"'],
                ]]],
            ]]],
            ['name' => 'open_page', 'description' => 'Open a page on the customer\'s screen right away: a product (its photos, price, options), a shop, their basket, their orders or their points. Use it when they say show me / open it / let me see it, or when seeing it is the natural next step. product and shop need the id from search results.',
                'input_schema' => ['type' => 'object', 'required' => ['page'], 'properties' => [
                    'page' => ['type' => 'string', 'enum' => self::PAGES],
                    'id' => ['type' => 'integer', 'description' => 'the product or shop id'],
                ]]]] : [];

        return [...$guide,
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
            ['name' => 'my_usuals', 'description' => 'What the signed-in customer usually orders, from their own past orders: for each shop, the items with how many times they ordered each, the usual quantity and option, and their last order there. Use it for "my usual", "like last time", "the same as Friday".',
                'input_schema' => ['type' => 'object', 'properties' => ['shop_id' => ['type' => 'integer', 'description' => 'only this shop']]]],
            ['name' => 'propose_basket', 'description' => 'Prepare a basket for the signed-in customer: shows a "put in basket" button under your reply with these items, which may come from several shops (each shop gets its own order and delivers it itself). Nothing is ordered: they press the button, check the basket and send it themselves. Give either order_number (to repeat a past order of theirs) or items (product ids from my_usuals or search results, with quantity and the option they had).',
                'input_schema' => ['type' => 'object', 'properties' => [
                    'order_number' => ['type' => 'string', 'description' => 'one of the customer\'s own order numbers, to repeat it'],
                    'items' => ['type' => 'array', 'maxItems' => 15, 'items' => ['type' => 'object', 'required' => ['product_id'], 'properties' => [
                        'product_id' => ['type' => 'integer'],
                        'qty' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
                        'variant_id' => ['type' => 'integer'],
                        'size' => ['type' => 'string'],
                        'color' => ['type' => 'string'],
                    ]]],
                ]]],
            ['name' => 'my_points', 'description' => 'The signed-in customer\'s loyalty points: balance, pending, per shop, and how points are earned.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []]],
            ['name' => 'my_appointments', 'description' => 'The signed-in customer\'s salon bookings.',
                'input_schema' => ['type' => 'object', 'properties' => (object) []]],
            ['name' => 'check_coupon', 'description' => 'Check a discount code the customer has for a shop: whether it works now, what it gives (percent or shekels), the minimum order, and the saving on a basket total if given. Codes are private to each shop: only check a code the customer typed; never list or guess codes.',
                'input_schema' => ['type' => 'object', 'required' => ['shop_id', 'code'], 'properties' => [
                    'shop_id' => ['type' => 'integer'],
                    'code' => ['type' => 'string'],
                    'basket_total' => ['type' => 'number', 'minimum' => 0, 'description' => 'the basket total in shekels, if known'],
                ]]],
            ['name' => 'salon_availability', 'description' => 'For a salon (a cosmetics shop that takes bookings): its services and the times still free on a local date (YYYY-MM-DD, today or later).',
                'input_schema' => ['type' => 'object', 'required' => ['shop_id', 'date'], 'properties' => [
                    'shop_id' => ['type' => 'integer'],
                    'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD (local date)'],
                ]]],
            ['name' => 'propose_booking', 'description' => 'Prepare a salon booking for the signed-in customer, with a service and a free time from salon_availability. It only shows a "book" button under your reply; the booking is made when the customer presses it, with the name and phone on their account. Only after the customer chose the salon, service, day and time.',
                'input_schema' => ['type' => 'object', 'required' => ['shop_id', 'service', 'date', 'time'], 'properties' => [
                    'shop_id' => ['type' => 'integer'],
                    'service' => ['type' => 'string'],
                    'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD (local date)'],
                    'time' => ['type' => 'string', 'description' => 'HH:MM, one of the free times'],
                    'notes' => ['type' => 'string', 'maxLength' => 500],
                ]]],
            ['name' => 'show_products', 'description' => 'Show product cards under your answer (photo, price, open button, add to basket). Use it whenever you recommend products. Up to 6 ids from search results.',
                'input_schema' => ['type' => 'object', 'required' => ['product_ids'], 'properties' => ['product_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'maxItems' => 6]]]],
            ['name' => 'show_shops', 'description' => 'Show shop cards under your answer (logo, open now, open button). Up to 6 ids from search results.',
                'input_schema' => ['type' => 'object', 'required' => ['shop_ids'], 'properties' => ['shop_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'maxItems' => 6]]]],
        ];
    }

    /** A page the robot opens on the customer's screen: a known page, and a product or shop that is really on sale. */
    private function openPage(array $input, array &$cards): array
    {
        $page = (string) ($input['page'] ?? '');
        $id = (int) ($input['id'] ?? 0);
        if (! in_array($page, self::PAGES, true)) {
            throw new \InvalidArgumentException('Unknown page. Use one of: '.implode(', ', self::PAGES));
        }
        if ($page === 'product' && ! $this->products()->whereKey($id)->exists()) {
            throw new \InvalidArgumentException('No product with this id on sale.');
        }
        if ($page === 'shop' && ! Shop::where('is_active', true)->whereKey($id)->exists()) {
            throw new \InvalidArgumentException('No shop with this id.');
        }
        $cards['open'] = ['page' => $page, 'id' => in_array($page, ['product', 'shop'], true) ? $id : null];

        return ['opened' => $page, 'note' => 'It opens on their screen as your reply is read. Say it in a few words ("هيو قدامك").'];
    }

    /** The robot's steps: known spots only, short lines, at most five. */
    private function guide(mixed $steps, array &$cards): array
    {
        $clean = collect(is_array($steps) ? $steps : [])
            ->filter(fn ($step) => is_array($step) && isset(self::SPOTS[$step['spot'] ?? '']))
            ->map(fn ($step) => ['spot' => $step['spot'], 'say' => mb_substr(trim((string) ($step['say'] ?? '')), 0, 90)])
            ->take(5)->values()->all();
        if (! $clean) {
            throw new \InvalidArgumentException('No known spots. Use one of: '.implode(', ', array_keys(self::SPOTS)));
        }
        $cards['guide'] = $clean;

        return ['shown' => count($clean), 'note' => 'The robot will fly to these buttons after your reply is read. Mention it in a few words ("خليني أوريك"), don\'t describe the buttons again.'];
    }

    /** @param  array{products: int[], shops: int[], actions?: int[]}  $cards */
    public function run(string $name, array $input, ?User $user, array &$cards, ?AssistantConversation $conversation = null): array
    {
        try {
            return match ($name) {
                'search_products' => $this->searchProducts($input),
                'product_details' => $this->productDetails((int) ($input['product_id'] ?? 0)),
                'search_shops' => $this->searchShops($input),
                'shop_details' => $this->shopDetails((int) ($input['shop_id'] ?? 0)),
                'my_orders' => $this->myOrders($user, $input),
                'my_usuals' => $this->myUsuals($user, $input),
                'propose_basket' => $this->proposeBasket($input, $user, $cards),
                'my_points' => $this->myPoints($user),
                'my_appointments' => $this->myAppointments($user),
                'check_coupon' => $this->checkCoupon($input, $user),
                'salon_availability' => $this->salonAvailability($input),
                'propose_booking' => $this->proposeBooking($input, $user, $conversation, $cards),
                'show_products' => $this->show('products', $input['product_ids'] ?? [], $cards),
                'show_shops' => $this->show('shops', $input['shop_ids'] ?? [], $cards),
                'guide_to' => $this->guide($input['steps'] ?? [], $cards),
                'open_page' => $this->openPage($input, $cards),
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
        // Only a plain "is there any X?" counts as lost demand, not a search narrowed by price or offers.
        if ($total === 0 && filled($input['query'] ?? null) && ! isset($input['max_price']) && ! isset($input['min_price']) && empty($input['on_offer'])) {
            app(\App\Services\Demand\LostDemand::class)->record((string) $input['query'], 'assistant',
                ! empty($input['shop_id']) ? Shop::find($input['shop_id']) : null, $input['shop_type'] ?? null);
        }
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

    /** The customer's own habits, per shop: their memory of "the usual". Only from their own orders. */
    private function myUsuals(?User $user, array $input): array
    {
        $user = $this->needUser($user);
        $orders = FrontOrder::with('shop:id,name,catalog_type,is_active')->where('user_id', $user->id)->where('status', '!=', 'cancelled')
            ->when(! empty($input['shop_id']), fn ($q) => $q->where('shop_id', (int) $input['shop_id']))
            ->where('created_at', '>=', now()->subDays(180))->latest('id')->limit(40)->get()
            ->filter(fn (FrontOrder $o) => $o->shop?->is_active);
        if ($orders->isEmpty()) {
            return ['shops' => [], 'note' => 'No orders on this account in the last 6 months. Orders placed as a guest are not linked to an account.'];
        }
        $active = Product::where('is_active', true)->whereIn('id', $orders->flatMap(fn ($o) => collect($o->items ?? [])->pluck('product_id'))->filter()->unique())
            ->pluck('id')->flip();

        $shops = $orders->groupBy('shop_id')->take(6)->map(function ($shopOrders) use ($active) {
            $items = [];
            // Newest first, so the usual quantity and option are the latest ones.
            foreach ($shopOrders as $order) {
                foreach ($order->items ?? [] as $item) {
                    $id = (int) ($item['product_id'] ?? 0);
                    $custom = ! empty($item['addons']) || ! empty($item['choices']) || ! empty($item['excluded']);
                    $key = $id.'|'.($item['variant_id'] ?? '').'|'.($item['size'] ?? '').'|'.($item['color'] ?? '').'|'.($custom ? 'custom' : '');
                    $items[$key] ??= array_filter([
                        'product_id' => $id ?: null, 'name' => $item['name'] ?? null, 'times' => 0, 'usual_qty' => max(1, (int) ($item['qty'] ?? 1)),
                        'variant_id' => $item['variant_id'] ?? null, 'size' => $item['size'] ?? null, 'color' => $item['color'] ?? null,
                        'still_sold' => $id && isset($active[$id]),
                        'note' => $custom ? 'had extras or meal choices: the customer picks them again on the product' : null,
                    ], fn ($v) => $v !== null && $v !== '');
                    $items[$key]['times']++;
                }
            }
            $last = $shopOrders->first();

            return [
                'shop' => ['id' => $last->shop->id, 'name' => $last->shop->name, 'type' => $last->shop->catalog_type],
                'orders' => $shopOrders->count(),
                'items' => collect($items)->sortByDesc('times')->take(8)->values()->all(),
                'last_order' => ['number' => $last->order_number, 'placed_at' => $last->created_at?->timezone(self::TZ)->format('Y-m-d H:i'),
                    'items' => collect($last->items ?? [])->map(fn ($i) => ($i['qty'] ?? 1).'× '.($i['name'] ?? ''))->implode('، ')],
            ];
        })->values()->all();

        return ['shops' => $shops];
    }

    /** A "put in basket" button with these lines. It only fills the basket in the app; the customer sends the orders. */
    private function proposeBasket(array $input, ?User $user, array &$cards): array
    {
        $user = $this->needUser($user);
        if (! empty($input['order_number'])) {
            $order = FrontOrder::where('user_id', $user->id)->where('order_number', (string) $input['order_number'])->first();
            if (! $order) {
                return ['error' => 'No order with this number on this account.'];
            }
            $items = $order->items ?? [];
        } else {
            $items = collect((array) ($input['items'] ?? []))->take(15)->filter(fn ($item) => is_array($item) && ! empty($item['product_id']))
                ->map(fn ($item) => array_intersect_key($item, array_flip(['product_id', 'qty', 'variant_id', 'size', 'color'])))->values()->all();
        }
        if (empty($items)) {
            return ['error' => 'Give order_number or items.'];
        }
        ['lines' => $lines, 'skipped' => $skipped] = \App\Services\Basket\BasketLines::from($items);
        if (empty($lines)) {
            return ['error' => 'None of these can be put in the basket now.', 'skipped' => $skipped];
        }
        // Kept as the item specs; the card is built fresh (prices, stock) each time the message is shown.
        // Several calls in one reply (an old order, then another shop's item) add up to one basket.
        $new = collect($lines)->map(fn ($line) => array_intersect_key($line, array_flip(['qty', 'variant_id', 'size', 'color'])) + ['product_id' => $line['product']['id']]);
        $cards['basket'] = [
            'items' => collect($cards['basket']['items'] ?? [])->concat($new)
                ->unique(fn ($item) => $item['product_id'].'|'.($item['variant_id'] ?? '').'|'.($item['size'] ?? '').'|'.($item['color'] ?? ''))
                ->take(20)->values()->all(),
            'skipped' => array_values(array_unique([...($cards['basket']['skipped'] ?? []), ...$skipped])),
        ];
        $shops = collect($lines)->groupBy(fn ($line) => $line['product']['shop_id'] ?? 0);

        return [
            'ready' => collect($lines)->map(fn ($line) => ['name' => $line['product']['name'], 'qty' => $line['qty'], 'price' => $line['product']['final_price'] ?? null,
                'shop_id' => $line['product']['shop_id'] ?? null] + array_intersect_key($line, array_flip(['size', 'color'])))->all(),
            'shops' => $shops->count(),
            'skipped' => $skipped,
            'note' => ($this->robot
                ? 'The app puts these in the customer\'s basket right away, no button. Say in a few words that you added them (qty × name). Nothing is ordered yet: they send the order from the basket. '
                : 'A button to put these in the basket appears under your reply (mention "the button under the message", don\'t quote its label). Nothing is ordered yet: they press it, check the basket and send it. ')
                .($shops->count() > 1 ? 'Each shop gets its own order and delivers it itself. ' : '')
                .($this->robot ? 'Mention anything skipped with why.' : 'Write a short reply listing what is ready (qty × name, price), and anything skipped with why (they pick it again on the product). Don\'t say you ordered or added anything.'),
        ];
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

    // ── Coupons and bookings ─────────────────────────────────────────────────

    private function checkCoupon(array $input, ?User $user): array
    {
        $shop = Shop::where('is_active', true)->find((int) ($input['shop_id'] ?? 0));
        if (! $shop) {
            throw new \InvalidArgumentException('No such shop.');
        }
        $coupons = app(CouponService::class);
        try {
            $coupon = $coupons->find($shop, (string) ($input['code'] ?? ''), $user);
            $saving = isset($input['basket_total']) ? $coupons->discount($coupon, (float) $input['basket_total']) : null;
        } catch (ValidationException $e) {
            return ['works' => false, 'reason' => collect($e->errors())->flatten()->first()];
        }

        return ['works' => true, 'shop' => $shop->name] + \App\Http\Controllers\WebCouponController::couponSummary($coupon)
            + ['ends' => $coupon->ends_at?->timezone(self::TZ)->toDateString(), 'saving' => $saving,
                'how' => 'The customer types the code in the basket before sending the order.'];
    }

    private function salon(int $id): Shop
    {
        $shop = Shop::where('is_active', true)->where('catalog_type', 'cosmetics')->find($id);
        if (! $shop) {
            throw new \InvalidArgumentException('That shop does not take salon bookings.');
        }

        return $shop;
    }

    /** Free times on a local day: the salon's slots that aren't taken and haven't passed. */
    private function freeTimes(Shop $shop, string $date): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < now(self::TZ)->toDateString()) {
            throw new \InvalidArgumentException('date must be YYYY-MM-DD, today or later.');
        }
        $taken = $shop->salonAppointments()->whereDate('appointment_at', $date)
            ->whereIn('status', [SalonAppointment::STATUS_NEW, SalonAppointment::STATUS_CONFIRMED])
            ->get()->map(fn ($a) => $a->appointment_at->format('H:i'))->all();
        $now = now(self::TZ)->format('Y-m-d H:i');

        return array_values(array_filter(SalonAppointmentController::TIMES, fn ($t) => ! in_array($t, $taken, true) && "$date $t" > $now));
    }

    private function salonAvailability(array $input): array
    {
        $shop = $this->salon((int) ($input['shop_id'] ?? 0));
        $date = (string) ($input['date'] ?? '');

        return ['shop' => $shop->name, 'date' => $date, 'services' => SalonAppointmentController::SERVICES, 'free_times' => $this->freeTimes($shop, $date)];
    }

    private function proposeBooking(array $input, ?User $user, ?AssistantConversation $conversation, array &$cards): array
    {
        $user = $this->needUser($user);
        if (! $conversation) {
            throw new \InvalidArgumentException('Booking is not available here.');
        }
        if (blank($user->phone)) {
            return ['error' => 'The account has no phone number. Ask the customer to add one in their account, or to book from the salon page.'];
        }
        $shop = $this->salon((int) ($input['shop_id'] ?? 0));
        $date = (string) ($input['date'] ?? '');
        $time = (string) ($input['time'] ?? '');
        $service = trim((string) ($input['service'] ?? ''));
        if ($service === '' || mb_strlen($service) > 120) {
            throw new \InvalidArgumentException('Give the service, e.g. one from salon_availability.');
        }
        if (! in_array($time, $this->freeTimes($shop, $date), true)) {
            throw new \InvalidArgumentException('That time is not free. Check salon_availability again.');
        }
        $notes = trim((string) ($input['notes'] ?? ''));
        $day = Carbon::createFromFormat('Y-m-d', $date, self::TZ);
        $action = AssistantAction::create([
            'assistant_conversation_id' => $conversation->id, 'shop_id' => $shop->id, 'user_id' => $user->id, 'type' => 'salon_booking',
            'payload' => ['service' => $service, 'date' => $date, 'time' => $time, 'notes' => $notes !== '' ? mb_substr($notes, 0, 500) : null],
            'summary' => Str::limit(__('حجز :service عند :shop · :day :date الساعة :time', ['service' => $service, 'shop' => $shop->name,
                'day' => $day->locale(app()->getLocale())->translatedFormat('l'), 'date' => $day->format('d/m'), 'time' => $time]), 490),
            'status' => AssistantAction::PENDING,
        ]);
        $cards['actions'] = [...($cards['actions'] ?? []), $action->id];

        return ['proposed' => true, 'summary' => $action->summary,
            'note' => 'Not booked yet. A "book" button is shown under your reply; tell the customer to press it. Do not say it is booked.'];
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

        $actions = empty($cards['actions']) ? [] : AssistantAction::whereIn('id', $cards['actions'])->get()
            ->map(fn (AssistantAction $a) => self::presentAction($a))->values()->all();

        // A ready basket: the app shows a "put in basket" button; the website shows its products as cards.
        $basket = null;
        if (! empty($cards['basket']['items'])) {
            $built = \App\Services\Basket\BasketLines::from($cards['basket']['items'], null, true);
            $basket = $built['lines'] ? ['lines' => $built['lines'], 'skipped' => array_values(array_unique([...($cards['basket']['skipped'] ?? []), ...$built['skipped']]))] : null;
        }

        $open = isset($cards['open']['page']) && in_array($cards['open']['page'], self::PAGES, true)
            ? ['page' => $cards['open']['page'], 'id' => isset($cards['open']['id']) ? (int) $cards['open']['id'] : null] : null;
        $guide = collect($cards['guide'] ?? [])->filter(fn ($step) => is_array($step) && isset(self::SPOTS[$step['spot'] ?? '']))->values()->all();

        return ['products' => $products->all(), 'shops' => $shops->all(), 'actions' => $actions, 'basket' => $basket, 'guide' => $guide, 'open' => $open];
    }

    public static function presentAction(AssistantAction $action): array
    {
        $status = $action->status === AssistantAction::PENDING && $action->isStale() ? AssistantAction::EXPIRED : $action->status;

        return ['id' => $action->id, 'type' => $action->type, 'summary' => $action->summary, 'status' => $status, 'result' => $action->result];
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
