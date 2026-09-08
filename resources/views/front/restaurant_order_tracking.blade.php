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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08def4;--green:#27dd86;--red:#ff6678;--muted:#9ca9b4;--border:rgba(150,174,190,.18)}
        *{box-sizing:border-box}body{min-height:100vh;margin:0;display:grid;place-items:center;padding:22px;background:radial-gradient(circle at 80% 5%,rgba(8,222,244,.13),transparent 30%),radial-gradient(circle at 10% 90%,rgba(102,50,255,.14),transparent 30%),#05070a;color:#fff;font-family:"Cairo",Arial,sans-serif}
        .card{width:min(680px,100%);overflow:hidden;border:1px solid rgba(8,222,244,.26);border-radius:30px;background:linear-gradient(145deg,rgba(17,24,31,.98),rgba(5,11,15,.98));box-shadow:0 28px 100px rgba(0,0,0,.55)}
        .head{display:flex;align-items:center;gap:15px;padding:24px 26px;border-bottom:1px solid var(--border);background:rgba(8,222,244,.04)}
        .logo{width:64px;height:64px;object-fit:cover;border:1px solid rgba(8,222,244,.45);border-radius:20px;background:#071014}.head h1{margin:0;font-size:23px}.head p{margin:3px 0 0;color:var(--muted);font-size:13px}
        .body{padding:26px}.live{display:flex;align-items:center;justify-content:space-between;gap:15px}.live strong{font-size:20px}.live span{display:inline-flex;align-items:center;gap:7px;color:var(--green);font-size:12px;font-weight:800}.live span:before{content:"";width:8px;height:8px;border-radius:50%;background:currentColor;box-shadow:0 0 12px currentColor}
        .number{margin:7px 0 20px;color:var(--muted);font-size:13px}.prep{display:flex;align-items:center;gap:12px;margin-bottom:24px;padding:15px;border:1px solid rgba(8,222,244,.2);border-radius:16px;background:rgba(8,222,244,.07)}.prep i{color:var(--cyan);font-size:26px}.prep small{display:block;color:var(--muted)}.prep b{font-size:17px}
        .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin:24px 0;padding:0;list-style:none}.step{position:relative;display:grid;justify-items:center;gap:9px;color:#687680;text-align:center;font-size:11px;font-weight:800}.step:not(:last-child):after{content:"";position:absolute;z-index:0;top:18px;right:58%;width:84%;height:3px;background:#26323a}.step.done:not(:last-child):after{background:var(--cyan)}.step span{position:relative;z-index:1;display:grid;place-items:center;width:38px;height:38px;border:2px solid #26323a;border-radius:50%;background:#080d11;font-size:18px}.step.done,.step.active{color:#eaffff}.step.done span,.step.active span{border-color:var(--cyan);background:var(--cyan);color:#001114;box-shadow:0 0 18px rgba(8,222,244,.32)}.step.active span{animation:pulse 1.8s infinite}
        .status-box{padding:18px;border-radius:18px;background:#080d11;border:1px solid var(--border)}.status-box h2{margin:0;color:var(--cyan);font-size:21px}.status-box p{margin:7px 0 0;color:#d7e1e7;line-height:1.8}.card.cancelled{border-color:rgba(255,102,120,.4)}.card.cancelled .status-box h2{color:var(--red)}
        .footer{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:20px;color:var(--muted);font-size:11px}.back{color:var(--cyan);font-size:13px;font-weight:800;text-decoration:none}
        @keyframes pulse{50%{box-shadow:0 0 0 10px rgba(8,222,244,0)}}
        @media(max-width:560px){body{padding:12px}.card{border-radius:23px}.head,.body{padding:19px}.head h1{font-size:18px}.logo{width:54px;height:54px}.step{font-size:9px}.step span{width:32px;height:32px}.step:not(:last-child):after{top:15px}.footer{align-items:flex-start;flex-direction:column}}
    </style>
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
            <div class="prep" id="prepBox" hidden><i class="ti ti-clock-hour-4"></i><div><small>مدة التجهيز المتوقعة</small><b><span id="prepMinutes"></span> دقيقة تقريباً</b></div></div>
            <ol class="steps">
                <li class="step" data-step="1"><span><i class="ti ti-receipt"></i></span>تم الاستلام</li>
                <li class="step" data-step="2"><span><i class="ti ti-tools-kitchen-2"></i></span>قيد التحضير</li>
                <li class="step" data-step="3"><span><i class="ti ti-bell-check"></i></span>جاهز</li>
                <li class="step" data-step="4"><span><i class="ti ti-circle-check"></i></span>مكتمل</li>
            </ol>
            <section class="status-box"><h2 id="statusLabel"></h2><p id="statusMessage"></p></section>
            <footer class="footer"><span id="lastUpdate"></span><a class="back" href="{{ route('restaurant.menu', $shop) }}">العودة إلى المنيو</a></footer>
        </div>
    </main>
    <script>
        (() => {
            const initial = @json($tracking);
            const card = document.getElementById('trackingCard');
            const render = tracking => {
                const step = Number(tracking.step) || 0;
                card.classList.toggle('cancelled', Boolean(tracking.is_cancelled));
                document.getElementById('orderNumber').textContent = `رقم الطلب: ${tracking.order_number}`;
                document.getElementById('statusLabel').textContent = tracking.status_label;
                document.getElementById('statusMessage').textContent = tracking.status_message;
                const prep = Number(tracking.estimated_preparation_minutes) || 0;
                document.getElementById('prepBox').hidden = !prep || tracking.is_cancelled;
                document.getElementById('prepMinutes').textContent = prep;
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
            const timer = setInterval(refresh, 6000);
        })();
    </script>
</body>
</html>
