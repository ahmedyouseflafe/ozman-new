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
        :root{--cyan:#08dcf4;--green:#25df87;--gold:#ffe29a;--panel:#0d171f;--border:rgba(139,160,179,.2);--muted:#9ba7b4}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 83% 8%,rgba(0,222,244,.12),transparent 31%),radial-gradient(circle at 8% 18%,rgba(118,44,255,.13),transparent 29%),linear-gradient(135deg,#080313,#02080a 65%,#071118);color:#f7f9fb;font-family:Cairo,Arial,sans-serif}.order-page{margin:0 245px 0 0;padding:26px clamp(18px,2.2vw,36px) 52px}.glass{border:1px solid var(--border);background:linear-gradient(135deg,rgba(25,23,39,.91),rgba(11,25,28,.9));box-shadow:0 20px 70px rgba(0,0,0,.25);backdrop-filter:blur(15px)}.hero{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:25px 28px;border-radius:24px}.eyebrow{color:var(--cyan);font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:6px 0;font-size:clamp(24px,3vw,34px)}.hero p{margin:0;color:var(--muted);font-size:12px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:43px;padding:10px 14px;border:1px solid var(--border);border-radius:13px;background:#0b1720;color:#fff;font:800 12px Cairo,Arial,sans-serif;text-decoration:none}.layout{display:grid;grid-template-columns:minmax(0,1fr) 315px;gap:18px;margin-top:18px}.panel{padding:20px;border-radius:22px}.panel h2{margin:0 0 14px;font-size:19px}.item{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:14px 0;border-bottom:1px solid rgba(139,160,179,.15)}.item:last-child{border-bottom:0}.item h3{margin:0;font-size:16px}.item p{margin:4px 0 0;color:var(--muted);font-size:11px}.qty{display:grid;place-items:center;min-width:36px;height:36px;border-radius:11px;background:rgba(8,220,244,.12);color:var(--cyan);font-size:12px;font-weight:900}.price{color:var(--gold);white-space:nowrap;font-size:16px;font-weight:900}.info{display:grid;gap:11px}.info-row{padding:11px 13px;border:1px solid var(--border);border-radius:13px;background:rgba(3,8,12,.42)}.info-row span{display:block;margin-bottom:3px;color:var(--cyan);font-size:10px;font-weight:900}.info-row b{font-size:13px}.tag{display:inline-flex;padding:6px 10px;border:1px solid rgba(8,220,244,.3);border-radius:999px;background:rgba(8,220,244,.1);color:var(--cyan);font-size:11px;font-weight:900}.total{display:flex;align-items:center;justify-content:space-between;margin-top:15px;padding-top:15px;border-top:1px solid var(--border);font-size:16px;font-weight:900}.total strong{color:var(--gold);font-size:22px}.empty{color:var(--muted);font-size:13px}@media(max-width:900px){.order-page{margin:0;padding:18px 14px 100px}.hero{align-items:flex-start;flex-direction:column;padding:22px}.layout{grid-template-columns:1fr}}@media(max-width:520px){.hero h1{font-size:24px}.panel{padding:16px}.item{align-items:flex-start}.price{font-size:14px}}
    </style>
</head>
<body>
    @include('admin.includes.sidebar')
    <main class="order-page">
        <section class="hero glass">
            <div><span class="eyebrow">ORDER DETAILS</span><h1>طلب {{ $order->order_number }}</h1><p><i class="ti ti-calendar-event"></i> وصل {{ $order->created_at?->copy()->locale('ar')->translatedFormat('l، d/m/Y · h:i A') }}</p></div>
            <a class="btn" href="{{ route('restaurant.dashboard', $shop) }}"><i class="ti ti-arrow-right"></i> رجوع للطلبات</a>
        </section>
        <div class="layout">
            <section class="panel glass">
                <h2><i class="ti ti-receipt-2" style="color:var(--cyan)"></i> الوجبات المطلوبة</h2>
                @forelse($order->items ?? [] as $item)
                    <article class="item"><div style="display:flex;gap:10px"><span class="qty">{{ $item['qty'] ?? 1 }}×</span><div><h3>{{ $item['name'] ?? 'وجبة' }} {{ $item['size'] ?? '' }}</h3><p>@if(!empty($item['addons'])) إضافات: {{ implode('، ', $item['addons']) }} @endif @if(!empty($item['excluded'])) · بدون: {{ implode('، ', $item['excluded']) }} @endif @if(!empty($item['notes'])) · {{ $item['notes'] }} @endif</p></div></div><strong class="price">{{ number_format((float) (($item['price'] ?? 0) * ($item['qty'] ?? 1)), 2) }} ₪</strong></article>
                @empty
                    <p class="empty">لا توجد وجبات مسجلة لهذا الطلب.</p>
                @endforelse
                <div class="total"><span>المجموع</span><strong>{{ number_format((float) $order->total, 2) }} ₪</strong></div>
            </section>
            <aside class="panel glass">
                <h2>بيانات الطلب</h2>
                <div class="info">
                    <div class="info-row"><span>الحالة</span><b><span class="tag">{{ $order->statusLabel() }}</span></b></div>
                    <div class="info-row"><span>نوع الطلب</span><b>{{ ['dine_in' => 'طلب طاولة', 'delivery' => 'توصيل', 'pickup' => 'استلام'][$order->order_type] ?? $order->order_type }}</b></div>
                    <div class="info-row"><span>العميل</span><b>{{ $order->customer_name ?: '—' }}</b></div>
                    <div class="info-row"><span>رقم الجوال</span><b dir="ltr">{{ $order->customer_phone ?: '—' }}</b></div>
                    @if($order->restaurantTable)<div class="info-row"><span>الطاولة</span><b>{{ $order->restaurantTable->name }}</b></div>@endif
                    @if($order->restaurantDriver)<div class="info-row"><span>مندوب التوصيل</span><b>{{ $order->restaurantDriver->user?->name }}</b></div>@endif
                </div>
            </aside>
        </div>
    </main>
</body>
</html>
