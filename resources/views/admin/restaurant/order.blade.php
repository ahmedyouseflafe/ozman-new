<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>طلب {{ $order->order_number }} · {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.restaurant.order')
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="order-page">
        @php
            $types = ['dine_in' => 'طلب طاولة', 'delivery' => 'توصيل', 'pickup' => 'استلام'];
            $canManage = auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.orders.status');
            $canSetPreparationTime = in_array($order->status, ['new', 'preparing'], true);
            $moves = array_filter(['new' => 'جديد', 'preparing' => 'قيد التحضير', 'ready' => 'جاهز',
                'out_for_delivery' => $order->order_type === 'delivery' ? 'خرج للتوصيل' : null,
                'completed' => $order->order_type === 'delivery' ? 'تم التوصيل' : 'تم التسليم', 'cancelled' => 'إلغاء']);
            $back = url()->previous() !== url()->current() && str_contains(url()->previous(), '/restaurant') && ! str_contains(url()->previous(), '/orders/') ? url()->previous() : route('restaurant.dashboard', $shop);
        @endphp
        <header class="od-head glass">
            <a class="od-back" href="{{ $back }}" aria-label="رجوع للطلبات"><i class="ti ti-arrow-right"></i></a>
            <div class="od-title">
                <h1>طلب {{ \Illuminate\Support\Str::afterLast($order->order_number, '-') }} <span class="tag">{{ $order->statusLabel() }}</span></h1>
                <p>{{ $types[$order->order_type] ?? 'طلب' }}{{ $order->restaurantTable ? ' · '.$order->restaurantTable->name : '' }} · {{ $order->created_at?->copy()->locale('ar')->translatedFormat('l d/m · h:i A') }} · <span dir="ltr">{{ $order->order_number }}</span></p>
            </div>
        </header>
        @if (session('status'))<div class="notice notice-ok"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>@endif
        @if ($errors->any())<div class="notice notice-err">{{ $errors->first() }}</div>@endif

        <div class="od-grid">
            @if($canManage)
                <section class="panel glass od-actions">
                    @if($canSetPreparationTime)
                        <form id="order-status-form" action="{{ route('restaurant.orders.status', $order) }}" method="post">
                            @csrf @method('patch')
                            <input type="hidden" name="status" value="preparing">
                            <input type="hidden" id="preparation-minutes" name="estimated_preparation_minutes" value="{{ $order->estimated_preparation_minutes }}">
                            <div class="od-label"><i class="ti ti-clock"></i> وقت التجهيز {{ $order->status === 'new' ? '— اختار وبيبلّش التحضير' : '' }}</div>
                            <div class="od-chips">
                                @foreach(range(10, 60, 5) as $minutes)
                                    <button type="button" class="order-preparation-choice @if((int) $order->estimated_preparation_minutes === $minutes) is-selected @endif" data-preparation-minutes="{{ $minutes }}">{{ $minutes }}<small>د</small></button>
                                @endforeach
                            </div>
                            <p class="order-status-feedback" id="order-status-feedback" aria-live="polite"></p>
                        </form>
                    @endif
                    <form method="post" action="{{ route('restaurant.orders.status', $order) }}"
                        onsubmit="return event.submitter?.value !== 'cancelled' || confirm('متأكد إنك بدك تلغي هالطلب؟ رح يوصل للزبون إشعار بالإلغاء.')">
                        @csrf @method('patch')
                        <input type="hidden" name="estimated_preparation_minutes" value="{{ $order->estimated_preparation_minutes }}">
                        <div class="od-label"><i class="ti ti-switch-horizontal"></i> تغيير الحالة</div>
                        <div class="status-moves">
                            @foreach($moves as $value => $label)
                                <button type="submit" name="status" value="{{ $value }}" class="move-{{ $value }} {{ $order->status === $value ? 'is-current' : '' }}" @disabled($order->status === $value)>{{ $label }}</button>
                            @endforeach
                        </div>
                    </form>
                </section>
            @endif

            <section class="panel glass od-customer">
                <dl class="od-info">
                    <div><dt><i class="ti ti-user"></i></dt><dd>{{ $order->customer_name ?: '—' }}</dd></div>
                    @if($order->customer_phone)<div><dt><i class="ti ti-phone"></i></dt><dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $order->customer_phone) }}" dir="ltr">{{ $order->customer_phone }}</a></dd></div>@endif
                    @if($order->customer_address)<div><dt><i class="ti ti-map-pin"></i></dt><dd>{{ $order->customer_address }}</dd></div>@endif
                    @if($order->restaurantDriver)<div><dt><i class="ti ti-motorbike"></i></dt><dd>المندوب: {{ $order->restaurantDriver->user?->name }}</dd></div>@endif
                    @if($order->customer_notes)<div class="od-note"><dt><i class="ti ti-message-2"></i></dt><dd>{{ $order->customer_notes }}</dd></div>@endif
                </dl>
            </section>

            <section class="panel glass od-items">
                @forelse($order->items ?? [] as $item)
                    <article class="item"><span class="qty">{{ $item['qty'] ?? 1 }}×</span><div class="item-name"><h3>{{ $item['name'] ?? 'وجبة' }} {{ $item['size'] ?? '' }}</h3>@if(!empty($item['addons']) || !empty($item['excluded']) || !empty($item['notes']))<p>@if(!empty($item['addons'])) إضافات: {{ implode('، ', $item['addons']) }} @endif @if(!empty($item['excluded'])) · بدون: {{ implode('، ', $item['excluded']) }} @endif @if(!empty($item['notes'])) · {{ $item['notes'] }} @endif</p>@endif @if(!empty($item['choices']))<p>{{ \App\Services\MealChoiceService::summary($item['choices']) }}</p>@endif</div><strong class="price">{{ number_format((float) (($item['price'] ?? 0) * ($item['qty'] ?? 1)), 2) }} ₪</strong></article>
                @empty
                    <p class="empty">لا توجد وجبات مسجلة لهذا الطلب.</p>
                @endforelse
                @include('admin.restaurant.partials.purchase_reward')
                @if($order->reward_discount_type === 'gift')
                    <div class="od-gift">🎁 هدية التسجيل: <b>{{ $order->reward_label }}</b>
                        @if($order->reward_gift_image)<img src="{{ asset($order->reward_gift_image) }}" alt="{{ $order->reward_label }}">@endif
                    </div>
                @endif
                @if((float) $order->discount > 0)
                    <div class="total small"><span>قبل الخصم</span><span>{{ number_format((float) $order->subtotal, 2) }} ₪</span></div>
                    <div class="total small"><span>خصم التسجيل</span><span>−{{ number_format((float) $order->discount, 2) }} ₪</span></div>
                @endif
                <div class="total"><span>المجموع</span><strong>{{ number_format((float) $order->total, 2) }} ₪</strong></div>
            </section>
        </div>
    </main>
    @viewScript('admin.restaurant.order')
</body>
</html>
