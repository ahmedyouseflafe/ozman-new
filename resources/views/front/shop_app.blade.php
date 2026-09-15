<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="description" content="حمّل تطبيق {{ $shop->name }} وافتح {{ $shop->catalog_type === 'restaurant' ? 'قائمة الطعام' : 'المحل' }} مباشرة من جوالك.">
    <title>تحميل تطبيق {{ $shop->name }}</title>
    @include('front.partials.merchant_pwa_head', [
        'pwaShop' => $shop,
        'offerNotificationsPrompt' => false,
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{--cyan:#0bdcf3;--green:#2bdd8a;--panel:#0d1a22;--border:#203b49;--muted:#93aab7}*{box-sizing:border-box}body{min-height:100vh;margin:0;background:radial-gradient(circle at 82% 5%,rgba(11,220,243,.17),transparent 34%),radial-gradient(circle at 5% 84%,rgba(101,66,255,.14),transparent 30%),#05090d;color:#f8fbfd;font-family:Cairo,Arial,sans-serif}.page{display:grid;place-items:center;width:min(960px,calc(100% - 28px));min-height:100vh;margin:auto;padding:28px 0}.card{display:grid;grid-template-columns:minmax(0,1fr) 330px;width:100%;overflow:hidden;border:1px solid var(--border);border-radius:30px;background:linear-gradient(135deg,#101d26,#08131a);box-shadow:0 25px 80px rgba(0,0,0,.34)}.content{padding:clamp(27px,5vw,54px)}.eyebrow{display:inline-flex;align-items:center;gap:8px;color:var(--cyan);font-size:12px;font-weight:900}.eyebrow::before{content:"";width:9px;height:9px;border-radius:50%;background:var(--green);box-shadow:0 0 13px var(--green)}h1{margin:11px 0 13px;font-size:clamp(30px,5vw,53px);line-height:1.2}.lead{margin:0 0 27px;color:var(--muted);line-height:1.9;font-size:15px}.actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}.btn{min-height:53px;display:flex;align-items:center;justify-content:center;padding:11px 16px;border:1px solid var(--border);border-radius:15px;background:#0b1720;color:#fff;font:800 14px Cairo,Arial,sans-serif;text-decoration:none;cursor:pointer}.btn-primary{grid-column:1/-1;border:0;background:linear-gradient(135deg,var(--cyan),#3eb8ff);color:#001319;font-size:16px}.status{min-height:25px;margin:15px 0 0;color:var(--muted);font-size:12px;font-weight:700}.status[data-state=success]{color:var(--green)}.status[data-state=error]{color:#ff7f8d}.preview{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:38px 27px;background:radial-gradient(circle,rgba(11,220,243,.12),transparent 58%),#071118;border-inline-start:1px solid var(--border)}.logo-frame{width:205px;height:205px;padding:9px;border-radius:47px;background:linear-gradient(145deg,var(--cyan),var(--green),#7c60ff);box-shadow:0 0 45px rgba(11,220,243,.28)}.logo-frame img{display:block;width:100%;height:100%;object-fit:cover;border-radius:39px;background:#03080b}.preview strong{margin-top:18px;font-size:23px;text-align:center}.preview span{margin-top:5px;color:var(--muted);font-size:12px;text-align:center;line-height:1.7}@media(max-width:720px){.page{padding:12px 0}.card{grid-template-columns:1fr;border-radius:25px}.preview{grid-row:1;padding:27px 20px;border:0;border-bottom:1px solid var(--border)}.logo-frame{width:148px;height:148px;border-radius:35px}.logo-frame img{border-radius:28px}.preview strong{font-size:20px}.content{padding:25px 18px}.actions{grid-template-columns:1fr}.btn,.btn-primary{grid-column:1}}
    </style>
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
                <a class="btn" href="{{ $shop->publicUrl() }}">{{ $shop->catalog_type === 'restaurant' ? 'فتح قائمة الطعام' : 'فتح المحل' }}</a>
                <button class="btn" type="button" data-shop-pwa-share>مشاركة رابط التطبيق</button>
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
