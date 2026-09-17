<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>طلبات التوصيل | {{ $driver->shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08dcf4;--green:#28dc88;--muted:#aab6bf;--border:rgba(139,160,179,.24)}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 80% 0,rgba(8,220,244,.13),transparent 34%),#050b0e;color:#f6fbfd;font-family:Cairo,Arial,sans-serif}button{font:inherit}a{color:var(--cyan)}main{width:min(920px,100%);margin:auto;padding:22px 16px 70px}.hero,.order-card,.stat{border:1px solid var(--border);border-radius:20px;background:#0b171d;box-shadow:0 15px 38px rgba(0,0,0,.2)}.hero{padding:20px;display:flex;justify-content:space-between;align-items:flex-start;gap:15px}.hero h1{margin:0;font-size:25px}.hero p{margin:4px 0 0;color:var(--muted)}.hero-actions{display:flex;flex-wrap:wrap;gap:8px}.btn,.action{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:8px 13px;border:1px solid rgba(8,220,244,.4);border-radius:12px;background:rgba(8,220,244,.1);color:var(--cyan);font-weight:800;text-decoration:none;cursor:pointer}.btn:hover,.action:hover{background:rgba(8,220,244,.2)}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:15px 0}.stat{padding:13px}.stat small{display:block;color:var(--muted)}.stat strong{font-size:25px;color:var(--cyan)}.notice{margin:14px 0;padding:12px 15px;border:1px solid rgba(40,220,136,.45);border-radius:12px;background:rgba(40,220,136,.1);color:#8df5b9}.notice.error{border-color:#a84555;background:#31151b;color:#ffb7c0}.feed-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:18px 0 10px}.feed-head h2{font-size:20px;margin:0}.feed-head span{font-size:12px;color:var(--green)}.orders{display:grid;gap:13px}.order-card{padding:17px;scroll-margin-top:15px}.order-head,.order-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.order-head small,.order-details small,.order-items small{display:block;color:var(--muted);font-size:11px}.order-head h2{margin:0;font-size:18px;overflow-wrap:anywhere}.badge{padding:5px 10px;border:1px solid rgba(8,220,244,.4);border-radius:30px;color:var(--cyan);font-size:12px;font-weight:800}.order-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:15px 0}.order-details>div{min-width:0;overflow-wrap:anywhere}.order-details .full{grid-column:1/-1}.map-link{display:inline-flex;align-items:center;gap:5px}.order-items{padding:12px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}.order-items div{font-size:13px}.order-foot{padding-top:14px}.order-foot>strong{color:var(--green)}.action{background:var(--cyan);color:#01151a}.empty{text-align:center;padding:45px 20px;border:1px dashed var(--border);border-radius:18px;color:var(--muted)}@media(max-width:600px){main{padding:10px 10px 70px}.hero{display:block}.hero h1{font-size:21px}.hero-actions{margin-top:13px}.stats{gap:7px}.stat{padding:10px}.stat strong{font-size:21px}.order-details{grid-template-columns:1fr}.order-details .full{grid-column:1}.order-foot form,.order-foot .action{width:100%}}
        .delivery-eta-form{display:grid;gap:6px;min-width:220px}.delivery-eta-form label,.delivery-eta-form small{color:var(--muted);font-size:12px}.delivery-eta-form input{width:100%;min-height:42px;padding:8px 12px;border:1px solid var(--border);border-radius:12px;background:#081116;color:#fff;font:inherit}.delivery-eta-form button{width:100%}@media(max-width:600px){.order-foot .delivery-eta-form{width:100%}}
    </style>
</head>
<body>
<main>
    <header class="hero">
        <div><h1><i class="ti ti-motorbike"></i> طلبات التوصيل</h1><p>{{ $driver->user->name }} · {{ $driver->shop->name }}</p></div>
        <div class="hero-actions">
            <button class="btn" type="button" id="driver-notifications"><i class="ti ti-bell"></i> تفعيل إشعارات الطلبات</button>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">تسجيل الخروج</button></form>
        </div>
    </header>
    <p id="notification-message" class="notice" hidden role="status"></p>
    @if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
    @if($errors->any())<p class="notice error">{{ $errors->first() }}</p>@endif
    <div class="stats">
        <div class="stat"><small>بانتظار الاستلام</small><strong id="stat-assigned">{{ $stats['assigned'] }}</strong></div>
        <div class="stat"><small>في الطريق</small><strong id="stat-on-the-way">{{ $stats['on_the_way'] }}</strong></div>
        <div class="stat"><small>تم تسليمها اليوم</small><strong id="stat-delivered-today">{{ $stats['delivered_today'] }}</strong></div>
    </div>
    <div class="feed-head"><h2>الطلبات المسندة إليك</h2><span id="feed-status">تحديث مباشر</span></div>
    <div class="orders" id="driver-orders">@include('driver.partials.orders', ['orders' => $orders])</div>
</main>
<script>
(() => {
    const orders = document.getElementById('driver-orders');
    const feedStatus = document.getElementById('feed-status');
    const notificationButton = document.getElementById('driver-notifications');
    const notificationMessage = document.getElementById('notification-message');
    const feedUrl = {{ Illuminate\Support\Js::from(route('driver.orders.feed')) }};
    const keyUrl = {{ Illuminate\Support\Js::from(route('merchant-app.push.public-key')) }};
    const subscribeUrl = {{ Illuminate\Support\Js::from(route('driver.push.store')) }};
    const workerUrl = {{ Illuminate\Support\Js::from(asset('merchant-pwa-sw.js')) }};
    let refreshing = false;
    async function refreshOrders() {
        if (refreshing) return;
        refreshing = true;
        try {
            const response = await fetch(feedUrl, {headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store'});
            if (!response.ok) throw new Error('feed');
            const data = await response.json();
            if (!orders.contains(document.activeElement)) orders.innerHTML = data.html;
            for (const [key,id] of Object.entries({assigned:'stat-assigned',on_the_way:'stat-on-the-way',delivered_today:'stat-delivered-today'})) {
                document.getElementById(id).textContent = data.stats[key] ?? 0;
            }
            feedStatus.textContent = 'متصل · تحديث مباشر';
        } catch (_) { feedStatus.textContent = 'تعذّر التحديث؛ ستتم إعادة المحاولة'; }
        finally { refreshing = false; }
    }
    setInterval(refreshOrders, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshOrders(); });
    const showMessage = (message, error = false) => {
        notificationMessage.hidden = false;
        notificationMessage.textContent = message;
        notificationMessage.classList.toggle('error', error);
    };
    const decodeKey = key => {
        const base64 = (key + '='.repeat((4 - key.length % 4) % 4)).replace(/-/g,'+').replace(/_/g,'/');
        return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
    };
    notificationButton.addEventListener('click', async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            showMessage('هذا المتصفح لا يدعم إشعارات الطلبات. أبقِ الصفحة مفتوحة لرؤية الطلبات مباشرة.', true); return;
        }
        notificationButton.disabled = true;
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') throw new Error('اسمح بالإشعارات من إعدادات المتصفح ثم حاول مجددًا.');
            const keyResponse = await fetch(keyUrl, {headers:{Accept:'application/json'}});
            if (!keyResponse.ok) throw new Error('تعذّر تجهيز مفتاح الإشعارات.');
            const {public_key: publicKey} = await keyResponse.json();
            const registration = await navigator.serviceWorker.register(workerUrl, {scope:'/'});
            let subscription = await registration.pushManager.getSubscription();
            if (!subscription) subscription = await registration.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:decodeKey(publicKey)});
            const serialized = subscription.toJSON();
            serialized.contentEncoding = window.PushManager?.supportedContentEncodings?.[0] || 'aes128gcm';
            const response = await fetch(subscribeUrl, {method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({subscription:serialized})});
            if (!response.ok) throw new Error('تعذّر ربط الجهاز بإشعارات المندوب.');
            showMessage('تم تفعيل إشعارات طلبات التوصيل لهذا الحساب.');
            notificationButton.innerHTML = '<i class="ti ti-bell-check"></i> الإشعارات مفعّلة';
        } catch (error) { showMessage(error.message || 'تعذّر تفعيل الإشعارات.', true); }
        finally { notificationButton.disabled = false; }
    });
})();
</script>
</body>
</html>
