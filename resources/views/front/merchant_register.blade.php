<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب متجر - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @viewStyle('front.merchant_register')
</head>

<body>
    <main class="card">
        <div class="head">
            <div class="icon"><i class="fas fa-store"></i></div>
            <h1>إنشاء حساب متجر جديد</h1>
            <p>سيتم ربط متجرك تلقائيًا بالموزع أو المروّج صاحب QR، وستصل طلباتك للجهة المرتبطة.</p>
        </div>

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('merchant.register.store') }}" enctype="multipart/form-data">
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
                <label class="logo-field">
                    <span><i class="fas fa-image"></i> شعار المحل</span>
                    <input id="shopLogoInput" type="file" name="logo" accept="image/jpeg,image/png,image/webp">
                    <small class="logo-hint">اختر صورة JPG أو PNG أو WebP، بحجم لا يزيد عن 2MB.</small>
                    <img id="shopLogoPreview" class="logo-preview" alt="معاينة شعار المحل">
                </label>
                <label>
                    <span>البريد الإلكتروني</span>
                    <input type="email" name="email" value="{{ old('email') }}" dir="ltr" autocomplete="email"
                        required>
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
                    <input type="password" name="password_confirmation" dir="ltr" autocomplete="new-password"
                        required>
                </label>
            </div>
            <button type="submit"><i class="fas fa-user-plus"></i> إنشاء المتجر والدخول</button>
        </form>
        <a class="back" href="{{ route('merchant.login', ['redirect' => $redirectTo]) }}">لدي حساب بالفعل — تسجيل
            الدخول</a>
    </main>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @viewScript('front.merchant_register')
</body>

</html>
