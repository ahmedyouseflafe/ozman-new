@forelse($orders as $order)
    <tr id="restaurant-order-{{ $order->id }}" data-order-id="{{ $order->id }}">
        <td data-label="الطلب">{{ $order->order_number }}<br><small>{{ $order->created_at }}</small></td>
        <td data-label="المصدر"><span class="tag">{{ ['dine_in'=>'طلب طاولة','delivery'=>'توصيل','pickup'=>'استلام'][$order->order_type] ?? $order->order_type }}</span></td>
        <td data-label="الزبون / الطاولة">
            @if($order->restaurantTable)
                <strong><i class="ti ti-table"></i> {{ $order->restaurantTable->name }}</strong>
                <br><small>العميل: {{ $order->customer_name }}</small>
            @else
                {{ $order->customer_name }}
            @endif
            <br>{{ $order->customer_phone }}
            @if($order->order_type === 'delivery' && $order->map_link)<br><a href="{{ $order->map_link }}" target="_blank" rel="noopener"><i class="ti ti-map-pin"></i> موقع التوصيل</a>@endif
        </td>
        <td data-label="تفاصيل الوجبات">
            @foreach($order->items ?? [] as $item)
                <div>
                    <b>{{ $item['qty'] }}× {{ $item['name'] }}</b> {{ $item['size'] ?? '' }}<br>
                    <small>إضافات: {{ implode('، ',$item['addons'] ?? []) ?: '-' }} | بدون: {{ implode('، ',$item['excluded'] ?? []) ?: '-' }} {{ $item['notes'] ?? '' }}
                        
                    </small>
                </div>
            @endforeach
            @if($order->estimated_preparation_minutes)
                <div class="tag" style="margin-top:8px"><i class="ti ti-clock"></i> تجهيز متوقع: {{ $order->estimated_preparation_minutes }} دقيقة</div>
            @endif
        </td>
        <td data-label="المجموع">{{ $order->total }} ₪</td>
        <td data-label="الحالة">
            @if($canManageOrders)
                <form class="status-form" method="post" action="{{ route('restaurant.orders.status',$order) }}">
                    @csrf @method('patch')
                    @php
                        $statusLabels = ['new'=>'جديد','preparing'=>'قيد التحضير','ready'=>'جاهز','completed'=>'مكتمل','cancelled'=>'ملغي'];
                        $allowedTransitions = [
                            'new' => ['preparing', 'cancelled'],
                            'preparing' => ['ready', 'cancelled'],
                            'ready' => ['completed', 'cancelled'],
                            'completed' => [],
                            'cancelled' => [],
                        ][$order->status] ?? [];
                    @endphp
                    <div class="status-choices" role="group" aria-label="حالة الطلب {{ $order->order_number }}">
                        @foreach($statusLabels as $key => $label)
                            <button type="submit" name="status" value="{{ $key }}"
                                class="status-choice status-choice-{{ $key }} {{ $order->status === $key ? 'is-current' : '' }}"
                                aria-label="{{ $label }}{{ $order->status === $key ? '، الحالة الحالية' : '' }}"
                                aria-pressed="{{ $order->status === $key ? 'true' : 'false' }}"
                                @disabled(!in_array($key, $allowedTransitions, true))>{{ $label }}</button>
                        @endforeach
                    </div>
                    <label class="status-field">
                        <span><i class="ti ti-clock-hour-4"></i> وقت التجهيز</span>
                        <input class="field" name="estimated_preparation_minutes" type="number" min="1" max="1440"
                            value="{{ $order->estimated_preparation_minutes }}" placeholder="مثال: 25" inputmode="numeric"
                            aria-label="مدة تجهيز الطلب {{ $order->order_number }} بالدقائق">
                    </label>
                    <small class="status-help">الدقائق التي ستظهر للعميل في شاشة تتبّع طلبه.</small>
                    <button class="btn btn-primary status-save" type="submit" name="status" value="{{ $order->status }}"><i class="ti ti-device-floppy"></i> حفظ وإشعار العميل</button>
                    <span class="status-feedback" role="status" aria-live="polite"></span>
                </form>
            @else
                <span class="tag">{{ ['new'=>'جديد','preparing'=>'قيد التحضير','ready'=>'جاهز','completed'=>'مكتمل','cancelled'=>'ملغي'][$order->status] ?? $order->status }}</span>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="6">لا توجد طلبات.</td></tr>
@endforelse
