<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('front.partials.web_push_client')
    <title>طلبات التوصيل | {{ $driver->shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('driver.dashboard')
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
        <div class="stat"><small>طلبات متاحة</small><strong id="stat-available">{{ $stats['available'] }}</strong></div>
        <div class="stat"><small>بانتظار الاستلام</small><strong id="stat-assigned">{{ $stats['assigned'] }}</strong></div>
        <div class="stat"><small>في الطريق</small><strong id="stat-on-the-way">{{ $stats['on_the_way'] }}</strong></div>
        <div class="stat"><small>تم تسليمها اليوم</small><strong id="stat-delivered-today">{{ $stats['delivered_today'] }}</strong></div>
    </div>
    <div class="feed-head"><h2>طلبات متاحة للاستلام</h2><span>الأول يقبل يستلم الطلب</span></div>
    <div class="offers" id="driver-offers">@include('driver.partials.offers', ['offers' => $offers])</div>
    <div class="feed-head"><h2>الطلبات المسندة إليك</h2><span id="feed-status">تحديث مباشر</span></div>
    <div class="orders" id="driver-orders">@include('driver.partials.orders', ['orders' => $orders])</div>
</main>
<script>
(() => {
    const orders = document.getElementById('driver-orders');
    const offers = document.getElementById('driver-offers');
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
            if (!offers.contains(document.activeElement)) offers.innerHTML = data.offers_html;
            for (const [key,id] of Object.entries({available:'stat-available',assigned:'stat-assigned',on_the_way:'stat-on-the-way',delivered_today:'stat-delivered-today'})) {
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
            const registration = await window.OzmanWebPush.register(workerUrl);
            const subscription = await window.OzmanWebPush.subscription(registration, publicKey);
            await window.OzmanWebPush.save(subscribeUrl, subscription);
            showMessage('تم تفعيل إشعارات طلبات التوصيل لهذا الحساب.');
            notificationButton.innerHTML = '<i class="ti ti-bell-check"></i> الإشعارات مفعّلة';
        } catch (error) { showMessage((error.code ? window.OzmanWebPush.message(error) : error.message) || 'تعذّر تفعيل الإشعارات.', true); }
        finally { notificationButton.disabled = false; }
    });
})();
</script>
</body>
</html>
