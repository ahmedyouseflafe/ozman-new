<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل بيانات العميل - Ozman</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
</head>

<body class="customer-page-body">
    <main class="customer-page">
        <a href="{{ route('home') }}" class="location-btn customer-page-back">
            <i class="fas fa-chevron-right"></i>
            رجوع للموقع
        </a>

        <section class="modal-content glass customer-login-card customer-page-card">
            <div class="modal-header">
                <h3><i class="fas fa-user-check"></i> تسجيل بيانات العميل</h3>
            </div>

            <form class="customer-login-form" id="standaloneCustomerForm">
                <div class="customer-fields-grid">
                    <label class="customer-field">
                        <span>اسم العميل</span>
                        <input type="text" id="customerName" placeholder="اكتب اسمك" required>
                    </label>

                    <label class="customer-field">
                        <span>رقم الهاتف</span>
                        <input type="tel" id="customerPhone" placeholder="05xxxxxxxx" inputmode="tel" maxlength="16"
                            pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                            title="أدخل رقم جوال صحيح مثل 0591234567" dir="ltr" required>
                    </label>

                    <label class="customer-field">
                        <span>رقم الواتس اب</span>
                        <input type="tel" id="customerWhatsapp" placeholder="رقم واتساب للتواصل" inputmode="tel" maxlength="16"
                            pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                            title="أدخل رقم واتساب صحيح مثل 0591234567" dir="ltr" required>
                    </label>

                    <label class="customer-field">
                        <span>العنوان / اللوكيشن</span>
                        <textarea id="customerAddress" rows="3" placeholder="اكتب المدينة، الحي، أقرب علامة"></textarea>
                    </label>
                </div>

                <div class="customer-map-box">
                    <div class="customer-map-head">
                        <div>
                            <strong>تحديد الموقع على الخريطة</strong>
                            <span id="customerLocationStatus">اضغط على زر تحديد موقعي لاختيار موقعك الحالي.</span>
                        </div>
                        <button type="button" class="customer-map-btn" id="detectCustomerLocationBtn">
                            <i class="fas fa-crosshairs"></i>
                            حدد موقعي
                        </button>
                    </div>
                    <iframe id="customerMapFrame" src="about:blank" width="100%" height="260" style="border:0;" loading="lazy" allowfullscreen></iframe>
                    <input type="hidden" id="customerLatitude">
                    <input type="hidden" id="customerLongitude">
                    <input type="hidden" id="customerMapLink">
                </div>

                <button type="submit" class="cart-checkout-btn">
                    <i class="fas fa-check"></i>
                    حفظ البيانات
                </button>
            </form>
        </section>
    </main>

    <script>
        const storageKey = 'ozman_customer_profile';
        const visitorDoneKey = 'ozman_visitor_registration_done_v2';
        const visitorTypeKey = 'ozman_visitor_type';
        const form = document.getElementById('standaloneCustomerForm');
        const mapFrame = document.getElementById('customerMapFrame');
        const statusText = document.getElementById('customerLocationStatus');
        const fields = {
            name: document.getElementById('customerName'),
            phone: document.getElementById('customerPhone'),
            whatsapp: document.getElementById('customerWhatsapp'),
            address: document.getElementById('customerAddress'),
            latitude: document.getElementById('customerLatitude'),
            longitude: document.getElementById('customerLongitude'),
            mapLink: document.getElementById('customerMapLink'),
        };

        function saveStandaloneCustomerProfile(profile) {
            localStorage.setItem(storageKey, JSON.stringify(profile || {}));
            if (profile?.name && (profile?.phone || profile?.whatsapp)) {
                localStorage.setItem(visitorDoneKey, '1');
                localStorage.setItem(visitorTypeKey, 'customer');
            }
        }

        try {
            const profile = JSON.parse(localStorage.getItem(storageKey) || '{}');
            Object.keys(fields).forEach((key) => fields[key].value = profile[key] || '');
            if (profile.latitude && profile.longitude) {
                if (!fields.mapLink.value) fields.mapLink.value = `https://www.google.com/maps?q=${profile.latitude},${profile.longitude}`;
                mapFrame.src = `https://www.google.com/maps?q=${profile.latitude},${profile.longitude}&z=16&output=embed`;
                statusText.textContent = 'تم تحميل الموقع المحفوظ مسبقا.';
            }
        } catch (error) {}

        document.getElementById('detectCustomerLocationBtn').addEventListener('click', () => {
            if (!navigator.geolocation) {
                statusText.textContent = 'المتصفح لا يدعم تحديد الموقع تلقائيا.';
                return;
            }

            statusText.textContent = 'جاري تحديد موقعك...';
            navigator.geolocation.getCurrentPosition((position) => {
                const latitude = position.coords.latitude.toFixed(7);
                const longitude = position.coords.longitude.toFixed(7);
                fields.latitude.value = latitude;
                fields.longitude.value = longitude;
                fields.mapLink.value = `https://www.google.com/maps?q=${latitude},${longitude}`;
                mapFrame.src = `https://www.google.com/maps?q=${latitude},${longitude}&z=16&output=embed`;
                statusText.textContent = 'تم تحديد موقعك على الخريطة.';
                saveStandaloneCustomerProfile(Object.fromEntries(Object.entries(fields).map(([key, field]) => [key, field.value.trim()])));
            }, () => {
                statusText.textContent = 'لم نقدر نحدد الموقع. تأكد من السماح للموقع بالوصول للّوكيشن.';
            }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const profile = Object.fromEntries(Object.entries(fields).map(([key, field]) => [key, field.value.trim()]));
            saveStandaloneCustomerProfile(profile);
            statusText.textContent = 'تم حفظ بياناتك. ارجع للموقع وكمل طلبك.';
        });
    </script>
</body>

</html>
