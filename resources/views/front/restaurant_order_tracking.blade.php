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
        .steps{display:grid;grid-template-columns:repeat(var(--steps,4),1fr);gap:6px;margin:24px 0;padding:0;list-style:none}.step{position:relative;display:grid;justify-items:center;gap:9px;color:#687680;text-align:center;font-size:11px;font-weight:800}.step:not(:last-child):after{content:"";position:absolute;z-index:0;top:18px;right:58%;width:84%;height:3px;background:#26323a}.step.done:not(:last-child):after{background:var(--cyan)}.step span{position:relative;z-index:1;display:grid;place-items:center;width:38px;height:38px;border:2px solid #26323a;border-radius:50%;background:#080d11;font-size:18px}.step.done,.step.active{color:#eaffff}.step.done span,.step.active span{border-color:var(--cyan);background:var(--cyan);color:#001114;box-shadow:0 0 18px rgba(8,222,244,.32)}.step.active span{animation:pulse 1.8s infinite}
        .status-box{padding:18px;border-radius:18px;background:#080d11;border:1px solid var(--border)}.status-box h2{margin:0;color:var(--cyan);font-size:21px}.status-box p{margin:7px 0 0;color:#d7e1e7;line-height:1.8}.card.cancelled{border-color:rgba(255,102,120,.4)}.card.cancelled .status-box h2{color:var(--red)}
        .delivery-map{margin-top:20px;padding:16px;border:1px solid var(--border);border-radius:18px;background:#0a141a}.delivery-map h2{margin:0;color:var(--cyan);font-size:19px}.delivery-map p{margin:6px 0 0;color:#c2d1d9;font-size:13px;line-height:1.7}.delivery-map #deliveryMap{height:290px;margin-top:13px;border-radius:13px;background:#112028}.delivery-map .route-eta{color:var(--green);font-weight:800}
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
            <ol class="steps" style="--steps:{{ $order->order_type === 'delivery' ? 5 : 4 }}">
                <li class="step" data-step="1"><span><i class="ti ti-receipt"></i></span>تم الاستلام</li>
                <li class="step" data-step="2"><span><i class="ti ti-tools-kitchen-2"></i></span>قيد التحضير</li>
                <li class="step" data-step="3"><span><i class="ti ti-bell-check"></i></span>جاهز</li>
                @if($order->order_type === 'delivery')<li class="step" data-step="4"><span><i class="ti ti-motorbike"></i></span>في الطريق</li>@endif
                <li class="step" data-step="{{ $order->order_type === 'delivery' ? 5 : 4 }}"><span><i class="ti ti-circle-check"></i></span>مكتمل</li>
            </ol>
            <section class="status-box"><h2 id="statusLabel"></h2><p id="statusMessage"></p></section>
            @if($order->order_type === 'delivery')
                <section class="delivery-map" id="deliveryMapSection" hidden aria-label="تتبع التوصيل على الخريطة">
                    <h2><i class="ti ti-map-pin"></i> موقع المندوب ومسار التوصيل</h2>
                    <p id="deliveryMapStatus" role="status"></p>
                    @if(config('services.google_maps.browser_key'))<div id="deliveryMap" aria-label="خريطة التوصيل"></div>@endif
                </section>
            @endif
            <footer class="footer"><span id="lastUpdate"></span><a class="back" href="{{ route('restaurant.menu', $shop) }}">العودة إلى المنيو</a></footer>
        </div>
    </main>
    <script>
        (() => {
            const initial = @json($tracking);
            const card = document.getElementById('trackingCard');
            const mapSection = document.getElementById('deliveryMapSection');
            const mapStatus = document.getElementById('deliveryMapStatus');
            const mapsConfigured = @json((bool) config('services.google_maps.browser_key'));
            let latestTracking = initial;
            let map = null;
            let RouteClass = null;
            let routePolylines = [];
            let driverMarker = null;
            let restaurantMarker = null;
            let destinationMarker = null;
            let lastRouteAt = 0;
            let routePending = false;
            let lastEtaText = '';
            let mapsReady = false;
            let mapsFailed = false;
            function clearRoute() {
                routePolylines.forEach(polyline => polyline.setMap(null));
                routePolylines = [];
                lastRouteAt = 0;
                lastEtaText = '';
            }
            function renderDeliveryMap(tracking) {
                if (!mapSection) return;
                const active = tracking.status === 'out_for_delivery';
                mapSection.hidden = !active;
                if (!active) { if (driverMarker) driverMarker.setMap(null); clearRoute(); return; }
                if (!mapsConfigured) { mapStatus.textContent = 'الخريطة ووقت الطريق غير متاحين حاليًا حتى يتم تفعيل خدمة الخرائط للموقع.'; return; }
                const points = tracking.delivery_map || {};
                if (mapsFailed) { mapStatus.textContent = 'تعذّر تشغيل خدمة الخرائط والمسارات الآن. حاول لاحقًا.'; return; }
                if (!mapsReady) { mapStatus.textContent = 'جارٍ تحميل الخريطة...'; return; }
                if (!map) {
                    map = new google.maps.Map(document.getElementById('deliveryMap'), {center:points.driver || points.restaurant || points.destination || {lat:31.9,lng:35.2},zoom:13,mapTypeControl:false,streetViewControl:false,fullscreenControl:false});
                }
                if (points.restaurant && !restaurantMarker) restaurantMarker = new google.maps.Marker({map,position:points.restaurant,title:'المطعم',label:'م'});
                if (points.destination && !destinationMarker) destinationMarker = new google.maps.Marker({map,position:points.destination,title:'موقع التوصيل',label:'ع'});
                if (!points.destination) { mapStatus.textContent = 'لم يحدد العميل موقعًا صالحًا للتوصيل؛ لا يمكن عرض المسار.'; clearRoute(); return; }
                if (!points.driver) {
                    if (driverMarker) { driverMarker.setMap(null); driverMarker = null; }
                    clearRoute();
                    map.setCenter(points.restaurant || points.destination);
                    mapStatus.textContent = 'بانتظار مشاركة المندوب لموقعه. سيظهر موقعه هنا بعد السماح للمتصفح باستخدام GPS.';
                    return;
                }
                if (!driverMarker) driverMarker = new google.maps.Marker({map,position:points.driver,title:'المندوب',label:'د'});
                else driverMarker.setPosition(points.driver);
                const updated = new Date(points.driver.updated_at).toLocaleTimeString('ar-PS', {hour:'2-digit',minute:'2-digit'});
                if (routePending || (lastRouteAt && Date.now() - lastRouteAt < 60000)) {
                    mapStatus.textContent = lastEtaText ? `${lastEtaText} آخر موقع للمندوب: ${updated}.` : `آخر موقع للمندوب: ${updated}. جارٍ حساب وقت الطريق...`;
                    return;
                }
                mapStatus.textContent = `آخر موقع للمندوب: ${updated}. جارٍ حساب وقت الطريق...`;
                routePending = true;
                lastRouteAt = Date.now();
                const requestedPoint = points.driver;
                RouteClass.computeRoutes({origin:requestedPoint,destination:points.destination,travelMode:'DRIVING',routingPreference:'TRAFFIC_AWARE',departureTime:new Date(),fields:['durationMillis','path']})
                    .then(({routes}) => {
                        if (latestTracking.status !== 'out_for_delivery' || !latestTracking.delivery_map?.driver) return;
                        const bestRoute = routes?.[0];
                        if (!bestRoute || !Number.isFinite(bestRoute.durationMillis)) throw new Error('no-route');
                        clearRoute();
                        routePolylines = bestRoute.createPolylines();
                        routePolylines.forEach(polyline => polyline.setMap(map));
                        const bounds = new google.maps.LatLngBounds();
                        bounds.extend(requestedPoint);
                        bounds.extend(points.destination);
                        map.fitBounds(bounds, 45);
                        lastRouteAt = Date.now();
                        const minutes = Math.max(1, Math.ceil(bestRoute.durationMillis / 60000));
                        lastEtaText = `وصول تقريبي خلال ${minutes} دقيقة حسب الطريق المتاح.`;
                        mapStatus.textContent = `${lastEtaText} آخر موقع للمندوب: ${updated}.`;
                    }).catch(() => {
                        if (latestTracking.status !== 'out_for_delivery' || !latestTracking.delivery_map?.driver) return;
                        clearRoute();
                        lastRouteAt = Date.now();
                        mapStatus.textContent = 'موقع المندوب ظاهر، لكن خدمة الطرق لم تتمكن من حساب المسار أو وقت الوصول الآن.';
                        map.setCenter(latestTracking.delivery_map.driver);
                    }).finally(() => { routePending = false; });
            }
            window.initDeliveryMap = async () => {
                try {
                    ({Route:RouteClass} = await google.maps.importLibrary('routes'));
                    mapsReady = true;
                    renderDeliveryMap(latestTracking);
                } catch (_) { mapsFailed = true; renderDeliveryMap(latestTracking); }
            };
            window.deliveryMapLoadError = () => { mapsFailed = true; renderDeliveryMap(latestTracking); };
            const render = tracking => {
                latestTracking = tracking;
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
                renderDeliveryMap(tracking);
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
    @if($order->order_type === 'delivery' && config('services.google_maps.browser_key'))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google_maps.browser_key')) }}&amp;callback=initDeliveryMap&amp;loading=async&amp;language=ar" async defer onerror="deliveryMapLoadError()"></script>
    @endif
</body>
</html>
