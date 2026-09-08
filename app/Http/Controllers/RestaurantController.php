<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\PushDevice;
use App\Models\RestaurantTable;
use App\Models\Shop;
use App\Rules\ValidPhoneNumber;
use App\Services\FirebaseMessagingService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

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
        $latestOrderId = (int) ((clone $ordersQuery)->max('id') ?? 0);
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
            'latestOrderId' => $latestOrderId,
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
        $latestOrder = (clone $statsQuery)
            ->latest('id')
            ->first(['id', 'order_number', 'customer_name', 'order_type']);

        return response()->json([
            'latest_id' => (int) ($latestOrder?->id ?? 0),
            'latest_order' => $latestOrder ? [
                'id' => (int) $latestOrder->id,
                'number' => $latestOrder->order_number,
                'customer' => $latestOrder->customer_name,
                'type' => $latestOrder->order_type,
            ] : null,
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

    public function storeOrder(Request $request, Shop $shop, FirebaseMessagingService $firebase): JsonResponse
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
        if (filled($data['table_code'] ?? null)) {
            $table = $shop->restaurantTables()
                ->where('code', $data['table_code'])
                ->where('is_active', true)
                ->firstOrFail();
            $data['order_type'] = 'dine_in';
        } elseif ($data['order_type'] === 'dine_in') {
            return response()->json(['message' => 'امسح QR الطاولة لإرسال طلب من داخل المطعم.'], 422);
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
        $estimatedPreparationMinutes = 0;
        foreach ($data['items'] as $row) {
            $product = $products->get((int) $row['product_id']);
            abort_unless($product, 422, 'إحدى الوجبات غير متاحة.');
            $attributes = $product->catalog_attributes ?? [];
            $preparationMinutes = min(1440, max(0, (int) ($attributes['preparation_time'] ?? 0)));
            $estimatedPreparationMinutes = max($estimatedPreparationMinutes, $preparationMinutes);
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
                'preparation_time' => $preparationMinutes ?: null,
            ];
        }

        $customerPushToken = $request->session()->get('app_push_token');
        if (! is_string($customerPushToken) || ! PushDevice::query()->where('token', $customerPushToken)->exists()) {
            $customerPushToken = null;
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
            'estimated_preparation_minutes' => $estimatedPreparationMinutes ?: null,
            'customer_push_token' => $customerPushToken,
            'payment_status' => 'pending', 'status' => 'new',
        ]);
        $this->sendNewOrderPush($shop, $order, $firebase);

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'estimated_preparation_minutes' => $order->estimated_preparation_minutes,
            'tracking_url' => URL::signedRoute('restaurant.orders.track', $order),
            'tracking' => $this->trackingPayload($order),
        ]);
    }

    public function track(Request $request, FrontOrder $order): JsonResponse|View
    {
        abort_unless($order->order_type && $order->shop?->catalog_type === 'restaurant', 404);
        $tracking = $this->trackingPayload($order);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'tracking' => $tracking]);
        }

        return view('front.restaurant_order_tracking', [
            'order' => $order,
            'shop' => $order->shop,
            'tracking' => $tracking,
        ]);
    }

    public function status(Request $request, FrontOrder $order, FirebaseMessagingService $firebase): RedirectResponse
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
        $previousStatus = $order->status;
        $order->update($data);
        if ($previousStatus !== $order->status) {
            $this->sendCustomerStatusPush($order->fresh('shop'), $firebase);
        }
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

    private function sendNewOrderPush(Shop $shop, FrontOrder $order, FirebaseMessagingService $firebase): void
    {
        if (! $shop->user_id) {
            return;
        }

        $tokens = PushDevice::query()
            ->where('user_id', $shop->user_id)
            ->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $typeLabel = match ($order->order_type) {
            'dine_in' => 'طلب طاولة',
            'delivery' => 'طلب توصيل',
            'pickup' => 'طلب استلام',
            default => 'طلب جديد',
        };
        $url = route('restaurant.dashboard', $shop).'#restaurant-order-'.$order->id;

        try {
            $firebase->sendToTokens(
                $tokens,
                'طلب جديد · '.$shop->name,
                "{$typeLabel} رقم {$order->order_number} بقيمة {$order->total} شيكل",
                $url,
                [
                    'type' => 'restaurant_order',
                    'screen' => 'restaurant_dashboard',
                    'shop_id' => $shop->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            );
        } catch (Throwable $exception) {
            // لا يجب أن يفشل طلب الزبون بسبب عطل مؤقت في خدمة الإشعارات.
            report($exception);
        }
    }

    private function sendCustomerStatusPush(FrontOrder $order, FirebaseMessagingService $firebase): void
    {
        if (! filled($order->customer_push_token) || ! $order->shop) {
            return;
        }

        $title = match ($order->status) {
            'preparing' => 'بدأ تحضير طلبك 🍳',
            'ready' => $order->order_type === 'delivery' ? 'طلبك جاهز للتوصيل 🛵' : 'طلبك جاهز 🎉',
            'completed' => 'تم إكمال طلبك ✅',
            'cancelled' => 'تم إلغاء الطلب',
            default => 'تحديث على طلبك',
        };
        $preparationText = $order->status === 'preparing' && $order->estimated_preparation_minutes
            ? " ومدة التجهيز المتوقعة {$order->estimated_preparation_minutes} دقيقة"
            : '';
        $body = "طلبك {$order->order_number} من {$order->shop->name}: {$order->statusLabel()}{$preparationText}.";
        $url = URL::signedRoute('restaurant.orders.track', $order);

        try {
            $firebase->sendToTokens(
                [$order->customer_push_token],
                $title,
                $body,
                $url,
                [
                    'type' => 'restaurant_order_status',
                    'screen' => 'restaurant_order_tracking',
                    'shop_id' => $order->shop_id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                ],
            );
        } catch (Throwable $exception) {
            // تحديث الحالة ينجح حتى لو كانت خدمة الإشعارات متوقفة مؤقتاً.
            report($exception);
        }
    }

    private function trackingPayload(FrontOrder $order): array
    {
        $order->loadMissing('shop');
        $step = match ($order->status) {
            'new' => 1,
            'preparing' => 2,
            'ready' => 3,
            'completed' => 4,
            default => 0,
        };
        $message = match ($order->status) {
            'new' => 'استلم المطعم طلبك وسيبدأ العمل عليه قريباً.',
            'preparing' => 'المطبخ يجهّز وجباتك الآن.',
            'ready' => $order->order_type === 'delivery'
                ? 'طلبك جاهز وسيبدأ التوصيل إليك.'
                : 'طلبك جاهز، يمكنك استلامه الآن.',
            'completed' => 'اكتمل طلبك، نتمنى لك وجبة شهية.',
            'cancelled' => 'تم إلغاء الطلب. تواصل مع المطعم لمزيد من التفاصيل.',
            default => 'يتم الآن تحديث حالة طلبك.',
        };

        return [
            'order_number' => $order->order_number,
            'restaurant_name' => $order->shop?->name,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'status_message' => $message,
            'step' => $step,
            'is_cancelled' => $order->status === 'cancelled',
            'order_type' => $order->order_type,
            'estimated_preparation_minutes' => $order->estimated_preparation_minutes,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
    }
}
