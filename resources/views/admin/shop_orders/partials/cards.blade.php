@php
    $canManageOrders = $canManageOrders ?? (auth()->user()?->isSuperAdmin() || auth()->user()?->canAccessRouteName('shop-orders.status'));
@endphp
@forelse($orders as $order)
    @php
        $items = collect($order->items ?? []);
        $channel = [
            'app' => ['ti-device-mobile', 'من التطبيق'],
            'whatsapp' => ['ti-brand-whatsapp', 'واتساب'],
            'electronics' => ['ti-world', 'من الموقع'],
            'instant_payment' => ['ti-credit-card', 'دفع فوري'],
        ][$order->order_channel] ?? ['ti-receipt', 'طلب'];
        $delivery = str_contains((string) $order->customer_notes, 'توصيل') || filled($order->customer_address);
        $finished = in_array($order->status, ['delivered', 'completed', 'cancelled'], true);
        $count = (int) $items->sum(fn ($item) => (int) ($item['qty'] ?? 1));
        $orderUrl = route('shop-orders.show', [$shop, $order]);
        // The next natural step for this order, shown as the main button.
        $next = match ($order->status) {
            'new' => ['preparing', 'ti-package', 'ابدأ التجهيز'],
            'preparing' => ['ready', 'ti-check', 'جاهز'],
            'ready' => $delivery ? ['out_for_delivery', 'ti-truck-delivery', 'خرج للتوصيل'] : ['delivered', 'ti-circle-check', 'تم التسليم'],
            'out_for_delivery' => ['delivered', 'ti-circle-check', 'تم التسليم'],
            default => null,
        };
        $moves = \App\Http\Controllers\ShopOrdersController::STATUSES;
        $moves['cancelled'] = 'إلغاء الطلب';
        if (! $delivery) unset($moves['out_for_delivery']);
        unset($moves[$order->status]);
    @endphp
    <article class="order-card order-card-{{ $order->status }} {{ $finished ? 'is-finished' : '' }}" id="shop-order-{{ $order->id }}"
        data-order-id="{{ $order->id }}" data-order-url="{{ $orderUrl }}">
        <a class="oc-head" href="{{ $orderUrl }}" title="فتح تفاصيل الطلب">
            <div class="oc-line1">
                <b class="oc-number">{{ \Illuminate\Support\Str::afterLast($order->order_number, '-') }}</b>
                <span class="oc-type oc-type-{{ $delivery ? 'delivery' : 'pickup' }}"><i class="ti {{ $delivery ? 'ti-truck-delivery' : 'ti-shopping-bag' }}" aria-hidden="true"></i> {{ $delivery ? 'توصيل' : 'استلام' }}</span>
                <span class="oc-status status-tag-{{ $order->status }}"><i aria-hidden="true"></i>{{ \App\Http\Controllers\ShopOrdersController::STATUSES[$order->status] ?? $order->statusLabel() }}</span>
            </div>
            <div class="oc-line2">
                <span class="oc-who">{{ $order->customer_name ?: 'زبون' }}</span>
                <span class="oc-dot">·</span>
                <span>{{ $count }} {{ $count === 1 ? 'قطعة' : 'قطع' }}</span>
                <span class="oc-dot">·</span>
                <span title="{{ $channel[1] }}"><i class="ti {{ $channel[0] }}" aria-hidden="true"></i></span>
                <span title="{{ $order->created_at?->format('Y-m-d H:i') }}">{{ $order->created_at?->isToday() ? $order->created_at?->format('h:i A') : $order->created_at?->format('d/m h:i A') }}</span>
                <b class="oc-total order-total">{{ number_format((float) $order->total, 2) }} ₪</b>
                <i class="ti ti-chevron-left oc-chevron" aria-hidden="true"></i>
            </div>
        </a>

        @if($canManageOrders)
            <form class="status-form quick-status" method="post" action="{{ route('shop-orders.status', $order) }}">
                @csrf @method('patch')
                <div class="quick-actions">
                    @if($next)
                        <button type="submit" name="status" value="{{ $next[0] }}" class="quick-btn quick-ready"><i class="ti {{ $next[1] }}" aria-hidden="true"></i> {{ $next[2] }}</button>
                    @endif
                    <button type="button" class="quick-btn status-menu-toggle" aria-expanded="false"><i class="ti ti-switch-horizontal" aria-hidden="true"></i> تغيير الحالة</button>
                </div>
                <div class="status-menu" hidden>
                    @foreach($moves as $value => $label)
                        <button type="submit" name="status" value="{{ $value }}" class="status-move status-move-{{ $value === 'delivered' ? 'completed' : $value }}">{{ $label }}</button>
                    @endforeach
                </div>
                <span class="status-feedback" role="status" aria-live="polite"></span>
            </form>
        @endif
    </article>
@empty
    <div class="orders-empty"><i class="ti ti-shopping-bag" aria-hidden="true"></i><b>ما في طلبات هون</b><span>جرّب تغيّر الفترة أو الحالة.</span></div>
@endforelse
