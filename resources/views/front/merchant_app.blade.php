<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <title>تثبيت تطبيق {{ $shop->name }}</title>
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop, 'vapidPublicKey' => $vapidPublicKey])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    @viewStyle('front.merchant_app')
</head>
<body>
<main class="page">
    <div class="top">
        <div class="brand"><i aria-hidden="true"></i> تطبيق خاص بمحلك</div>
        <a class="back" href="{{ route($shop->dashboardRouteName(), $shop) }}">العودة إلى الإدارة</a>
    </div>

    <section class="card">
        <div class="content">
            <span class="eyebrow">جاهز للتثبيت على الجوال</span>
            <h1>تطبيق {{ $shop->name }}</h1>
            <p class="lead">ثبّت واجهة محلك باسمك وشعارك. عند فتح التطبيق سينتقل مباشرة إلى محلك، وستصل طلباتك كإشعارات حتى عندما يكون التطبيق مغلقًا.</p>

            <div class="actions">
                <button class="btn btn-primary" type="button" data-pwa-install>تثبيت تطبيق {{ $shop->name }}</button>
                <button class="btn btn-notifications" type="button" data-pwa-notifications>تفعيل إشعارات الطلبات</button>
                <a class="btn" href="{{ $shop->publicUrl() }}">فتح واجهة المحل</a>
                <a class="btn" href="{{ route('shop-app.index', $shop) }}">رابط تطبيق الزبائن</a>
                <a class="btn" href="{{ route($shop->dashboardRouteName(), $shop) }}">إدارة المحل</a>
            </div>
            <p class="status" data-pwa-status>جارٍ تجهيز التطبيق لهذا الجهاز…</p>
        </div>

        <div class="preview">
            <div class="logo-frame"><img src="{{ route('merchant-app.icon', ['shop' => $shop, 'size' => 512]) }}" alt="شعار {{ $shop->name }}"></div>
            <strong>{{ $shop->name }}</strong>
            <span>سيظهر بهذا الشكل على شاشة الجوال</span>
        </div>
    </section>

    <section class="steps" aria-label="خطوات التثبيت">
        <article class="step"><b>1</b><strong>ثبّت التطبيق</strong><p>على Android اضغط زر التثبيت. على iPhone استخدم مشاركة ثم إضافة إلى الشاشة الرئيسية.</p></article>
        <article class="step"><b>2</b><strong>اسم وشعار محلك</strong><p>يُنشأ التطبيق باسم المحل وشعاره المسجلين في لوحة التحكم.</p></article>
        <article class="step"><b>3</b><strong>فعّل الإشعارات</strong><p>اسمح بالإشعارات لتصلك الطلبات الجديدة حتى عندما تكون الواجهة مغلقة.</p></article>
    </section>
</main>
</body>
</html>
