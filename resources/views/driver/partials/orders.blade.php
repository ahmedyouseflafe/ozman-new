@forelse($orders as $order)
    <article class="order-card" id="delivery-order-{{ $order->id }}">
        <div class="order-head">
            <div><small>طلب توصيل · {{ $order->created_at?->format('Y-m-d H:i') }}</small><h2>{{ $order->order_number }}</h2></div>
            <span class="badge">{{ $order->statusLabel() }}</span>
        </div>
        <div class="order-details">
            <div><small>العميل</small><strong>{{ $order->customer_name }}</strong></div>
            <div><small>الجوال</small><a href="tel:{{ $order->customer_phone }}" dir="ltr">{{ $order->customer_phone }}</a></div>
            @if($order->customer_address)<div class="full"><small>العنوان</small><strong>{{ $order->customer_address }}</strong></div>@endif
            @if($order->customer_notes)<div class="full"><small>ملاحظات العميل</small><strong>{{ $order->customer_notes }}</strong></div>@endif
            @if($order->map_link)<div class="full"><a class="map-link" href="{{ $order->map_link }}" target="_blank" rel="noopener"><i class="ti ti-map-pin"></i> فتح موقع التوصيل على الخريطة</a></div>@endif
        </div>
        <div class="order-items">
            <small>الوجبات</small>
            @foreach($order->items ?? [] as $item)
                <div>{{ $item['qty'] ?? 1 }}× {{ $item['name'] ?? '' }} {{ $item['size'] ?? '' }}</div>
            @endforeach
        </div>
        <div class="order-foot">
            <strong>المجموع: {{ $order->total }} ₪</strong>
            @if($order->status === 'ready')
                <form method="post" action="{{ route('driver.orders.status', $order) }}">
                    @csrf @method('patch')
                    <input type="hidden" name="status" value="out_for_delivery">
                    <button type="submit" class="action"><i class="ti ti-motorbike"></i> استلمت الطلب وخرجت للتوصيل</button>
                </form>
            @elseif($order->status === 'out_for_delivery')
                <form method="post" action="{{ route('driver.orders.status', $order) }}">
                    @csrf @method('patch')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="action"><i class="ti ti-circle-check"></i> تم تسليم الطلب للعميل</button>
                </form>
            @elseif(in_array($order->status, ['new', 'preparing'], true))
                <small>الطلب عند المطعم؛ سيظهر زر الاستلام عندما يصبح جاهزًا.</small>
            @endif
        </div>
    </article>
@empty
    <div class="empty">لا توجد طلبات توصيل مسندة إليك حاليًا.</div>
@endforelse
