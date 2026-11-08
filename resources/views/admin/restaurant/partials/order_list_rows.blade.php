@php($canManageOrders = $canManageOrders ?? (auth()->user()?->isSuperAdmin() || auth()->user()?->canAccessRouteName('restaurant.orders.status')))
@forelse($orders as $order)
    @php($items = collect($order->items ?? []))
    <tr class="order-summary-row" data-order-url="{{ route('restaurant.orders.show', [$shop, $order]) }}" tabindex="0" role="link" aria-label="فتح تفاصيل الطلب {{ $order->order_number }}">
        <td data-label="الطلب">
            <a class="order-number-link" href="{{ route('restaurant.orders.show', [$shop, $order]) }}">{{ $order->order_number }}</a>
            <small><i class="ti ti-calendar-event"></i> {{ $order->created_at?->copy()->locale('ar')->translatedFormat('l، d/m/Y · h:i A') }}</small>
        </td>
        <td data-label="الوجبات" class="order-meals">
            @include('admin.restaurant.partials.purchase_reward')
            @forelse($items->take(2) as $item)
                <div><b>{{ $item['qty'] ?? 1 }}× {{ $item['name'] ?? 'وجبة' }}</b>@if(!empty($item['choices']))<small>{{ \App\Services\MealChoiceService::summary($item['choices']) }}</small>@endif</div>
            @empty
                <span>لا توجد وجبات مسجلة</span>
            @endforelse
            @if($items->count() > 2)<small class="more-items">+ {{ $items->count() - 2 }} وجبات أخرى</small>@endif
            @if($order->reward_discount_type === 'gift')<div style="color:#51e4b0">🎁 {{ $order->reward_label }} — هدية مع الطلب</div>@endif
        </td>
        <td data-label="المجموع" class="order-total">{{ number_format((float) $order->total, 2) }} ₪</td>
        <td data-label="الحالة" class="order-status-cell">
            <span class="tag status-tag status-tag-{{ $order->status }}">{{ $order->statusLabel() }}</span>
            @if($canManageOrders && in_array($order->status, ['new', 'preparing', 'ready'], true))
                @php($dueAt = $order->preparationDueAt())
                <form class="status-form quick-status" method="post" action="{{ route('restaurant.orders.status', $order) }}">
                    @csrf @method('patch')
                    <input type="hidden" name="row" value="list">
                    <input type="hidden" name="estimated_preparation_minutes" value="{{ $order->estimated_preparation_minutes }}">
                    @if($order->status === 'preparing' && $dueAt)
                        <div class="prep-timer" data-due-at="{{ $dueAt->toIso8601String() }}" role="timer" aria-live="off">
                            <i class="ti ti-hourglass-high" aria-hidden="true"></i>
                            <b class="prep-timer-value">{{ gmdate('i:s', max(0, now()->diffInSeconds($dueAt, false))) }}</b>
                            <span class="prep-timer-chosen">{{ $order->estimated_preparation_minutes }} د</span>
                        </div>
                        <button type="button" class="quick-link prep-edit" aria-expanded="false">تعديل الوقت</button>
                    @endif
                    @if($order->status !== 'ready')
                        <div class="quick-prep" @if($order->status === 'preparing') hidden @endif>
                            <small>{{ $order->status === 'new' ? 'اختار وقت التحضير وابدأ:' : 'وقت جديد:' }}</small>
                            <div class="preparation-options" role="group" aria-label="وقت تحضير الطلب {{ $order->order_number }}">
                                @foreach([10, 15, 20, 25, 30, 40, 45, 60] as $minutes)
                                    <button type="button" class="preparation-choice" data-preparation-minutes="{{ $minutes }}" aria-pressed="false"><b>{{ $minutes }}</b><small>د</small></button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="quick-actions">
                        @if($order->status === 'preparing')
                            <button type="submit" name="status" value="ready" class="quick-btn quick-ready"><i class="ti ti-check" aria-hidden="true"></i> جاهز</button>
                        @elseif($order->status === 'ready' && ! ($order->order_type === 'delivery' && $order->restaurant_driver_id))
                            <button type="submit" name="status" value="completed" class="quick-btn quick-ready"><i class="ti ti-circle-check" aria-hidden="true"></i> تم التسليم</button>
                        @endif
                        <button type="submit" name="status" value="cancelled" class="quick-btn quick-cancel">إلغاء</button>
                    </div>
                    <button type="submit" class="status-save" name="status" value="preparing" hidden></button>
                    <span class="status-feedback" role="status" aria-live="polite"></span>
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="4">لا توجد طلبات ضمن الفلترة المختارة.</td></tr>
@endforelse
