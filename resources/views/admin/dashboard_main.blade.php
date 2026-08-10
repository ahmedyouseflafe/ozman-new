<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>لوحة التحكم الرئيسية - Ozman</title>
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

    :root {
        --red: #00e5ff;
        --red-light: #00e5ff;
        --red-dark: #003f48;
        --bg: #050505;
        --bg2: rgba(0, 0, 0, .78);
        --bg3: rgba(255, 255, 255, .05);
        --bg4: #000;
        --border: rgba(255, 255, 255, .1);
        --border2: rgba(255, 255, 255, .1);
        --text: #fff;
        --text2: rgba(255, 255, 255, .72);
        --text3: rgba(255, 255, 255, .42);
        --gold: #f1c40f;
        --primary-color: #00e5ff;
        --accent-color: #7000ff;
        --green: #25d366;
        --danger: #ff3b30;
    }

    body {
        min-height: 100%;
        background:
            radial-gradient(circle at 50% 4%, rgba(0, 229, 255, .10), transparent 34%),
            radial-gradient(circle at 12% 70%, rgba(112, 0, 255, .10), transparent 28%),
            #050505 !important;
        color: var(--text);
        font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        overflow-x: hidden;
    }

    body::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        background:
            linear-gradient(rgba(255, 255, 255, .018) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, .018) 1px, transparent 1px);
        background-size: 52px 52px;
        mask-image: radial-gradient(circle at center, black, transparent 72%);
        opacity: .5;
    }

    .shell {
        min-height: 100vh;
    }

    .dashboard-main-shell > .sidebar:not(.admin-neon-sidebar) {
        display: none;
    }

    .main {
        margin-right: 245px;
        min-width: 0;
        background: transparent !important;
    }

    .topbar {
        background: rgba(0, 0, 0, .78) !important;
        backdrop-filter: blur(15px);
        border-bottom: 1px solid var(--border);
        height: 68px;
        position: sticky;
        top: 0;
        z-index: 15;
        box-shadow: 0 10px 35px rgba(0, 229, 255, .05);
    }

    .topbar-title {
        color: var(--primary-color);
        font-size: 16px;
        font-weight: 900;
        text-shadow: 0 0 12px rgba(0, 229, 255, .45);
    }

    .content {
        max-width: 1500px;
        margin: 0 auto;
        padding: 28px;
    }

    .page {
        animation: dashboardSlideUp .45s ease both;
    }

    .page-header,
    .page-header[style] {
        background: rgba(255, 255, 255, .045);
        border: 1px solid var(--border);
        border-radius: 25px;
        padding: 22px;
        margin-bottom: 22px;
        backdrop-filter: blur(14px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, .24);
    }

    .page-header h1 {
        color: var(--primary-color);
        font-size: 28px;
        font-weight: 900;
        text-shadow: 0 0 16px rgba(0, 229, 255, .45);
    }

    .page-header p {
        color: var(--text2);
    }

    .stats-grid {
        gap: 16px;
    }

    .stat-card,
    .card {
        background: rgba(255, 255, 255, .045) !important;
        border: 1px solid var(--border) !important;
        border-radius: 24px !important;
        backdrop-filter: blur(14px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, .22);
        transition: all .34s cubic-bezier(.175, .885, .32, 1.275);
    }

    .stat-card {
        min-height: 126px;
        padding: 20px;
    }

    .stat-card::before {
        top: auto;
        right: 18px;
        left: 18px;
        bottom: 0;
        width: auto;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--accent, var(--primary-color)), transparent);
    }

    .stat-card:hover,
    .card:hover {
        transform: translateY(-6px);
        border-color: var(--accent, var(--primary-color)) !important;
        box-shadow: 0 18px 42px rgba(0, 0, 0, .32), 0 0 24px rgba(0, 229, 255, .15);
    }

    .stat-label {
        color: var(--text2);
        font-size: 12px;
        font-weight: 800;
    }

    .stat-val {
        color: var(--accent, var(--primary-color)) !important;
        font-size: 34px;
        font-weight: 900;
        text-shadow: 0 0 18px rgba(0, 229, 255, .42);
    }

    .stat-icon {
        color: var(--accent, var(--primary-color));
        opacity: .22;
        font-size: 42px;
        filter: drop-shadow(0 0 12px currentColor);
    }

    .card-hd {
        border-bottom: 1px solid var(--border);
        padding-bottom: 14px;
    }

    .card-hd h3 {
        color: var(--text);
        font-weight: 900;
    }

    .badge,
    .tag {
        border-radius: 999px;
        border: 1px solid currentColor;
        background: rgba(255, 255, 255, .04) !important;
        font-weight: 900;
    }

    .tag-g,
    .stat-up {
        color: var(--green) !important;
    }

    .tag-r,
    .stat-down {
        color: var(--danger) !important;
    }

    .tag-y {
        color: var(--gold) !important;
    }

    table {
        border-collapse: separate;
        border-spacing: 0 9px;
        min-width: 760px;
    }

    th {
        color: var(--primary-color);
        border-bottom: 0;
        font-weight: 900;
        text-shadow: 0 0 10px rgba(0, 229, 255, .3);
        padding: 7px 12px;
    }

    td {
        background: rgba(255, 255, 255, .035);
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        color: var(--text2);
        padding: 13px 12px;
    }

    td:first-child {
        border-right: 1px solid var(--border);
        border-radius: 0 16px 16px 0;
    }

    td:last-child {
        border-left: 1px solid var(--border);
        border-radius: 16px 0 0 16px;
    }

    tr:hover td {
        background: rgba(0, 229, 255, .075);
        border-color: rgba(0, 229, 255, .28);
        color: #fff;
    }

    .topbar-btn {
        background: rgba(255, 255, 255, .05);
        border: 1px solid var(--border);
        color: var(--text2);
        width: 38px;
        height: 38px;
        border-radius: 50%;
        transition: all .3s ease;
    }

    .topbar-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateY(-3px) scale(1.08);
        box-shadow: 0 0 16px rgba(0, 229, 255, .35);
    }

    .btn-red {
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        color: #fff;
        border-radius: 999px;
        padding: 10px 18px;
        box-shadow: 0 0 20px rgba(0, 229, 255, .28);
        transition: all .3s ease;
    }

    .btn-red:hover {
        transform: translateY(-3px);
        box-shadow: 0 0 28px rgba(0, 229, 255, .58);
    }

    .search-inp,
    select,
    input {
        background: rgba(255, 255, 255, .05) !important;
        border: 1px solid var(--border) !important;
        color: #fff !important;
        border-radius: 999px !important;
        font-family: inherit;
    }

    .search-icon {
        color: var(--primary-color);
    }

    .avatar {
        background: #000;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        width: 42px;
        height: 42px;
        box-shadow: 0 0 16px rgba(0, 229, 255, .3);
    }

    .bar {
        background: linear-gradient(to top, var(--accent-color), var(--primary-color));
        box-shadow: 0 0 14px rgba(0, 229, 255, .25);
    }

    .recent-item {
        background: rgba(255, 255, 255, .045);
        border: 1px solid var(--border);
        border-radius: 16px;
    }

    .ri-icon {
        background: #000;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        border-radius: 50%;
        box-shadow: 0 0 14px rgba(0, 229, 255, .35);
    }

    .ri-title {
        color: var(--text);
        font-weight: 900;
    }

    .ri-val {
        color: var(--primary-color);
    }

    @keyframes dashboardSlideUp {
        from {
            opacity: 0;
            transform: translateY(22px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media(max-width: 900px) {
        .main {
            margin-right: 0;
        }

        .content {
            padding: 18px;
        }
    }
</style>
</head>
<body>

<h2 class="sr-only">لوحة تحكم هيلني شوب - نظرة عامة على المتجر والمنتجات والمستخدمين</h2>

<div class="shell dashboard-main-shell">
    @include('admin.includes.sidebar')
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
                        <div class="stat-val">{{ $shopsCount ?? 0 }}</div>
                        <div class="stat-sub stat-up">{{ $shopsDelta ?? '' }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:#8E44AD">
                        <div class="stat-label">المنتجات النشطة</div>
                        <div class="stat-val">{{ $activeProductsCount ?? 0 }}</div>
                        <div class="stat-sub stat-up">{{ $activeProductsDelta ?? '' }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:#2980B9">
                        <div class="stat-label">إجمالي المستخدمين</div>
                        <div class="stat-val">{{ $usersCount ?? 0 }}</div>
                        <div class="stat-sub stat-up">{{ $usersDelta ?? '' }}</div>
                        <i class="ti ti-users stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent:#16A085">
                        <div class="stat-label">الوكلاء والموزعون</div>
                        <div class="stat-val">{{ $agentsAndDistributorsCount ?? 0 }}</div>
                        <div class="stat-sub stat-down">{{ $agentsStatusDelta ?? '' }}</div>
                        <i class="ti ti-truck stat-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="grid2">
                    <div class="card">
                        <div class="card-hd">
                            <h3>المبيعات الشهرية</h3><span class="badge">{{ $salesYear ?? date('Y') }}</span>
                        </div>
                        <div class="chart-bars" id="bars">
                            @forelse($monthlySales ?? [] as $item)
                                <div class="bar-wrap">
                                    <div class="bar" style="height:{{ min(100, max(0, data_get($item, 'value', 0))) }}%"></div>
                                    <div class="bar-label">{{ data_get($item, 'label', '') }}</div>
                                </div>
                            @empty
                                <div style="width:100%;color:var(--text3);font-size:12px;text-align:center;padding:24px 0">لا توجد بيانات مبيعات</div>
                            @endforelse
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:4px">
                            <span style="font-size:10px;color:var(--text3)">{{ $salesRangeStart ?? 'بداية الشهر' }}</span>
                            <span style="font-size:10px;color:var(--text3)">{{ $salesRangeEnd ?? 'نهاية الشهر' }}</span>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3>آخر المنتجات المضافة</h3><button class="btn-red" onclick="showPage('products',null)"><i class="ti ti-plus" aria-hidden="true"></i> إضافة</button>
                        </div>
                        <div class="recent-list">
                            @forelse($recentProducts ?? [] as $product)
                                <div class="recent-item">
                                    <div class="ri-icon"><i class="ti ti-package" aria-hidden="true"></i></div>
                                    <div class="ri-info">
                                        <div class="ri-title">{{ data_get($product, 'name', '-') }}</div>
                                        <div class="ri-sub">{{ data_get($product, 'category', '-') }} · {{ data_get($product, 'added_at_label', '') }}</div>
                                    </div>
                                    <span class="ri-val">{{ data_get($product, 'price_formatted', '-') }}</span>
                                </div>
                            @empty
                                <div class="recent-item">
                                    <div class="ri-info">
                                        <div class="ri-title">لا توجد منتجات جديدة</div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>آخر المتاجر المسجلة</h3><span class="badge">{{ $recentShopsLabel ?? 'آخر تحديث' }}</span>
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
                            @forelse($latestShops ?? [] as $shop)
                                <tr>
                                    <td>{{ data_get($shop, 'name', '-') }}</td>
                                    <td>{{ data_get($shop, 'city', '-') }}</td>
                                    <td>{{ data_get($shop, 'products_count', '-') }}</td>
                                    <td><span class="tag {{ data_get($shop, 'status_class', 'tag-g') }}">{{ data_get($shop, 'status_label', '-') }}</span></td>
                                    <td>{{ data_get($shop, 'registered_at', '-') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;color:var(--text3)">لا توجد متاجر مسجلة حالياً</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SHOPS -->
            <div class="page" id="page-shops">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>إدارة المتاجر</h1>
                        <p>{{ $shopsCount ?? 0 }} متجر مسجل في النظام</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> متجر جديد</button>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي المتاجر</div>
                        <div class="stat-val">{{ $shopsCount ?? 0 }}</div><i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">نشط</div>
                        <div class="stat-val" style="color:#27AE60">{{ $activeShopsCount ?? 0 }}</div><i class="ti ti-check stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">معلق</div>
                        <div class="stat-val" style="color:#F1C40F">{{ $pendingShopsCount ?? 0 }}</div><i class="ti ti-clock stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">غير نشط</div>
                        <div class="stat-val" style="color:var(--red-light)">{{ $inactiveShopsCount ?? 0 }}</div><i class="ti ti-x stat-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة المتاجر</h3>
                        <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input class="search-inp" placeholder="بحث بالاسم..."></div>
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
                            @forelse($shops ?? [] as $shop)
                                <tr>
                                    <td>
                                        <div style="width:30px;height:30px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
                                            {{ strtoupper(mb_substr(data_get($shop, 'name', ''), 0, 1)) }}</div>
                                    </td>
                                    <td style="color:var(--text);font-weight:500">{{ data_get($shop, 'name', '-') }}</td>
                                    <td>{{ data_get($shop, 'city', '-') }}</td>
                                    <td dir="ltr">{{ data_get($shop, 'phone', '-') }}</td>
                                    <td>{{ data_get($shop, 'products_count', '-') }}</td>
                                    <td>{{ data_get($shop, 'hours', '-') }}</td>
                                    <td><span class="tag {{ data_get($shop, 'status_class', 'tag-g') }}">{{ data_get($shop, 'status_label', '-') }}</span></td>
                                    <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button><button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;color:var(--text3)">لا توجد متاجر لعرضها</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PRODUCTS -->
            <div class="page" id="page-products">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>إدارة المنتجات</h1>
                        <p>{{ $productsCount ?? 0 }} منتج في جميع المتاجر</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> منتج جديد</button>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي المنتجات</div>
                        <div class="stat-val">{{ $productsCount ?? 0 }}</div><i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">المميزة</div>
                        <div class="stat-val" style="color:var(--gold)">{{ $featuredProductsCount ?? 0 }}</div><i class="ti ti-star stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">نفد المخزون</div>
                        <div class="stat-val" style="color:var(--red-light)">{{ $outOfStockCount ?? 0 }}</div><i class="ti ti-alert-triangle stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">متوسط السعر</div>
                        <div class="stat-val">{{ $averagePrice ?? '-' }}</div><i class="ti ti-coin stat-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة المنتجات</h3>
                        <div style="display:flex;gap:8px">
                            <select style="background:var(--bg3);border:1px solid var(--border2);color:var(--text2);padding:5px 8px;border-radius:6px;font-size:12px">
                                <option>كل الفئات</option>
                            </select>
                            <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input class="search-inp" placeholder="بحث..."></div>
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
                            @forelse($products ?? [] as $product)
                                <tr>
                                    <td>
                                        <div style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                            <i class="ti ti-box" style="color:var(--red-light);font-size:16px" aria-hidden="true"></i>
                                        </div>
                                    </td>
                                    <td style="color:var(--text);font-weight:500">{{ data_get($product, 'name', '-') }}</td>
                                    <td>{{ data_get($product, 'category', '-') }}</td>
                                    <td style="color:var(--red-light);font-weight:600">{{ data_get($product, 'price_formatted', '-') }}</td>
                                    <td style="color:#27AE60">{{ data_get($product, 'discount_price_formatted', '—') }}</td>
                                    <td>{{ data_get($product, 'quantity', '-') }}</td>
                                    <td>{{ data_get($product, 'rating', '-') }}</td>
                                    <td><span class="tag {{ data_get($product, 'featured_class', 'tag-r') }}">{{ data_get($product, 'featured_label', '-') }}</span></td>
                                    <td><span class="tag {{ data_get($product, 'status_class', 'tag-g') }}">{{ data_get($product, 'status_label', '-') }}</span></td>
                                    <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button><button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" style="text-align:center;color:var(--text3)">لا توجد منتجات لعرضها</td>
                                </tr>
                            @endforelse
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
                    @forelse($categories ?? [] as $category)
                        <div class="card" style="text-align:center">
                            <div style="width:50px;height:50px;border-radius:12px;background:rgba(192,57,43,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
                                <i class="ti ti-tag" style="font-size:22px;color:var(--red-light)" aria-hidden="true"></i>
                            </div>
                            <div style="font-size:14px;font-weight:600;color:var(--text)">{{ data_get($category,'name','-') }}</div>
                            <div style="font-size:11px;color:var(--text3);margin:4px 0 10px">{{ data_get($category,'products_count', 0) }} منتج</div>
                            <div style="display:flex;gap:6px;justify-content:center">
                                <button class="topbar-btn" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button>
                                <button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    @empty
                        <div class="card" style="text-align:center;grid-column:1/-1;color:var(--text3)">لا توجد فئات لعرضها</div>
                    @endforelse
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
                            @forelse($agents ?? [] as $agent)
                                <tr>
                                    <td style="color:var(--text);font-weight:500">{{ data_get($agent,'name','-') }}</td>
                                    <td dir="ltr">{{ data_get($agent,'phone','-') }}</td>
                                    <td dir="ltr">{{ data_get($agent,'whatsapp','-') }}</td>
                                    <td>{{ data_get($agent,'email','-') }}</td>
                                    <td>{{ data_get($agent,'city','-') }}</td>
                                    <td><span class="tag {{ data_get($agent,'status_class','tag-g') }}">{{ data_get($agent,'status_label','-') }}</span></td>
                                    <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button><button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;color:var(--text3)">لا توجد وكلاء لعرضهم</td>
                                </tr>
                            @endforelse
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
                            @forelse($distributors ?? [] as $distributor)
                                <tr>
                                    <td style="color:var(--text);font-weight:500">{{ data_get($distributor,'name','-') }}</td>
                                    <td dir="ltr">{{ data_get($distributor,'phone','-') }}</td>
                                    <td>{{ data_get($distributor,'city','-') }}</td>
                                    <td>{{ data_get($distributor,'shop_name','-') }}</td>
                                    <td><span class="tag {{ data_get($distributor,'status_class','tag-g') }}">{{ data_get($distributor,'status_label','-') }}</span></td>
                                    <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button><button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--text3)">لا توجد موزعين لعرضهم</td>
                                </tr>
                            @endforelse
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
                            @forelse($ads ?? [] as $ad)
                                <tr>
                                    <td style="color:var(--text);font-weight:500">{{ data_get($ad,'title','-') }}</td>
                                    <td><span class="tag {{ data_get($ad,'type_class','') }}">{{ data_get($ad,'type_label','-') }}</span></td>
                                    <td>{{ data_get($ad,'shop_name','-') }}</td>
                                    <td>{{ data_get($ad,'duration', '-') }}</td>
                                    <td>{{ data_get($ad,'order', '-') }}</td>
                                    <td><span class="tag {{ data_get($ad,'status_class','tag-g') }}">{{ data_get($ad,'status_label','-') }}</span></td>
                                    <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button><button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center;color:var(--text3)">لا توجد إعلانات لعرضها</td>
                                </tr>
                            @endforelse
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
                            @forelse($screens ?? [] as $screen)
                                <tr>
                                    <td style="color:var(--text);font-weight:500">{{ data_get($screen,'title','-') }}</td>
                                    <td><span class="tag {{ data_get($screen,'type_class','') }}">{{ data_get($screen,'type_label','-') }}</span></td>
                                    <td>{{ data_get($screen,'duration', '-') }}</td>
                                    <td><span class="tag {{ data_get($screen,'status_class','tag-g') }}">{{ data_get($screen,'status_label','-') }}</span></td>
                                    <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button><button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center;color:var(--text3)">لا توجد شاشات لعرضها</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- USERS -->
            <div class="page" id="page-users">
                <div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <h1>إدارة المستخدمين</h1>
                        <p>{{ $usersCount ?? 0 }} مستخدم مسجل</p>
                    </div>
                    <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> مستخدم جديد</button>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Super Admin</div>
                        <div class="stat-val">{{ $superAdminCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">مشرف شركة</div>
                        <div class="stat-val">{{ $companyAdminCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">أصحاب متاجر</div>
                        <div class="stat-val">{{ $shopOwnerCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">عملاء</div>
                        <div class="stat-val">{{ $customerCount ?? 0 }}</div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة المستخدمين</h3>
                        <div style="display:flex;gap:8px">
                            <select style="background:var(--bg3);border:1px solid var(--border2);color:var(--text2);padding:5px 8px;border-radius:6px;font-size:12px">
                                <option>كل الأدوار</option>
                            </select>
                            <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input class="search-inp" placeholder="بحث..."></div>
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
                            @forelse($users ?? [] as $user)
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            <div style="width:28px;height:28px;border-radius:50%;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
                                                {{ strtoupper(mb_substr(data_get($user, 'name', ''), 0, 1)) }}</div><span style="color:var(--text)">{{ data_get($user,'name','-') }}</span>
                                        </div>
                                    </td>
                                    <td>{{ data_get($user,'email','-') }}</td>
                                    <td dir="ltr">{{ data_get($user,'phone','-') }}</td>
                                    <td><span class="tag {{ data_get($user,'role_class','') }}">{{ data_get($user,'role_label','-') }}</span></td>
                                    <td><span class="tag {{ data_get($user,'status_class','tag-g') }}">{{ data_get($user,'status_label','-') }}</span></td>
                                    <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i class="ti ti-edit" aria-hidden="true"></i></button><button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--text3)">لا توجد مستخدمين لعرضهم</td>
                                </tr>
                            @endforelse
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
                            <h3><i class="ti ti-user" aria-hidden="true" style="margin-left:6px;color:var(--red-light)"></i>الملف الشخصي</h3>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:10px">
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">الاسم</div><input
                                    style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                    value="{{ $profileName ?? '' }}" />
                            </div>
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">البريد الإلكتروني</div><input
                                    style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                    value="{{ $profileEmail ?? '' }}" />
                            </div>
                            <button class="btn-red" style="width:fit-content">حفظ التغييرات</button>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3><i class="ti ti-lock" aria-hidden="true" style="margin-left:6px;color:var(--red-light)"></i>تغيير كلمة المرور</h3>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:10px">
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">كلمة المرور الحالية</div><input type="password"
                                    style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                    placeholder="••••••••" />
                            </div>
                            <div>
                                <div style="font-size:11px;color:var(--text3);margin-bottom:4px">كلمة المرور الجديدة</div><input type="password"
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

    Object.assign(titles, {
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
    });

    function showPage(id, el) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.getElementById('page-' + id).classList.add('active');
        document.getElementById('page-title').textContent = titles[id] || id;
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        if (el) el.classList.add('active');
        else {
            document.querySelectorAll('.nav-item').forEach(n => {
                if (n.textContent.trim().includes(titles[id]?.substring(0, 4))) n.classList.add('active');
            });
        }
    }
</script>
</body>
</html>
