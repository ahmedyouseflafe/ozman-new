<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#101c20">
    @include('front.partials.seo', ['title'=>'أوزمان موتورز | سوق السيارات', 'description'=>'معاينة تصميم سوق السيارات الجديدة والمستعملة في أوزمان.', 'robots'=>'noindex, nofollow', 'image'=>route('cars.media','hero.png')])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>{!! file_get_contents(base_path('public/car-market.css')) !!}</style>
</head>
<body>
<svg class="icon-library" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M19 12H5m6-6-6 6 6 6"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/></symbol>
    <symbol id="i-heart" viewBox="0 0 24 24"><path d="M20.8 4.6a5.6 5.6 0 0 0-7.9 0L12 5.5l-.9-.9a5.6 5.6 0 0 0-7.9 7.9L12 21l8.8-8.5a5.6 5.6 0 0 0 0-7.9Z"/></symbol>
    <symbol id="i-pin" viewBox="0 0 24 24"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
    <symbol id="i-car" viewBox="0 0 24 24"><path d="m4 9 2-5h12l2 5M3 9h18v9H3zM5 18v3m14-3v3M6 13h2m8 0h2"/></symbol>
    <symbol id="i-speed" viewBox="0 0 24 24"><path d="M4 19a9 9 0 1 1 16 0M12 13l4-5M6 10l1 1m10 0 1-1M12 5v2"/><circle cx="12" cy="15" r="2"/></symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 2v6m10-6v6M3 11h18"/></symbol>
    <symbol id="i-filter" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2"/><circle cx="16" cy="12" r="2"/><circle cx="9" cy="18" r="2"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M6 18 18 6"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
</svg>
<div class="preview-strip"><span class="preview-dot"></span> معاينة التصميم <span class="preview-divider">/</span> السيارات والصور والأسعار أمثلة توضيحية وليست عروضًا للبيع.</div>
<header class="market-header">
    <div class="wrap nav-inner">
        <a class="wordmark" href="{{ route('cars.preview') }}" aria-label="أوزمان موتورز"><span class="brand-symbol">O<span></span></span><span><b dir="ltr">OZMAN<span class="brand-dot">.</span></b><small>MOTORS / سوق السيارات</small></span></a>
        <nav class="main-nav" aria-label="التنقل"><a href="#inventory" class="active">تصفّح السيارات</a><button type="button" data-condition-link="new">سيارات جديدة</button><button type="button" data-condition-link="used">سيارات مستعملة</button><a href="{{ route('front.home') }}">عالم أوزمان ↗</a></nav>
        <div class="nav-actions"><button class="icon-button" id="savedToggle" aria-label="عرض المفضلة" aria-pressed="false"><svg><use href="#i-heart"/></svg><span id="savedCount">0</span></button><button class="login-button" data-dialog="accountDialog"><svg><use href="#i-user"/></svg><span>دخول</span></button><button class="button primary add-top" data-dialog="sellDialog"><svg><use href="#i-plus"/></svg> أضف سيارتك</button></div>
    </div>
</header>
<main>
    <section class="hero-market wrap" aria-labelledby="heroTitle">
        <div class="hero-copy">
            <span class="eyebrow"><span></span> وجهتك الجديدة لعالم السيارات</span>
            <h1 id="heroTitle">مش بس سيارة.<br>بداية <em>طريق جديد.</em></h1>
            <p>من أول سيارة، لسيارة أحلامك.<br>اكتشف الجديدة والمستعملة، ولاقي اللي بتشبهك.</p>
            <div class="hero-links"><a class="button primary" href="#inventory">اكتشف السيارات <svg><use href="#i-arrow"/></svg></a><button class="text-button" data-dialog="sellDialog">بدك تبيع سيارتك؟ <span>↗</span></button></div>
            <div class="hero-notes"><span><svg><use href="#i-check"/></svg>جديدة ومستعملة</span><span><svg><use href="#i-check"/></svg>تواصل مباشر مع المعلن</span></div>
        </div>
        <div class="hero-visual">
            <img src="{{ route('cars.media','hero.png') }}" alt="صورة توضيحية مولّدة لسيارة كروس أوفر رمادية في معرض" fetchpriority="high" width="1536" height="1024">
            <span class="visual-label">THE NEXT CHAPTER</span>
            <div class="hero-caption"><span><small>لكل طريق، سيارته</small><b>اختيارات تناسب أسلوب حياتك.</b></span><a href="#inventory" aria-label="استكشف السيارات"><svg><use href="#i-arrow"/></svg></a></div>
            <small class="image-note">صورة توضيحية</small>
        </div>
    </section>
    <section class="search-section wrap" aria-label="ابحث عن سيارة">
        <div class="condition-tabs" role="group" aria-label="حالة السيارة">
            <button class="selected" data-condition="" aria-pressed="true">كل السيارات</button><button data-condition="new" aria-pressed="false">جديدة <span class="tiny-dot"></span></button><button data-condition="used" aria-pressed="false">مستعملة</button>
        </div>
        <form id="carSearch" class="search-bar">
            <label class="keyword-field"><span>شو السيارة اللي ببالك؟</span><div><svg><use href="#i-search"/></svg><input id="searchText" type="search" placeholder="ابحث بالاسم أو الماركة..." autocomplete="off"></div></label>
            <label><span>الماركة</span><select id="makeFilter"><option value="">كل الماركات</option>@foreach(collect($cars)->pluck('make')->unique() as $make)<option>{{ $make }}</option>@endforeach</select></label>
            <label><span>الميزانية</span><select id="priceFilter"><option value="">كل الأسعار</option><option value="100000">حتى 100,000 ₪</option><option value="200000">حتى 200,000 ₪</option><option value="300000">حتى 300,000 ₪</option></select></label>
            <label><span>المدينة</span><select id="cityFilter"><option value="">كل المدن</option>@foreach(collect($cars)->pluck('city')->unique() as $city)<option>{{ $city }}</option>@endforeach</select></label>
            <button class="button search-submit" type="submit"><svg><use href="#i-search"/></svg> ابحث عن سيارتك</button>
        </form>
    </section>
    <section class="wrap body-selector" aria-label="نوع السيارة">
        <span>شو بيلبقلك؟</span>
        <div class="body-options"><button data-body="" class="selected" aria-pressed="true">كل الأنواع</button><button data-body="sedan" aria-pressed="false"><svg><use href="#i-car"/></svg> سيدان</button><button data-body="suv" aria-pressed="false"><svg><use href="#i-car"/></svg> دفع رباعي <b>SUV</b></button><button data-body="hatchback" aria-pressed="false"><svg><use href="#i-car"/></svg> هاتشباك</button><button data-body="coupe" aria-pressed="false"><svg><use href="#i-car"/></svg> كوبيه ورياضية</button></div>
    </section>
    <section class="inventory wrap" id="inventory" aria-labelledby="inventoryTitle">
        <div class="section-title"><div><span class="eyebrow light">اكتشف. قارن. اختار.</span><h2 id="inventoryTitle">سيارتك الجاية هون<span>.</span></h2><p id="resultCount" role="status">{{ count($cars) }} سيارات في معاينة التصميم</p></div><div class="inventory-actions"><button class="reset-button" id="resetFilters" hidden>مسح الفلاتر</button><label class="sort-label">الترتيب<select id="sortCars"><option value="featured">المميزة أولًا</option><option value="priceAsc">السعر: الأقل أولًا</option><option value="priceDesc">السعر: الأعلى أولًا</option><option value="newest">سنة الصنع: الأحدث</option></select></label></div></div>
        <div class="car-grid" id="carGrid">
            @foreach($cars as $car)
            <article class="car-card" data-car="{{ $car['id'] }}">
                <div class="card-photo">
                    <button class="photo-open" data-details="{{ $car['id'] }}" aria-label="عرض {{ $car['name'] }}"><img src="{{ route('cars.media', $car['image']) }}" alt="صورة توضيحية: {{ $car['name'] }}" width="880" height="587" loading="lazy"></button>
                    <span class="condition-badge {{ $car['condition']==='new' ? 'is-new' : '' }}">{{ $car['condition']==='new' ? 'جديدة' : 'مستعملة' }}</span>
                    <button class="save-car" data-save="{{ $car['id'] }}" aria-label="حفظ {{ $car['name'] }} في المفضلة" aria-pressed="false"><svg><use href="#i-heart"/></svg></button>
                    @if($car['featured'])<span class="featured-badge">✦ اختيار مميز</span>@endif
                    <span class="demo-badge">إعلان توضيحي</span>
                </div>
                <div class="card-info">
                    <div class="card-location"><span><svg><use href="#i-pin"/></svg>{{ $car['city'] }}</span><span>بائع فردي · مثال</span></div>
                    <h3><button data-details="{{ $car['id'] }}">{{ $car['name'] }}</button></h3><p class="model-label" dir="ltr">{{ $car['model'] }}</p>
                    <div class="car-specs"><span><svg><use href="#i-calendar"/></svg>{{ $car['year'] }}</span><span><svg><use href="#i-speed"/></svg><bdi>{{ number_format($car['km']) }}</bdi> كم</span><span>{{ $car['gear'] }}</span></div>
                    <div class="card-bottom"><div><span class="price"><bdi>{{ number_format($car['price']) }}</bdi> <small>₪</small></span><small class="price-note">سعر توضيحي</small></div><button class="details-button" data-details="{{ $car['id'] }}">التفاصيل <svg><use href="#i-arrow"/></svg></button></div>
                </div>
            </article>
            @endforeach
        </div>
        <div class="empty-results" id="emptyResults" hidden><svg><use href="#i-search"/></svg><h3>ما لقينا سيارة بهالمواصفات</h3><p>جرّب توسّع البحث أو تمسح بعض الفلاتر.</p><button class="button primary" id="emptyReset">عرض كل السيارات</button></div>
        <p class="catalog-footnote">هذه مجموعة أمثلة لمعاينة شكل الإعلانات. المواصفات والأسعار لا تمثّل عروضًا حقيقية.</p>
    </section>
    <section class="sell-banner wrap"><div class="sell-illustration" aria-hidden="true"><svg><use href="#i-car"/></svg><span>+</span></div><div><span class="eyebrow">سيارتك إلها مشوار جديد</span><h2>سيارتك للبيع؟ خلّيها تنشاف.</h2><p>صور واضحة، معلومات كاملة، وسعر مناسب.<br>هيك بتبدأ الخطوة الأولى مع المشتري المناسب.</p></div><button class="button primary" data-dialog="sellDialog">أضف إعلان سيارتك <svg><use href="#i-plus"/></svg></button></section>
    <section class="how-it-works wrap"><div><span class="step-number">01</span><h3>اعمل حسابك</h3><p>مساحتك لإضافة سياراتك وإدارة إعلاناتك.</p></div><div><span class="step-number">02</span><h3>احكي عن سيارتك</h3><p>أضف الصور والمواصفات والسعر المطلوب.</p></div><div><span class="step-number">03</span><h3>انشر وتواصل</h3><p>إعلانك يظهر مباشرة، والمشتري بتواصل معك.</p></div></section>
</main>
<footer class="market-footer wrap"><a class="footer-brand" href="{{ route('front.home') }}">OZMAN<span>.</span> <small>مساحة جديدة لكل مشوار.</small></a><span>سوق السيارات · معاينة التصميم</span><a href="#heroTitle">العودة للأعلى ↑</a></footer>
<nav class="mobile-nav" aria-label="قائمة الجوال"><a href="#inventory"><svg><use href="#i-car"/></svg>السيارات</a><button data-dialog="sellDialog"><svg><use href="#i-plus"/></svg>أضف سيارتك</button><button id="savedMobile"><svg><use href="#i-heart"/></svg>المفضلة</button><button data-dialog="accountDialog"><svg><use href="#i-user"/></svg>حسابي</button></nav>
<dialog id="detailsDialog" class="market-dialog details-dialog" aria-labelledby="detailTitle"><button class="dialog-close" data-close aria-label="إغلاق"><svg><use href="#i-close"/></svg></button><img id="detailImage" class="detail-image" alt=""><div class="dialog-body"><span class="eyebrow light">معاينة إعلان</span><h2 id="detailTitle"></h2><p id="detailLocation"></p><div id="detailSpecs" class="detail-specs"></div><div class="detail-price" id="detailPrice"></div><h3>عن السيارة</h3><p>هون رح تظهر تفاصيل السيارة اللي بيكتبها صاحب الإعلان، حالتها، الإضافات الموجودة فيها، ومعلومات الفحص والصيانة.</p><div class="contact-preview"><svg><use href="#i-user"/></svg><span><b>اسم صاحب السيارة</b><small>بيانات البائع تظهر مع الإعلان الحقيقي</small></span></div><button class="button primary wide" data-preview-contact>تواصل مع البائع</button><small class="dialog-note">إعلان توضيحي، لا توجد سيارة أو جهة بيع مرتبطة به.</small></div></dialog>
<dialog id="accountDialog" class="market-dialog form-dialog" aria-labelledby="accountTitle"><button class="dialog-close" data-close aria-label="إغلاق"><svg><use href="#i-close"/></svg></button><div class="dialog-body"><span class="eyebrow light">أهلًا في أوزمان موتورز</span><h2 id="accountTitle">كل مشوار ببلّش بخطوة.</h2><p>سجّل دخولك واحفظ اختياراتك وأضف سيارتك.</p><div class="form-tabs"><button data-account-tab="login" class="selected" aria-pressed="true">تسجيل دخول</button><button data-account-tab="register" aria-pressed="false">حساب جديد</button></div><form id="accountPreview" method="dialog"><label id="registerName" hidden>الاسم الكامل<input name="name" autocomplete="off" placeholder="اسمك"></label><label>البريد الإلكتروني<input type="email" name="email" autocomplete="off" placeholder="name@example.com" dir="ltr" required></label><label>كلمة المرور<input type="password" name="password" autocomplete="new-password" minlength="8" placeholder="8 أحرف على الأقل" required></label><p class="preview-form-note">معاينة للشكل فقط؛ لا تدخل بيانات حقيقية. التسجيل الفعلي بالمرحلة التالية.</p><button class="button primary wide" type="submit" id="accountSubmit">معاينة الدخول</button></form></div></dialog>
<dialog id="sellDialog" class="market-dialog form-dialog" aria-labelledby="sellTitle"><button class="dialog-close" data-close aria-label="إغلاق"><svg><use href="#i-close"/></svg></button><div class="dialog-body"><span class="eyebrow light">إعلانك يبدأ من هون</span><h2 id="sellTitle">عرّفنا على سيارتك.</h2><p>معاينة نموذج الإعلان — البيانات لن تُحفظ أو تُنشر.</p><form id="sellPreview" method="dialog"><label class="upload-preview"><svg><use href="#i-plus"/></svg><b>أضف صورة سيارتك</b><small>JPG، PNG أو WebP · حتى 8 ميجابايت</small><input id="previewPhoto" type="file" accept="image/jpeg,image/png,image/webp"><img id="uploadThumbnail" alt="معاينة صورة السيارة" hidden></label><label>عنوان الإعلان<input name="title" placeholder="مثلًا: فولكس فاجن جولف 2021" maxlength="100" required></label><div class="form-row"><label>حالة السيارة<select name="condition"><option>مستعملة</option><option>جديدة</option></select></label><label>السعر المطلوب (₪)<input type="number" name="price" min="1" placeholder="85,000" required></label></div><div class="form-row"><label>سنة الصنع<input type="number" min="1950" max="{{ now()->year+1 }}" name="year" placeholder="2021" required></label><label>المسافة (كم)<input type="number" min="0" name="km" placeholder="45,000" required></label></div><label>معلومات إضافية<textarea rows="3" placeholder="احكي عن حالة السيارة ومواصفاتها..." maxlength="1500"></textarea></label><button class="button primary wide" type="submit">معاينة الخطوة التالية <svg><use href="#i-arrow"/></svg></button></form></div></dialog>
<div class="toast" id="marketToast" role="status" hidden></div>
<script>window.carMarketExamples = @json($cars); window.carMarketMediaBase = @json(url('/cars/media'));</script>
<script>{!! file_get_contents(base_path('public/car-market.js')) !!}</script>
</body>
</html>
