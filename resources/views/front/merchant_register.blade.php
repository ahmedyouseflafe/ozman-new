<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب متجر - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#020707;color:#fff;font-family:Cairo,sans-serif}
        .card{width:min(100%,560px);padding:30px;border:1px solid rgba(0,229,255,.35);border-radius:26px;background:linear-gradient(145deg,rgba(0,229,255,.09),rgba(14,9,28,.94));box-shadow:0 0 40px rgba(0,229,255,.14)}
        .head{text-align:center;margin-bottom:24px}
        .icon{width:72px;height:72px;display:grid;place-items:center;margin:0 auto 15px;border:1px solid #00e5ff;border-radius:50%;color:#00e5ff;font-size:28px}
        h1{margin:0 0 8px;font-size:1.6rem}
        .head p{margin:0;color:rgba(255,255,255,.68);line-height:1.8;font-size:.9rem}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        label{display:grid;gap:7px;font-weight:800}
        label.full{grid-column:1/-1}
        input,textarea{width:100%;padding:13px 15px;border:1px solid rgba(255,255,255,.18);border-radius:14px;background:rgba(0,0,0,.46);color:#fff;font:inherit;outline:none}
        input:focus,textarea:focus{border-color:#00e5ff;box-shadow:0 0 0 3px rgba(0,229,255,.1)}
        textarea{min-height:82px;resize:vertical}
        .errors{margin-bottom:18px;padding:12px 14px;border:1px solid rgba(255,70,70,.45);border-radius:12px;background:rgba(255,50,50,.1);color:#ff9b9b}
        button{width:100%;margin-top:20px;padding:14px;border:0;border-radius:14px;background:#00e5ff;color:#001014;font:900 1rem Cairo,sans-serif;cursor:pointer}
        .back{display:block;margin-top:17px;text-align:center;color:#00e5ff;text-decoration:none;font-weight:800}
        .location-box{grid-column:1/-1;padding:15px;border:1px solid rgba(0,229,255,.25);border-radius:16px;background:rgba(0,229,255,.045)}
        .location-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:11px}
        .location-title{font-weight:900}
        .locate-btn{width:auto;margin:0;padding:10px 14px;border:1px solid #00e5ff;background:rgba(0,229,255,.1);color:#00e5ff;font-size:.86rem}
        #shopLocationMap{height:250px;border-radius:14px;overflow:hidden;background:#111}
        .location-status{margin-top:9px;color:rgba(255,255,255,.68);font-size:.82rem;font-weight:700}
        .location-status.selected{color:#65ffab}
        .leaflet-container{font-family:Cairo,sans-serif}
        @media(max-width:620px){body{padding:14px}.card{padding:22px}.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <main class="card">
        <div class="head">
            <div class="icon"><i class="fas fa-store"></i></div>
            <h1>إنشاء حساب متجر جديد</h1>
            <p>سيتم ربط متجرك تلقائيًا بالموزع أو المروّج صاحب QR، وستصل طلباتك للجهة المرتبطة.</p>
        </div>

        @if($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('merchant.register.store') }}">
            @csrf
            <input type="hidden" name="redirect" value="{{ old('redirect', $redirectTo) }}">
            <div class="grid">
                <label>
                    <span>اسم صاحب المتجر</span>
                    <input name="owner_name" value="{{ old('owner_name') }}" required>
                </label>
                <label>
                    <span>اسم المتجر</span>
                    <input name="shop_name" value="{{ old('shop_name') }}" required>
                </label>
                <label>
                    <span>البريد الإلكتروني</span>
                    <input type="email" name="email" value="{{ old('email') }}" dir="ltr" autocomplete="email" required>
                </label>
                <label>
                    <span>رقم الهاتف</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="tel" maxlength="16"
                        pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                        title="أدخل رقم جوال صحيح مثل 0591234567" dir="ltr" required>
                </label>
                <label>
                    <span>رقم واتساب</span>
                    <input type="tel" name="whatsapp" value="{{ old('whatsapp') }}" inputmode="tel" maxlength="16"
                        pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                        title="أدخل رقم واتساب صحيح مثل 0591234567" dir="ltr">
                </label>
                <label class="full">
                    <span>العنوان</span>
                    <textarea name="address">{{ old('address') }}</textarea>
                </label>
                <div class="location-box">
                    <div class="location-head">
                        <div class="location-title"><i class="fas fa-location-dot"></i> موقع المحل على الخريطة</div>
                        <button class="locate-btn" id="useCurrentLocationBtn" type="button">
                            <i class="fas fa-crosshairs"></i> استخدام موقعي الحالي
                        </button>
                    </div>
                    <div id="shopLocationMap"></div>
                    <div class="location-status" id="shopLocationStatus">
                        اضغط على الخريطة لتحديد موقع المحل، أو استخدم موقع الجهاز.
                    </div>
                    <input type="hidden" id="shopLatitude" name="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" id="shopLongitude" name="longitude" value="{{ old('longitude') }}">
                </div>
                <label>
                    <span>كلمة المرور</span>
                    <input type="password" name="password" dir="ltr" autocomplete="new-password" required>
                </label>
                <label>
                    <span>تأكيد كلمة المرور</span>
                    <input type="password" name="password_confirmation" dir="ltr" autocomplete="new-password" required>
                </label>
            </div>
            <button type="submit"><i class="fas fa-user-plus"></i> إنشاء المتجر والدخول</button>
        </form>
        <a class="back" href="{{ route('merchant.login', ['redirect' => $redirectTo]) }}">لدي حساب بالفعل — تسجيل الدخول</a>
    </main>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (() => {
            const form = document.querySelector('form');
            const latitudeInput = document.getElementById('shopLatitude');
            const longitudeInput = document.getElementById('shopLongitude');
            const status = document.getElementById('shopLocationStatus');
            const locateButton = document.getElementById('useCurrentLocationBtn');
            const defaultPosition = [31.90, 35.20];
            const savedLatitude = Number.parseFloat(latitudeInput.value);
            const savedLongitude = Number.parseFloat(longitudeInput.value);
            const hasSavedPosition = Number.isFinite(savedLatitude) && Number.isFinite(savedLongitude);
            const initialPosition = hasSavedPosition ? [savedLatitude, savedLongitude] : defaultPosition;
            const map = L.map('shopLocationMap').setView(initialPosition, hasSavedPosition ? 16 : 9);
            let marker = null;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            function selectLocation(latitude, longitude, center = true) {
                latitudeInput.value = Number(latitude).toFixed(7);
                longitudeInput.value = Number(longitude).toFixed(7);

                if (!marker) {
                    marker = L.marker([latitude, longitude], { draggable: true }).addTo(map);
                    marker.on('dragend', () => {
                        const position = marker.getLatLng();
                        selectLocation(position.lat, position.lng, false);
                    });
                } else {
                    marker.setLatLng([latitude, longitude]);
                }

                if (center) map.setView([latitude, longitude], 17);
                status.textContent = `تم تحديد موقع المحل: ${latitudeInput.value}, ${longitudeInput.value}`;
                status.classList.add('selected');
            }

            if (hasSavedPosition) selectLocation(savedLatitude, savedLongitude);
            map.on('click', (event) => selectLocation(event.latlng.lat, event.latlng.lng));

            locateButton.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    status.textContent = 'الجهاز لا يدعم تحديد الموقع. اختر موقع المحل بالضغط على الخريطة.';
                    return;
                }

                locateButton.disabled = true;
                status.textContent = 'جاري تحديد موقعك...';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        selectLocation(position.coords.latitude, position.coords.longitude);
                        locateButton.disabled = false;
                    },
                    () => {
                        status.textContent = 'تعذر الوصول للموقع. اسمح بصلاحية الموقع أو اضغط على الخريطة.';
                        locateButton.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 }
                );
            });

            form.addEventListener('submit', (event) => {
                if (!latitudeInput.value || !longitudeInput.value) {
                    event.preventDefault();
                    status.textContent = 'يجب تحديد موقع المحل على الخريطة قبل إنشاء الحساب.';
                    status.classList.remove('selected');
                    document.getElementById('shopLocationMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        })();
    </script>
</body>
</html>
