@php
    $canManageOrders = $canManageOrders ?? (auth()->user()?->isSuperAdmin() || auth()->user()?->canAccessRouteName('restaurant.orders.status'));
@endphp
@forelse($orders as $order)
    @php
        $items = collect($order->items ?? []);
        $type = [
            'dine_in' => ['ti-armchair', 'طاولة'.($order->restaurantTable ? ' '.$order->restaurantTable->name : '')],
            'delivery' => ['ti-motorbike', 'توصيل'],
            'pickup' => ['ti-shopping-bag', 'استلام'],
        ][$order->order_type] ?? ['ti-receipt', 'طلب'];
        $steps = ['جديد', 'تحضير', 'جاهز', $order->order_type === 'delivery' ? 'توصيل' : 'تسليم'];
        $stepIndex = ['new' => 0, 'preparing' => 1, 'ready' => 2, 'out_for_delivery' => 3, 'completed' => 3][$order->status] ?? 0;
        $driver = $order->restaurantDriver?->user;
        $finished = in_array($order->status, ['completed', 'cancelled'], true);
        $phone = $order->customer_phone ?: $order->customer_whatsapp;
        $count = (int) $items->sum(fn ($item) => (int) ($item['qty'] ?? 1));
        $driverHandles = $order->order_type === 'delivery' && $order->restaurant_driver_id;
        $dueAt = $order->preparationDueAt();
        // Every status the owner can move the order to from the dashboard.
        $moves = array_filter([
            'new' => 'جديد', 'preparing' => 'قيد التحضير', 'ready' => 'جاهز',
            'out_for_delivery' => $order->order_type === 'delivery' ? 'خرج للتوصيل' : null,
            'completed' => $order->order_type === 'delivery' ? 'تم التوصيل' : 'تم التسليم', 'cancelled' => 'إلغاء الطلب',
        ]);
        unset($moves[$order->status]);
    @endphp
    <article class="order-card order-card-{{ $order->status }} {{ $finished ? 'is-finished' : '' }}" id="restaurant-order-{{ $order->id }}"
        data-order-id="{{ $order->id }}" data-order-url="{{ route('restaurant.orders.show', [$shop, $order]) }}">
        <header class="oc-head" role="button" tabindex="0" aria-expanded="false" aria-controls="order-more-{{ $order->id }}" title="اكبس لعرض التفاصيل">
            <div class="oc-line1">
                <b class="oc-number">{{ \Illuminate\Support\Str::afterLast($order->order_number, '-') }}</b>
                <span class="oc-type oc-type-{{ $order->order_type }}"><i class="ti {{ $type[0] }}" aria-hidden="true"></i> {{ $type[1] }}</span>
                <span class="oc-status status-tag-{{ $order->status }}"><i aria-hidden="true"></i>{{ $order->statusLabel() }}</span>
            </div>
            <div class="oc-line2">
                <span class="oc-who">{{ $order->customer_name ?: 'زبون' }}</span>
                <span class="oc-dot">·</span>
                <span>{{ $count }} {{ $count === 1 ? 'وجبة' : 'وجبات' }}</span>
                <span class="oc-dot">·</span>
                <span title="{{ $order->created_at?->format('Y-m-d H:i') }}">{{ $order->created_at?->format('h:i A') }}</span>
                <b class="oc-total order-total">{{ number_format((float) $order->total, 2) }} ₪</b>
                <i class="ti ti-chevron-down oc-chevron" aria-hidden="true"></i>
            </div>
        </header>

        <div class="oc-more" id="order-more-{{ $order->id }}" hidden>
            <div class="oc-meta">
                <span><i class="ti ti-clock" aria-hidden="true"></i> {{ $order->created_at?->copy()->locale('ar')->diffForHumans() }}</span>
                <span><i class="ti ti-hash" aria-hidden="true"></i> {{ $order->order_number }}</span>
                @if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" dir="ltr"><i class="ti ti-phone" aria-hidden="true"></i> {{ $phone }}</a>@endif
                @if($driver)<span><i class="ti ti-motorbike" aria-hidden="true"></i> المندوب: {{ $driver->name }}</span>@endif
            </div>
            @if($order->order_type === 'delivery' && $order->customer_address)
                <div class="oc-address"><i class="ti ti-map-pin" aria-hidden="true"></i> {{ $order->customer_address }}</div>
            @endif
            <div class="oc-items order-meals">
                @include('admin.restaurant.partials.purchase_reward')
                @forelse($items as $item)
                    <div class="oc-item"><b>{{ $item['qty'] ?? 1 }}×</b> <span>{{ $item['name'] ?? 'وجبة' }}@if(!empty($item['choices']))<small>{{ \App\Services\MealChoiceService::summary($item['choices']) }}</small>@endif</span></div>
                @empty
                    <span class="oc-muted">لا توجد وجبات مسجلة</span>
                @endforelse
                @if($order->reward_discount_type === 'gift')<div class="oc-gift">🎁 {{ $order->reward_label }} — هدية مع الطلب</div>@endif
                @if($order->customer_notes)<div class="oc-note"><i class="ti ti-message-2" aria-hidden="true"></i> {{ $order->customer_notes }}</div>@endif
            </div>
            @if($order->status !== 'cancelled')
                <ol class="oc-steps" aria-label="مراحل الطلب">
                    @foreach($steps as $i => $label)
                        <li class="{{ $i < $stepIndex || ($i === $stepIndex && $order->status === 'completed') ? 'done' : ($i === $stepIndex ? 'current' : '') }}">{{ $label }}</li>
                    @endforeach
                </ol>
            @endif
            <a class="oc-details" href="{{ route('restaurant.orders.show', [$shop, $order]) }}">صفحة الطلب كاملة <i class="ti ti-chevron-left" aria-hidden="true"></i></a>
        </div>

        @if($canManageOrders)
            <form class="status-form quick-status" method="post" action="{{ route('restaurant.orders.status', $order) }}">
                @csrf @method('patch')
                <input type="hidden" name="row" value="list">
                <input type="hidden" name="estimated_preparation_minutes" value="{{ $order->estimated_preparation_minutes }}">

                @if($order->status === 'preparing' && $dueAt)
                    <div class="prep-timer" data-due-at="{{ $dueAt->toIso8601String() }}" role="timer" aria-live="off">
                        <i class="ti ti-hourglass-high" aria-hidden="true"></i>
                        <b class="prep-timer-value">--:--</b>
                        <span class="prep-timer-chosen">{{ $order->estimated_preparation_minutes }} د</span>
                        <button type="button" class="quick-link prep-edit" aria-expanded="false" title="تعديل الوقت"><i class="ti ti-pencil" aria-hidden="true"></i> الوقت</button>
                    </div>
                @endif

                <div class="quick-prep" @if($order->status !== 'new') hidden @endif>
                    <small>{{ $order->status === 'new' ? 'اختار وقت التحضير وابدأ:' : 'اختار وقت التحضير:' }}</small>
                    <div class="preparation-options" role="group" aria-label="وقت تحضير الطلب {{ $order->order_number }}">
                        @foreach([10, 15, 20, 25, 30, 40, 45, 60] as $minutes)
                            <button type="button" class="preparation-choice {{ (int) $order->estimated_preparation_minutes === $minutes && $order->status === 'preparing' ? 'is-selected' : '' }}" data-preparation-minutes="{{ $minutes }}" aria-pressed="false"><b>{{ $minutes }}</b><small>د</small></button>
                        @endforeach
                    </div>
                </div>

                <div class="quick-actions">
                    @if($order->status === 'preparing')
                        <button type="submit" name="status" value="ready" class="quick-btn quick-ready"><i class="ti ti-check" aria-hidden="true"></i> جاهز</button>
                    @elseif($order->status === 'ready' && ! $driverHandles)
                        <button type="submit" name="status" value="completed" class="quick-btn quick-ready"><i class="ti ti-circle-check" aria-hidden="true"></i> {{ $order->order_type === 'delivery' ? 'تم التوصيل' : 'تم التسليم' }}</button>
                    @elseif($order->status === 'ready')
                        <span class="oc-wait"><i class="ti ti-motorbike" aria-hidden="true"></i> <span>بانتظار المندوب {{ $driver?->name }}</span></span>
                    @elseif($order->status === 'out_for_delivery')
                        <span class="oc-wait"><i class="ti ti-motorbike" aria-hidden="true"></i> <span>مع المندوب {{ $driver?->name }}</span></span>
                    @endif
                    <button type="button" class="quick-btn status-menu-toggle" aria-expanded="false"><i class="ti ti-switch-horizontal" aria-hidden="true"></i> تغيير الحالة</button>
                </div>
                <div class="status-menu" hidden>
                    @foreach($moves as $value => $label)
                        <button type="submit" name="status" value="{{ $value }}" class="status-move status-move-{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
                <button type="submit" class="status-save" name="status" value="preparing" hidden></button>
                <span class="status-feedback" role="status" aria-live="polite"></span>
            </form>
        @endif
    </article>
@empty
    <div class="orders-empty"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i><b>ما في طلبات هون</b><span>جرّب تغيّر الفترة أو نوع الطلب أو الحالة.</span></div>
@endforelse
