<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Shop;
use App\Rules\ValidPhoneNumber;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function dashboard(Request $request, Shop $shop): View
    {
        $this->authorizeShop($request, $shop);
        abort_unless($shop->catalog_type === 'restaurant', 404);

        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $allowedStatuses = ['new', 'preparing', 'ready', 'completed', 'cancelled'];
        $allowedTypes = ['dine_in', 'delivery', 'pickup'];
        $ordersQuery = FrontOrder::with('restaurantTable')->where('shop_id', $shop->id)->whereNotNull('order_type');
        $stats = [
            'new' => (clone $ordersQuery)->where('status', 'new')->count(),
            'preparing' => (clone $ordersQuery)->where('status', 'preparing')->count(),
            'ready' => (clone $ordersQuery)->where('status', 'ready')->count(),
            'today' => (clone $ordersQuery)->whereDate('created_at', today())->count(),
        ];

        return view('admin.restaurant.dashboard', [
            'shop' => $shop,
            'tables' => $shop->restaurantTables()->latest()->get(),
            'orders' => $ordersQuery
                ->when(in_array($status, $allowedStatuses, true), fn($query) => $query->where('status', $status))
                ->when(in_array($type, $allowedTypes, true), fn($query) => $query->where('order_type', $type))
                ->latest()->paginate(50)->withQueryString(),
            'stats' => $stats,
            'selectedStatus' => $status,
            'selectedType' => $type,
        ]);
    }

    public function ordersFeed(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request, $shop);
        abort_unless($shop->catalog_type === 'restaurant', 404);

        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $allowedStatuses = ['new', 'preparing', 'ready', 'completed', 'cancelled'];
        $allowedTypes = ['dine_in', 'delivery', 'pickup'];
        $ordersQuery = FrontOrder::with('restaurantTable')
            ->where('shop_id', $shop->id)
            ->whereNotNull('order_type');
        $statsQuery = FrontOrder::query()
            ->where('shop_id', $shop->id)
            ->whereNotNull('order_type');
        $orders = $ordersQuery
            ->when(in_array($status, $allowedStatuses, true), fn($query) => $query->where('status', $status))
            ->when(in_array($type, $allowedTypes, true), fn($query) => $query->where('order_type', $type))
            ->latest()
            ->limit(50)
            ->get();
        $stats = [
            'new' => (clone $statsQuery)->where('status', 'new')->count(),
            'preparing' => (clone $statsQuery)->where('status', 'preparing')->count(),
            'ready' => (clone $statsQuery)->where('status', 'ready')->count(),
            'today' => (clone $statsQuery)->whereDate('created_at', today())->count(),
        ];

        return response()->json([
            'latest_id' => (int) ($orders->max('id') ?? 0),
            'stats' => $stats,
            'html' => view('admin.restaurant.partials.orders_rows', [
                'orders' => $orders,
                'canManageOrders' => $request->user()->isSuperAdmin()
                    || $request->user()->canAccessRouteName('restaurant.orders.status'),
            ])->render(),
        ]);
    }

    public function storeTable(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        abort_unless($shop->catalog_type === 'restaurant', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('restaurant_tables', 'name')->where('shop_id', $shop->id)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        abort_if($shop->restaurantTables()->count() >= 500, 422, 'تم الوصول للحد الأقصى لعدد الطاولات.');
        $data['code'] = Str::lower(Str::random(32));
        $data['is_active'] = true;
        $shop->restaurantTables()->create($data);
        return back()->with('status', 'تمت إضافة الطاولة وإنشاء QR خاص بها.');
    }

    public function destroyTable(Request $request, RestaurantTable $table): RedirectResponse
    {
        $this->authorizeShop($request, $table->shop);
        $table->delete();
        return back()->with('status', 'تم حذف الطاولة.');
    }

    public function tableQr(RestaurantTable $table): Response
    {
        abort_unless($table->is_active && $table->shop?->catalog_type === 'restaurant', 404);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(500, 2), new SvgImageBackEnd())))
            ->writeString(route('restaurant.table', [$table->shop, $table->code]));
        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function menu(Shop $shop, ?string $tableCode = null): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'restaurant', 404);
        $table = $tableCode ? $shop->restaurantTables()->where('code', $tableCode)->where('is_active', true)->firstOrFail() : null;
        $categories = $shop->categories()->where('is_active', true)->orderBy('name')->get();
        $products = Product::with('category')
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('category_id')
                    ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('is_active', true));
            })
            ->get();
        return view('front.restaurant_menu', compact('shop', 'table', 'products', 'categories'));
    }

    public function storeOrder(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'restaurant', 404);
        $data = $request->validate([
            'order_type' => ['required', 'in:dine_in,delivery,pickup'],
            'table_code' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:60', new ValidPhoneNumber()],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required_if:order_type,delivery', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:order_type,delivery', 'nullable', 'numeric', 'between:-180,180'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.addons' => ['nullable', 'array', 'max:30'],
            'items.*.addons.*' => ['string', 'max:100'],
            'items.*.excluded' => ['nullable', 'array', 'max:30'],
            'items.*.excluded.*' => ['string', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $table = null;
        if ($data['order_type'] === 'dine_in') {
            $table = $shop->restaurantTables()->where('code', $data['table_code'] ?? '')->where('is_active', true)->firstOrFail();
        } elseif (!filled($data['customer_phone'])) {
            return response()->json(['message' => 'رقم الهاتف مطلوب لطلبات التوصيل والاستلام.'], 422);
        }
        if ($data['order_type'] === 'delivery' && !filled($data['customer_address'])) {
            return response()->json(['message' => 'عنوان التوصيل مطلوب.'], 422);
        }

        $products = Product::where('shop_id', $shop->id)->where('is_active', true)
            ->whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
        $items = [];
        $subtotal = 0;
        foreach ($data['items'] as $row) {
            $product = $products->get((int) $row['product_id']);
            abort_unless($product, 422, 'إحدى الوجبات غير متاحة.');
            $attributes = $product->catalog_attributes ?? [];
            $sizePrices = $this->pricedOptions($attributes['meal_size_prices'] ?? []);
            $addonPrices = $this->pricedOptions($attributes['addon_prices'] ?? []);
            $unit = (float) ($product->discount_price ?: $product->price);
            $size = $row['size'] ?? null;
            abort_if($size && !array_key_exists($size, $sizePrices), 422, 'حجم الوجبة المحدد غير متاح.');
            if ($size && array_key_exists($size, $sizePrices)) $unit = $sizePrices[$size];
            $requestedAddons = collect($row['addons'] ?? [])->unique()->values();
            abort_if($requestedAddons->contains(fn($name) => !array_key_exists($name, $addonPrices)), 422, 'إحدى الإضافات المحددة غير متاحة.');
            $requestedExcluded = collect($row['excluded'] ?? [])->unique()->values();
            $removable = collect($attributes['removable_ingredients'] ?? []);
            abort_if($requestedExcluded->diff($removable)->isNotEmpty(), 422, 'لا يمكن حذف أحد المكونات المحددة.');
            $addons = $requestedAddons;
            $unit += $addons->sum(fn($name) => $addonPrices[$name]);
            $line = round($unit * (int) $row['qty'], 2);
            $subtotal += $line;
            $items[] = [
                'product_id' => $product->id, 'name' => $product->name, 'price' => $unit,
                'qty' => (int) $row['qty'], 'size' => $size, 'addons' => $addons->all(),
                'excluded' => $requestedExcluded->all(),
                'notes' => $row['notes'] ?? null, 'line_total' => $line,
            ];
        }

        $order = FrontOrder::create([
            'shop_id' => $shop->id, 'restaurant_table_id' => $table?->id,
            'order_number' => 'RST-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
            'customer_name' => $data['customer_name'], 'customer_phone' => $data['customer_phone'] ?? null,
            'customer_address' => $data['customer_address'] ?? null, 'customer_notes' => $data['customer_notes'] ?? null,
            'latitude' => $data['latitude'] ?? null, 'longitude' => $data['longitude'] ?? null,
            'map_link' => isset($data['latitude'], $data['longitude'])
                ? 'https://www.google.com/maps?q=' . $data['latitude'] . ',' . $data['longitude']
                : null,
            'items' => $items, 'subtotal' => $subtotal, 'total' => $subtotal, 'discount' => 0,
            'order_channel' => 'restaurant', 'order_type' => $data['order_type'],
            'payment_status' => 'pending', 'status' => 'new',
        ]);
        return response()->json(['ok' => true, 'order_id' => $order->id, 'order_number' => $order->order_number]);
    }

    public function status(Request $request, FrontOrder $order): RedirectResponse
    {
        abort_unless($order->shop && $order->order_type, 404);
        $this->authorizeShop($request, $order->shop);
        $data = $request->validate(['status' => ['required', 'in:new,preparing,ready,completed,cancelled']]);
        $transitions = [
            'new' => ['new', 'preparing', 'cancelled'],
            'preparing' => ['preparing', 'ready', 'cancelled'],
            'ready' => ['ready', 'completed', 'cancelled'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];
        abort_unless(in_array($data['status'], $transitions[$order->status] ?? [], true), 422, 'لا يمكن إعادة الطلب إلى حالة سابقة.');
        $order->update($data);
        return back()->with('status', 'تم تحديث حالة الطلب.');
    }

    private function pricedOptions(array $values): array
    {
        return collect($values)->mapWithKeys(function ($value) {
            [$name, $price] = array_pad(explode(':', (string) $value, 2), 2, null);
            $name = trim($name);
            return $name !== '' && is_numeric($price) ? [$name => max(0, (float) $price)] : [];
        })->all();
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
    }
}
