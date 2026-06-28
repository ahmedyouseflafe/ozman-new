<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الموزعون - Ozman</title>
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
            --danger: #ff3b30;
            --bg: #050505;
            --panel: rgba(255, 255, 255, .045);
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .62);
            --dim: rgba(255, 255, 255, .38);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 18% 16%, rgba(112, 0, 255, .13), transparent 28%),
                radial-gradient(circle at 82% 6%, rgba(0, 229, 255, .12), transparent 32%),
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
                linear-gradient(rgba(255, 255, 255, .025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .025) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 82%);
        }

        .shell { min-height: 100vh; }

        .main {
            min-height: 100vh;
            margin-right: 245px;
            position: relative;
            z-index: 1;
        }

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

        .input-wrap { position: relative; }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 16px;
        }

        .search-inp {
            width: 250px;
            height: 44px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 999px;
            color: #fff;
            padding: 0 16px 0 42px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            transition: all .3s ease;
        }

        .search-inp:focus {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .search-inp::placeholder { color: var(--dim); }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(0, 0, 0, .22);
        }

        table {
            width: 100%;
            min-width: 940px;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 17px 18px;
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

        tbody tr { transition: background .25s ease; }
        tbody tr:hover { background: rgba(0, 229, 255, .055); }
        tr:last-child td { border-bottom: 0; }

        .dist-name {
            display: flex;
            align-items: center;
            gap: 11px;
            color: #fff;
            font-weight: 900;
        }

        .dist-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #000;
            border: 1px solid var(--primary);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 900;
            box-shadow: 0 0 16px rgba(0, 229, 255, .35);
            flex-shrink: 0;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            min-height: 30px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid currentColor;
        }

        .tag-g { color: var(--green); background: rgba(37, 211, 102, .1); }
        .tag-r { color: var(--danger); background: rgba(255, 59, 48, .1); }
        .tag-y { color: var(--yellow); background: rgba(255, 214, 10, .1); }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .actions form { display: inline-flex; }

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
            transition: all .3s ease;
        }

        .icon-btn:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-3px) scale(1.06);
            box-shadow: 0 0 16px rgba(0, 229, 255, .32);
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 14px 18px;
            border: 1px solid rgba(37, 211, 102, .32);
            background: rgba(37, 211, 102, .08);
            color: rgba(255, 255, 255, .88);
            border-radius: 18px;
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

        @media(max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
        }

        @media(max-width: 680px) {
            .content { padding: 20px 16px 34px; }
            .page-head,
            .panel-head {
                align-items: stretch;
                flex-direction: column;
            }
            .stats-grid { grid-template-columns: 1fr; }
            .search-inp { width: 100%; }
            h1 { font-size: 28px; }
        }
    </style>
</head>

<body>
    @php
        $distributorItems = collect($distributors ?? [
            [
                'name' => 'شركة التوزيع المركزية',
                'phone' => '059-500-6000',
                'whatsapp' => '059-500-6000',
                'city' => 'نابلس',
                'address' => 'المنطقة الصناعية',
                'shop_name' => 'Ozman الرئيسي',
                'status_class' => 'tag-g',
                'status_label' => 'نشط',
            ],
            [
                'name' => 'موزع الجنوب',
                'phone' => '059-600-7000',
                'whatsapp' => '059-600-7000',
                'city' => 'الخليل',
                'address' => 'حي الراشد',
                'shop_name' => 'متجر الجمال الفاخر',
                'status_class' => 'tag-g',
                'status_label' => 'نشط',
            ],
            [
                'name' => 'موزع الشمال',
                'phone' => '059-700-8000',
                'whatsapp' => '059-700-8000',
                'city' => 'جنين',
                'address' => 'شارع القدس',
                'shop_name' => 'صيدلية الشفاء',
                'status_class' => 'tag-r',
                'status_label' => 'غير نشط',
            ],
        ]);

        $totalDistributors = $distributorsCount ?? $distributorItems->count();
        $activeDistributors = $activeDistributorsCount ?? $distributorItems->filter(fn($item) => data_get($item, 'status_class') === 'tag-g')->count();
        $inactiveDistributors = $inactiveDistributorsCount ?? $distributorItems->filter(fn($item) => data_get($item, 'status_class') === 'tag-r')->count();
        $linkedShops = $linkedShopsCount ?? $distributorItems->filter(fn($item) => filled(data_get($item, 'shop_name')))->count();
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'الموزعون'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط تنبيهات الموزعين">
                    <div class="ticker">
                        <span>إدارة شبكة التوزيع من مكان واحد</span>
                        <span>متابعة المدن والمتاجر المرتبطة والحالة مباشرة</span>
                        <span>Ozman موزعون جاهزون لخدمة المتاجر</span>
                        <span>إدارة شبكة التوزيع من مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">لوحة التحكم</div>
                        <h1>الموزعون</h1>
                        <p>إدارة موزعي المتاجر وأرقام التواصل والربط مع الفروع.</p>
                    </div>
                    <a href="{{ route('distributors.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        موزع جديد
                    </a>
                </header>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <section class="stats-grid" aria-label="إحصائيات الموزعين">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي الموزعين</div>
                        <div class="stat-val">{{ $totalDistributors }}</div>
                        <i class="ti ti-truck-delivery stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--green)">
                        <div class="stat-label">نشط</div>
                        <div class="stat-val">{{ $activeDistributors }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--danger)">
                        <div class="stat-label">غير نشط</div>
                        <div class="stat-val">{{ $inactiveDistributors }}</div>
                        <i class="ti ti-circle-x stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">المتاجر المرتبطة</div>
                        <div class="stat-val">{{ $linkedShops }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            قائمة الموزعين
                        </h2>

                        <div class="input-wrap">
                            <i class="ti ti-search" aria-hidden="true"></i>
                            <input class="search-inp" id="distSearch" type="search" placeholder="بحث بالاسم، المدينة، المتجر...">
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="distTable">
                            <thead>
                                <tr>
                                    <th>الموزع</th>
                                    <th>الهاتف</th>
                                    <th>واتساب</th>
                                    <th>المدينة</th>
                                    <th>العنوان</th>
                                    <th>المتجر المرتبط</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($distributorItems as $distributor)
                                    <tr>
                                        <td>
                                            <div class="dist-name">
                                                <span class="dist-avatar">{{ mb_substr(data_get($distributor, 'name', '-'), 0, 1) }}</span>
                                                <span>{{ data_get($distributor, 'name', '-') }}</span>
                                            </div>
                                        </td>
                                        <td dir="ltr">{{ data_get($distributor, 'phone', '-') }}</td>
                                        <td dir="ltr">{{ data_get($distributor, 'whatsapp', data_get($distributor, 'phone', '-')) }}</td>
                                        <td>{{ data_get($distributor, 'shop.city', data_get($distributor, 'city', '-')) }}</td>
                                        <td>{{ data_get($distributor, 'address', '-') }}</td>
                                        <td>{{ data_get($distributor, 'shop.name', data_get($distributor, 'shop_name', '-')) }}</td>
                                        <td>
                                            <span class="tag {{ data_get($distributor, 'status_class', 'tag-g') }}">
                                                {{ data_get($distributor, 'status_label', '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                @if(data_get($distributor, 'id'))
                                                    <a href="{{ route('distributors.show', $distributor) }}" class="icon-btn" aria-label="عرض">
                                                        <i class="ti ti-eye" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="{{ route('distributors.edit', $distributor) }}" class="icon-btn" aria-label="تعديل">
                                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                                    </a>
                                                    @if(data_get($distributor, 'user_id'))
                                                        <a href="{{ route('distributors.permissions.edit', $distributor) }}" class="icon-btn" aria-label="صلاحيات">
                                                            <i class="ti ti-lock" aria-hidden="true"></i>
                                                        </a>
                                                    @endif
                                                    <form action="{{ route('distributors.destroy', $distributor) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا الموزع؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="icon-btn" aria-label="حذف">
                                                            <i class="ti ti-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="icon-btn" aria-label="تعديل">
                                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="icon-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="ti ti-truck-off" aria-hidden="true"></i>
                                                لا يوجد موزعون لعرضهم حاليا
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

    <script>
        const distSearch = document.getElementById('distSearch');
        const distRows = document.querySelectorAll('#distTable tbody tr');

        if (distSearch) {
            distSearch.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();

                distRows.forEach((row) => {
                    row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
                });
            });
        }
    </script>
</body>

</html>
