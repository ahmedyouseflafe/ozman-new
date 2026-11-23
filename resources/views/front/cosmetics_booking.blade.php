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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.cosmetics_booking')
</head>
<body>
<main class="booking-shell">
    <header class="booking-top">
        <a class="booking-back" href="{{ route('cosmetics.store', $shop) }}"><i class="ti ti-arrow-right"></i> {{ __('العودة للمتجر') }}</a>
        <div class="booking-logo"><img src="{{ $shopLogo }}" alt="{{ $shop->name }}"><div><strong>{{ $shop->name }}</strong><span>{{ __('حجز موعد الصالون') }}</span></div></div>
    </header>
    <div class="booking-layout">
        <section class="booking-form-card">
            <p class="booking-kicker">{{ __('موعدك الخاص') }}</p><h1>{{ __('احجزي لحظتك الجميلة') }}</h1>
            <p class="booking-lead">{{ __('اختاري الخدمة والموعد المناسبين، ثم أرسلي طلب الحجز. سيتواصل معك الصالون لتأكيده.') }}</p>
            <form class="booking-form" id="salonBookingForm">
                <div class="booking-section"><h2>{{ __('الخدمة المطلوبة') }}</h2><div class="booking-grid"><label class="booking-field full">{{ __('اختاري الخدمة') }}<select name="service" required><option value="">{{ __('اختاري خدمة') }}</option><option>{{ __('تسريحة شعر') }}</option><option>{{ __('مكياج') }}</option><option>{{ __('عناية بالبشرة') }}</option><option>{{ __('رموش وحواجب') }}</option><option>{{ __('استشارة عناية وجمال') }}</option><option>{{ __('خدمة أخرى') }}</option></select></label></div></div>
                <div class="booking-section"><h2>{{ __('اختاري الوقت') }}</h2><div class="booking-grid"><label class="booking-field">{{ __('التاريخ') }}<input type="date" name="date" id="bookingDate" required></label><label class="booking-field">{{ __('الوقت المفضل') }}<select name="time" id="bookingTime" required><option value="">{{ __('اختاري الوقت') }}</option><option value="10:00">{{ __('10:00 صباحًا') }}</option><option value="12:00">{{ __('12:00 ظهرًا') }}</option><option value="14:00">{{ __('2:00 مساءً') }}</option><option value="16:00">{{ __('4:00 مساءً') }}</option><option value="18:00">{{ __('6:00 مساءً') }}</option></select></label></div></div>
                <div class="booking-section"><h2>{{ __('بيانات التواصل') }}</h2><div class="booking-grid"><label class="booking-field">{{ __('الاسم') }}<input name="name" autocomplete="name" required></label><label class="booking-field">{{ __('الجوال أو واتساب') }}<input name="phone" inputmode="tel" autocomplete="tel" required></label><label class="booking-field full">{{ __('ملاحظات إضافية (اختياري)') }}<textarea name="notes" placeholder="{{ __('مثلاً: نوع المناسبة أو أي طلب خاص') }}"></textarea></label></div></div>
                <button class="booking-submit" type="submit"><i class="ti ti-brand-whatsapp"></i> {{ __('إرسال طلب الحجز') }}</button><div class="booking-note" id="bookingNote"></div>
            </form>
        </section>
        <aside class="booking-side"><div class="booking-side-icon"><i class="ti ti-calendar-heart"></i></div><h2>{{ __('حجز بسيط وسريع') }}</h2><p>{{ __('لا يوجد دفع الآن؛ طلبك يصل إلى الصالون عبر واتساب ليتم تأكيد الموعد المناسب.') }}</p><div class="booking-steps"><div class="booking-step"><span>1</span>{{ __('اختاري الخدمة') }}</div><div class="booking-step"><span>2</span>{{ __('حددي الوقت') }}</div><div class="booking-step"><span>3</span>{{ __('نؤكد الموعد') }}</div></div></aside>
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
