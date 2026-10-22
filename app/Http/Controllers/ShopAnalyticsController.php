<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\Shop;
use App\Models\ShopActivityEvent;
use App\Services\ShopActivityRecorder;
use App\Services\ShopAnalytics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopAnalyticsController extends Controller
{
    public function index()
    {
        return view('admin.analytics.index', ['shops' => Shop::orderBy('name')->get(['id', 'name']),
            'ready' => app(ShopActivityRecorder::class)->available()]);
    }

    private function period(Request $request): array
    {
        abort_unless(app(ShopActivityRecorder::class)->available(), 503, 'يلزم تشغيل تحديث قاعدة البيانات أولاً.');
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
        ]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();
        abort_if($to->lt($from) || $from->diffInDays($to) > 366, 422, 'اختر فترة صحيحة لا تتجاوز سنة.');
        return [$from, $to, isset($data['shop_id']) ? (int) $data['shop_id'] : null];
    }

    public function data(Request $request, ShopAnalytics $analytics)
    {
        return response()->json($analytics->report(...$this->period($request)));
    }

    public function activity(Request $request)
    {
        [$from, $to, $shopId] = $this->period($request);
        $request->validate(['kind' => ['nullable', 'string', 'max:60'], 'before' => ['nullable', 'integer', 'min:1']]);
        $events = ShopActivityEvent::with(['shop:id,name', 'actor:id,name'])->whereBetween('created_at', [$from, $to])
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->kind))
            ->when($request->filled('before'), fn ($q) => $q->where('id', '<', $request->integer('before')))
            ->orderByDesc('id')->limit(51)->get();
        return response()->json(['events' => $events->take(50)->map(fn ($e) => $this->event($e)),
            'next' => $events->count() > 50 ? $events[49]->id : null]);
    }

    public function notifications(Request $request)
    {
        if (! app(ShopActivityRecorder::class)->available()) return response()->json(['ready' => false, 'events' => [], 'unread' => 0, 'latest' => 0]);
        $lastRead = (int) DB::table('shop_analytics_reads')->where('user_id', $request->user()->id)->value('last_event_id');
        $query = ShopActivityEvent::whereIn('kind', ShopActivityEvent::NOTIFICATIONS);
        return response()->json(['ready' => true, 'latest' => (int) (clone $query)->max('id'), 'read_through' => $lastRead,
            'unread' => (clone $query)->where('id', '>', $lastRead)->count(),
            'events' => $query->with(['shop:id,name', 'actor:id,name'])->latest('id')->limit(30)->get()->map(fn ($e) => $this->event($e))]);
    }

    public function read(Request $request)
    {
        abort_unless(app(ShopActivityRecorder::class)->available(), 503);
        $data = $request->validate(['through' => ['required', 'integer', 'min:0']]);
        $max = (int) ShopActivityEvent::whereIn('kind', ShopActivityEvent::NOTIFICATIONS)->max('id');
        DB::table('shop_analytics_reads')->insertOrIgnore(['user_id' => $request->user()->id, 'last_event_id' => 0]);
        DB::table('shop_analytics_reads')->where('user_id', $request->user()->id)
            ->where('last_event_id', '<', min($max, $data['through']))->update(['last_event_id' => min($max, $data['through'])]);
        return response()->json(['ok' => true]);
    }

    public function order(FrontOrder $order)
    {
        return response()->json([
            'number' => $order->order_number, 'shop' => $order->shop?->name ?? 'غير مربوط بمتجر',
            'customer' => $order->customer_name, 'phone' => $order->customer_phone, 'whatsapp' => $order->customer_whatsapp,
            'address' => $order->customer_address, 'notes' => $order->customer_notes,
            'items' => collect($order->items)->map(fn ($item) => ['name' => $item['name'] ?? '',
                'qty' => $item['qty'] ?? $item['quantity'] ?? 1, 'price' => $item['price'] ?? 0]),
            'subtotal' => $order->subtotal, 'discount' => $order->discount, 'total' => $order->total,
            'status' => $order->statusLabel(), 'payment' => $order->payment_status,
            'created_at' => $order->created_at->toIso8601String(), 'sale_confirmed_at' => $order->sale_confirmed_at,
            'manage_url' => $order->order_type && $order->shop_id ? route('restaurant.dashboard', $order->shop_id) : route('front-orders.index', ['shop_id' => $order->shop_id]),
        ]);
    }

    public function export(Request $request, ShopAnalytics $analytics)
    {
        $report = $analytics->report(...$this->period($request));
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['المتجر', 'من', 'إلى', 'الطلبات المنشأة', 'المبيعات المؤكدة', 'قيمة المبيعات ₪', 'مشاهدات الصفحات', 'المتصفحات الفريدة', 'تسجيلات العملاء', 'دخول صاحب المحل']);
            foreach ($report['shops'] as $row) {
                $name = preg_match('/^[\s]*[=+@\-\t\r\n]/u', $row['name']) ? "'".$row['name'] : $row['name'];
                fputcsv($out, [$name, $report['period']['from'], $report['period']['to'], $row['orders'], $row['sales'],
                    $row['revenue'], $row['views'], $row['visitors'], $row['registrations'], $row['logins']]);
            }
            fclose($out);
        }, 'ozman-analytics-'.$report['period']['from'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function event(ShopActivityEvent $event): array
    {
        return ['id' => $event->id, 'kind' => $event->kind, 'title' => $event->title,
            'shop' => $event->shop?->name ?? 'عام / متجر محذوف', 'actor' => $event->actor?->name,
            'data' => $event->data, 'created_at' => $event->created_at->toIso8601String(),
            'order_id' => $event->subject_type === 'FrontOrder' ? $event->subject_id : null];
    }
}
