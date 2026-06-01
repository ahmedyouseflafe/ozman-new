<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الرئيسية — Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --primary-color: #00e5ff;
            --accent-color: #7000ff;
            --green: #25d366;
            --yellow: #f1c40f;
            --danger: #ff3b30;
            --glass-bg: rgba(255, 255, 255, .05);
            --glass-border: rgba(255, 255, 255, .1);
            --text-main: #fff;
            --text-soft: rgba(255, 255, 255, .72);
            --text-muted: rgba(255, 255, 255, .42);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 50% 4%, rgba(0, 229, 255, .10), transparent 34%),
                radial-gradient(circle at 12% 70%, rgba(112, 0, 255, .10), transparent 28%),
                #050505;
            color: var(--text-main);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
            overflow-x: hidden
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
            opacity: .5
        }

        .shell {
            display: flex;
            min-height: 100vh
        }

        .main {
            flex: 1;
            margin-right: 245px;
            min-width: 0
        }

        .content {
            padding: 28px;
            max-width: 1500px;
            margin: 0 auto
        }

        .hero-panel {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 24px;
            align-items: center;
            margin-bottom: 24px
        }

        .display-screen {
            min-height: 150px;
            background: rgba(255, 255, 255, .035);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            overflow: hidden;
            display: flex;
            align-items: center;
            position: relative;
            box-shadow: inset 0 0 40px rgba(0, 229, 255, .035)
        }

        .display-screen::before {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            pointer-events: none;
            background: linear-gradient(90deg, transparent, rgba(0, 229, 255, .2), transparent);
            opacity: .35
        }

        .story-slider {
            display: flex;
            width: max-content;
            animation: slide-rtl 24s linear infinite;
            white-space: nowrap
        }

        .welcome-msg {
            color: var(--primary-color);
            font-weight: 900;
            font-size: 1.02rem;
            padding: 0 48px;
            text-shadow: 0 0 13px rgba(0, 229, 255, .55)
        }

        @keyframes slide-rtl {
            from {
                transform: translateX(0)
            }

            to {
                transform: translateX(50%)
            }
        }

        .hero-orb {
            width: 145px;
            height: 145px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #000;
            box-shadow: 0 0 34px rgba(0, 229, 255, .48);
            animation: float 4s ease-in-out infinite
        }

        .hero-orb i {
            font-size: 42px;
            color: var(--primary-color);
            filter: drop-shadow(0 0 12px rgba(0, 229, 255, .8))
        }

        .hero-orb span {
            margin-top: 8px;
            color: var(--text-main);
            font-size: 12px;
            font-weight: 800
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-10px)
            }
        }

        .page-header {
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 22px;
            margin-bottom: 22px;
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .24);
            animation: slideUp .7s ease both
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 900;
            color: var(--primary-color);
            text-shadow: 0 0 16px rgba(0, 229, 255, .45)
        }

        .page-header p {
            font-size: 13px;
            color: var(--text-soft);
            margin-top: 4px
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            min-height: 126px;
            backdrop-filter: blur(12px);
            animation: slideUp .8s ease both;
            transition: all .38s cubic-bezier(.175, .885, .32, 1.275)
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: auto 18px 0 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent, var(--primary-color)), transparent)
        }

        .stat-card:hover,
        .card:hover {
            transform: translateY(-7px);
            border-color: var(--accent, var(--primary-color));
            box-shadow: 0 18px 42px rgba(0, 0, 0, .32), 0 0 24px rgba(0, 229, 255, .15)
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-soft);
            margin-bottom: 10px;
            font-weight: 800
        }

        .stat-val {
            font-size: 34px;
            font-weight: 900;
            color: var(--accent, var(--primary-color));
            line-height: 1;
            text-shadow: 0 0 18px rgba(0, 229, 255, .42)
        }

        .stat-sub {
            font-size: 11px;
            margin-top: 8px;
            color: var(--green)
        }

        .stat-down {
            color: var(--danger)
        }

        .stat-icon {
            position: absolute;
            bottom: 14px;
            left: 16px;
            font-size: 42px;
            color: var(--accent, var(--primary-color));
            opacity: .22;
            pointer-events: none;
            filter: drop-shadow(0 0 12px currentColor)
        }

        .grid2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px
        }

        .card {
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 18px;
            margin-bottom: 16px;
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .24);
            animation: slideUp .9s ease both;
            transition: all .34s cubic-bezier(.175, .885, .32, 1.275)
        }

        .card-hd {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--glass-border)
        }

        .card-hd h3 {
            font-size: 16px;
            font-weight: 900;
            color: var(--text-main)
        }

        .badge {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background: rgba(0, 229, 255, .08);
            font-weight: 900
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 900;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 0 20px rgba(0, 229, 255, .28);
            transition: all .3s ease
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 28px rgba(0, 229, 255, .58)
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            height: 130px;
            padding-top: 12px
        }

        .bar-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px
        }

        .bar {
            width: 100%;
            background: linear-gradient(to top, var(--accent-color), var(--primary-color));
            border-radius: 7px 7px 0 0;
            box-shadow: 0 0 14px rgba(0, 229, 255, .25)
        }

        .bar-label {
            font-size: 10px;
            color: var(--text-muted)
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 10px
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 16px
        }

        .ri-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #000;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 14px rgba(0, 229, 255, .35);
            flex-shrink: 0
        }

        .ri-info {
            flex: 1
        }

        .ri-title {
            font-size: 13px;
            font-weight: 900;
            color: var(--text-main)
        }

        .ri-sub {
            font-size: 11px;
            color: var(--text-muted)
        }

        .ri-val {
            font-size: 13px;
            font-weight: 900;
            color: var(--primary-color)
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 9px;
            font-size: 12px;
            min-width: 720px
        }

        th {
            text-align: right;
            padding: 7px 12px;
            color: var(--primary-color);
            font-weight: 900;
            text-shadow: 0 0 10px rgba(0, 229, 255, .3)
        }

        td {
            padding: 13px 12px;
            background: rgba(255, 255, 255, .035);
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-soft);
            vertical-align: middle
        }

        td:first-child {
            border-right: 1px solid var(--glass-border);
            border-radius: 0 16px 16px 0
        }

        td:last-child {
            border-left: 1px solid var(--glass-border);
            border-radius: 16px 0 0 16px
        }

        tr:hover td {
            background: rgba(0, 229, 255, .075);
            border-color: rgba(0, 229, 255, .28);
            color: #fff
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 900;
            border: 1px solid currentColor;
            background: rgba(255, 255, 255, .04)
        }

        .tag-g {
            color: var(--green)
        }

        .tag-r {
            color: var(--danger)
        }

        .tag-y {
            color: var(--yellow)
        }

        .empty-text {
            width: 100%;
            color: var(--text-muted);
            font-size: 12px;
            text-align: center;
            padding: 24px 0
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(26px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        @media(max-width:1100px) {
            .stats-grid,
            .grid2 {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .hero-panel {
                grid-template-columns: 1fr
            }

            .hero-orb {
                display: none
            }
        }

        @media(max-width:900px) {
            .main {
                margin-right: 0
            }

            .content {
                padding: 18px
            }
        }

        @media(max-width:640px) {
            .stats-grid,
            .grid2 {
                grid-template-columns: 1fr
            }

            .display-screen {
                min-height: 118px
            }

            .welcome-msg {
                font-size: .92rem;
                padding: 0 30px
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <div class="main">
            @include('admin.includes.header', ['title' => 'الرئيسية'])

            <div class="content">
                <div class="hero-panel">
                    <div class="display-screen">
                        <div class="story-slider">
                            <span class="welcome-msg">لوحة Ozman الرئيسية تعرض المتاجر والمنتجات والمستخدمين من مكان واحد</span>
                            <span class="welcome-msg">{{ $shopsCount ?? 0 }} متجر، {{ $activeProductsCount ?? 0 }} منتج نشط، {{ $usersCount ?? 0 }} مستخدم</span>
                            <span class="welcome-msg">تابع آخر المنتجات والمتاجر والتحديثات اليومية بسرعة</span>
                        </div>
                    </div>
                    <div class="hero-orb">
                        <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
                        <span>الرئيسية</span>
                    </div>
                </div>

                <div class="page-header">
                    <h1>نظرة عامة</h1>
                    <p>مرحباً بك، آخر تحديث اليوم.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card" style="--accent: var(--danger)">
                        <div class="stat-label">إجمالي المتاجر</div>
                        <div class="stat-val">{{ $shopsCount ?? 0 }}</div>
                        <div class="stat-sub">{{ $shopsDelta ?? '' }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--accent-color)">
                        <div class="stat-label">المنتجات النشطة</div>
                        <div class="stat-val">{{ $activeProductsCount ?? 0 }}</div>
                        <div class="stat-sub">{{ $activeProductsDelta ?? '' }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: #3498db">
                        <div class="stat-label">إجمالي المستخدمين</div>
                        <div class="stat-val">{{ $usersCount ?? 0 }}</div>
                        <div class="stat-sub">{{ $usersDelta ?? '' }}</div>
                        <i class="ti ti-users stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--green)">
                        <div class="stat-label">الوكلاء والموزعون</div>
                        <div class="stat-val">{{ $agentsAndDistributorsCount ?? 0 }}</div>
                        <div class="stat-sub stat-down">{{ $agentsStatusDelta ?? '' }}</div>
                        <i class="ti ti-truck stat-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="grid2">
                    <div class="card">
                        <div class="card-hd">
                            <h3>المبيعات الشهرية</h3>
                            <span class="badge">{{ $salesYear ?? date('Y') }}</span>
                        </div>
                        <div class="chart-bars">
                            @forelse($monthlySales ?? [] as $item)
                                <div class="bar-wrap">
                                    <div class="bar" style="height:{{ min(100, max(0, data_get($item, 'value', 0))) }}%"></div>
                                    <div class="bar-label">{{ data_get($item, 'label', '') }}</div>
                                </div>
                            @empty
                                <div class="empty-text">لا توجد بيانات مبيعات</div>
                            @endforelse
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:8px">
                            <span style="font-size:10px;color:var(--text-muted)">{{ $salesRangeStart ?? 'بداية الشهر' }}</span>
                            <span style="font-size:10px;color:var(--text-muted)">{{ $salesRangeEnd ?? 'نهاية الشهر' }}</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-hd">
                            <h3>آخر المنتجات المضافة</h3>
                            <a href="{{ route('products') }}" class="btn-primary">
                                <i class="ti ti-plus" aria-hidden="true"></i>
                                إضافة
                            </a>
                        </div>
                        <div class="recent-list">
                            @forelse($recentProducts ?? [] as $product)
                                <div class="recent-item">
                                    <div class="ri-icon"><i class="ti ti-package" aria-hidden="true"></i></div>
                                    <div class="ri-info">
                                        <div class="ri-title">{{ data_get($product, 'name', '-') }}</div>
                                        <div class="ri-sub">{{ data_get($product, 'category', '-') }} - {{ data_get($product, 'added_at_label', '') }}</div>
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
                        <h3>آخر المتاجر المسجلة</h3>
                        <span class="badge">{{ $recentShopsLabel ?? 'آخر تحديث' }}</span>
                    </div>
                    <div class="table-wrap">
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
                                        <td style="color:var(--text-main);font-weight:900">{{ data_get($shop, 'name', '-') }}</td>
                                        <td>{{ data_get($shop, 'city', '-') }}</td>
                                        <td>{{ data_get($shop, 'products_count', '-') }}</td>
                                        <td>
                                            <span class="tag {{ data_get($shop, 'status_class', 'tag-g') }}">
                                                {{ data_get($shop, 'status_label', '-') }}
                                            </span>
                                        </td>
                                        <td>{{ data_get($shop, 'registered_at', '-') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:var(--text-muted)">لا توجد متاجر مسجلة حالياً</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>
