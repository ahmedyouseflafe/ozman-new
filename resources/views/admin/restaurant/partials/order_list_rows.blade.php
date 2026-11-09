@php
    $canManageOrders = $canManageOrders ?? (auth()->user()?->isSuperAdmin() || auth()->user()?->canAccessRouteName('restaurant.orders.status'));
@endphp
@forelse($orders as $order)
    @php
        $items = collect($order->items ?? []);
        $type = [
            'dine_in' => ['ti-armchair', 'طلب طاولة'.($order->restaurantTable ? ' · '.$order->restaurantTable->name : '')],
            'delivery' => ['ti-motorbike', 'توصيل'],
            'pickup' => ['ti-shopping-bag', 'استلام من المطعم'],
        ][$order->order_type] ?? ['ti-receipt', 'طلب مطعم'];
        $steps = ['new' => 'جديد', 'preparing' => 'تحضير', 'ready' => 'جاهز', 'done' => $order->order_type === 'delivery' ? 'توصيل' : 'تسليم'];
        $stepIndex = ['new' => 0, 'preparing' => 1, 'ready' => 2, 'out_for_delivery' => 3, 'completed' => 3][$order->status] ?? 0;
        $driver = $order->restaurantDriver?->user;
        $finished = in_array($order->status, ['completed', 'cancelled'], true);
        $phone = $order->customer_phone ?: $order->customer_whatsapp;
    @endphp
    <article class="order-card order-card-{{ $order->status }} {{ $finished ? 'is-finished' : '' }}" id="restaurant-order-{{ $order->id }}"
        data-order-id="{{ $order->id }}" data-order-url="{{ route('restaurant.orders.show', [$shop, $order]) }}">
        <header class="oc-head">
            <div class="oc-id">
                <a class="order-number-link" href="{{ route('restaurant.orders.show', [$shop, $order]) }}">{{ $order->order_number }}</a>
                <span class="oc-type oc-type-{{ $order->order_type }}"><i class="ti {{ $type[0] }}" aria-hidden="true"></i> {{ $type[1] }}</span>
            </div>
            <span class="oc-status status-tag-{{ $order->status }}"><i aria-hidden="true"></i>{{ $order->statusLabel() }}</span>
        </header>

        <div class="oc-meta">
            <span title="{{ $order->created_at?->format('Y-m-d H:i') }}"><i class="ti ti-clock" aria-hidden="true"></i> {{ $order->created_at?->copy()->locale('ar')->diffForHumans() }} · {{ $order->created_at?->format('h:i A') }}</span>
            @if($order->customer_name)<span><i class="ti ti-user" aria-hidden="true"></i> {{ $order->customer_name }}</span>@endif
            @if($phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" dir="ltr"><i class="ti ti-phone" aria-hidden="true"></i> {{ $phone }}</a>@endif
        </div>
        @if($order->order_type === 'delivery' && $order->customer_address)
            <div class="oc-address"><i class="ti ti-map-pin" aria-hidden="true"></i> {{ $order->customer_address }}</div>
        @endif

        <div class="oc-items order-meals">
            @include('admin.restaurant.partials.purchase_reward')
            @forelse($items->take(3) as $item)
                <div class="oc-item"><b>{{ $item['qty'] ?? 1 }}×</b> <span>{{ $item['name'] ?? 'وجبة' }}@if(!empty($item['choices']))<small>{{ \App\Services\MealChoiceService::summary($item['choices']) }}</small>@endif</span></div>
            @empty
                <span class="oc-muted">لا توجد وجبات مسجلة</span>
            @endforelse
            @if($items->count() > 3)<a class="more-items" href="{{ route('restaurant.orders.show', [$shop, $order]) }}">+ {{ $items->count() - 3 }} وجبات أخرى</a>@endif
            @if($order->reward_discount_type === 'gift')<div class="oc-gift">🎁 {{ $order->reward_label }} — هدية مع الطلب</div>@endif
            @if($order->customer_notes)<div class="oc-note"><i class="ti ti-message-2" aria-hidden="true"></i> {{ \Illuminate\Support\Str::limit($order->customer_notes, 140) }}</div>@endif
        </div>

        @if($order->status !== 'cancelled')
            <ol class="oc-steps" aria-label="مراحل الطلب">
                @foreach(array_values($steps) as $i => $label)
                    <li class="{{ $i < $stepIndex || ($i === $stepIndex && $order->status === 'completed') ? 'done' : ($i === $stepIndex ? 'current' : '') }}">{{ $label }}</li>
                @endforeach
            </ol>
        @endif

        <footer class="oc-foot">
            <div class="oc-total order-total"><small>المجموع</small>{{ number_format((float) $order->total, 2) }} ₪</div>
            <a class="oc-details" href="{{ route('restaurant.orders.show', [$shop, $order]) }}">التفاصيل <i class="ti ti-chevron-left" aria-hidden="true"></i></a>
        </footer>

        @if($canManageOrders && in_array($order->status, ['new', 'preparing', 'ready'], true))
            @php
                $dueAt = $order->preparationDueAt();
                $driverHandles = $order->order_type === 'delivery' && $order->restaurant_driver_id;
            @endphp
            <form class="status-form quick-status" method="post" action="{{ route('restaurant.orders.status', $order) }}">
                @csrf @method('patch')
                <input type="hidden" name="row" value="list">
                <input type="hidden" name="estimated_preparation_minutes" value="{{ $order->estimated_preparation_minutes }}">

                @if($order->status === 'preparing' && $dueAt)
                    <div class="oc-timer-row">
                        <div class="prep-timer" data-due-at="{{ $dueAt->toIso8601String() }}" role="timer" aria-live="off">
                            <i class="ti ti-hourglass-high" aria-hidden="true"></i>
                            <b class="prep-timer-value">{{ gmdate('i:s', max(0, now()->diffInSeconds($dueAt, false))) }}</b>
                            <span class="prep-timer-chosen">{{ $order->estimated_preparation_minutes }} د</span>
                        </div>
                        <button type="button" class="quick-link prep-edit" aria-expanded="false"><i class="ti ti-pencil" aria-hidden="true"></i> تعديل الوقت</button>
                    </div>
                @endif

                @if($order->status !== 'ready')
                    <div class="quick-prep" @if($order->status === 'preparing') hidden @endif>
                        <small>{{ $order->status === 'new' ? 'اختار وقت التحضير وابدأ:' : 'اختار وقت جديد:' }}</small>
                        <div class="preparation-options" role="group" aria-label="وقت تحضير الطلب {{ $order->order_number }}">
                            @foreach([10, 15, 20, 25, 30, 40, 45, 60] as $minutes)
                                <button type="button" class="preparation-choice {{ (int) $order->estimated_preparation_minutes === $minutes && $order->status === 'preparing' ? 'is-selected' : '' }}" data-preparation-minutes="{{ $minutes }}" aria-pressed="false"><b>{{ $minutes }}</b><small>د</small></button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="quick-actions">
                    @if($order->status === 'preparing')
                        <button type="submit" name="status" value="ready" class="quick-btn quick-ready"><i class="ti ti-check" aria-hidden="true"></i> الطلب جاهز</button>
                    @elseif($order->status === 'ready' && ! $driverHandles)
                        <button type="submit" name="status" value="completed" class="quick-btn quick-ready"><i class="ti ti-circle-check" aria-hidden="true"></i> {{ $order->order_type === 'delivery' ? 'تم التوصيل' : 'تم التسليم للزبون' }}</button>
                    @elseif($order->status === 'ready')
                        <div class="oc-wait"><i class="ti ti-motorbike" aria-hidden="true"></i> <span>بانتظار المندوب <b>{{ $driver?->name ?? '' }}</b> يستلم الطلب ويوصله</span></div>
                    @endif
                    <button type="submit" name="status" value="cancelled" class="quick-cancel"><i class="ti ti-x" aria-hidden="true"></i> إلغاء الطلب</button>
                </div>
                @if($order->status === 'ready' && $order->order_type === 'delivery' && ! $driverHandles)
                    <small class="oc-hint">ما في مندوب على الطلب: لما يوصل، اكبس «تم التوصيل». أو عيّن مندوب من التفاصيل.</small>
                @endif
                <button type="submit" class="status-save" name="status" value="preparing" hidden></button>
                <span class="status-feedback" role="status" aria-live="polite"></span>
            </form>
        @elseif($order->status === 'out_for_delivery')
            <div class="oc-wait"><i class="ti ti-motorbike" aria-hidden="true"></i> <span>مع المندوب <b>{{ $driver?->name ?? '' }}</b> بالطريق للزبون</span></div>
        @endif
    </article>
@empty
    <div class="orders-empty"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i><b>ما في طلبات هون</b><span>جرّب تغيّر الفترة أو نوع الطلب أو الحالة.</span></div>
@endforelse
