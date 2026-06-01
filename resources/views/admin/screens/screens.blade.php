<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الشاشات الرئيسية - Ozman</title>
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
            min-width: 154px;
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
            min-width: 930px;
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

        .screen-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 900;
            min-width: 230px;
        }

        .screen-thumb {
            width: 52px;
            height: 42px;
            border-radius: 14px;
            border: 1px solid rgba(0, 229, 255, .55);
            background: #000;
            display: grid;
            place-items: center;
            color: var(--primary);
            overflow: hidden;
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
            flex-shrink: 0;
        }

        .screen-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .screen-sub {
            color: var(--dim);
            font-size: 11px;
            margin-top: 3px;
            direction: ltr;
            text-align: right;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 72px;
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

        .tag-p {
            color: #b35cff;
            background: rgba(112, 0, 255, .12);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .actions form {
            display: inline-flex;
        }

        .actions>button {
            display: none;
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(37, 211, 102, .35);
            background: rgba(37, 211, 102, .09);
            color: #fff;
            border-radius: 18px;
            font-size: 13px;
            font-weight: 800;
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
            .filter-select {
                width: 100%;
            }

            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>
    @php
        $screenItems = collect(
            $screens ?? [
                [
                    'title' => 'الشاشة الرئيسية 1',
                    'type' => 'image',
                    'type_label' => 'صورة',
                    'media' => 'main_screen_1.jpg',
                    'duration' => 10,
                    'is_active' => true,
                ],
                [
                    'title' => 'فيديو ترحيبي',
                    'type' => 'video',
                    'type_label' => 'فيديو',
                    'media' => 'welcome.mp4',
                    'duration' => 45,
                    'is_active' => true,
                ],
                [
                    'title' => 'شاشة العروض',
                    'type' => 'image',
                    'type_label' => 'صورة',
                    'media' => 'offers.jpg',
                    'duration' => 15,
                    'is_active' => true,
                ],
                [
                    'title' => 'شاشة يوتيوب',
                    'type' => 'youtube',
                    'type_label' => 'يوتيوب',
                    'media' => 'youtube.com/watch?v=xxx',
                    'duration' => 30,
                    'is_active' => false,
                ],
            ],
        );

        $screensTotal = $screensCount ?? $screenItems->count();
        $activeScreens =
            $activeScreensCount ??
            $screenItems
                ->filter(
                    fn($screen) => (bool) data_get($screen, 'is_active', data_get($screen, 'status_class') === 'tag-g'),
                )
                ->count();
        $inactiveScreens = $inactiveScreensCount ?? max($screensTotal - $activeScreens, 0);
        $averageDuration =
            $averageScreenDuration ??
            round($screenItems->avg(fn($screen) => (float) data_get($screen, 'duration', 0)) ?: 0);
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'الشاشات الرئيسية'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط الشاشات">
                    <div class="ticker">
                        <span>تحكم بمحتوى واجهة العرض من مكان واحد</span>
                        <span>صور وفيديو ويوتيوب بترتيب واضح وحالة مباشرة</span>
                        <span>شاشات Ozman جاهزة للعرض داخل المتجر</span>
                        <span>تحكم بمحتوى واجهة العرض من مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">الإعلانات</div>
                        <h1>الشاشات الرئيسية</h1>
                        <p>إدارة محتوى شاشات العرض ونوع الوسائط ومدة الظهور.</p>
                    </div>
                    <a href="{{ route('screens.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        شاشة جديدة
                    </a>
                </header>

                @if (session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <section class="stats-grid" aria-label="إحصائيات الشاشات">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي الشاشات</div>
                        <div class="stat-val">{{ $screensTotal }}</div>
                        <i class="ti ti-device-tv stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--green)">
                        <div class="stat-label">نشطة</div>
                        <div class="stat-val">{{ $activeScreens }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--danger)">
                        <div class="stat-label">معطلة</div>
                        <div class="stat-val">{{ $inactiveScreens }}</div>
                        <i class="ti ti-circle-x stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">متوسط المدة</div>
                        <div class="stat-val">{{ $averageDuration }}ث</div>
                        <i class="ti ti-clock stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            قائمة الشاشات
                        </h2>

                        <div class="filter-row">
                            <select class="filter-select" id="typeFilter" aria-label="فلترة النوع">
                                <option value="">كل الأنواع</option>
                                <option value="image">صور</option>
                                <option value="video">فيديو</option>
                                <option value="youtube">يوتيوب</option>
                            </select>

                            <div class="input-wrap">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input class="search-inp" id="scrSearch" type="search"
                                    placeholder="بحث بالعنوان أو الملف...">
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="scrTable">
                            <thead>
                                <tr>
                                    <th>الشاشة</th>
                                    <th>النوع</th>
                                    <th>الملف / الرابط</th>
                                    <th>المدة</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($screenItems as $screen)
                                    @php
                                        $type = data_get($screen, 'type', 'image');
                                        $typeLabels = ['image' => 'صورة', 'video' => 'فيديو', 'youtube' => 'يوتيوب'];
                                        $typeIcons = [
                                            'image' => 'ti-photo',
                                            'video' => 'ti-video',
                                            'youtube' => 'ti-brand-youtube',
                                        ];
                                        $typeClasses = ['image' => 'tag-c', 'video' => 'tag-p', 'youtube' => 'tag-r'];
                                        $media = data_get($screen, 'media', data_get($screen, 'file', '-'));
                                        $isActive = (bool) data_get(
                                            $screen,
                                            'is_active',
                                            data_get($screen, 'status_class') === 'tag-g',
                                        );
                                    @endphp
                                    <tr data-type="{{ $type }}">
                                        <td>
                                            <div class="screen-cell">
                                                <span class="screen-thumb">
                                                    @if ($type === 'image' && $media && !str_contains($media, 'http') && str_contains($media, '.'))
                                                        <img src="{{ asset($media) }}" alt="">
                                                    @else
                                                        <i class="ti {{ $typeIcons[$type] ?? 'ti-device-tv' }}"
                                                            aria-hidden="true"></i>
                                                    @endif
                                                </span>
                                                <span>
                                                    {{ data_get($screen, 'title', '-') }}
                                                    <span class="screen-sub">{{ $media }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="tag {{ $typeClasses[$type] ?? 'tag-c' }}">
                                                {{ data_get($screen, 'type_label', $typeLabels[$type] ?? $type) }}
                                            </span>
                                        </td>
                                        <td dir="ltr">{{ $media }}</td>
                                        <td>{{ data_get($screen, 'duration', '-') }} ث</td>
                                        <td><span
                                                class="tag {{ $isActive ? 'tag-g' : 'tag-r' }}">{{ $isActive ? 'نشط' : 'معطل' }}</span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="{{ route('screens.show', $screen) }}" class="icon-btn"
                                                    aria-label="معاينة">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </a>
                                                <a href="{{ route('screens.edit', $screen) }}" class="icon-btn"
                                                    aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </a>
                                                <form action="{{ route('screens.destroy', $screen) }}" method="POST"
                                                    onsubmit="return confirm('هل تريد حذف هذه الشاشة؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="icon-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="icon-btn" aria-label="معاينة">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="icon-btn" aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="icon-btn" aria-label="حذف">
                                                    <i class="ti ti-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="ti ti-device-tv-off" aria-hidden="true"></i>
                                                لا توجد شاشات لعرضها حاليا
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
        const scrSearch = document.getElementById('scrSearch');
        const typeFilter = document.getElementById('typeFilter');
        const screenRows = document.querySelectorAll('#scrTable tbody tr');

        function filterScreens() {
            const query = (scrSearch?.value || '').trim().toLowerCase();
            const type = typeFilter?.value || '';

            screenRows.forEach((row) => {
                const matchesQuery = row.textContent.toLowerCase().includes(query);
                const matchesType = !type || row.dataset.type === type;
                row.style.display = matchesQuery && matchesType ? '' : 'none';
            });
        }

        scrSearch?.addEventListener('input', filterScreens);
        typeFilter?.addEventListener('change', filterScreens);
    </script>
</body>

</html>
