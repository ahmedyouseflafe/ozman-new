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
    @viewStyle('admin.shop_orders.order')
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="order-page">
        @php
            $statuses = \App\Http\Controllers\ShopOrdersController::STATUSES;
            $canManage = auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('shop-orders.status');
            $delivery = str_contains((string) $order->customer_notes, 'توصيل') || filled($order->customer_address);
            $moves = $statuses;
            $moves['cancelled'] = 'إلغاء';
            if (! $delivery) unset($moves['out_for_delivery']);
            $channels = ['app' => 'من التطبيق', 'whatsapp' => 'واتساب', 'electronics' => 'من الموقع', 'instant_payment' => 'دفع فوري'];
            $previous = url()->previous();
            $back = $previous !== url()->current() && str_contains($previous, '/orders-board') && ! str_contains($previous, '/orders-board/') ? $previous : route('shop-orders.dashboard', $shop);
            $phone = $order->customer_phone ?: $order->customer_whatsapp;
            $whatsapp = $phone ? preg_replace('/\D/', '', $phone) : null;
            if ($whatsapp && str_starts_with($whatsapp, '05')) $whatsapp = '972'.substr($whatsapp, 1);
        @endphp
        <header class="od-head glass">
            <a class="od-back" href="{{ $back }}" aria-label="رجوع للطلبات"><i class="ti ti-arrow-right"></i></a>
            <div class="od-title">
                <h1>طلب {{ \Illuminate\Support\Str::afterLast($order->order_number, '-') }} <span class="tag">{{ $statuses[$order->status] ?? $order->statusLabel() }}</span></h1>
                <p>{{ $delivery ? 'توصيل' : 'استلام من المحل' }} · {{ $channels[$order->order_channel] ?? 'طلب' }} · {{ $order->created_at?->copy()->locale('ar')->translatedFormat('l d/m · h:i A') }} · <span dir="ltr">{{ $order->order_number }}</span></p>
            </div>
        </header>
        @if (session('status'))<div class="notice notice-ok"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>@endif
        @if ($errors->any())<div class="notice notice-err">{{ $errors->first() }}</div>@endif

        <div class="od-grid">
            @if($canManage)
                <section class="panel glass od-actions">
                    <form method="post" action="{{ route('shop-orders.status', $order) }}"
                        onsubmit="return event.submitter?.value !== 'cancelled' || confirm('متأكد إنك بدك تلغي هالطلب؟ رح يوصل للزبون إشعار بالإلغاء.')">
                        @csrf @method('patch')
                        <div class="od-label"><i class="ti ti-switch-horizontal"></i> تغيير الحالة — بيوصل للزبون إشعار</div>
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
                    @if($phone)<div><dt><i class="ti ti-phone"></i></dt><dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" dir="ltr">{{ $phone }}</a>@if($whatsapp) · <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener"><i class="ti ti-brand-whatsapp"></i> واتساب</a>@endif</dd></div>@endif
                    @if($order->customer_address)<div><dt><i class="ti ti-map-pin"></i></dt><dd>{{ $order->customer_address }}@if($order->map_link) · <a href="{{ $order->map_link }}" target="_blank" rel="noopener">الخريطة</a>@endif</dd></div>@endif
                    @if($order->customer_notes)<div class="od-note"><dt><i class="ti ti-message-2"></i></dt><dd style="white-space:pre-line">{{ $order->customer_notes }}</dd></div>@endif
                </dl>
            </section>

            <section class="panel glass od-items">
                @forelse($order->items ?? [] as $item)
                    <article class="item">
                        <span class="qty">{{ $item['qty'] ?? 1 }}×</span>
                        <div class="item-name">
                            <h3>{{ $item['name'] ?? 'منتج' }}</h3>
                            @php($specs = array_filter([$item['brand'] ?? null, $item['model'] ?? null, $item['storage'] ?? null, $item['ram'] ?? null, $item['color_name'] ?? null]))
                            @if($specs)<p>{{ implode(' · ', $specs) }}</p>@endif
                        </div>
                        <strong class="price">{{ number_format((float) (($item['price'] ?? 0) * ($item['qty'] ?? 1)), 2) }} ₪</strong>
                    </article>
                @empty
                    <p class="empty">لا توجد منتجات مسجلة لهذا الطلب.</p>
                @endforelse
                {{-- The prize the customer won on the shop's purchase wheel. --}}
                @include('admin.restaurant.partials.purchase_reward')
                @if((float) $order->discount > 0)
                    <div class="total small"><span>قبل الخصم</span><span>{{ number_format((float) $order->subtotal, 2) }} ₪</span></div>
                    <div class="total small"><span>الخصم</span><span>−{{ number_format((float) $order->discount, 2) }} ₪</span></div>
                @endif
                <div class="total"><span>المجموع</span><strong>{{ number_format((float) $order->total, 2) }} ₪</strong></div>
            </section>
        </div>
    </main>
</body>
</html>
