<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>طلب {{ $order->order_number }} · {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08dcf4;--green:#25df87;--gold:#ffe29a;--panel:#0d171f;--border:rgba(139,160,179,.2);--muted:#9ba7b4}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 83% 8%,rgba(0,222,244,.12),transparent 31%),radial-gradient(circle at 8% 18%,rgba(118,44,255,.13),transparent 29%),linear-gradient(135deg,#080313,#02080a 65%,#071118);color:#f7f9fb;font-family:Cairo,Arial,sans-serif}.order-page{margin:0 245px 0 0;padding:26px clamp(18px,2.2vw,36px) 52px}.glass{border:1px solid var(--border);background:linear-gradient(135deg,rgba(25,23,39,.91),rgba(11,25,28,.9));box-shadow:0 20px 70px rgba(0,0,0,.25);backdrop-filter:blur(15px)}.hero{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:25px 28px;border-radius:24px}.eyebrow{color:var(--cyan);font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:6px 0;font-size:clamp(24px,3vw,34px)}.hero p{margin:0;color:var(--muted);font-size:12px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:43px;padding:10px 14px;border:1px solid var(--border);border-radius:13px;background:#0b1720;color:#fff;font:800 12px Cairo,Arial,sans-serif;text-decoration:none}.layout{display:grid;grid-template-columns:minmax(0,1fr) 315px;gap:18px;margin-top:18px}.panel{padding:20px;border-radius:22px}.panel h2{margin:0 0 14px;font-size:19px}.item{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:14px 0;border-bottom:1px solid rgba(139,160,179,.15)}.item:last-child{border-bottom:0}.item h3{margin:0;font-size:16px}.item p{margin:4px 0 0;color:var(--muted);font-size:11px}.qty{display:grid;place-items:center;min-width:36px;height:36px;border-radius:11px;background:rgba(8,220,244,.12);color:var(--cyan);font-size:12px;font-weight:900}.price{color:var(--gold);white-space:nowrap;font-size:16px;font-weight:900}.info{display:grid;gap:11px}.info-row{padding:11px 13px;border:1px solid var(--border);border-radius:13px;background:rgba(3,8,12,.42)}.info-row span{display:block;margin-bottom:3px;color:var(--cyan);font-size:10px;font-weight:900}.info-row b{font-size:13px}.tag{display:inline-flex;padding:6px 10px;border:1px solid rgba(8,220,244,.3);border-radius:999px;background:rgba(8,220,244,.1);color:var(--cyan);font-size:11px;font-weight:900}.total{display:flex;align-items:center;justify-content:space-between;margin-top:15px;padding-top:15px;border-top:1px solid var(--border);font-size:16px;font-weight:900}.total strong{color:var(--gold);font-size:22px}.empty{color:var(--muted);font-size:13px}.order-side{display:grid;align-content:start;gap:18px}.order-status-form{display:grid;gap:12px}.order-status-form h2{margin-bottom:0}.order-status-form label,.order-status-form legend{color:var(--cyan);font-size:11px;font-weight:900}.order-status-select{width:100%;padding:11px 13px;border:1px solid var(--border);border-radius:13px;outline:0;background:rgba(3,8,12,.62);color:#fff;font:700 13px Cairo,Arial,sans-serif}.order-status-select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(8,220,244,.1)}.order-preparation-picker{margin:0;padding:13px 0 0;border:0;border-top:1px solid var(--border)}.order-preparation-options{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:7px;margin-top:9px}.order-preparation-choice{display:grid;place-items:center;gap:0;min-height:51px;padding:5px 2px;border:1px solid rgba(139,160,179,.2);border-radius:50%;aspect-ratio:1;background:rgba(3,8,12,.55);color:#a9b8c5;cursor:pointer;font-family:Cairo,Arial,sans-serif;transition:.2s}.order-preparation-choice b{font-size:14px;line-height:1}.order-preparation-choice small{font-size:9px;font-weight:800}.order-preparation-choice:hover,.order-preparation-choice.is-selected{border-color:var(--cyan);background:var(--cyan);box-shadow:0 7px 20px rgba(8,220,244,.26);color:#022027}.order-status-save{width:100%;border:0;border-radius:13px;min-height:45px;background:linear-gradient(90deg,#08cbe4,#21baf2);color:#03141a;cursor:pointer;font:900 14px Cairo,Arial,sans-serif}.order-status-save:disabled{opacity:.62;cursor:wait}.order-status-help,.order-status-feedback{margin:0;color:var(--muted);font-size:10px;line-height:1.7}.order-status-feedback{min-height:17px}.order-status-feedback.is-success{color:var(--green)}.order-status-feedback.is-error{color:#ff9aac}@media(max-width:900px){.order-page{margin:0;padding:18px 14px 100px}.hero{align-items:flex-start;flex-direction:column;padding:22px}.layout{grid-template-columns:1fr}}@media(max-width:520px){.hero h1{font-size:24px}.panel{padding:16px}.item{align-items:flex-start}.price{font-size:14px}.order-preparation-options{grid-template-columns:repeat(6,minmax(0,1fr));gap:6px}.order-preparation-choice{min-height:45px}.order-preparation-choice b{font-size:12px}}
        .status-panel h2{margin-bottom:6px}.status-now{display:flex;align-items:center;gap:8px;margin-bottom:12px;color:var(--muted);font-size:12px;font-weight:800}
        .status-moves{display:grid;grid-template-columns:1fr 1fr;gap:8px}.status-moves button{min-height:44px;border:1px solid var(--border);border-radius:12px;background:rgba(3,8,12,.55);color:#e8f6ff;font:800 13px Cairo,Arial,sans-serif;cursor:pointer}
        .status-moves button:hover{border-color:var(--cyan);color:var(--cyan)}.status-moves button.is-current{border-color:var(--cyan);background:rgba(8,220,244,.14);color:var(--cyan);cursor:default}
        .status-moves .move-cancelled{grid-column:1/-1;color:#ff9aac;border-color:rgba(255,98,116,.35)}
        .notice{margin-top:14px;padding:12px 16px;border-radius:14px;font-size:13px;font-weight:800}.notice-ok{border:1px solid rgba(37,223,135,.35);background:rgba(37,223,135,.08);color:var(--green)}.notice-err{border:1px solid rgba(255,98,116,.35);background:rgba(255,98,116,.08);color:#ff9aac}
        .info-row a{color:var(--cyan);text-decoration:none}
        .od-head{display:flex;align-items:center;gap:14px;padding:16px 20px;border-radius:20px}
        .od-back{flex:0 0 auto;display:grid;place-items:center;width:42px;height:42px;border-radius:12px;border:1px solid var(--border);background:#0b1720;color:#fff;font-size:20px;text-decoration:none}
        .od-title{min-width:0}.od-title h1{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0;font-size:clamp(20px,2.4vw,28px);line-height:1.3}
        .od-title p{margin:2px 0 0;color:var(--muted);font-size:12px;font-weight:700}
        .od-grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;grid-template-areas:"items actions" "items customer";align-items:start;gap:14px;margin-top:14px}
        .od-grid>*,.od-actions form{min-width:0}.od-actions{grid-area:actions;display:grid;grid-template-columns:minmax(0,1fr);gap:14px}.od-customer{grid-area:customer}.od-items{grid-area:items}
        .od-grid .panel{padding:16px;border-radius:18px}
        .od-label{margin-bottom:8px;color:var(--cyan);font-size:12px;font-weight:900}
        .od-chips{display:flex;gap:6px;overflow-x:auto;scrollbar-width:none;padding-bottom:2px}.od-chips::-webkit-scrollbar{display:none}
        .od-chips .order-preparation-choice{flex:0 0 auto;display:inline-flex;align-items:baseline;gap:2px;min-height:38px;padding:0 11px;aspect-ratio:auto;border-radius:11px;font-size:14px;font-weight:900}
        .od-chips .order-preparation-choice small{font-size:10px}
        .status-moves{grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}.status-moves button{min-height:40px;font-size:12.5px;border-radius:11px}
        .status-moves .move-cancelled{grid-column:auto}
        .od-info{display:grid;gap:8px;margin:0}.od-info div{display:flex;gap:10px;align-items:flex-start}
        .od-info dt{flex:0 0 auto;color:var(--cyan);font-size:17px;line-height:1.4}.od-info dd{margin:0;font-size:14px;font-weight:700;line-height:1.6;overflow-wrap:anywhere}
        .od-info a{color:var(--cyan);text-decoration:none}.od-note dd{color:var(--gold)}
        .od-items .item{display:flex;align-items:flex-start;gap:10px;padding:10px 0}.od-items .qty{flex:0 0 auto;min-width:32px;height:30px;border-radius:9px}
        .item-name{flex:1;min-width:0}.od-items .item h3{font-size:15px}.od-items .item p{margin:2px 0 0}
        .od-items .price{font-size:14px}
        .od-gift{margin-top:10px;color:#51e4b0;font-size:13px}.od-gift img{display:block;width:90px;height:90px;object-fit:contain;background:#fff;border-radius:10px;margin-top:8px}
        .total{margin-top:10px;padding-top:10px}.total.small{margin-top:4px;padding-top:4px;border:0;font-size:13px;color:var(--muted)}
        @media(max-width:900px){
            .order-page{padding:12px 12px 100px}
            .od-head{padding:12px 14px;gap:10px;border-radius:16px}.od-back{width:38px;height:38px}
            .od-grid{grid-template-columns:minmax(0,1fr);grid-template-areas:"actions" "customer" "items";gap:10px;margin-top:10px}
            .od-grid .panel{padding:13px;border-radius:16px}
        }
    </style>
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
