<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>قائمة المتاجر - Ozman</title>
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
            padding: 0;
        }

        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --yellow: #ffd60a;
            --danger: #ff3b30;
            --bg: #050505;
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .64);
            --dim: rgba(255, 255, 255, .4);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 15% 14%, rgba(112, 0, 255, .14), transparent 29%),
                radial-gradient(circle at 78% 8%, rgba(0, 229, 255, .14), transparent 34%),
                linear-gradient(180deg, #030303 0%, #050505 52%, #08020f 100%);
            color: var(--text);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, .026) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .026) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 82%);
        }

        .shell {
            min-height: 100vh;
        }

        .main {
            min-height: 100vh;
            margin-right: 245px;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 28px 34px 46px;
        }

        .hero-strip {
            min-height: 136px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: linear-gradient(90deg, rgba(0, 229, 255, .08), rgba(255, 255, 255, .035), rgba(112, 0, 255, .08));
            backdrop-filter: blur(18px);
            overflow: hidden;
            display: flex;
            align-items: center;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .42), inset 0 0 45px rgba(0, 229, 255, .035);
            margin-bottom: 28px;
        }

        .ticker {
            display: flex;
            gap: 54px;
            width: max-content;
            white-space: nowrap;
            animation: slideRtl 24s linear infinite;
            color: var(--primary);
            font-size: 18px;
            font-weight: 900;
            text-shadow: 0 0 14px rgba(0, 229, 255, .55);
        }

        @keyframes slideRtl {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(50%);
            }
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 22px;
        }

        .page-kicker {
            color: var(--primary);
            font-size: 13px;
            font-weight: 900;
            text-shadow: 0 0 12px rgba(0, 229, 255, .5);
            margin-bottom: 6px;
        }

        h1 {
            font-size: 34px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--primary);
            text-shadow: 0 0 20px rgba(0, 229, 255, .42);
        }

        .page-head p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .btn-primary {
            border: 0;
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            min-height: 46px;
            padding: 0 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 0 22px rgba(0, 229, 255, .34);
            transition: transform .3s ease, box-shadow .3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 34px rgba(0, 229, 255, .58);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .stat-card,
        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 26px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
        }

        .stat-card {
            min-height: 142px;
            padding: 22px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--card-color, var(--primary)));
            box-shadow: 0 0 18px var(--card-color, var(--primary));
        }

        .stat-label {
            color: rgba(255, 255, 255, .72);
            font-size: 13px;
            font-weight: 900;
        }

        .stat-val {
            margin-top: 18px;
            color: var(--card-color, var(--primary));
            font-size: 38px;
            line-height: 1;
            font-weight: 900;
            text-shadow: 0 0 18px rgba(0, 229, 255, .45);
        }

        .stat-icon {
            position: absolute;
            left: 20px;
            bottom: 18px;
            font-size: 48px;
            color: var(--card-color, var(--primary));
            opacity: .18;
        }

        .panel {
            padding: 24px;
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
        }

        .panel-title {
            color: #fff;
            font-size: 19px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title i {
            color: var(--primary);
            filter: drop-shadow(0 0 10px rgba(0, 229, 255, .55));
        }

        .filter-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 16px;
        }

        .search-inp,
        .filter-select {
            height: 44px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 999px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition: all .3s ease;
        }

        .search-inp {
            width: 250px;
            padding: 0 16px 0 42px;
        }

        .filter-select {
            min-width: 160px;
            padding: 0 16px;
            cursor: pointer;
        }

        .search-inp:focus,
        .filter-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .search-inp::placeholder {
            color: var(--dim);
        }

        .filter-select option {
            color: #111;
            background: #fff;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(0, 0, 0, .22);
        }

        table {
            width: 100%;
            min-width: 1060px;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 16px 18px;
            text-align: right;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
        }

        th {
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            text-shadow: 0 0 9px rgba(0, 229, 255, .35);
            background: rgba(255, 255, 255, .025);
        }

        td {
            color: var(--muted);
            font-weight: 700;
        }

        tbody tr {
            transition: background .25s ease;
        }

        tbody tr:hover {
            background: rgba(0, 229, 255, .055);
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .shop-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 900;
            min-width: 230px;
        }

        .shop-logo {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: #000;
            border: 1px solid var(--primary);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 900;
            overflow: hidden;
            box-shadow: 0 0 16px rgba(0, 229, 255, .3);
            flex-shrink: 0;
        }

        .shop-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .shop-sub {
            display: block;
            color: var(--dim);
            font-size: 11px;
            margin-top: 3px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 74px;
            min-height: 30px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid currentColor;
        }

        .tag-g {
            color: var(--green);
            background: rgba(37, 211, 102, .1);
        }

        .tag-r {
            color: var(--danger);
            background: rgba(255, 59, 48, .1);
        }

        .tag-y {
            color: var(--yellow);
            background: rgba(255, 214, 10, .1);
        }

        .tag-c {
            color: var(--primary);
            background: rgba(0, 229, 255, .09);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .05);
            color: rgba(255, 255, 255, .72);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            transition: all .3s ease;
        }

        .icon-btn:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-3px) scale(1.06);
            box-shadow: 0 0 16px rgba(0, 229, 255, .32);
        }

        .empty-state {
            padding: 46px 18px;
            text-align: center;
            color: var(--dim);
        }

        .empty-state i {
            display: block;
            color: var(--primary);
            font-size: 42px;
            margin-bottom: 12px;
            filter: drop-shadow(0 0 14px rgba(0, 229, 255, .45));
        }

        @media(max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media(max-width: 900px) {
            .main {
                margin-right: 0;
            }
        }

        @media(max-width: 680px) {
            .content {
                padding: 20px 16px 34px;
            }

            .page-head,
            .panel-head {
                align-items: stretch;
                flex-direction: column;
            }

            .filter-row {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-inp,
            .filter-select,
            .btn-primary {
                width: 100%;
            }

            h1 {
                font-size: 28px;
            }
        }
        .assign-modal{position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(0,0,0,.78);backdrop-filter:blur(8px)}.assign-modal.open{display:flex}.assign-card{width:min(520px,100%);padding:24px;border:1px solid rgba(0,229,255,.28);border-radius:24px;background:#101217;box-shadow:0 24px 80px rgba(0,0,0,.6)}.assign-card h2{margin:0 0 6px;color:#00e5ff}.assign-card p{margin:0 0 18px;color:rgba(255,255,255,.6)}.assign-card select{width:100%;height:54px;padding:0 14px;border:1px solid rgba(255,255,255,.15);border-radius:14px;background:#171a20;color:#fff;font-family:inherit}.assign-card-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}.assign-card-actions button{border:0;border-radius:999px;padding:11px 18px;font-family:inherit;font-weight:900;cursor:pointer}.assign-save{background:#00e5ff;color:#001014}.assign-cancel{background:rgba(255,255,255,.08);color:#fff}
    </style>
</head>

<body>
    @php
        $currentUser = auth()->user();
        $canCreateShop = $currentUser?->isSuperAdmin()
            || $currentUser?->canAccessRouteName('shops.create');
        $canOpenOzmanShop = $currentUser?->isSuperAdmin();

        $shopItems = collect(
            $shops ?? [
                [
                    'id' => 1,
                    'name' => 'Ozman الرئيسي',
                    'slug' => 'ozman-main',
                    'city' => 'غزة',
                    'phone' => '059-000-0001',
                    'products_count' => 128,
                    'open_time' => '09:00',
                    'close_time' => '22:00',
                    'is_active' => true,
                    'status_label' => 'نشط',
                    'status_class' => 'tag-g',
                ],
                [
                    'id' => 2,
                    'name' => 'Healthy Shop',
                    'slug' => 'healthy-shop',
                    'city' => 'رام الله',
                    'phone' => '059-111-2222',
                    'products_count' => 86,
                    'open_time' => '10:00',
                    'close_time' => '21:00',
                    'is_active' => true,
                    'status_label' => 'نشط',
                    'status_class' => 'tag-g',
                ],
                [
                    'id' => 3,
                    'name' => 'متجر الجمال الفاخر',
                    'slug' => 'beauty-lux',
                    'city' => 'الخليل',
                    'phone' => '059-333-4444',
                    'products_count' => 42,
                    'open_time' => '11:00',
                    'close_time' => '20:00',
                    'is_active' => false,
                    'status_label' => 'غير نشط',
                    'status_class' => 'tag-r',
                ],
            ],
        );

        $shopsTotal = $shopsCount ?? $shopItems->count();
        $activeShops =
            $activeShopsCount ??
            $shopItems
                ->filter(fn($shop) => (bool) data_get($shop, 'is_active', data_get($shop, 'status_class') === 'tag-g'))
                ->count();
        $inactiveShops = $inactiveShopsCount ?? max($shopsTotal - $activeShops, 0);
        $productsTotal = $shopItems->sum(fn($shop) => (int) data_get($shop, 'products_count', 0));
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'قائمة المتاجر'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط المتاجر">
                    <div class="ticker">
                        <span>إدارة متاجر Ozman من مكان واحد</span>
                        <span>تحكم بالحالة، المنتجات، أوقات العمل، وبيانات التواصل</span>
                        <span>كل متجر جاهز للتعديل والربط مع الكنترولر لاحقا</span>
                        <span>إدارة متاجر Ozman من مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">المتجر</div>
                        <h1>قائمة المتاجر</h1>
                        <p>{{ $shopsTotal }} متجر داخل النظام مع متابعة الحالة وعدد المنتجات.</p>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end">
                    @if($canOpenOzmanShop)
                    <a href="{{ route('shops.ozman') }}" class="btn-primary">
                        <i class="ti ti-building-store" aria-hidden="true"></i>
                        متجر Ozman
                    </a>
                    @endif
                    @if($canCreateShop)
                    <a href="{{ route('shops.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        متجر جديد
                    </a>
                    @endif
                    </div>
                </header>

                @if (session('status'))
                    <div class="panel"
                        style="margin-bottom:18px;border-color:rgba(37,211,102,.35);background:rgba(37,211,102,.08);color:#fff">
                        {{ session('status') }}
                    </div>
                @endif

                <section class="stats-grid" aria-label="إحصائيات المتاجر">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي المتاجر</div>
                        <div class="stat-val">{{ $shopsTotal }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--green)">
                        <div class="stat-label">متاجر نشطة</div>
                        <div class="stat-val">{{ $activeShops }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--danger)">
                        <div class="stat-label">غير نشطة</div>
                        <div class="stat-val">{{ $inactiveShops }}</div>
                        <i class="ti ti-circle-x stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">إجمالي المنتجات</div>
                        <div class="stat-val">{{ $productsTotal }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            المتاجر
                        </h2>

                        <div class="filter-row">
                            <select class="filter-select" id="statusFilter" aria-label="فلترة الحالة">
                                <option value="">كل الحالات</option>
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>

                            <div class="input-wrap">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input class="search-inp" id="shopSearch" type="search"
                                    placeholder="بحث بالاسم، المدينة، الهاتف...">
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="shopsTable">
                            <thead>
                                <tr>
                                    <th>المتجر</th>
                                    <th>المدينة</th>
                                    <th>الهاتف</th>
                                    <th>المنتجات</th>
                                    <th>أوقات العمل</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shopItems as $shop)
                                    @php
                                        $shopId = data_get($shop, 'id', 1);
                                        $shopName = data_get($shop, 'name', '-');
                                        $logo = data_get($shop, 'logo');
                                        $isActive = (bool) data_get(
                                            $shop,
                                            'is_active',
                                            data_get($shop, 'status_class') === 'tag-g',
                                        );
                                        $statusClass = data_get($shop, 'status_class', $isActive ? 'tag-g' : 'tag-r');
                                        $statusLabel = data_get($shop, 'status_label', $isActive ? 'نشط' : 'غير نشط');
                                        $openTime = data_get($shop, 'open_time');
                                        $closeTime = data_get($shop, 'close_time');
                                    @endphp
                                    <tr data-status="{{ $isActive ? 'active' : 'inactive' }}">
                                        <td>
                                            <div class="shop-cell">
                                                <span class="shop-logo">
                                                    @if ($logo)
                                                        <img src="{{ asset($logo) }}" alt="">
                                                    @else
                                                        {{ mb_substr($shopName, 0, 1) }}
                                                    @endif
                                                </span>
                                                <span>
                                                    {{ $shopName }}
                                                    <span
                                                        class="shop-sub">{{ data_get($shop, 'slug', 'shop-' . $shopId) }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ data_get($shop, 'city', '-') }}</td>
                                        <td dir="ltr">{{ data_get($shop, 'phone', '-') }}</td>
                                        <td><span class="tag tag-c">{{ data_get($shop, 'products_count', 0) }}</span>
                                        </td>
                                        <td>{{ $openTime && $closeTime ? $openTime . ' - ' . $closeTime : '-' }}</td>
                                        <td><span class="tag {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                        <td>
    <div class="actions">
        @if(auth()->user()?->isSuperAdmin())
            <button type="button" class="icon-btn" data-assign-distributor
                data-shop-name="{{ $shopName }}"
                data-action="{{ route('shops.distributor.assign', $shopId) }}"
                data-distributor-id="{{ data_get($shop, 'distributor_id') }}"
                title="تعيين موزع للمتجر" aria-label="تعيين موزع للمتجر">
                <i class="ti ti-truck-delivery" aria-hidden="true"></i>
            </button>
        @endif

        @if(auth()->user()?->isSuperAdmin() && data_get($shop, 'user_id'))
            <form action="{{ route('shops.enter-dashboard', $shopId) }}" method="POST">
                @csrf
                <button type="submit" class="icon-btn" aria-label="الدخول إلى لوحة تحكم المتجر"
                    title="الدخول إلى لوحة تحكم المتجر">
                    <i class="ti ti-login-2" aria-hidden="true"></i>
                </button>
            </form>
        @endif

        <!-- عرض -->
        <a href="{{ route('shops.show', $shopId) }}" class="icon-btn"
            aria-label="عرض">
            <i class="ti ti-eye" aria-hidden="true"></i>
        </a>

        <!-- تعديل -->
        <a href="{{ route('shops.edit', $shopId) }}" class="icon-btn"
            aria-label="تعديل">
            <i class="ti ti-edit" aria-hidden="true"></i>
        </a>

        <!-- حذف -->
        <form action="{{ route('shops.destroy', $shopId) }}" method="POST"
            onsubmit="return confirm('هل تريد حذف هذا المتجر؟')">
            @csrf
            @method('DELETE')

            <button type="submit" class="icon-btn" aria-label="حذف">
                <i class="ti ti-trash" aria-hidden="true"></i>
            </button>
        </form>

    </div>
</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="ti ti-building-store-off" aria-hidden="true"></i>
                                                لا توجد متاجر لعرضها حاليا
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    @if(auth()->user()?->isSuperAdmin())
        <div class="assign-modal" id="assignDistributorModal" role="dialog" aria-modal="true" aria-labelledby="assignDistributorTitle">
            <form class="assign-card" id="assignDistributorForm" method="POST">
                @csrf
                @method('PATCH')
                <h2 id="assignDistributorTitle">تعيين موزع للمتجر</h2>
                <p>المتجر: <strong id="assignDistributorShopName"></strong></p>
                <label for="assignDistributorSelect" style="display:block;margin-bottom:8px;font-weight:900">الموزع المسؤول عن الطلبات</label>
                <select id="assignDistributorSelect" name="distributor_id">
                    <option value="">بدون موزع / إلغاء الربط</option>
                    @foreach($assignableDistributors as $distributor)
                        <option value="{{ $distributor->id }}">{{ $distributor->name }}{{ $distributor->phone ? ' — ' . $distributor->phone : '' }}</option>
                    @endforeach
                </select>
                <small style="display:block;margin-top:9px;color:rgba(255,255,255,.5)">بعد الحفظ ستُنسب طلبات المتجر إلى الموزع المختار. تغيير الموزع يفك أي مسوّق تابع لموزع مختلف.</small>
                <div class="assign-card-actions">
                    <button type="button" class="assign-cancel" data-close-assign>إلغاء</button>
                    <button type="submit" class="assign-save">حفظ التعيين</button>
                </div>
            </form>
        </div>
    @endif

    <script>
        const shopSearch = document.getElementById('shopSearch');
        const statusFilter = document.getElementById('statusFilter');
        const shopRows = document.querySelectorAll('#shopsTable tbody tr');

        function filterShops() {
            const query = (shopSearch?.value || '').trim().toLowerCase();
            const status = statusFilter?.value || '';

            shopRows.forEach((row) => {
                const matchesQuery = row.textContent.toLowerCase().includes(query);
                const matchesStatus = !status || row.dataset.status === status;
                row.style.display = matchesQuery && matchesStatus ? '' : 'none';
            });
        }

        shopSearch?.addEventListener('input', filterShops);
        statusFilter?.addEventListener('change', filterShops);

        const assignModal = document.getElementById('assignDistributorModal');
        const assignForm = document.getElementById('assignDistributorForm');
        const assignSelect = document.getElementById('assignDistributorSelect');
        const assignShopName = document.getElementById('assignDistributorShopName');
        document.querySelectorAll('[data-assign-distributor]').forEach((button) => {
            button.addEventListener('click', () => {
                assignForm.action = button.dataset.action;
                assignSelect.value = button.dataset.distributorId || '';
                assignShopName.textContent = button.dataset.shopName || '';
                assignModal.classList.add('open');
            });
        });
        document.querySelector('[data-close-assign]')?.addEventListener('click', () => assignModal?.classList.remove('open'));
        assignModal?.addEventListener('click', (event) => {
            if (event.target === assignModal) assignModal.classList.remove('open');
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') assignModal?.classList.remove('open');
        });
    </script>
</body>

</html>
