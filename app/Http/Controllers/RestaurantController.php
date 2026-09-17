<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\PushDevice;
use App\Models\RestaurantTable;
use App\Models\Shop;
use App\Rules\ValidPhoneNumber;
use App\Services\FirebaseMessagingService;
use App\Services\WebPushService;
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
use Illuminate\Validation\ValidationException;
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
        $allowedStatuses = ['new', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
        $allowedTypes = ['dine_in', 'delivery', 'pickup'];
        $ordersQuery = FrontOrder::with('restaurantTable', 'restaurantDriver.user')->where('shop_id', $shop->id)->whereNotNull('order_type');
        $latestOrderId = (int) ((clone $ordersQuery)->max('id') ?? 0);
        $stats = [
            'new' => (clone $ordersQuery)->where('status', 'new')->count(),
            'preparing' => (clone $ordersQuery)->where('status', 'preparing')->count(),
            'ready' => (clone $ordersQuery)->where('status', 'ready')->count(),
            'today' => (clone $ordersQuery)->whereDate('created_at', today())->count(),
            'sales_total' => (float) (clone $ordersQuery)->where('status', 'completed')->sum('total'),
        ];

        return view('admin.restaurant.dashboard', [
            'shop' => $shop,
            'tables' => $shop->restaurantTables()->latest()->get(),
            'drivers' => $shop->restaurantDrivers()->with('user')->latest()->get(),
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
        $allowedStatuses = ['new', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
        $allowedTypes = ['dine_in', 'delivery', 'pickup'];
        $ordersQuery = FrontOrder::with('restaurantTable', 'restaurantDriver.user')
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
            'sales_total' => (float) (clone $statsQuery)->where('status', 'completed')->sum('total'),
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
                'drivers' => $shop->restaurantDrivers()->with('user')->where('is_active', true)->whereHas('user', fn ($query) => $query->where('is_active', true))->get(),
                'canManageOrders' => $request->user()->isSuperAdmin()
                    || $request->user()->canAccessRouteName('restaurant.orders.status'),
                'canAssignDrivers' => $request->user()->isSuperAdmin()
                    || $request->user()->canAccessRouteName('restaurant.orders.driver'),
            ])->render(),
        ]);
    }

    public function availability(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        abort_unless($shop->catalog_type === 'restaurant', 404);

        $data = $request->validate([
            'is_accepting_orders' => ['required', 'boolean'],
        ]);

        $isAcceptingOrders = (bool) $data['is_accepting_orders'];
        $shop->update(['is_accepting_orders' => $isAcceptingOrders]);

        return back()->with(
            'status',
            $isAcceptingOrders
                ? 'تم فتح المطعم، ويمكن للعملاء إرسال طلبات جديدة الآن.'
                : 'تم إغلاق المطعم، وتم إيقاف استقبال الطلبات الجديدة.'
        );
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
        $shop->loadMissing('social');
        $table = $tableCode ? $shop->restaurantTables()->where('code', $tableCode)->where('is_active', true)->firstOrFail() : null;
        $hasActiveStories = $shop->stories()->where('expires_at', '>', now())->exists();
        $displayItems = $shop->advertisements()
            ->where('is_active', true)
            ->whereNotNull('media')
            ->where('media', '!=', '')
            ->orderBy('sort_order')
            ->latest()
            ->get();
        $ozmanLogo = Shop::query()
            ->where('slug', 'ozman')
            ->where('is_active', true)
            ->value('logo');
        $categories = $shop->categories()->where('is_active', true)->orderBy('name')->get();
        $products = Product::with('category')
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('category_id')
                    ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('is_active', true));
            })
            ->get();
        return view('front.restaurant_menu', compact('shop', 'table', 'products', 'categories', 'hasActiveStories', 'displayItems', 'ozmanLogo'));
    }

    public function storeOrder(Request $request, Shop $shop, FirebaseMessagingService $firebase): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'restaurant', 404);
        if (! $shop->is_accepting_orders) {
            $message = match (app()->getLocale()) {
                'he' => 'המסעדה סגורה כעת ואינה מקבלת הזמנות חדשות.',
                'en' => 'The restaurant is currently closed and is not accepting new orders.',
                default => 'المطعم مغلق حالياً ولا يستقبل طلبات جديدة.',
            };

            return response()->json(['message' => $message], 409);
        }

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
        $request->session()->put([
            'restaurant_customer_names.'.$shop->id => $data['customer_name'],
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

    public function track(Request $request, FrontOrder $order): JsonResponse|Response
    {
        abort_unless($order->order_type && $order->shop?->catalog_type === 'restaurant', 404);
        $tracking = $this->trackingPayload($order);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'tracking' => $tracking])
                ->header('Cache-Control', 'private, no-store');
        }

        return response()->view('front.restaurant_order_tracking', [
            'order' => $order,
            'shop' => $order->shop,
            'tracking' => $tracking,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function status(Request $request, FrontOrder $order, FirebaseMessagingService $firebase): JsonResponse|RedirectResponse
    {
        abort_unless($order->shop && $order->order_type, 404);
        $this->authorizeShop($request, $order->shop);
        $data = $request->validate([
            'status' => ['required', 'in:new,preparing,ready,completed,cancelled'],
            'estimated_preparation_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);
        $transitions = [
            'new' => ['new', 'preparing', 'cancelled'],
            'preparing' => ['preparing', 'ready', 'cancelled'],
            'ready' => $order->order_type === 'delivery' && $order->restaurant_driver_id
                ? ['ready', 'cancelled']
                : ['ready', 'completed', 'cancelled'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
            'out_for_delivery' => ['out_for_delivery'],
        ];
        abort_unless(in_array($data['status'], $transitions[$order->status] ?? [], true), 422, 'لا يمكن إعادة الطلب إلى حالة سابقة.');
        if ($data['status'] === 'preparing' && empty($data['estimated_preparation_minutes']) && ! $order->estimated_preparation_minutes) {
            throw ValidationException::withMessages([
                'estimated_preparation_minutes' => 'حدد مدة التجهيز المتوقعة قبل نقل الطلب إلى قيد التحضير.',
            ]);
        }

        $previousStatus = $order->status;
        $previousPreparationMinutes = $order->estimated_preparation_minutes;
        $order->update($data);
        $statusChanged = $previousStatus !== $order->status;
        $preparationTimeChanged = $previousPreparationMinutes !== $order->estimated_preparation_minutes;
        if ($statusChanged || $preparationTimeChanged) {
            $this->sendCustomerStatusPush($order->fresh('shop'), $firebase, $statusChanged, $preparationTimeChanged);
        }
        if ($request->expectsJson()) {
            return response()->json([
                'status' => $order->status,
                'message' => 'تم حفظ حالة الطلب ومدة التجهيز وإبلاغ العميل.',
                'html' => view('admin.restaurant.partials.orders_rows', [
                    'orders' => collect([$order->fresh(['restaurantTable', 'restaurantDriver.user'])]),
                    'drivers' => $order->shop->restaurantDrivers()->with('user')->where('is_active', true)->whereHas('user', fn ($query) => $query->where('is_active', true))->get(),
                    'canManageOrders' => true,
                    'canAssignDrivers' => $request->user()->isSuperAdmin()
                        || $request->user()->canAccessRouteName('restaurant.orders.driver'),
                ])->render(),
            ]);
        }
        return back()->with('status', 'تم حفظ حالة الطلب ومدة التجهيز وإبلاغ العميل.');
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

        $typeLabel = match ($order->order_type) {
            'dine_in' => 'طلب طاولة',
            'delivery' => 'طلب توصيل',
            'pickup' => 'طلب استلام',
            default => 'طلب جديد',
        };
        $url = route('restaurant.dashboard', $shop).'#restaurant-order-'.$order->id;

        $notificationData = [
            'type' => 'restaurant_order',
            'screen' => 'restaurant_dashboard',
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];
        $title = 'طلب جديد · '.$shop->name;
        $message = "{$typeLabel} رقم {$order->order_number} بقيمة {$order->total} شيكل";

        if ($tokens->isNotEmpty()) {
            try {
                $firebase->sendToTokens($tokens, $title, $message, $url, $notificationData);
            } catch (Throwable $exception) {
                // لا يجب أن يفشل طلب الزبون بسبب عطل مؤقت في خدمة الإشعارات.
                report($exception);
            }
        }

        try {
            app(WebPushService::class)->sendToShop($shop, $title, $message, $url, $notificationData);
        } catch (Throwable $exception) {
            // طلب الزبون ينجح حتى لو تعذر إرسال إشعار الويب مؤقتاً.
            report($exception);
        }
    }

    private function sendCustomerStatusPush(
        FrontOrder $order,
        FirebaseMessagingService $firebase,
        bool $statusChanged,
        bool $preparationTimeChanged,
    ): void
    {
        if (! filled($order->customer_push_token) || ! $order->shop) {
            return;
        }

        $title = ! $statusChanged && $preparationTimeChanged
            ? 'تم تحديث وقت تجهيز طلبك ⏱️'
            : match ($order->status) {
            'preparing' => 'بدأ تحضير طلبك 🍳',
            'ready' => $order->order_type === 'delivery' ? 'طلبك جاهز للتوصيل 🛵' : 'طلبك جاهز 🎉',
            'completed' => 'تم إكمال طلبك ✅',
            'cancelled' => 'تم إلغاء الطلب',
            default => 'تحديث على طلبك',
        };
        $preparationText = $order->estimated_preparation_minutes && ($order->status === 'preparing' || $preparationTimeChanged)
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
        $shop = $order->shop;
        $locationIsFresh = $order->status === 'out_for_delivery'
            && $order->driver_location_at?->isAfter(now()->subMinutes(2))
            && $order->driver_latitude !== null
            && $order->driver_longitude !== null;
        $step = match ($order->status) {
            'new' => 1,
            'preparing' => 2,
            'ready' => 3,
            'out_for_delivery' => 4,
            'completed' => $order->order_type === 'delivery' ? 5 : 4,
            default => 0,
        };
        $message = match ($order->status) {
            'new' => 'استلم المطعم طلبك وسيبدأ العمل عليه قريباً.',
            'preparing' => 'المطبخ يجهّز وجباتك الآن.',
            'ready' => $order->order_type === 'delivery'
                ? 'طلبك جاهز وسيبدأ التوصيل إليك.'
                : 'طلبك جاهز، يمكنك استلامه الآن.',
            'out_for_delivery' => 'استلم المندوب طلبك وهو الآن في الطريق إليك.',
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
            'delivery_map' => $order->order_type === 'delivery' ? [
                'restaurant' => $shop?->latitude !== null && $shop?->longitude !== null
                    ? ['lat' => (float) $shop->latitude, 'lng' => (float) $shop->longitude]
                    : null,
                'destination' => $order->latitude !== null && $order->longitude !== null
                    ? ['lat' => (float) $order->latitude, 'lng' => (float) $order->longitude]
                    : null,
                'driver' => $locationIsFresh ? [
                    'lat' => (float) $order->driver_latitude,
                    'lng' => (float) $order->driver_longitude,
                    'accuracy_meters' => $order->driver_location_accuracy_meters,
                    'updated_at' => $order->driver_location_at->toIso8601String(),
                ] : null,
            ] : null,
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
