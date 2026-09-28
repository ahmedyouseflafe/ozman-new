@forelse($orders as $order)
    @php
        $items = collect($order->items ?? []);
        $itemCount = $items->sum(fn ($item) => (int) ($item['qty'] ?? 1));
        $itemsPreview = $items->take(2)->map(fn ($item) => ($item['qty'] ?? 1).'× '.($item['name'] ?? ''))->filter()->join('، ');
    @endphp
    <article class="order-card" id="delivery-order-{{ $order->id }}">
        <div class="order-top">
            <div>
                <span class="order-number">{{ $order->order_number }}</span>
                <small class="order-time"><i class="ti ti-clock"></i> {{ $order->created_at?->format('H:i · d/m/Y') }}</small>
            </div>
            <span class="badge">{{ $order->statusLabel() }}</span>
            <strong class="order-total"><small>المجموع</small>{{ $order->total }} ₪</strong>
        </div>

        <div class="order-customer-row">
            <span class="customer-chip"><i class="ti ti-user"></i><small>العميل</small> {{ $order->customer_name }}</span>
            @if($order->customer_phone)
                <a class="quick-contact" href="tel:{{ $order->customer_phone }}" dir="ltr"><i class="ti ti-phone"></i> {{ $order->customer_phone }}</a>
            @endif
            @if($order->map_link)
                <a class="map-link" href="{{ $order->map_link }}" target="_blank" rel="noopener"><i class="ti ti-map-pin"></i> الخريطة</a>
            @endif
        </div>

        @if($order->customer_address)
            <div class="order-address"><i class="ti ti-map-pin"></i><div><small>العنوان</small><strong>{{ $order->customer_address }}</strong></div></div>
        @endif

        @if($order->customer_notes)
            <details class="order-notes"><summary><i class="ti ti-notes"></i> ملاحظات العميل</summary><div>{{ $order->customer_notes }}</div></details>
        @endif

        <details class="order-items">
            <summary>
                <span><i class="ti ti-bowl-spoon"></i> {{ $itemCount }} وجبة @if($itemsPreview) · {{ $itemsPreview }} @endif</span>
                <i class="ti ti-chevron-down"></i>
            </summary>
            <div class="order-items-list">
                @forelse($items as $item)
                    <div>{{ $item['qty'] ?? 1 }}× {{ $item['name'] ?? '' }} {{ $item['size'] ?? '' }}</div>
                @empty
                    <div>لا توجد وجبات مسجلة.</div>
                @endforelse
            </div>
        </details>

        <div class="order-actions">
            @if($order->status === 'ready')
                <form class="delivery-eta-form" method="post" action="{{ route('driver.orders.status', $order) }}">
                    @csrf @method('patch')
                    <input type="hidden" name="status" value="out_for_delivery">
                    <label for="delivery-minutes-{{ $order->id }}">وقت الوصول المتوقع (دقائق)</label>
                    <input id="delivery-minutes-{{ $order->id }}" type="number" name="estimated_delivery_minutes" min="1" max="240" inputmode="numeric" placeholder="20" required>
                    <button type="submit" class="action"><i class="ti ti-motorbike"></i> استلام وبدء التوصيل</button>
                </form>
            @elseif($order->status === 'out_for_delivery')
                <form class="delivery-eta-form" method="post" action="{{ route('driver.orders.delivery-time', $order) }}">
                    @csrf @method('patch')
                    <label for="delivery-minutes-{{ $order->id }}">تحديث الوقت المتبقي</label>
                    <input id="delivery-minutes-{{ $order->id }}" type="number" name="estimated_delivery_minutes" min="1" max="240" inputmode="numeric" placeholder="15" required>
                    <button type="submit" class="btn"><i class="ti ti-clock"></i> حفظ الوقت</button>
                </form>
                <form method="post" action="{{ route('driver.orders.status', $order) }}">
                    @csrf @method('patch')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="action"><i class="ti ti-circle-check"></i> تم التسليم</button>
                </form>
            @elseif(in_array($order->status, ['new', 'preparing'], true))
                <span class="order-wait"><i class="ti ti-chef-hat"></i> الطلب عند المطعم؛ سيصبح متاحًا عند التجهيز.</span>
            @elseif($order->status === 'completed')
                <span class="order-wait"><i class="ti ti-circle-check"></i> تم تسليم الطلب.</span>
            @endif
        </div>
    </article>
@empty
    <div class="empty">لا توجد طلبات توصيل مسندة إليك حاليًا.</div>
@endforelse
