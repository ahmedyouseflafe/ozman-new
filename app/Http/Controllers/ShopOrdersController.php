<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\Shop;
use App\Services\AppShopOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The orders board for every shop that is not a restaurant (clothing, cosmetics, electronics,
 * sweets…): the same live cards as the restaurant dashboard, without the kitchen parts.
 */
class ShopOrdersController extends Controller
{
    public const STATUSES = [
        'new' => 'جديد',
        'preparing' => 'قيد التجهيز',
        'ready' => 'جاهز',
        'out_for_delivery' => 'خرج للتوصيل',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
    ];

    private const PERIODS = ['today' => 'طلبات اليوم', 'week' => 'طلبات هذا الأسبوع', 'month' => 'طلبات هذا الشهر', 'all' => 'كل الطلبات'];

    /** Orders that still need work come first. */
    private const ACTIVE_FIRST = "CASE status WHEN 'new' THEN 0 WHEN 'preparing' THEN 1 WHEN 'ready' THEN 2 WHEN 'out_for_delivery' THEN 3 ELSE 4 END";

    public function dashboard(Request $request, Shop $shop): View|RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        if ($shop->catalog_type === 'restaurant') {
            return redirect()->route('restaurant.dashboard', $shop);
        }
        [$period, $status] = $this->filters($request);
        $periodOrders = $this->inPeriod($this->ordersOf($shop), $period);

        return view('admin.shop_orders.dashboard', [
            'shop' => $shop,
            'orders' => $this->inPeriod($this->ordersOf($shop), $period)
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->orderByRaw(self::ACTIVE_FIRST)->latest()->paginate(50)->withQueryString(),
            'stats' => $this->stats($periodOrders),
            'selectedStatus' => $status,
            'selectedPeriod' => $period,
            'selectedPeriodLabel' => self::PERIODS[$period],
            'latestOrderId' => (int) ((clone $periodOrders)->max('id') ?? 0),
        ]);
    }

    public function feed(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request, $shop);
        abort_if($shop->catalog_type === 'restaurant', 404);
        [$period, $status] = $this->filters($request);
        $periodOrders = $this->inPeriod($this->ordersOf($shop), $period);
        $orders = $this->inPeriod($this->ordersOf($shop), $period)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByRaw(self::ACTIVE_FIRST)->latest()->limit(50)->get();
        $latest = (clone $periodOrders)->latest('id')->first(['id', 'order_number', 'customer_name', 'order_channel']);

        return response()->json([
            'latest_id' => (int) ($latest?->id ?? 0),
            'latest_order' => $latest ? ['id' => $latest->id, 'number' => $latest->order_number, 'customer' => $latest->customer_name, 'channel' => $latest->order_channel] : null,
            'stats' => $this->stats($periodOrders),
            'html' => view('admin.shop_orders.partials.cards', ['orders' => $orders, 'shop' => $shop])->render(),
        ]);
    }

    public function show(Request $request, Shop $shop, FrontOrder $order): View
    {
        $this->authorizeShop($request, $shop);
        abort_unless((int) $order->shop_id === (int) $shop->id && ! $order->order_type, 404);

        return view('admin.shop_orders.order', ['shop' => $shop, 'order' => $order]);
    }

    public function status(Request $request, FrontOrder $order, AppShopOrderService $orders): JsonResponse|RedirectResponse
    {
        $shop = $order->shop;
        abort_unless($shop, 404);
        $this->authorizeShop($request, $shop);
        abort_if(filled($order->order_type), 422, 'طلبات المطعم تُدار من لوحة المطعم.');
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(self::STATUSES))]]);

        $previous = $order->status;
        $order->update(['status' => $data['status']]);
        if ($previous !== $order->status) {
            $orders->notifyCustomer($order->setRelation('shop', $shop));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'status' => $order->status,
                'html' => view('admin.shop_orders.partials.cards', ['orders' => collect([$order]), 'shop' => $shop])->render(),
            ]);
        }

        return back()->with('status', 'تم تحديث حالة الطلب وإشعار الزبون.');
    }

    public function availability(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        $open = (bool) $request->validate(['is_accepting_orders' => ['required', 'boolean']])['is_accepting_orders'];
        $shop->update(['is_accepting_orders' => $open]);

        return back()->with('status', $open ? 'المحل مفتوح ويستقبل طلبات.' : 'تم إيقاف استقبال الطلبات مؤقتاً.');
    }

    /**
     * Orders customers placed with this shop: app, WhatsApp checkout, electronics. Restaurant
     * orders (with an order type) and the shop's own Ozman drink purchases ("ORD-…") are left out.
     */
    private function ordersOf(Shop $shop)
    {
        return FrontOrder::query()
            ->where('shop_id', $shop->id)
            ->whereNull('order_type')
            ->where('order_number', 'not like', 'ORD-%');
    }

    private function stats($periodOrders): array
    {
        return [
            'orders' => (clone $periodOrders)->count(),
            'new' => (clone $periodOrders)->where('status', 'new')->count(),
            'preparing' => (clone $periodOrders)->where('status', 'preparing')->count(),
            'ready' => (clone $periodOrders)->whereIn('status', ['ready', 'out_for_delivery'])->count(),
            'sales_total' => (float) (clone $periodOrders)->whereIn('status', ['delivered', 'completed'])->sum('total'),
        ];
    }

    /** @return array{0: string, 1: string} */
    private function filters(Request $request): array
    {
        // Shops get fewer orders than kitchens, and yesterday's unfinished order still matters.
        $period = (string) $request->query('period', 'all');
        $status = (string) $request->query('status', '');

        return [
            array_key_exists($period, self::PERIODS) ? $period : 'all',
            array_key_exists($status, self::STATUSES) || $status === 'completed' ? $status : '',
        ];
    }

    private function inPeriod($query, string $period)
    {
        return match ($period) {
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'all' => $query,
            default => $query->whereDate('created_at', today()),
        };
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
    }
}
