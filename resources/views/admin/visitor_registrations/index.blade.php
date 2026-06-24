<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>تسجيلات الزوار - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --yellow: #ffd60a;
            --bg: #050505;
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .64);
            --dim: rgba(255, 255, 255, .42);
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

        .shell { min-height: 100vh; }
        .main { min-height: 100vh; margin-right: 245px; position: relative; z-index: 1; }
        .content { padding: 28px 34px 46px; }

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
            from { transform: translateX(0); }
            to { transform: translateX(50%); }
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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
            min-height: 128px;
            padding: 22px;
            position: relative;
            overflow: hidden;
        }

        .stat-label {
            color: rgba(255, 255, 255, .72);
            font-size: 13px;
            font-weight: 900;
        }

        .stat-val {
            margin-top: 16px;
            color: var(--card-color, var(--primary));
            font-size: 36px;
            line-height: 1;
            font-weight: 900;
            text-shadow: 0 0 18px rgba(0, 229, 255, .45);
        }

        .stat-icon {
            position: absolute;
            left: 20px;
            bottom: 18px;
            font-size: 46px;
            color: var(--card-color, var(--primary));
            opacity: .18;
        }

        .panel { padding: 24px; }

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

        .search-inp,
        .filter-select,
        .filter-btn {
            height: 44px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 999px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
        }

        .search-inp {
            width: min(290px, 100%);
            padding: 0 16px;
        }

        .filter-select {
            min-width: 150px;
            padding: 0 16px;
            cursor: pointer;
        }

        .filter-select option {
            color: #111;
            background: #fff;
        }

        .filter-btn {
            border-color: rgba(0, 229, 255, .4);
            background: rgba(0, 229, 255, .12);
            color: var(--primary);
            padding: 0 18px;
            cursor: pointer;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(0, 0, 0, .22);
        }

        table {
            width: 100%;
            min-width: 1180px;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 15px 16px;
            text-align: right;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            vertical-align: top;
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

        tbody tr:hover { background: rgba(0, 229, 255, .055); }
        tr:last-child td { border-bottom: 0; }

        .main-cell {
            color: #fff;
            font-weight: 900;
            min-width: 180px;
        }

        .sub-line {
            display: block;
            color: var(--dim);
            font-size: 11px;
            margin-top: 4px;
            direction: ltr;
            text-align: right;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 78px;
            min-height: 30px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid currentColor;
        }

        .tag-c {
            color: var(--primary);
            background: rgba(0, 229, 255, .09);
        }

        .tag-y {
            color: var(--yellow);
            background: rgba(255, 214, 10, .1);
        }

        .map-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 900;
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

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .pagination a,
        .pagination span {
            min-width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid var(--border);
            color: rgba(255, 255, 255, .74);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            text-decoration: none;
            font-weight: 900;
        }

        .pagination .active,
        .pagination a:hover {
            color: #001014;
            background: var(--primary);
            border-color: var(--primary);
        }

        .pagination .disabled {
            opacity: .38;
        }

        @media(max-width: 1000px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
        }

        @media(max-width: 680px) {
            .content { padding: 20px 16px 34px; }
            .page-head,
            .panel-head {
                align-items: flex-start;
                flex-direction: column;
            }
            .filter-row,
            .search-inp,
            .filter-select,
            .filter-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            <div class="content">
                <div class="hero-strip">
                    <div class="ticker">
                        <span>تسجيلات الزوار والعملاء والتجار</span>
                        <span>بيانات الدخول الأولى للموقع</span>
                        <span>متابعة طلبات الانضمام واللوكيشن</span>
                        <span>تسجيلات الزوار والعملاء والتجار</span>
                    </div>
                </div>

                <div class="page-head">
                    <div>
                        <div class="page-kicker">إدارة الزوار</div>
                        <h1>تسجيلات الزوار</h1>
                        <p>كل عميل أو صاحب متجر سجل بياناته من واجهة الموقع يظهر هنا.</p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card" style="--card-color: var(--primary);">
                        <div class="stat-label">كل التسجيلات</div>
                        <div class="stat-val">{{ $totalCount }}</div>
                        <i class="ti ti-address-book stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--green);">
                        <div class="stat-label">عملاء</div>
                        <div class="stat-val">{{ $customersCount }}</div>
                        <i class="ti ti-user stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--yellow);">
                        <div class="stat-label">أصحاب متاجر</div>
                        <div class="stat-val">{{ $merchantsCount }}</div>
                        <i class="ti ti-building-store stat-icon"></i>
                    </div>
                </div>

                <section class="panel">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="ti ti-list-details"></i>
                            القائمة
                        </div>

                        <form class="filter-row" method="GET" action="{{ route('visitor-registrations.index') }}">
                            <input
                                class="search-inp"
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="بحث بالاسم، الهاتف، المتجر">

                            <select class="filter-select" name="type" aria-label="فلترة النوع">
                                <option value="">كل الأنواع</option>
                                <option value="customer" @selected($selectedType === 'customer')>عملاء</option>
                                <option value="merchant" @selected($selectedType === 'merchant')>أصحاب متاجر</option>
                            </select>

                            <button class="filter-btn" type="submit">
                                <i class="ti ti-search"></i>
                                بحث
                            </button>
                        </form>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>النوع</th>
                                    <th>الاسم والهاتف</th>
                                    <th>بيانات المتجر</th>
                                    <th>مكان السكن</th>
                                    <th>لوكيشن المحل</th>
                                    <th>تاريخ التسجيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registrations as $registration)
                                    <tr>
                                        <td>
                                            @if($registration->type === 'merchant')
                                                <span class="tag tag-y">صاحب متجر</span>
                                            @else
                                                <span class="tag tag-c">عميل</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="main-cell">
                                                {{ $registration->name }}
                                                <span class="sub-line">{{ $registration->phone }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($registration->type === 'merchant')
                                                <div class="main-cell">
                                                    {{ $registration->shop_name ?: '-' }}
                                                    <span class="sub-line">الملف الضريبي: {{ $registration->tax_file ?: '-' }}</span>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $registration->residence_address }}</td>
                                        <td>
                                            @if($registration->map_link)
                                                <a class="map-link" href="{{ $registration->map_link }}" target="_blank" rel="noopener noreferrer">
                                                    <i class="ti ti-map-pin"></i>
                                                    فتح الخريطة
                                                </a>
                                                <span class="sub-line">{{ $registration->latitude }}, {{ $registration->longitude }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            {{ $registration->created_at?->format('Y-m-d H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="ti ti-address-book-off"></i>
                                                لا توجد تسجيلات حتى الآن
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($registrations->hasPages())
                        <div class="pagination">
                            @if($registrations->onFirstPage())
                                <span class="disabled">السابق</span>
                            @else
                                <a href="{{ $registrations->previousPageUrl() }}">السابق</a>
                            @endif

                            @foreach($registrations->getUrlRange(1, $registrations->lastPage()) as $page => $url)
                                @if($page === $registrations->currentPage())
                                    <span class="active">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if($registrations->hasMorePages())
                                <a href="{{ $registrations->nextPageUrl() }}">التالي</a>
                            @else
                                <span class="disabled">التالي</span>
                            @endif
                        </div>
                    @endif
                </section>
            </div>
        </main>
    </div>
</body>

</html>
