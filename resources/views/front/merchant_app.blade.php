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
    <style>
        :root{--cyan:#0bdcf3;--green:#2bdd8a;--panel:#0d1a22;--border:#203b49;--muted:#93aab7}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 82% 5%,rgba(11,220,243,.17),transparent 34%),radial-gradient(circle at 5% 84%,rgba(101,66,255,.14),transparent 30%),#05090d;color:#f8fbfd;font-family:Cairo,Arial,sans-serif}.page{width:min(1040px,calc(100% - 28px));margin:auto;padding:32px 0 60px}.top{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:20px}.back{padding:10px 15px;border:1px solid var(--border);border-radius:13px;color:#d5e2e8;text-decoration:none;font-size:13px;font-weight:800}.brand{display:flex;align-items:center;gap:10px;color:var(--cyan);font-weight:900}.brand i{width:11px;height:11px;border-radius:50%;background:var(--green);box-shadow:0 0 15px var(--green)}.card{display:grid;grid-template-columns:minmax(0,1fr) 350px;overflow:hidden;border:1px solid var(--border);border-radius:30px;background:linear-gradient(135deg,#101d26,#08131a);box-shadow:0 25px 80px rgba(0,0,0,.32)}.content{padding:clamp(25px,5vw,56px)}.eyebrow{color:var(--cyan);font-size:12px;font-weight:900}.content h1{margin:9px 0 12px;font-size:clamp(30px,5vw,55px);line-height:1.15}.lead{margin:0 0 28px;color:var(--muted);line-height:1.9;font-size:15px}.actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}.btn{min-height:52px;display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 16px;border:1px solid var(--border);border-radius:15px;background:#0b1720;color:#fff;font:800 14px Cairo,Arial,sans-serif;text-decoration:none;cursor:pointer}.btn-primary{grid-column:1/-1;border:0;background:linear-gradient(135deg,var(--cyan),#3eb8ff);color:#001319;font-size:16px}.btn-notifications.enabled{border-color:rgba(43,221,138,.5);color:var(--green)}.status{min-height:25px;margin:15px 0 0;color:var(--muted);font-size:12px;font-weight:700}.status[data-state=success]{color:var(--green)}.status[data-state=error]{color:#ff7f8d}.preview{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:38px 28px;background:radial-gradient(circle,rgba(11,220,243,.11),transparent 58%),#071118;border-inline-start:1px solid var(--border)}.logo-frame{width:210px;height:210px;padding:10px;border-radius:48px;background:linear-gradient(145deg,var(--cyan),var(--green),#7c60ff);box-shadow:0 0 45px rgba(11,220,243,.28)}.logo-frame img{display:block;width:100%;height:100%;object-fit:cover;border-radius:39px;background:#03080b}.preview strong{margin-top:19px;font-size:24px;text-align:center}.preview span{margin-top:5px;color:var(--muted);font-size:12px}.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px}.step{padding:17px;border:1px solid var(--border);border-radius:18px;background:rgba(13,26,34,.78)}.step b{display:grid;place-items:center;width:31px;height:31px;margin-bottom:10px;border-radius:50%;background:rgba(11,220,243,.12);color:var(--cyan)}.step strong{display:block;font-size:13px}.step p{margin:6px 0 0;color:var(--muted);font-size:11px;line-height:1.7}@media(max-width:760px){.page{padding-top:14px}.card{grid-template-columns:1fr}.preview{grid-row:1;padding:28px;border:0;border-bottom:1px solid var(--border)}.logo-frame{width:150px;height:150px;border-radius:36px}.logo-frame img{border-radius:29px}.preview strong{font-size:20px}.content{padding:24px 18px}.actions{grid-template-columns:1fr}.btn,.btn-primary{grid-column:1}.steps{grid-template-columns:1fr}.top{align-items:flex-start}.brand{font-size:12px}}
    </style>
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
