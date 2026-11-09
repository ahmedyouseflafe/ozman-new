<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مركز متابعة المحلات | Ozman</title>
    <link rel="manifest" href="/admin/analytics/manifest.webmanifest">
    <link rel="icon" type="image/png" href="/admin/analytics/icon/192.png">
    <link rel="apple-touch-icon" href="/admin/analytics/icon/180.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="إدارة أوزمان">
    <meta name="theme-color" content="#071118">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    @include('admin.analytics.styles')
</head>
<body>
<div class="analytics-shell">
    <aside class="analytics-sidebar">
        <a class="brand" href="{{ route('admin.analytics.index') }}"><span class="brand-icon">O</span><span>Ozman<small>مركز الإدارة والمتابعة</small></span></a>
        <div class="sidebar-label">مساحة الإدارة</div>
        <nav aria-label="أقسام المتابعة">
            <a class="selected" href="#overview"><span>◈</span>نظرة عامة</a>
            <a href="#shops"><span>▦</span>أداء المحلات</a>
            <a href="#orders"><span>▤</span>الطلبات والمبيعات</a>
            <a href="#assistant"><span>✦</span>مساعد التحليل</a>
            <a href="#alerts"><span>⚑</span>تنبيهات</a>
            <a href="#funnel"><span>⇣</span>التحويل والمطبخ</a>
            <a href="#heatmap"><span>▩</span>أوقات الذروة</a>
            <a href="#engagement"><span>♥</span>التفاعل والنمو</a>
            <a href="#activity"><span>◷</span>سجل النشاط</a>
            <a href="{{ route('dashboard') }}"><span>↗</span>لوحة الإدارة العادية</a>
        </nav>
        <div class="sidebar-note"><span class="live-dot"></span> متابعة من مكان واحد<p>بيانات المحلات وإشعارات الطلبات والمبيعات، بصلاحية الإدارة الرئيسية.</p></div>
        <div class="admin-person"><span>{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div>{{ auth()->user()->name }}<small>الإدارة الرئيسية</small></div></div>
    </aside>
    <main class="analytics-main" id="overview">
        <header class="analytics-top"><span>أوزمان <b>/</b> إحصائيات المحلات</span><span class="live-label" id="syncStatus" role="status">جارٍ تحميل البيانات…</span></header>
        <div class="page-intro"><div><span class="eyebrow">كل محلاتك، صورة أوضح</span><h1>مركز متابعة المحلات</h1><p>تابع الأداء، افهم مبيعاتك، وابقَ على اطلاع بكل عملية.</p></div><button id="exportReport" type="button" class="button secondary">↓ تصدير التقرير CSV</button></div>
        @unless($ready)<div class="notice error">اللوحة جاهزة، ويلزم تشغيل <bdi>php artisan migrate --force</bdi> على السيرفر لتفعيل الإحصائيات الجديدة.</div>@endunless
        @include('admin.analytics.push')
        <form id="analyticsFilters" class="filters panel">
            <label class="shop-filter">المتجر<select name="shop_id" id="shopFilter"><option value="">كل المحلات</option>@foreach($shops as $shop)<option value="{{ $shop->id }}">{{ $shop->name }}</option>@endforeach</select></label>
            <label>من تاريخ<input name="from" type="date" value="{{ now()->startOfMonth()->toDateString() }}" required></label>
            <label>إلى تاريخ<input name="to" type="date" value="{{ now()->toDateString() }}" required></label>
            <button class="button primary" type="submit">عرض النتائج</button>
            <div class="presets"><button type="button" data-days="1">اليوم</button><button type="button" data-days="7">7 أيام</button><button type="button" data-days="30">30 يومًا</button></div>
        </form>
        <div id="analyticsError" class="notice error" role="alert" hidden></div>
        <section class="panel assistant-panel" id="assistant">
            <div class="section-head"><div><h2>✦ مساعد التحليل</h2><p>بيقرأ أرقام الفترة والمحل المختارين، وبيعطيك الخلاصة والمشاكل وشو تعمل. واسأله اللي بدك.</p></div><button type="button" class="button primary" id="assistantAnalyze">حلّل الفترة</button></div>
            <div class="assistant-chips" id="assistantChips">
                <button type="button">ليش المبيعات طلعت أو نزلت؟</button>
                <button type="button">أي محلات لازم أتواصل معها اليوم؟ وشو أحكيلهم؟</button>
                <button type="button">وين الزباين بيوقفوا وما بيطلبوا؟</button>
                <button type="button">شو أفضل أوقات للعروض والبوستات؟</button>
                <button type="button">اقترحلي خطة للأسبوع الجاي</button>
            </div>
            <div class="assistant-log" id="assistantLog" aria-live="polite"><div class="assistant-empty">اكبس «حلّل الفترة» أو اختار سؤال جاهز أو اكتب سؤالك تحت.</div></div>
            <form class="assistant-form" id="assistantForm"><textarea id="assistantInput" rows="2" maxlength="1000" placeholder="اسأل عن أي إشي بالأرقام… مثلاً: ليش بانكاي فيه زوار كثير وطلبات قليلة؟"></textarea><button type="submit" class="button primary" id="assistantSend">إرسال</button></form>
            <div class="note">المساعد بيشوف نفس الأرقام اللي بالصفحة بس (بدون أرقام تلفونات الزباين). إذا غيّرت الفترة أو المحل، بتبلّش محادثة جديدة.</div>
        </section>
        <section class="panel alerts-panel" id="alerts"><div class="section-head"><div><h2>تنبيهات بحاجة لانتباهك</h2><p>طلبات ما انرد عليها، محلات وقّفت الطلبات، أصحاب محلات غايبين، ومبيعات نازلة</p></div><span class="pill" id="alertsCount">0</span></div><div id="alertsList" class="alerts-list"></div></section>
        <section id="metrics" class="metrics" aria-label="ملخص الإحصائيات" aria-live="polite"></section>
        <div class="overview-grid">
            <section class="panel revenue-panel"><div class="section-head"><div><h2>حركة المبيعات</h2><p>قيمة المبيعات المؤكدة حسب تاريخ إتمام البيع</p></div><span class="pill">₪ شيكل</span></div><div id="salesChart" class="sales-chart"></div><div id="comparison" class="comparison"></div></section>
            <section class="panel"><div class="section-head"><div><h2>حالة الطلبات</h2><p>الطلبات المنشأة خلال الفترة المحددة</p></div></div><div id="orderStatuses" class="status-list"></div><div class="note">فتح محادثة واتساب لا يعني إتمام البيع. المبيعات تشمل «مكتمل» و«تم التسليم» فقط.</div></section>
        </div>
        <div class="overview-grid" id="funnel">
            <section class="panel"><div class="section-head"><div><h2>قمع التحويل</h2><p>زوار ← طلبات ← بيع مؤكد. وين الزباين بيوقفوا؟</p></div></div><div id="funnelTotal" class="funnel"></div><div class="table-scroll"><table><thead><tr><th>المتجر</th><th>زوار</th><th>طلبات</th><th>بيع</th><th>زائر→طلب</th><th>طلب→بيع</th></tr></thead><tbody id="funnelBody"></tbody></table></div><div class="note">نسبة «زائر→طلب» القليلة مع زوار كثير معناها: الأسعار أو الصور أو إنه المحل مسكّر كثير.</div></section>
            <section class="panel"><div class="section-head"><div><h2>أداء المطبخ</h2><p>وقت التحضير الفعلي مقابل الوقت الموعود للزبون</p></div></div><div id="kitchenTotal" class="detail-stats"></div><div class="table-scroll"><table><thead><tr><th>المتجر</th><th>فعلي</th><th>موعود</th><th>متأخر</th><th>ملغي</th></tr></thead><tbody id="kitchenBody"></tbody></table></div><div class="note">بينحسب من لحظة «ابدأ التحضير» للحظة «جاهز» بلوحة المطعم، للطلبات الجديدة بعد هالتحديث.</div></section>
        </div>
        <section class="panel" id="shops"><div class="section-head"><div><h2>أداء المحلات</h2><p>مرتبة حسب قيمة المبيعات • اضغط اسم المحل لتفاصيله</p></div><label class="search-label">بحث<input id="shopSearch" type="search" placeholder="اسم المحل…"></label></div><div class="table-scroll"><table><thead><tr><th>المتجر</th><th>المشاهدات</th><th>المتصفحات</th><th>تسجيلات العملاء</th><th>دخول الإدارة</th><th>الطلبات</th><th>المبيعات</th><th>قيمة المبيعات</th></tr></thead><tbody id="shopsBody"></tbody></table></div></section>
        <div class="overview-grid">
            <section class="panel"><div class="section-head"><div><h2>الأكثر مبيعًا</h2><p>حسب الكمية في الطلبات المكتملة</p></div></div><div id="topProducts" class="rank-list"></div></section>
            <section class="panel"><div class="section-head"><div><h2>العملاء والكتالوج</h2><p>تفاصيل تساعدك على قراءة الأداء</p></div></div><div id="customerStats" class="detail-stats"></div><div class="note">العملاء المشترون يُقدّرون برقم التواصل لكل متجر. «عائد» يعني لديه شراء مؤكد قبل بداية الفترة؛ التسجيل ليس تسجيل دخول.</div></section>
        </div>
        <div class="overview-grid">
            <section class="panel" id="heatmap"><div class="section-head"><div><h2>أوقات الذروة</h2><p>عدد الطلبات حسب اليوم والساعة • أغمق = طلبات أكثر</p></div><span class="pill" id="heatmapTz"></span></div><div id="heatmapGrid" class="heatmap"></div><div id="heatmapPeak" class="note"></div></section>
            <section class="panel"><div class="section-head"><div><h2>صاعد ونازل</h2><p>مقارنة المبيعات مع الفترة السابقة بنفس الطول</p></div></div><h3 class="mini-title up">⬆ الأكثر صعوداً</h3><div id="risingList" class="rank-list"></div><h3 class="mini-title down">⬇ الأكثر نزولاً</h3><div id="fallingList" class="rank-list"></div></section>
        </div>
        <div class="overview-grid three" id="engagement">
            <section class="panel"><div class="section-head"><div><h2>اللايكات والمشاركة</h2><p>أكثر منتجات حبّوها الزباين وشاركوها</p></div></div><div id="engagementStats" class="detail-stats"></div><h3 class="mini-title">❤️ الأكثر لايكات</h3><div id="topLiked" class="rank-list"></div><h3 class="mini-title">↗ الأكثر مشاركة</h3><div id="topShared" class="rank-list"></div></section>
            <section class="panel"><div class="section-head"><div><h2>السوشال والعجلات والمروّجين</h2><p>منشورات، لفّات، نقاط، مروّجين والمتجر الحر</p></div></div><div id="growthStats" class="detail-stats"></div><h3 class="mini-title">🎬 أكثر مروّجين جابوا زيارات</h3><div id="topPromoters" class="rank-list"></div></section>
            <section class="panel"><div class="section-head"><div><h2>أفضل الزباين</h2><p>حسب قيمة المشتريات المؤكدة بالفترة</p></div></div><div id="topCustomers" class="rank-list"></div></section>
        </div>
        <section class="panel" id="orders"><div class="section-head"><div><h2>آخر الطلبات</h2><p>آخر 20 طلبًا في الفترة المحددة، مع تفاصيل كل طلب</p></div><a href="{{ route('front-orders.index') }}" class="text-link">إدارة الطلبات ↗</a></div><div class="table-scroll"><table><thead><tr><th>الطلب</th><th>المتجر</th><th>العميل</th><th>القيمة</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody id="ordersBody"></tbody></table></div></section>
        <section class="panel" id="activity"><div class="section-head"><div><h2>سجل النشاط</h2><p>تغييرات المحلات والمنتجات والطلبات وعمليات الدخول</p></div><label>نوع النشاط<select id="activityKind"><option value="">كل الأنشطة</option><option value="sale_confirmed">بيع مؤكد</option><option value="order_created">طلب جديد</option><option value="order_cancelled">طلب ملغي</option><option value="sale_reversed">تراجع عن بيع</option><option value="order_status">تغيير حالة الطلب</option><option value="owner_login">دخول صاحب المتجر</option><option value="customer_registered">تسجيل عميل</option><option value="product_created">منتج جديد</option><option value="product_updated">تعديل منتج</option><option value="product_deleted">حذف منتج</option><option value="content_updated">تعديل محتوى</option></select></label></div><div id="activityList" class="activity-list"></div><button id="moreActivity" class="button secondary" type="button" hidden>عرض أنشطة أقدم</button></section>
        <footer class="data-notes"><strong>كيف نقرأ هذه الأرقام؟</strong><p>الفترات اليومية حسب توقيت النظام: {{ config('app.timezone') }}.</p><p id="trackingSince"></p><p>الزيارات تمثل تحميل صفحات المتجر من السيرفر، والمتصفحات الفريدة تقديرية حسب جلسة المتصفح؛ تغيير الجهاز أو مسح بياناته قد يحسب زيارة جديدة. دخول الإدارة يعني دخول صاحب الحساب، ويظهر لكل المحلات التابعة له. لا نعرض نسبة تحويل لعدم ربط الزيارات بهوية المشتري.</p><p>المبيعات هي قيمة الطلبات المكتملة بعد الخصومات، وليست صافي الربح أو إثبات قبض مالي. التراجع عن إكمال طلب يستبعده من المبيعات. لا توجد دورة استرجاع مالي مسجلة حاليًا؛ لا يُحسب الإلغاء كاسترداد مالي.</p><p id="historyNotice"></p></footer>
    </main>
</div>
<dialog id="orderDetail" class="order-dialog"><div class="dialog-head"><h2>تفاصيل الطلب</h2><button type="button" id="closeOrder" aria-label="إغلاق">×</button></div><div id="orderDetailBody"></div></dialog>
@include('admin.analytics.notifications')
<script>window.OzmanAnalyticsConfig = @json(['base' => url('/admin/analytics'), 'ready' => $ready]);</script>
<script>{!! file_get_contents(public_path('admin-shop-analytics.js')) !!}</script>
@include('front.partials.web_push_client')
<script>{!! file_get_contents(public_path('admin-activity-pwa.js')) !!}</script>
</body></html>
