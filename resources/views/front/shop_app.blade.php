<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="description" content="حمّل تطبيق {{ $shop->name }} وافتح {{ $shop->catalog_type === 'restaurant' ? 'قائمة الطعام' : 'المحل' }} مباشرة من جوالك.">
    <title>تحميل تطبيق {{ $shop->name }}</title>
    @include('front.partials.merchant_pwa_head', [
        'pwaShop' => $shop,
        'forceCustomerPwa' => true,
        'offerNotificationsPrompt' => false,
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    @viewStyle('front.shop_app')
</head>
<body>
<main class="page">
    <section class="card">
        <div class="content">
            <span class="eyebrow">التطبيق الرسمي للمحل</span>
            <h1>تطبيق {{ $shop->name }}</h1>
            <p class="lead">
                ثبّت التطبيق باسم وشعار {{ $shop->name }}، وافتح
                {{ $shop->catalog_type === 'restaurant' ? 'قائمة الطعام' : 'المحل' }}
                مباشرة من شاشة جوالك بدون تسجيل دخول.
            </p>

            <div class="actions">
                <button class="btn btn-primary" type="button" data-shop-pwa-install>تثبيت تطبيق {{ $shop->name }}</button>
                <section class="ios-help" id="shop-pwa-ios-help" data-shop-pwa-ios hidden tabindex="-1" aria-labelledby="shop-pwa-ios-title">
                    <h2 id="shop-pwa-ios-title">إضافة {{ $shop->name }} على iPhone أو iPad</h2>
                    <p data-shop-pwa-ios-browser hidden>افتح رابط التثبيت في Safari أولًا. إذا أنت داخل تطبيق، انسخ الرابط من الزر أدناه والصقه في Safari.</p>
                    <ol>
                        <li>من شريط Safari اضغط <strong>مشاركة</strong> (مربع وسهم للأعلى). إذا مش ظاهرة، افتح قائمة <strong>…</strong> ثم «مشاركة».</li>
                        <li>مرّر قائمة المشاركة واختر <strong>إضافة إلى الشاشة الرئيسية</strong> (Add to Home Screen).</li>
                        <li>إذا ظهر خيار <strong>فتح كتطبيق ويب</strong> (Open as Web App)، اتركه مفعّلًا، ثم اضغط <strong>إضافة</strong>.</li>
                    </ol>
                    <p>بعد الإضافة افتح التطبيق من أيقونة {{ $shop->name }} على الشاشة الرئيسية.</p>
                    <p>إذا خيار الإضافة مش ظاهر، انزل لآخر قائمة المشاركة واضغط «تعديل الإجراءات» وأضف «إضافة إلى الشاشة الرئيسية».</p>
                </section>
                <a class="btn" href="{{ $shop->publicUrl() }}">{{ $shop->catalog_type === 'restaurant' ? 'فتح قائمة الطعام' : 'فتح المحل' }}</a>
                <button class="btn" type="button" data-shop-pwa-share>مشاركة رابط التطبيق</button>
                <button class="btn" type="button" data-shop-pwa-copy>نسخ رابط التثبيت</button>
            </div>
            <p class="status" data-shop-pwa-status>اضغط زر تثبيت التطبيق للمتابعة.</p>
        </div>

        <div class="preview">
            <div class="logo-frame">
                <img src="{{ route('merchant-app.icon', ['shop' => $shop, 'size' => 512]) }}" alt="شعار {{ $shop->name }}">
            </div>
            <strong>{{ $shop->name }}</strong>
            <span>سيظهر بهذا الاسم والشعار على شاشة الجوال</span>
        </div>
    </section>
</main>
</body>
</html>
