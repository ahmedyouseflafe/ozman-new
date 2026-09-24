@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $mediaUrl = fn (?string $path) => ! filled($path) ? '' : (preg_match('/^https?:\/\//i', $path) || str_starts_with($path, 'storage/') ? asset($path) : asset('storage/'.$path));
    $shopLogo = $mediaUrl($shop->logo ?: $shop->banner) ?: asset('images/logo.svg');
    $whatsapp = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $shop->phone));
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    <title>حجز موعد | {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--rose:#ff80b8;--gold:#e8c276;--ink:#08060b;--panel:#17101a;--line:rgba(255,205,228,.18);--muted:#c9bac8}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 88% 0,rgba(255,87,166,.19),transparent 28%),radial-gradient(circle at 5% 82%,rgba(189,138,255,.16),transparent 32%),var(--ink);color:#fff;font-family:Cairo,Arial,sans-serif}.booking-shell{width:min(1050px,calc(100% - 32px));margin:auto;padding:22px 0 50px}.booking-top{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:32px}.booking-back,.booking-language{display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border:1px solid var(--line);border-radius:999px;background:rgba(18,10,20,.66);color:#f5ddeb;font-size:11px;font-weight:900;text-decoration:none}.booking-logo{display:flex;align-items:center;gap:10px;text-align:right}.booking-logo img{width:48px;height:48px;border:1px solid var(--gold);border-radius:50%;background:#080509;object-fit:contain}.booking-logo strong{display:block;font-size:13px}.booking-logo span{display:block;color:var(--muted);font-size:10px}.booking-layout{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:20px;direction:ltr}.booking-form-card,.booking-side{direction:rtl;border:1px solid var(--line);border-radius:29px;background:linear-gradient(145deg,rgba(38,20,38,.94),rgba(12,8,15,.97));box-shadow:0 25px 70px rgba(0,0,0,.28)}.booking-form-card{padding:30px}.booking-kicker{margin:0;color:var(--gold);font-size:11px;font-weight:900}.booking-form-card h1{margin:6px 0;font-family:'Playfair Display',Cairo,serif;font-size:36px;line-height:1.25}.booking-lead{margin:0 0 24px;color:var(--muted);font-size:12px;line-height:1.9}.booking-form{display:grid;gap:16px}.booking-section{display:grid;gap:10px;padding-top:17px;border-top:1px solid var(--line)}.booking-section:first-child{padding-top:0;border-top:0}.booking-section h2{margin:0;color:#ffe6f2;font-size:15px}.booking-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.booking-field{display:grid;gap:5px;color:#f4ddeb;font-size:11px;font-weight:800}.booking-field.full{grid-column:1/-1}.booking-field input,.booking-field select,.booking-field textarea{width:100%;min-height:46px;padding:0 12px;border:1px solid var(--line);border-radius:13px;outline:0;background:#0e0911;color:#fff;font:inherit;font-size:12px}.booking-field textarea{min-height:82px;padding:11px 12px;resize:vertical}.booking-field input:focus,.booking-field select:focus,.booking-field textarea:focus{border-color:var(--rose);box-shadow:0 0 0 3px rgba(255,128,184,.12)}.booking-submit{display:flex;align-items:center;justify-content:center;gap:8px;min-height:50px;border:0;border-radius:14px;background:linear-gradient(135deg,#ff86bb,#bd78ff);color:#280617;font:inherit;font-size:14px;font-weight:900;cursor:pointer}.booking-submit:hover{filter:brightness(1.08)}.booking-side{display:flex;flex-direction:column;justify-content:center;min-height:420px;padding:25px;text-align:center}.booking-side-icon{display:grid;place-items:center;width:72px;height:72px;margin:0 auto 15px;border:1px solid var(--gold);border-radius:50%;background:rgba(232,194,118,.09);color:#ffe0a1;font-size:31px;box-shadow:0 0 30px rgba(255,128,184,.18)}.booking-side h2{margin:0;font-family:'Playfair Display',Cairo,serif;font-size:24px}.booking-side p{margin:8px 0 20px;color:var(--muted);font-size:11px;line-height:1.9}.booking-steps{display:grid;gap:9px;text-align:right}.booking-step{display:flex;align-items:center;gap:9px;padding:9px;border-radius:13px;background:rgba(255,255,255,.035);font-size:11px}.booking-step span{display:grid;place-items:center;width:25px;height:25px;border-radius:50%;background:rgba(255,128,184,.15);color:var(--rose);font-size:10px;font-weight:900}.booking-note{display:none;margin-top:11px;padding:10px;border:1px solid #68dfab;border-radius:12px;background:rgba(73,211,146,.08);color:#8dffc4;font-size:11px;text-align:center}@media(max-width:760px){.booking-shell{width:calc(100% - 18px);padding-top:10px}.booking-top{margin-bottom:18px}.booking-layout{grid-template-columns:1fr;gap:10px}.booking-form-card{padding:20px;border-radius:22px}.booking-form-card h1{font-size:29px}.booking-side{order:-1;min-height:0;padding:18px;border-radius:22px}.booking-side-icon{width:55px;height:55px;margin-bottom:9px;font-size:24px}.booking-side h2{font-size:20px}.booking-side p{margin-bottom:12px}.booking-steps{grid-template-columns:repeat(3,1fr);gap:5px}.booking-step{display:grid;justify-items:center;padding:7px;text-align:center;font-size:8px}.booking-grid{grid-template-columns:1fr}.booking-field.full{grid-column:auto}}
    </style>
</head>
<body>
<main class="booking-shell">
    <header class="booking-top">
        <a class="booking-back" href="{{ route('cosmetics.store', $shop) }}"><i class="ti ti-arrow-right"></i> العودة للمتجر</a>
        <div class="booking-logo"><img src="{{ $shopLogo }}" alt="{{ $shop->name }}"><div><strong>{{ $shop->name }}</strong><span>حجز موعد الصالون</span></div></div>
    </header>
    <div class="booking-layout">
        <section class="booking-form-card">
            <p class="booking-kicker">موعدك الخاص</p><h1>احجزي لحظتك الجميلة</h1>
            <p class="booking-lead">اختاري الخدمة والموعد المناسبين، ثم أرسلي طلب الحجز. سيتواصل معك الصالون لتأكيده.</p>
            <form class="booking-form" id="salonBookingForm">
                <div class="booking-section"><h2>الخدمة المطلوبة</h2><div class="booking-grid"><label class="booking-field full">اختاري الخدمة<select name="service" required><option value="">اختاري خدمة</option><option>تسريحة شعر</option><option>مكياج</option><option>عناية بالبشرة</option><option>رموش وحواجب</option><option>استشارة عناية وجمال</option><option>خدمة أخرى</option></select></label></div></div>
                <div class="booking-section"><h2>اختاري الوقت</h2><div class="booking-grid"><label class="booking-field">التاريخ<input type="date" name="date" id="bookingDate" required></label><label class="booking-field">الوقت المفضل<select name="time" id="bookingTime" required><option value="">اختاري الوقت</option><option value="10:00">10:00 صباحًا</option><option value="12:00">12:00 ظهرًا</option><option value="14:00">2:00 مساءً</option><option value="16:00">4:00 مساءً</option><option value="18:00">6:00 مساءً</option></select></label></div></div>
                <div class="booking-section"><h2>بيانات التواصل</h2><div class="booking-grid"><label class="booking-field">الاسم<input name="name" autocomplete="name" required></label><label class="booking-field">الجوال أو واتساب<input name="phone" inputmode="tel" autocomplete="tel" required></label><label class="booking-field full">ملاحظات إضافية (اختياري)<textarea name="notes" placeholder="مثلاً: نوع المناسبة أو أي طلب خاص"></textarea></label></div></div>
                <button class="booking-submit" type="submit"><i class="ti ti-brand-whatsapp"></i> إرسال طلب الحجز</button><div class="booking-note" id="bookingNote"></div>
            </form>
        </section>
        <aside class="booking-side"><div class="booking-side-icon"><i class="ti ti-calendar-heart"></i></div><h2>حجز بسيط وسريع</h2><p>لا يوجد دفع الآن؛ طلبك يصل إلى الصالون عبر واتساب ليتم تأكيد الموعد المناسب.</p><div class="booking-steps"><div class="booking-step"><span>1</span>اختاري الخدمة</div><div class="booking-step"><span>2</span>حددي الوقت</div><div class="booking-step"><span>3</span>نؤكد الموعد</div></div></aside>
    </div>
</main>
<script>
(() => {
    const form = document.getElementById('salonBookingForm');
    const date = document.getElementById('bookingDate');
    const time = document.getElementById('bookingTime');
    const note = document.getElementById('bookingNote');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const availabilityUrl = @json(route('cosmetics.booking.availability', $shop));
    const bookingUrl = @json(route('cosmetics.booking.store', $shop));
    const showNote = message => { note.textContent = message; note.style.display = 'block'; };
    date.min = new Date().toISOString().slice(0, 10);
    const refreshAvailability = async () => {
        if (!date.value) return;
        time.disabled = true;
        try {
            const response = await fetch(`${availabilityUrl}?date=${encodeURIComponent(date.value)}`, {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            const booked = new Set(data.booked_times || []);
            [...time.options].forEach(option => {
                if (!option.value) return;
                option.disabled = booked.has(option.value);
                option.textContent = option.disabled ? `${option.dataset.label || option.textContent.replace(' — محجوز', '')} — محجوز` : (option.dataset.label || option.textContent);
                option.dataset.label ||= option.textContent.replace(' — محجوز', '');
            });
            if (time.selectedOptions[0]?.disabled) time.value = '';
        } catch (_) { showNote('تعذر تحميل الأوقات المتاحة. حدّث الصفحة وحاول مرة أخرى.'); }
        finally { time.disabled = false; }
    };
    date.addEventListener('change', refreshAvailability);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const data = new FormData(form);
        const whatsapp = @json($whatsapp);
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        const message = [
            `مرحباً، أريد حجز موعد في ${@json($shop->name)}`,
            '',
            `الخدمة: ${data.get('service')}`,
            `التاريخ: ${data.get('date')}`,
            `الوقت: ${data.get('time')}`,
            `الاسم: ${data.get('name')}`,
            `الجوال: ${data.get('phone')}`,
            data.get('notes') ? `ملاحظات: ${data.get('notes')}` : '',
        ].filter(Boolean).join('\n');
        try {
            const response = await fetch(bookingUrl, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(Object.fromEntries(data))});
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {})[0]?.[0] || 'تعذر تسجيل الحجز.');
            if (whatsapp) window.open(`https://wa.me/${whatsapp}?text=${encodeURIComponent(message)}`, '_blank', 'noopener');
            showNote(result.message || 'تم تسجيل طلب الحجز. سيتواصل معك الصالون لتأكيده.');
            form.reset(); date.min = new Date().toISOString().slice(0, 10); refreshAvailability();
        } catch (error) { showNote(error.message); refreshAvailability(); }
        finally { submit.disabled = false; }
    });
})();
</script>
</body>
</html>
