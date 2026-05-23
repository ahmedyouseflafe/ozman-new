<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0
    }

    :root {
        --red: #C0392B;
        --red-light: #E74C3C;
        --red-dark: #922B21;
        --bg: #0a0a0a;
        --bg2: #111;
        --bg3: #1a1a1a;
        --bg4: #222;
        --border: #2a2a2a;
        --border2: #333;
        --text: #f0f0f0;
        --text2: #aaa;
        --text3: #666;
        --gold: #D4AC0D;
    }

    body {
        background: var(--bg);
        color: var(--text);
        font-family: 'Segoe UI', Tahoma, sans-serif;
        direction: rtl
    }

    .shell {
        display: flex;
        min-height: 700px
    }

    .sidebar {
        width: 200px;
        background: var(--bg2);
        border-left: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        flex-shrink: 0
    }

    .logo {
        padding: 20px 16px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px
    }

    .logo-icon {
        width: 36px;
        height: 36px;
        background: var(--red);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 900;
        color: #fff
    }

    .logo-text {
        font-size: 13px;
        font-weight: 700;
        color: var(--text);
        line-height: 1.2
    }

    .logo-sub {
        font-size: 10px;
        color: var(--text3)
    }

    nav {
        padding: 12px 0;
        flex: 1
    }

    .nav-section {
        padding: 4px 12px 2px;
        font-size: 10px;
        color: var(--text3);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 8px
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        cursor: pointer;
        font-size: 13px;
        color: var(--text2);
        border-right: 2px solid transparent;
        transition: all .15s
    }

    .nav-item:hover {
        background: var(--bg3);
        color: var(--text)
    }

    .nav-item.active {
        background: rgba(192, 57, 43, .12);
        color: var(--red-light);
        border-right-color: var(--red)
    }

    .nav-item i {
        font-size: 15px;
        width: 18px;
        text-align: center
    }

    .main {
        flex: 1;
        overflow: auto;
        background: var(--bg);
        padding: 0
    }

    .topbar {
        background: var(--bg2);
        border-bottom: 1px solid var(--border);
        padding: 0 24px;
        height: 54px;
        display: flex;
        align-items: center;
        justify-content: space-between
    }

    .topbar-title {
        font-size: 15px;
        font-weight: 600
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 12px
    }

    .topbar-btn {
        background: transparent;
        border: 1px solid var(--border2);
        color: var(--text2);
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        transition: all .15s
    }

    .topbar-btn:hover {
        border-color: var(--red);
        color: var(--red-light)
    }

    .avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--red-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer
    }

    .content {
        padding: 24px
    }

    .page-header {
        margin-bottom: 20px
    }

    .page-header h1 {
        font-size: 20px;
        font-weight: 700
    }

    .page-header p {
        font-size: 13px;
        color: var(--text3);
        margin-top: 2px
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px
    }

    .stat-card {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 16px;
        position: relative;
        overflow: hidden
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 3px;
        height: 100%;
        background: var(--accent, var(--red))
    }

    .stat-label {
        font-size: 11px;
        color: var(--text3);
        margin-bottom: 6px
    }

    .stat-val {
        font-size: 26px;
        font-weight: 800;
        color: var(--text);
        line-height: 1
    }

    .stat-sub {
        font-size: 11px;
        margin-top: 6px
    }

    .stat-up {
        color: #27AE60
    }

    .stat-down {
        color: #E74C3C
    }

    .stat-icon {
        position: absolute;
        bottom: 10px;
        left: 12px;
        font-size: 28px;
        opacity: .1
    }

    .grid2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px
    }

    .card {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 16px
    }

    .card-hd {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px
    }

    .card-hd h3 {
        font-size: 14px;
        font-weight: 600
    }

    .badge {
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 20px;
        background: rgba(192, 57, 43, .18);
        color: var(--red-light)
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px
    }

    th {
        text-align: right;
        padding: 6px 8px;
        color: var(--text3);
        font-weight: 500;
        border-bottom: 1px solid var(--border)
    }

    td {
        padding: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, .04);
        color: var(--text2)
    }

    tr:last-child td {
        border-bottom: none
    }

    tr:hover td {
        background: rgba(255, 255, 255, .02)
    }

    .tag {
        display: inline-block;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 500
    }

    .tag-g {
        background: rgba(39, 174, 96, .15);
        color: #27AE60
    }

    .tag-r {
        background: rgba(231, 76, 60, .15);
        color: #E74C3C
    }

    .tag-y {
        background: rgba(241, 196, 15, .15);
        color: #F1C40F
    }

    .chart-bars {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        height: 90px;
        padding-top: 8px
    }

    .bar-wrap {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px
    }

    .bar {
        width: 100%;
        background: linear-gradient(to top, var(--red-dark), var(--red-light));
        border-radius: 3px 3px 0 0;
        transition: height .3s
    }

    .bar-label {
        font-size: 9px;
        color: var(--text3)
    }

    .recent-list {
        display: flex;
        flex-direction: column;
        gap: 8px
    }

    .recent-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px;
        background: var(--bg3);
        border-radius: 7px
    }

    .ri-icon {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        background: var(--bg4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: var(--red-light)
    }

    .ri-info {
        flex: 1
    }

    .ri-title {
        font-size: 12px;
        font-weight: 500;
        color: var(--text)
    }

    .ri-sub {
        font-size: 10px;
        color: var(--text3)
    }

    .ri-val {
        font-size: 13px;
        font-weight: 700;
        color: var(--red-light)
    }

    .page {
        display: none
    }

    .page.active {
        display: block
    }

    .btn-red {
        background: var(--red);
        color: #fff;
        border: none;
        padding: 7px 14px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px
    }

    .btn-red:hover {
        background: var(--red-light)
    }

    .input-wrap {
        position: relative
    }

    .search-inp {
        background: var(--bg3);
        border: 1px solid var(--border2);
        color: var(--text);
        padding: 6px 10px 6px 30px;
        border-radius: 6px;
        font-size: 12px;
        outline: none;
        width: 180px
    }

    .search-inp::placeholder {
        color: var(--text3)
    }

    .search-icon {
        position: absolute;
        left: 9px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text3);
        font-size: 13px
    }
</style>

<h2 class="sr-only">لوحة تحكم هيلني شوب - نظرة عامة على المتجر والمنتجات والمستخدمين</h2>

<div class="shell">
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">H</div>
            <div>
                <div class="logo-text">هيلني شوب</div>
                <div class="logo-sub">لوحة التحكم</div>
            </div>
        </div>
         @include('admin.includes.sidebar')
    </div>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title" id="page-title">لوحة التحكم</div>
            <div class="topbar-right">
                <div class="input-wrap">
                    <i class="ti ti-search search-icon" aria-hidden="true"></i>
                    <input class="search-inp" placeholder="بحث...">
                </div>
                <button class="topbar-btn" aria-label="إشعارات"><i class="ti ti-bell" aria-hidden="true"></i></button>
                <button class="topbar-btn" aria-label="إعدادات"><i class="ti ti-settings"
                        aria-hidden="true"></i></button>
                <div class="avatar" title="المشرف">A</div>
            </div>
        </div>

        <div class="content">

            <!-- DASHBOARD -->
            <div class="page active" id="page-dashboard">
                <div class="page-header">
                    <h1>نظرة عامة</h1>
                    <p>مرحباً بك — آخر تحديث اليوم</p>
                </div>
                <div class="stats-grid">
                    <div class="stat-card" style="--accent:#E74C3C">
                        <div class="stat-label">إجمالي المتاجر</div>
                        <div class="stat-val">24</div>
                        <div class="stat-sub stat-up">↑ 3 هذا الشهر</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:#8E44AD">
                        <div class="stat-label">المنتجات النشطة</div>
                        <div class="stat-val">1,248</div>
                        <div class="stat-sub stat-up">↑ 12% عن الشهر الماضي</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:#2980B9">
                        <div class="stat-label">إجمالي المستخدمين</div>
                        <div class="stat-val">3,891</div>
                        <div class="stat-sub stat-up">↑ 87 مستخدم جديد</div>
                        <i class="ti ti-users stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:#16A085">
                        <div class="stat-label">الوكلاء والموزعون</div>
                        <div class="stat-val">56</div>
                        <div class="stat-sub stat-down">↓ 2 غير نشط</div>
                        <i class="ti ti-truck stat-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="grid2">
                    <div class="card">
                        <div class="card-hd">
                            <h3>المبيعات الشهرية</h3><span class="badge">2025</span>
                        </div>
                        <div class="chart-bars" id="bars"></div>
                        <div style="display:flex;justify-content:space-between;margin-top:4px">
                            <span style="font-size:10px;color:var(--text3)">يناير</span>
                            <span style="font-size:10px;color:var(--text3)">يونيو</span>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3>آخر المنتجات المضافة</h3><button class="btn-red"
                                onclick="showPage('products',null)"><i class="ti ti-plus" aria-hidden="true"></i>
                                إضافة</button>
                        </div>
                        <div class="recent-list">
                            <div class="recent-item">
                                <div class="ri-icon"><i class="ti ti-bottle" aria-hidden="true"></i></div>
                                <div class="ri-info">
                                    <div class="ri-title">Cola الأصلية</div>
                                    <div class="ri-sub">مشروبات · منذ ساعتين</div>
                                </div><span class="ri-val">15₪</span>
                            </div>
                            <div class="recent-item">
                                <div class="ri-icon"><i class="ti ti-droplet" aria-hidden="true"></i></div>
                                <div class="ri-info">
                                    <div class="ri-title">كريم العناية بالوجه</div>
                                    <div class="ri-sub">العناية بالبشرة · أمس</div>
                                </div><span class="ri-val">45₪</span>
                            </div>
                            <div class="recent-item">
                                <div class="ri-icon"><i class="ti ti-spray" aria-hidden="true"></i></div>
                                <div class="ri-info">
                                    <div class="ri-title">شامبو Cliven</div>
                                    <div class="ri-sub">العناية بالشعر · أمس</div>
                                </div><span class="ri-val">28₪</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>آخر المتاجر المسجلة</h3><span class="badge">هذا الأسبوع</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>اسم المتجر</th>
                                <th>المدينة</th>
                                <th>المنتجات</th>
                                <th>الحالة</th>
                                <th>تاريخ التسجيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>هيلني شوب الرئيسي</td>
                                <td>نابلس</td>
                                <td>312</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td>22 مايو 2025</td>
                            </tr>
                            <tr>
                                <td>متجر الجمال الفاخر</td>
                                <td>رام الله</td>
                                <td>85</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td>20 مايو 2025</td>
                            </tr>
                            <tr>
                                <td>دار العطور</td>
                                <td>الخليل</td>
                                <td>47</td>
                                <td><span class="tag tag-y">معلق</span></td>
                                <td>18 مايو 2025</td>
                            </tr>
                            <tr>
                                <td>صيدلية الشفاء</td>
                                <td>جنين</td>
                                <td>120</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td>15 مايو 2025</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SHOPS -->
            <div class="page" id="page-shops">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>إدارة المتاجر</h1>
                        <p>24 متجر مسجل في النظام</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> متجر جديد</button>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي المتاجر</div>
                        <div class="stat-val">24</div><i class="ti ti-building-store stat-icon"
                            aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">نشط</div>
                        <div class="stat-val" style="color:#27AE60">19</div><i class="ti ti-check stat-icon"
                            aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">معلق</div>
                        <div class="stat-val" style="color:#F1C40F">3</div><i class="ti ti-clock stat-icon"
                            aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">غير نشط</div>
                        <div class="stat-val" style="color:var(--red-light)">2</div><i class="ti ti-x stat-icon"
                            aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة المتاجر</h3>
                        <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input
                                class="search-inp" placeholder="بحث بالاسم..."></div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>الشعار</th>
                                <th>الاسم</th>
                                <th>المدينة</th>
                                <th>الهاتف</th>
                                <th>المنتجات</th>
                                <th>أوقات العمل</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div
                                        style="width:30px;height:30px;border-radius:6px;background:var(--red-dark);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
                                        ه</div>
                                </td>
                                <td style="color:var(--text);font-weight:500">هيلني شوب الرئيسي</td>
                                <td>نابلس</td>
                                <td dir="ltr">059-000-0000</td>
                                <td>312</td>
                                <td>9ص – 9م</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div
                                        style="width:30px;height:30px;border-radius:6px;background:#8E44AD;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
                                        ج</div>
                                </td>
                                <td style="color:var(--text);font-weight:500">متجر الجمال الفاخر</td>
                                <td>رام الله</td>
                                <td dir="ltr">058-111-2222</td>
                                <td>85</td>
                                <td>10ص – 8م</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div
                                        style="width:30px;height:30px;border-radius:6px;background:#2980B9;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
                                        د</div>
                                </td>
                                <td style="color:var(--text);font-weight:500">دار العطور</td>
                                <td>الخليل</td>
                                <td dir="ltr">059-333-4444</td>
                                <td>47</td>
                                <td>8ص – 6م</td>
                                <td><span class="tag tag-y">معلق</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PRODUCTS -->
            <div class="page" id="page-products">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>إدارة المنتجات</h1>
                        <p>1,248 منتج في جميع المتاجر</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> منتج جديد</button>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي المنتجات</div>
                        <div class="stat-val">1,248</div><i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">المميزة</div>
                        <div class="stat-val" style="color:var(--gold)">48</div><i class="ti ti-star stat-icon"
                            aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">نفد المخزون</div>
                        <div class="stat-val" style="color:var(--red-light)">17</div><i
                            class="ti ti-alert-triangle stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">متوسط السعر</div>
                        <div class="stat-val">32₪</div><i class="ti ti-coin stat-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة المنتجات</h3>
                        <div style="display:flex;gap:8px">
                            <select
                                style="background:var(--bg3);border:1px solid var(--border2);color:var(--text2);padding:5px 8px;border-radius:6px;font-size:12px">
                                <option>كل الفئات</option>
                                <option>مشروبات</option>
                                <option>العناية بالبشرة</option>
                                <option>العناية بالشعر</option>
                            </select>
                            <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input
                                    class="search-inp" placeholder="بحث..."></div>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>الاسم</th>
                                <th>الفئة</th>
                                <th>السعر</th>
                                <th>سعر الخصم</th>
                                <th>الكمية</th>
                                <th>التقييم</th>
                                <th>مميز</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div
                                        style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                        <i class="ti ti-bottle" style="color:var(--red-light);font-size:16px"
                                            aria-hidden="true"></i></div>
                                </td>
                                <td style="color:var(--text);font-weight:500">Cola الأصلية</td>
                                <td>مشروبات</td>
                                <td style="color:var(--red-light);font-weight:600">15₪</td>
                                <td style="color:#27AE60">12₪</td>
                                <td>240</td>
                                <td>⭐ 4.8</td>
                                <td><span class="tag tag-y">نعم</span></td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div
                                        style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                        <i class="ti ti-droplet" style="color:#8E44AD;font-size:16px"
                                            aria-hidden="true"></i></div>
                                </td>
                                <td style="color:var(--text);font-weight:500">كريم العناية بالوجه</td>
                                <td>العناية بالبشرة</td>
                                <td style="color:var(--red-light);font-weight:600">45₪</td>
                                <td>—</td>
                                <td>80</td>
                                <td>⭐ 4.5</td>
                                <td><span class="tag tag-r">لا</span></td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div
                                        style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                        <i class="ti ti-spray" style="color:#2980B9;font-size:16px"
                                            aria-hidden="true"></i></div>
                                </td>
                                <td style="color:var(--text);font-weight:500">شامبو Cliven</td>
                                <td>العناية بالشعر</td>
                                <td style="color:var(--red-light);font-weight:600">28₪</td>
                                <td style="color:#27AE60">22₪</td>
                                <td>0</td>
                                <td>⭐ 4.2</td>
                                <td><span class="tag tag-y">نعم</span></td>
                                <td><span class="tag tag-r">نفد</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CATEGORIES -->
            <div class="page" id="page-categories">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>الفئات</h1>
                        <p>تصنيف منتجات المتاجر</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> فئة جديدة</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                    <div class="card" style="text-align:center">
                        <div
                            style="width:50px;height:50px;border-radius:12px;background:rgba(192,57,43,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
                            <i class="ti ti-bottle" style="font-size:22px;color:var(--red-light)"
                                aria-hidden="true"></i></div>
                        <div style="font-size:14px;font-weight:600;color:var(--text)">مشروبات</div>
                        <div style="font-size:11px;color:var(--text3);margin:4px 0 10px">48 منتج</div>
                        <div style="display:flex;gap:6px;justify-content:center">
                            <button class="topbar-btn" aria-label="تعديل"><i class="ti ti-edit"
                                    aria-hidden="true"></i></button>
                            <button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                    aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="card" style="text-align:center">
                        <div
                            style="width:50px;height:50px;border-radius:12px;background:rgba(142,68,173,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
                            <i class="ti ti-droplet" style="font-size:22px;color:#8E44AD" aria-hidden="true"></i>
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--text)">العناية بالبشرة</div>
                        <div style="font-size:11px;color:var(--text3);margin:4px 0 10px">125 منتج</div>
                        <div style="display:flex;gap:6px;justify-content:center">
                            <button class="topbar-btn" aria-label="تعديل"><i class="ti ti-edit"
                                    aria-hidden="true"></i></button>
                            <button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                    aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="card" style="text-align:center">
                        <div
                            style="width:50px;height:50px;border-radius:12px;background:rgba(41,128,185,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
                            <i class="ti ti-spray" style="font-size:22px;color:#2980B9" aria-hidden="true"></i></div>
                        <div style="font-size:14px;font-weight:600;color:var(--text)">العناية بالشعر</div>
                        <div style="font-size:11px;color:var(--text3);margin:4px 0 10px">89 منتج</div>
                        <div style="display:flex;gap:6px;justify-content:center">
                            <button class="topbar-btn" aria-label="تعديل"><i class="ti ti-edit"
                                    aria-hidden="true"></i></button>
                            <button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                    aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="card" style="text-align:center">
                        <div
                            style="width:50px;height:50px;border-radius:12px;background:rgba(22,160,133,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
                            <i class="ti ti-gender-male" style="font-size:22px;color:#16A085" aria-hidden="true"></i>
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--text)">العناية للرجال</div>
                        <div style="font-size:11px;color:var(--text3);margin:4px 0 10px">63 منتج</div>
                        <div style="display:flex;gap:6px;justify-content:center">
                            <button class="topbar-btn" aria-label="تعديل"><i class="ti ti-edit"
                                    aria-hidden="true"></i></button>
                            <button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                    aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AGENTS -->
            <div class="page" id="page-agents">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>الوكلاء</h1>
                        <p>إدارة وكلاء المبيعات</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> وكيل جديد</button>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>واتساب</th>
                                <th>البريد</th>
                                <th>المدينة</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--text);font-weight:500">أحمد خالد</td>
                                <td dir="ltr">059-100-2000</td>
                                <td dir="ltr">059-100-2000</td>
                                <td>ahmed@example.com</td>
                                <td>نابلس</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td style="color:var(--text);font-weight:500">سارة محمود</td>
                                <td dir="ltr">059-200-3000</td>
                                <td dir="ltr">059-200-3000</td>
                                <td>sara@example.com</td>
                                <td>رام الله</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td style="color:var(--text);font-weight:500">محمد نور</td>
                                <td dir="ltr">059-300-4000</td>
                                <td dir="ltr">059-300-4000</td>
                                <td>m.nour@example.com</td>
                                <td>الخليل</td>
                                <td><span class="tag tag-r">غير نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DISTRIBUTORS -->
            <div class="page" id="page-distributors">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>الموزعون</h1>
                        <p>شبكة توزيع المنتجات</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> موزع جديد</button>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الهاتف</th>
                                <th>المدينة</th>
                                <th>المتجر</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--text);font-weight:500">شركة التوزيع المركزية</td>
                                <td dir="ltr">059-500-6000</td>
                                <td>نابلس</td>
                                <td>هيلني شوب الرئيسي</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td style="color:var(--text);font-weight:500">موزع الجنوب</td>
                                <td dir="ltr">059-600-7000</td>
                                <td>الخليل</td>
                                <td>متجر الجمال الفاخر</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ADS -->
            <div class="page" id="page-ads">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>الإعلانات</h1>
                        <p>إدارة إعلانات المتاجر</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> إعلان جديد</button>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>المتجر</th>
                                <th>المدة (ث)</th>
                                <th>الترتيب</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--text);font-weight:500">عرض الصيف الحصري</td>
                                <td><span class="tag"
                                        style="background:rgba(41,128,185,.15);color:#2980B9">صورة</span></td>
                                <td>هيلني شوب الرئيسي</td>
                                <td>10</td>
                                <td>1</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td style="color:var(--text);font-weight:500">فيديو Cola الجديدة</td>
                                <td><span class="tag"
                                        style="background:rgba(231,76,60,.15);color:#E74C3C">فيديو</span></td>
                                <td>هيلني شوب الرئيسي</td>
                                <td>30</td>
                                <td>2</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td style="color:var(--text);font-weight:500">فيديو يوتيوب خاص</td>
                                <td><span class="tag"
                                        style="background:rgba(192,57,43,.15);color:var(--red-light)">يوتيوب</span>
                                </td>
                                <td>متجر الجمال</td>
                                <td>15</td>
                                <td>3</td>
                                <td><span class="tag tag-y">معلق</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SCREENS -->
            <div class="page" id="page-screens">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>الشاشات الرئيسية</h1>
                        <p>محتوى شاشات العرض</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> شاشة جديدة</button>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>المدة (ث)</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="color:var(--text);font-weight:500">الشاشة الرئيسية 1</td>
                                <td><span class="tag"
                                        style="background:rgba(41,128,185,.15);color:#2980B9">صورة</span></td>
                                <td>10</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td style="color:var(--text);font-weight:500">فيديو ترحيبي</td>
                                <td><span class="tag"
                                        style="background:rgba(231,76,60,.15);color:#E74C3C">فيديو</span></td>
                                <td>45</td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- USERS -->
            <div class="page" id="page-users">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>إدارة المستخدمين</h1>
                        <p>3,891 مستخدم مسجل</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> مستخدم جديد</button>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Super Admin</div>
                        <div class="stat-val">1</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">مشرف شركة</div>
                        <div class="stat-val">5</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">أصحاب متاجر</div>
                        <div class="stat-val">24</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">عملاء</div>
                        <div class="stat-val">3,861</div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة المستخدمين</h3>
                        <div style="display:flex;gap:8px">
                            <select
                                style="background:var(--bg3);border:1px solid var(--border2);color:var(--text2);padding:5px 8px;border-radius:6px;font-size:12px">
                                <option>كل الأدوار</option>
                                <option>super_admin</option>
                                <option>company_admin</option>
                                <option>shop_owner</option>
                                <option>agent</option>
                                <option>distributor</option>
                                <option>customer</option>
                            </select>
                            <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input
                                    class="search-inp" placeholder="بحث..."></div>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الإيميل</th>
                                <th>الهاتف</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div
                                            style="width:28px;height:28px;border-radius:50%;background:var(--red-dark);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
                                            A</div><span style="color:var(--text)">Admin</span>
                                    </div>
                                </td>
                                <td>admin@helni.com</td>
                                <td dir="ltr">059-000-0001</td>
                                <td><span class="tag"
                                        style="background:rgba(192,57,43,.18);color:var(--red-light)">super_admin</span>
                                </td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" aria-label="تعديل"><i class="ti ti-edit"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div
                                            style="width:28px;height:28px;border-radius:50%;background:#8E44AD;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
                                            أ</div><span style="color:var(--text)">أحمد سالم</span>
                                    </div>
                                </td>
                                <td>ahmed@helni.com</td>
                                <td dir="ltr">059-111-2222</td>
                                <td><span class="tag"
                                        style="background:rgba(41,128,185,.15);color:#2980B9">shop_owner</span></td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div
                                            style="width:28px;height:28px;border-radius:50%;background:#16A085;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
                                            م</div><span style="color:var(--text)">منى حسن</span>
                                    </div>
                                </td>
                                <td>mona@gmail.com</td>
                                <td dir="ltr">059-222-3333</td>
                                <td><span class="tag"
                                        style="background:rgba(22,160,133,.15);color:#16A085">customer</span></td>
                                <td><span class="tag tag-g">نشط</span></td>
                                <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                            class="ti ti-edit" aria-hidden="true"></i></button><button
                                        class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SETTINGS -->
            <div class="page" id="page-settings">
                <div class="page-header">
                    <h1>الإعدادات</h1>
                    <p>إعدادات النظام العامة</p>
                </div>
                <div class="grid2">
                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="ti ti-user" aria-hidden="true"
                                    style="margin-left:6px;color:var(--red-light)"></i>الملف الشخصي</h3>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:10px">
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">الاسم</div><input
                                    style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                    value="Admin" />
                            </div>
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">البريد الإلكتروني
                                </div><input
                                    style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                    value="admin@helni.com" />
                            </div>
                            <button class="btn-red" style="width:fit-content">حفظ التغييرات</button>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="ti ti-lock" aria-hidden="true"
                                    style="margin-left:6px;color:var(--red-light)"></i>تغيير كلمة المرور</h3>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:10px">
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">كلمة المرور الحالية
                                </div><input type="password"
                                    style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                    placeholder="••••••••" />
                            </div>
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">كلمة المرور الجديدة
                                </div><input type="password"
                                    style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                    placeholder="••••••••" />
                            </div>
                            <button class="btn-red" style="width:fit-content">تحديث</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const titles = {
        dashboard: 'لوحة التحكم',
        shops: 'إدارة المتاجر',
        products: 'إدارة المنتجات',
        categories: 'الفئات',
        agents: 'الوكلاء',
        distributors: 'الموزعون',
        ads: 'الإعلانات',
        screens: 'الشاشات الرئيسية',
        users: 'إدارة المستخدمين',
        settings: 'الإعدادات'
    };

    function showPage(id, el) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.getElementById('page-' + id).classList.add('active');
        document.getElementById('page-title').textContent = titles[id] || id;
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        if (el) el.classList.add('active');
        else {
            document.querySelectorAll('.nav-item').forEach(n => {
                if (n.textContent.trim().includes(titles[id]?.substring(0, 4))) n.classList.add('active')
            });
        }
    }
    const vals = [55, 70, 45, 88, 62, 95];
    const bars = document.getElementById('bars');
    vals.forEach((v, i) => {
        const w = document.createElement('div');
        w.className = 'bar-wrap';
        w.innerHTML =
            `<div class="bar" style="height:${v}%"></div><div class="bar-label">${['ي','ف','م','أ','م','ي'][i]}</div>`;
        bars.appendChild(w);
    });
</script>
