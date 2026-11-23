<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>تتبّع الطلب {{ $order->order_number }} | {{ $shop->name }}</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.restaurant_order_tracking')
</head>
<body>
    <main class="card" id="trackingCard">
        <header class="head">
            @if($shop->logo)<img class="logo" src="{{ asset($shop->logo) }}" alt="{{ $shop->name }}">@endif
            <div><h1>تتبّع طلبك من {{ $shop->name }}</h1><p>ستتحدث الحالة هنا تلقائياً، ولا حاجة لإعادة تحميل الصفحة.</p></div>
        </header>
        <div class="body">
            <div class="live"><strong>حالة الطلب</strong><span>تحديث مباشر</span></div>
            <p class="number" id="orderNumber"></p>
            <div class="prep" id="prepBox" hidden><i class="ti ti-clock-hour-4"></i><div><small id="prepLabel">مدة التجهيز المتوقعة</small><b id="prepFixed"><span id="prepMinutes"></span> دقيقة تقريباً</b><b id="prepCountdown" class="prep-countdown" dir="ltr" hidden></b></div></div>
            <ol class="steps" style="--steps:{{ $order->order_type === 'delivery' ? 5 : 4 }}">
                <li class="step" data-step="1"><span><i class="ti ti-receipt"></i></span>تم الاستلام</li>
                <li class="step" data-step="2"><span><i class="ti ti-tools-kitchen-2"></i></span>قيد التحضير</li>
                <li class="step" data-step="3"><span><i class="ti ti-bell-check"></i></span>جاهز</li>
                @if($order->order_type === 'delivery')<li class="step" data-step="4"><span><i class="ti ti-motorbike"></i></span>في الطريق</li>@endif
                <li class="step" data-step="{{ $order->order_type === 'delivery' ? 5 : 4 }}"><span><i class="ti ti-circle-check"></i></span>مكتمل</li>
            </ol>
            <section class="status-box"><h2 id="statusLabel"></h2><p id="statusMessage"></p></section>
            @if($order->order_type === 'delivery')
                <section class="delivery-eta" id="deliveryEtaBox" hidden role="status"><i class="ti ti-clock"></i><div><small>وقت الوصول التقريبي الذي حدده المندوب</small><strong id="deliveryEtaText"></strong></div></section>
            @endif
            <footer class="footer"><span id="lastUpdate"></span><a class="back" href="{{ route('restaurant.menu', $shop) }}">العودة إلى المنيو</a></footer>
        </div>
    </main>
    <script>
        (() => {
            const initial = @json($tracking);
            const card = document.getElementById('trackingCard');
            // Countdown to the time the kitchen chose; it never shows "late" to the customer.
            let due = null, offset = 0;
            const tick = () => {
                const label = document.getElementById('prepLabel'), fixed = document.getElementById('prepFixed'), clock = document.getElementById('prepCountdown');
                fixed.hidden = Boolean(due); clock.hidden = !due;
                if (!due) { label.textContent = 'مدة التجهيز المتوقعة'; return; }
                const left = Math.round((due - (Date.now() + offset)) / 1000);
                label.textContent = left > 0 ? 'طلبك بيجهز خلال' : 'طلبك قرّب يجهز 🙏';
                clock.textContent = left > 0 ? `${String(Math.floor(left / 60)).padStart(2, '0')}:${String(left % 60).padStart(2, '0')}` : '';
                clock.hidden = left <= 0;
            };
            setInterval(tick, 1000);
            const render = tracking => {
                const step = Number(tracking.step) || 0;
                card.classList.toggle('cancelled', Boolean(tracking.is_cancelled));
                document.getElementById('orderNumber').textContent = `رقم الطلب: ${tracking.order_number}`;
                document.getElementById('statusLabel').textContent = tracking.status_label;
                document.getElementById('statusMessage').textContent = tracking.status_message;
                const prep = Number(tracking.estimated_preparation_minutes) || 0;
                document.getElementById('prepBox').hidden = !prep || tracking.is_cancelled || ['out_for_delivery', 'completed'].includes(tracking.status);
                document.getElementById('prepMinutes').textContent = prep;
                due = tracking.preparation_due_at && !tracking.is_cancelled ? Date.parse(tracking.preparation_due_at) : null;
                if (tracking.server_time) offset = Date.parse(tracking.server_time) - Date.now();
                tick();
                const etaBox = document.getElementById('deliveryEtaBox');
                if (etaBox) {
                    etaBox.hidden = tracking.status !== 'out_for_delivery';
                    if (!etaBox.hidden) {
                        const eta = tracking.estimated_delivery_at ? new Date(tracking.estimated_delivery_at) : null;
                        const remaining = eta && !Number.isNaN(eta.getTime()) ? Math.ceil((eta.getTime() - Date.now()) / 60000) : null;
                        document.getElementById('deliveryEtaText').textContent = remaining === null
                            ? 'بانتظار تحديد الوقت من المندوب'
                            : remaining > 0 ? `حوالي ${remaining} دقيقة (الساعة ${eta.toLocaleTimeString('ar-PS', {hour:'2-digit',minute:'2-digit'})})`
                            : 'تجاوزنا الوقت المقدر؛ بانتظار تحديث المندوب';
                    }
                }
                document.getElementById('lastUpdate').textContent = tracking.updated_at
                    ? `آخر تحديث: ${new Date(tracking.updated_at).toLocaleTimeString('ar-PS', {hour:'2-digit',minute:'2-digit'})}` : '';
                document.querySelectorAll('[data-step]').forEach(item => {
                    const number = Number(item.dataset.step);
                    item.classList.toggle('done', !tracking.is_cancelled && number < step);
                    item.classList.toggle('active', !tracking.is_cancelled && number === step);
                });
                return ['completed', 'cancelled'].includes(tracking.status);
            };
            render(initial);
            const refresh = async () => {
                try {
                    const response = await fetch(location.href, {headers:{Accept:'application/json'},cache:'no-store'});
                    if (!response.ok) return;
                    const data = await response.json();
                    if (render(data.tracking)) clearInterval(timer);
                } catch (_) {}
            };
            const timer = ['completed', 'cancelled'].includes(initial.status) ? null : setInterval(refresh, 6000);
        })();
    </script>
</body>
</html>
