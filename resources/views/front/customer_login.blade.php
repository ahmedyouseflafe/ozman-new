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

    @viewScript('front.customer_login')
</body>

</html>
