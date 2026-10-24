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
    <meta name="theme-color" content="#2563eb">
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
        <section id="metrics" class="metrics" aria-label="ملخص الإحصائيات" aria-live="polite"></section>
        <div class="overview-grid">
            <section class="panel revenue-panel"><div class="section-head"><div><h2>حركة المبيعات</h2><p>قيمة المبيعات المؤكدة حسب تاريخ إتمام البيع</p></div><span class="pill">₪ شيكل</span></div><div id="salesChart" class="sales-chart"></div><div id="comparison" class="comparison"></div></section>
            <section class="panel"><div class="section-head"><div><h2>حالة الطلبات</h2><p>الطلبات المنشأة خلال الفترة المحددة</p></div></div><div id="orderStatuses" class="status-list"></div><div class="note">فتح محادثة واتساب لا يعني إتمام البيع. المبيعات تشمل «مكتمل» و«تم التسليم» فقط.</div></section>
        </div>
        <section class="panel" id="shops"><div class="section-head"><div><h2>أداء المحلات</h2><p>مرتبة حسب قيمة المبيعات • اضغط اسم المحل لتفاصيله</p></div><label class="search-label">بحث<input id="shopSearch" type="search" placeholder="اسم المحل…"></label></div><div class="table-scroll"><table><thead><tr><th>المتجر</th><th>المشاهدات</th><th>المتصفحات</th><th>تسجيلات العملاء</th><th>دخول الإدارة</th><th>الطلبات</th><th>المبيعات</th><th>قيمة المبيعات</th></tr></thead><tbody id="shopsBody"></tbody></table></div></section>
        <div class="overview-grid">
            <section class="panel"><div class="section-head"><div><h2>الأكثر مبيعًا</h2><p>حسب الكمية في الطلبات المكتملة</p></div></div><div id="topProducts" class="rank-list"></div></section>
            <section class="panel"><div class="section-head"><div><h2>العملاء والكتالوج</h2><p>تفاصيل تساعدك على قراءة الأداء</p></div></div><div id="customerStats" class="detail-stats"></div><div class="note">العملاء المشترون يُقدّرون برقم التواصل لكل متجر. «عائد» يعني لديه شراء مؤكد قبل بداية الفترة؛ التسجيل ليس تسجيل دخول.</div></section>
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
