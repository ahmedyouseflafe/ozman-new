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
                @if((float) $order->discount > 0)
                    <div class="total"><span>قبل الخصم</span><strong>{{ number_format((float) $order->subtotal, 2) }} ₪</strong></div>
                    <div class="total"><span>خصم التسجيل</span><strong>−{{ number_format((float) $order->discount, 2) }} ₪</strong></div>
                @endif
                <div class="total"><span>المجموع</span><strong>{{ number_format((float) $order->total, 2) }} ₪</strong></div>
            </section>
            <aside class="order-side">
                @php
                    $canSetPreparationTime = in_array($order->status, ['new', 'preparing'], true);
                @endphp
                <form class="panel glass order-status-form" id="order-status-form" action="{{ route('restaurant.orders.status', $order) }}" method="post">
                    @csrf
                    @method('patch')
                    <h2><i class="ti ti-chef-hat" style="color:var(--cyan)"></i> إدارة الطلب</h2>
                    <input type="hidden" name="status" value="preparing">
                    <input type="hidden" id="preparation-minutes" name="estimated_preparation_minutes" value="{{ $order->estimated_preparation_minutes }}">
                    <fieldset class="order-preparation-picker" @disabled(! $canSetPreparationTime)>
                        <legend><i class="ti ti-clock"></i> وقت التجهيز</legend>
                        <div class="order-preparation-options">
                            @foreach(range(10, 60, 5) as $minutes)
                                <button type="button" class="order-preparation-choice @if((int) $order->estimated_preparation_minutes === $minutes) is-selected @endif" data-preparation-minutes="{{ $minutes }}" aria-pressed="{{ (int) $order->estimated_preparation_minutes === $minutes ? 'true' : 'false' }}" @disabled(! $canSetPreparationTime)>
                                    <b>{{ $minutes }}</b><small>د</small>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                    @if($canSetPreparationTime)
                        <p class="order-status-help">حدد وقت التجهيز؛ يصبح الطلب قيد التحضير ويصل طلب استلام لكل مندوبي التوصيل.</p>
                        <button class="order-status-save" type="submit"><i class="ti ti-send"></i> إرسال للمندوبين وإشعار العميل</button>
                    @else
                        <p class="order-status-help">تم تحديد وقت التجهيز لهذا الطلب، ولا يمكن تغييره بعد انتقاله للمرحلة التالية.</p>
                    @endif
                    <p class="order-status-feedback" id="order-status-feedback" aria-live="polite"></p>
                </form>
                <section class="panel glass">
                <h2>بيانات الطلب</h2>
                <div class="info">
                    <div class="info-row"><span>الحالة</span><b><span class="tag">{{ $order->statusLabel() }}</span></b></div>
                    <div class="info-row"><span>نوع الطلب</span><b>{{ ['dine_in' => 'طلب طاولة', 'delivery' => 'توصيل', 'pickup' => 'استلام'][$order->order_type] ?? $order->order_type }}</b></div>
                    <div class="info-row"><span>العميل</span><b>{{ $order->customer_name ?: '—' }}</b></div>
                    <div class="info-row"><span>رقم الجوال</span><b dir="ltr">{{ $order->customer_phone ?: '—' }}</b></div>
                    @if($order->restaurantTable)<div class="info-row"><span>الطاولة</span><b>{{ $order->restaurantTable->name }}</b></div>@endif
                    @if($order->restaurantDriver)<div class="info-row"><span>مندوب التوصيل</span><b>{{ $order->restaurantDriver->user?->name }}</b></div>@endif
                </div>
                </section>
            </aside>
        </div>
    </main>
    <script>
        (() => {
            const form = document.getElementById('order-status-form');
            if (!form) return;

            const minutesInput = document.getElementById('preparation-minutes');
            const feedback = document.getElementById('order-status-feedback');
            const saveButton = form.querySelector('.order-status-save');
            const choices = [...form.querySelectorAll('.order-preparation-choice')];

            const selectMinutes = (minutes) => {
                minutesInput.value = minutes;
                choices.forEach((choice) => {
                    const selected = Number(choice.dataset.preparationMinutes) === Number(minutes);
                    choice.classList.toggle('is-selected', selected);
                    choice.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });

            };

            choices.forEach((choice) => {
                choice.addEventListener('click', () => {
                    selectMinutes(choice.dataset.preparationMinutes);
                    form.requestSubmit();
                });
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!saveButton) return;
                saveButton.disabled = true;
                feedback.className = 'order-status-feedback';
                feedback.textContent = 'جارٍ حفظ التحديث...';

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form),
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'تعذّر حفظ التحديث.');
                    }

                    feedback.classList.add('is-success');
                    feedback.textContent = data.message || 'تم حفظ التحديث وإشعار العميل.';
                    window.setTimeout(() => window.location.reload(), 650);
                } catch (error) {
                    feedback.classList.add('is-error');
                    feedback.textContent = error.message || 'تعذّر حفظ التحديث.';
                    saveButton.disabled = false;
                }
            });
        })();
    </script>
</body>
</html>
