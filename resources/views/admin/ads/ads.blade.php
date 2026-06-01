@php
    $adsList = collect($ads ?? []);
    $adsCount = $adsCount ?? $adsList->count();
    $imageAdsCount = $imageAdsCount ?? $adsList->filter(fn($ad) => in_array(data_get($ad, 'type_label', data_get($ad, 'type')), ['صورة', 'image']))->count();
    $videoAdsCount = $videoAdsCount ?? $adsList->filter(fn($ad) => in_array(data_get($ad, 'type_label', data_get($ad, 'type')), ['فيديو', 'video']))->count();
    $youtubeAdsCount = $youtubeAdsCount ?? $adsList->filter(fn($ad) => in_array(data_get($ad, 'type_label', data_get($ad, 'type')), ['يوتيوب', 'youtube']))->count();
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الإعلانات — Ozman</title>
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

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 22px;
            animation: slideUp .7s ease both
        }

        .page-header-row h1 {
            font-size: 28px;
            font-weight: 900;
            color: var(--primary-color);
            text-shadow: 0 0 16px rgba(0, 229, 255, .45)
        }

        .page-header-row p {
            font-size: 13px;
            color: var(--text-soft);
            margin-top: 4px
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: #fff;
            border: none;
            padding: 11px 22px;
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

        .stat-card:nth-child(2) {
            animation-delay: .07s
        }

        .stat-card:nth-child(3) {
            animation-delay: .14s
        }

        .stat-card:nth-child(4) {
            animation-delay: .21s
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: auto 18px 0 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent, var(--primary-color)), transparent)
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.015);
            border-color: var(--accent, var(--primary-color));
            box-shadow: 0 16px 36px rgba(0, 0, 0, .36), 0 0 26px rgba(0, 229, 255, .2)
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

        .card {
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 18px;
            margin-bottom: 16px;
            backdrop-filter: blur(14px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .24);
            animation: slideUp .9s ease both
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
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px
        }

        .card-hd h3::before {
            content: '\ec48';
            font-family: tabler-icons;
            color: var(--primary-color);
            font-size: 20px;
            filter: drop-shadow(0 0 8px rgba(0, 229, 255, .65))
        }

        .filter-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap
        }

        .input-wrap {
            position: relative
        }

        .search-inp,
        .type-filter-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            padding: 9px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            min-height: 38px;
            transition: all .3s ease
        }

        .search-inp {
            width: 220px;
            padding-left: 36px
        }

        .type-filter {
            position: relative;
            min-width: 190px
        }

        .type-filter-btn {
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px
        }

        .type-filter-btn i {
            color: var(--primary-color);
            transition: transform .25s ease
        }

        .type-filter.open .type-filter-btn {
            border-color: var(--primary-color);
            box-shadow: 0 0 18px rgba(0, 229, 255, .24)
        }

        .type-filter.open .type-filter-btn i {
            transform: rotate(180deg)
        }

        .type-filter-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 100%;
            background: rgba(8, 8, 12, .96);
            border: 1px solid rgba(0, 229, 255, .28);
            border-radius: 18px;
            padding: 8px;
            display: none;
            z-index: 20;
            box-shadow: 0 18px 35px rgba(0, 0, 0, .35), 0 0 22px rgba(0, 229, 255, .16);
            backdrop-filter: blur(14px)
        }

        .type-filter.open .type-filter-menu {
            display: block;
            animation: menuIn .18s ease both
        }

        .type-filter-option {
            width: 100%;
            background: transparent;
            border: 0;
            color: var(--text-soft);
            padding: 10px 12px;
            border-radius: 12px;
            text-align: right;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all .2s ease
        }

        .type-filter-option:hover,
        .type-filter-option.active {
            background: rgba(0, 229, 255, .12);
            color: var(--primary-color);
            box-shadow: inset 0 0 0 1px rgba(0, 229, 255, .18)
        }

        .type-filter-option.active::after {
            content: '\ea5e';
            font-family: tabler-icons;
            font-size: 16px
        }

        .search-inp:focus,
        .type-filter-btn:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 18px rgba(0, 229, 255, .24)
        }

        .search-inp::placeholder {
            color: var(--text-muted)
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 15px;
            pointer-events: none
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
            min-width: 880px
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
            vertical-align: middle;
            transition: all .3s ease
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
            color: #fff;
            box-shadow: 0 0 18px rgba(0, 229, 255, .08)
        }

        .ad-title {
            color: var(--text-main);
            font-weight: 900
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
            color: var(--green);
            box-shadow: 0 0 12px rgba(37, 211, 102, .18)
        }

        .tag-r {
            color: var(--danger);
            box-shadow: 0 0 12px rgba(255, 59, 48, .18)
        }

        .tag-y {
            color: var(--yellow);
            box-shadow: 0 0 12px rgba(241, 196, 15, .16)
        }

        .tag-b,
        .tag-blue {
            color: var(--primary-color);
            box-shadow: 0 0 12px rgba(0, 229, 255, .18)
        }

        .action-btn {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .72);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all .3s ease
        }

        .ad-actions {
            display: flex;
            gap: 6px;
            align-items: center
        }

        .ad-actions form {
            display: inline-flex
        }

        .ad-actions > button {
            display: none
        }

        td > button.action-btn {
            display: none
        }

        .status-alert {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(37, 211, 102, .35);
            background: rgba(37, 211, 102, .09);
            color: #fff;
            border-radius: 18px;
            font-size: 13px;
            font-weight: 800
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px) scale(1.08);
            box-shadow: 0 0 16px rgba(0, 229, 255, .35)
        }

        .empty-state {
            text-align: center;
            padding: 54px 16px !important;
            color: var(--text-muted)
        }

        .empty-state i {
            display: block;
            color: var(--primary-color);
            font-size: 42px;
            margin-bottom: 10px;
            filter: drop-shadow(0 0 14px rgba(0, 229, 255, .6))
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

        @keyframes menuIn {
            from {
                opacity: 0;
                transform: translateY(-6px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        @media(max-width:1100px) {
            .stats-grid {
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

            .page-header-row,
            .card-hd {
                flex-direction: column;
                align-items: stretch
            }

            .stats-grid {
                grid-template-columns: 1fr
            }

            .display-screen {
                min-height: 118px
            }

            .welcome-msg {
                font-size: .92rem;
                padding: 0 30px
            }

            .btn-primary,
            .search-inp,
            .type-filter {
                width: 100%
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <div class="main">
            @include('admin.includes.header', ['title' => 'الإعلانات'])

            <div class="content">
                <div class="hero-panel">
                    <div class="display-screen">
                        <div class="story-slider">
                            <span class="welcome-msg">✨ إدارة إعلانات Ozman ✨ صور وفيديوهات ويوتيوب بتجربة عرض فاخرة ✨</span>
                            <span class="welcome-msg">✨ {{ $adsCount }} إعلان داخل النظام ✨ تحكم بالترتيب والحالة والمتجر من مكان واحد ✨</span>
                            <span class="welcome-msg">✨ إدارة إعلانات Ozman ✨ صور وفيديوهات ويوتيوب بتجربة عرض فاخرة ✨</span>
                        </div>
                    </div>
                    <div class="hero-orb">
                        <i class="ti ti-speakerphone" aria-hidden="true"></i>
                        <span>الإعلانات</span>
                    </div>
                </div>

                <div class="page-header-row">
                    <div>
                        <h1>الإعلانات</h1>
                        <p>إدارة إعلانات المتاجر وطريقة ظهورها داخل واجهة العرض.</p>
                    </div>
                    <a href="{{ route('ads.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        إعلان جديد
                    </a>
                </div>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <div class="stats-grid">
                    <div class="stat-card" style="--accent: var(--primary-color)">
                        <div class="stat-label">إجمالي الإعلانات</div>
                        <div class="stat-val">{{ $adsCount }}</div>
                        <i class="ti ti-speakerphone stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: #00ffd5">
                        <div class="stat-label">صور</div>
                        <div class="stat-val">{{ $imageAdsCount }}</div>
                        <i class="ti ti-photo stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--accent-color)">
                        <div class="stat-label">فيديوهات</div>
                        <div class="stat-val">{{ $videoAdsCount }}</div>
                        <i class="ti ti-video stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--yellow)">
                        <div class="stat-label">يوتيوب</div>
                        <div class="stat-val">{{ $youtubeAdsCount }}</div>
                        <i class="ti ti-brand-youtube stat-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="card">
                    <div class="card-hd">
                        <h3>قائمة الإعلانات</h3>
                        <div class="filter-row">
                            <div class="type-filter" id="adTypeFilter">
                                <input type="hidden" id="adTypeValue" value="">
                                <button type="button" class="type-filter-btn" id="adTypeButton" aria-expanded="false">
                                    <span id="adTypeLabel">كل الأنواع</span>
                                    <i class="ti ti-chevron-down" aria-hidden="true"></i>
                                </button>
                                <div class="type-filter-menu" id="adTypeMenu">
                                    <button type="button" class="type-filter-option active" data-value="">كل الأنواع</button>
                                    <button type="button" class="type-filter-option" data-value="صورة">صورة</button>
                                    <button type="button" class="type-filter-option" data-value="فيديو">فيديو</button>
                                    <button type="button" class="type-filter-option" data-value="يوتيوب">يوتيوب</button>
                                </div>
                            </div>
                            <div class="input-wrap">
                                <i class="ti ti-search search-icon" aria-hidden="true"></i>
                                <input class="search-inp" id="adSearch" placeholder="بحث...">
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="adsTable">
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>الوصف</th>
                                    <th>النوع</th>
                                    <th>المتجر</th>
                                    <th>المدة (ث)</th>
                                    <th>الترتيب</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adsList as $ad)
                                    @php
                                        $typeLabel = data_get($ad, 'type_label', data_get($ad, 'type', '-'));
                                    @endphp
                                    <tr data-type="{{ $typeLabel }}">
                                        <td class="ad-title">{{ data_get($ad, 'title', '-') }}</td>
                                        <td>{{ data_get($ad, 'description', '-') }}</td>
                                        <td>
                                            <span class="tag {{ data_get($ad, 'type_class', 'tag-blue') }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td>{{ data_get($ad, 'shop_name', '-') }}</td>
                                        <td>{{ data_get($ad, 'duration', '-') }}</td>
                                        <td>{{ data_get($ad, 'order', '-') }}</td>
                                        <td>
                                            <span class="tag {{ data_get($ad, 'status_class', 'tag-g') }}">
                                                {{ data_get($ad, 'status_label', '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="ad-actions">
                                                <a href="{{ route('ads.show', $ad) }}" class="action-btn" aria-label="عرض">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </a>
                                                <a href="{{ route('ads.edit', $ad) }}" class="action-btn" aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </a>
                                                <form action="{{ route('ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا الإعلان؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <button class="action-btn" style="margin-left:6px" aria-label="تعديل">
                                                <i class="ti ti-edit" aria-hidden="true"></i>
                                            </button>
                                            <button class="action-btn" aria-label="حذف">
                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <i class="ti ti-speakerphone-off" aria-hidden="true"></i>
                                            لا توجد إعلانات حالياً.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const adTypeFilter = document.getElementById('adTypeFilter');
        const adTypeButton = document.getElementById('adTypeButton');
        const adTypeLabel = document.getElementById('adTypeLabel');
        const adTypeValue = document.getElementById('adTypeValue');
        const adTypeOptions = document.querySelectorAll('.type-filter-option');

        function filterAds() {
            const searchInput = document.getElementById('adSearch');
            const q = searchInput.value.toLowerCase();
            const type = adTypeValue.value;

            document.querySelectorAll('#adsTable tbody tr').forEach(row => {
                if (!row.dataset.type) {
                    row.style.display = q || type ? 'none' : '';
                    return;
                }

                const matchesSearch = row.textContent.toLowerCase().includes(q);
                const matchesType = !type || row.dataset.type === type;
                row.style.display = matchesSearch && matchesType ? '' : 'none';
            });
        }

        adTypeButton.addEventListener('click', function() {
            const isOpen = adTypeFilter.classList.toggle('open');
            adTypeButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        adTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                adTypeOptions.forEach(item => item.classList.remove('active'));
                option.classList.add('active');
                adTypeValue.value = option.dataset.value;
                adTypeLabel.textContent = option.textContent;
                adTypeFilter.classList.remove('open');
                adTypeButton.setAttribute('aria-expanded', 'false');
                filterAds();
            });
        });

        document.addEventListener('click', function(event) {
            if (!adTypeFilter.contains(event.target)) {
                adTypeFilter.classList.remove('open');
                adTypeButton.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                adTypeFilter.classList.remove('open');
                adTypeButton.setAttribute('aria-expanded', 'false');
            }
        });

        document.getElementById('adSearch').addEventListener('input', filterAds);
    </script>
</body>

</html>
