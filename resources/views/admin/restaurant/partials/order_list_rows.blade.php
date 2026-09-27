@forelse($orders as $order)
    @php($items = collect($order->items ?? []))
    <tr class="order-summary-row" data-order-url="{{ route('restaurant.orders.show', [$shop, $order]) }}" tabindex="0" role="link" aria-label="فتح تفاصيل الطلب {{ $order->order_number }}">
        <td data-label="الطلب">
            <a class="order-number-link" href="{{ route('restaurant.orders.show', [$shop, $order]) }}">{{ $order->order_number }}</a>
            <small><i class="ti ti-calendar-event"></i> {{ $order->created_at?->copy()->locale('ar')->translatedFormat('l، d/m/Y · h:i A') }}</small>
        </td>
        <td data-label="الوجبات" class="order-meals">
            @forelse($items->take(2) as $item)
                <div><b>{{ $item['qty'] ?? 1 }}× {{ $item['name'] ?? 'وجبة' }}</b></div>
            @empty
                <span>لا توجد وجبات مسجلة</span>
            @endforelse
            @if($items->count() > 2)<small class="more-items">+ {{ $items->count() - 2 }} وجبات أخرى</small>@endif
        </td>
        <td data-label="المجموع" class="order-total">{{ number_format((float) $order->total, 2) }} ₪</td>
        <td data-label="الحالة"><span class="tag">{{ $order->statusLabel() }}</span></td>
    </tr>
@empty
    <tr><td colspan="4">لا توجد طلبات ضمن الفلترة المختارة.</td></tr>
@endforelse
