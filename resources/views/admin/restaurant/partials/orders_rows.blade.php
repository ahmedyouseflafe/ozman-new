@forelse($orders as $order)
    <tr data-order-id="{{ $order->id }}">
        <td>{{ $order->order_number }}<br><small>{{ $order->created_at }}</small></td>
        <td><span class="tag">{{ ['dine_in'=>'طلب طاولة','delivery'=>'توصيل','pickup'=>'استلام'][$order->order_type] ?? $order->order_type }}</span></td>
        <td>
            @if($order->restaurantTable)
                <strong><i class="ti ti-table"></i> {{ $order->restaurantTable->name }}</strong>
                <br><small>العميل: {{ $order->customer_name }}</small>
            @else
                {{ $order->customer_name }}
            @endif
            <br>{{ $order->customer_phone }}
            @if($order->order_type === 'delivery' && $order->map_link)<br><a href="{{ $order->map_link }}" target="_blank" rel="noopener"><i class="ti ti-map-pin"></i> موقع التوصيل</a>@endif
        </td>
        <td>
            @foreach($order->items ?? [] as $item)
                <div>
                    <b>{{ $item['qty'] }}× {{ $item['name'] }}</b> {{ $item['size'] ?? '' }}<br>
                    <small>إضافات: {{ implode('، ',$item['addons'] ?? []) ?: '-' }} | بدون: {{ implode('، ',$item['excluded'] ?? []) ?: '-' }} {{ $item['notes'] ?? '' }}</small>
                </div>
            @endforeach
        </td>
        <td>{{ $order->total }} ₪</td>
        <td>
            @if($canManageOrders)
                <form class="status-form" method="post" action="{{ route('restaurant.orders.status',$order) }}">
                    @csrf @method('patch')
                    <select class="field" name="status">
                        @foreach(['new'=>'جديد','preparing'=>'قيد التحضير','ready'=>'جاهز','completed'=>'مكتمل','cancelled'=>'ملغي'] as $key=>$label)
                            <option value="{{ $key }}" @selected($order->status===$key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary">حفظ</button>
                </form>
            @else
                <span class="tag">{{ ['new'=>'جديد','preparing'=>'قيد التحضير','ready'=>'جاهز','completed'=>'مكتمل','cancelled'=>'ملغي'][$order->status] ?? $order->status }}</span>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="6">لا توجد طلبات.</td></tr>
@endforelse
