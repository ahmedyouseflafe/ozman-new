@php
    $categoriesList = collect($categories ?? []);
    $categoriesCount = $categoriesCount ?? $categoriesList->count();
    $productsInCategoriesCount = $productsInCategoriesCount ?? $categoriesList->sum(fn($category) => (int) data_get($category, 'products_count', 0));
    $activeCategoriesCount = $activeCategoriesCount ?? $categoriesList->filter(fn($category) => data_get($category, 'status_class') === 'tag-g' || data_get($category, 'status_label') === 'نشط' || data_get($category, 'is_active') === true)->count();
    $emptyCategoriesCount = $emptyCategoriesCount ?? $categoriesList->filter(fn($category) => (int) data_get($category, 'products_count', 0) === 0)->count();
    $currentShop = $currentShop ?? null;
    $isRestaurantCategories = $currentShop?->catalog_type === 'restaurant';
    $categoriesPageTitle = $isRestaurantCategories ? 'أقسام المنيو' : 'الفئات';
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $categoriesPageTitle }} — Ozman</title>
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

        .stat-card:hover {
            transform: translateY(-8px) scale(1.015);
            border-color: var(--accent, var(--primary-color));
            box-shadow: 0 16px 36px rgba(0, 0, 0, .36), 0 0 26px rgba(0, 229, 255, .2)
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: auto 18px 0 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent, var(--primary-color)), transparent)
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

        .toolbar-card {
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 18px;
            margin-bottom: 18px;
            backdrop-filter: blur(14px);
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            animation: slideUp .85s ease both
        }

        .toolbar-card h3 {
            font-size: 16px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 8px
        }

        .toolbar-card h3 i {
            color: var(--primary-color);
            filter: drop-shadow(0 0 8px rgba(0, 229, 255, .65))
        }

        .input-wrap {
            position: relative
        }

        .search-inp {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            padding: 9px 12px 9px 36px;
            border-radius: 999px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            width: 260px;
            min-height: 38px;
            transition: all .3s ease
        }

        .search-inp:focus {
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

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px
        }

        .category-card {
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 24px;
            text-align: center;
            backdrop-filter: blur(14px);
            position: relative;
            overflow: hidden;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: slideUp .9s ease both;
            transition: all .38s cubic-bezier(.175, .885, .32, 1.275)
        }

        .category-card::before {
            content: '';
            position: absolute;
            inset: auto 24px 0 24px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--card-accent, var(--primary-color)), transparent)
        }

        .category-card:hover {
            transform: translateY(-9px) scale(1.012);
            border-color: var(--card-accent, var(--primary-color));
            box-shadow: 0 18px 42px rgba(0, 0, 0, .32), 0 0 26px rgba(0, 229, 255, .18)
        }

        .category-icon-wrap {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            border: 1px solid var(--card-accent, var(--primary-color));
            background: #000;
            color: var(--card-accent, var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 0 22px color-mix(in srgb, var(--card-accent, var(--primary-color)) 40%, transparent)
        }

        .category-icon-wrap i {
            font-size: 31px;
            filter: drop-shadow(0 0 9px currentColor)
        }

        .category-name {
            font-size: 16px;
            font-weight: 900;
            color: var(--text-main)
        }

        .category-count {
            font-size: 12px;
            color: var(--text-soft);
            margin: 6px 0 16px
        }

        .category-actions {
            display: flex;
            gap: 8px;
            justify-content: center
        }

        .category-actions form {
            display: inline-flex
        }

        .category-actions > button {
            display: none
        }

        .action-btn {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .72);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all .3s ease
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px) scale(1.08);
            box-shadow: 0 0 16px rgba(0, 229, 255, .35)
        }

        .empty-state {
            grid-column: 1 / -1;
            background: rgba(255, 255, 255, .045);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 54px 16px;
            text-align: center;
            color: var(--text-muted)
        }

        .empty-state i {
            display: block;
            color: var(--primary-color);
            font-size: 42px;
            margin-bottom: 10px;
            filter: drop-shadow(0 0 14px rgba(0, 229, 255, .6))
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
            .categories-grid {
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
            .toolbar-card {
                flex-direction: column;
                align-items: stretch
            }

            .stats-grid,
            .categories-grid {
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
            .search-inp {
                width: 100%
            }
        }
    </style>
</head>

<body>
    @php($canCreateCategories = auth()->user()?->canAccessRouteName('categories.create'))
    <div class="shell">
        @include('admin.includes.sidebar')

        <div class="main">
            @include('admin.includes.header', ['title' => $categoriesPageTitle])

            <div class="content">
                <div class="hero-panel">
                    <div class="display-screen">
                        <div class="story-slider">
                            <span class="welcome-msg">{{ $isRestaurantCategories ? 'نظّم منيو مطعم '.$currentShop->name.' بشكل واضح وسريع' : 'تصنيف منتجات Ozman بشكل واضح وسريع' }}</span>
                            <span class="welcome-msg">{{ $categoriesCount }} {{ $isRestaurantCategories ? 'قسم يحتوي على' : 'فئة تحتوي على' }} {{ $productsInCategoriesCount }} {{ $isRestaurantCategories ? 'وجبة' : 'منتج' }}</span>
                            <span class="welcome-msg">{{ $isRestaurantCategories ? 'قسّم الوجبات ليسهل على الزبون تصفّح المنيو' : 'نظّم واجهة المتجر حسب الفئات الأكثر أهمية' }}</span>
                        </div>
                    </div>
                    <div class="hero-orb">
                        <i class="ti ti-category" aria-hidden="true"></i>
                        <span>{{ $categoriesPageTitle }}</span>
                    </div>
                </div>

                <div class="page-header-row">
                    <div>
                        <h1>{{ $categoriesPageTitle }}</h1>
                        <p>{{ $isRestaurantCategories ? 'أنشئ أقسام المنيو ورتّب وجبات المطعم بطريقة واضحة للزبائن.' : 'تصنيف منتجات المتاجر وترتيب ظهورها داخل تجربة العرض.' }}</p>
                    </div>
                    @if($canCreateCategories)
                    <a href="{{ route('categories.create', $currentShop ? ['shop_id' => $currentShop->id] : []) }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        {{ $isRestaurantCategories ? 'قسم منيو جديد' : 'فئة جديدة' }}
                    </a>
                    @endif
                </div>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <div class="stats-grid">
                    <div class="stat-card" style="--accent: var(--primary-color)">
                        <div class="stat-label">{{ $isRestaurantCategories ? 'إجمالي الأقسام' : 'إجمالي الفئات' }}</div>
                        <div class="stat-val">{{ $categoriesCount }}</div>
                        <i class="ti ti-category stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--green)">
                        <div class="stat-label">{{ $isRestaurantCategories ? 'أقسام نشطة' : 'فئات نشطة' }}</div>
                        <div class="stat-val">{{ $activeCategoriesCount }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--accent-color)">
                        <div class="stat-label">{{ $isRestaurantCategories ? 'الوجبات داخل الأقسام' : 'المنتجات داخل الفئات' }}</div>
                        <div class="stat-val">{{ $productsInCategoriesCount }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--yellow)">
                        <div class="stat-label">{{ $isRestaurantCategories ? 'أقسام فارغة' : 'فئات فارغة' }}</div>
                        <div class="stat-val">{{ $emptyCategoriesCount }}</div>
                        <i class="ti ti-folder-off stat-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="toolbar-card">
                    <h3><i class="ti ti-layout-grid" aria-hidden="true"></i> قائمة {{ $categoriesPageTitle }}</h3>
                    <div class="input-wrap">
                        <i class="ti ti-search search-icon" aria-hidden="true"></i>
                        <input class="search-inp" id="categorySearch" placeholder="{{ $isRestaurantCategories ? 'بحث بالقسم...' : 'بحث بالفئة...' }}">
                    </div>
                </div>

                <div class="categories-grid" id="categoriesGrid">
                    <?php if ($categoriesList->isNotEmpty()): ?>
                    <?php foreach ($categoriesList as $index => $category): ?>
                        <?php
                            $accents = ['#00e5ff', '#00ffd5', '#7000ff', '#25d366', '#f1c40f', '#ff3b30'];
                            $icons = ['ti-category', 'ti-bottle', 'ti-droplet', 'ti-spray', 'ti-heart', 'ti-tag'];
                            $accent = $accents[$index % count($accents)];
                            $icon = data_get($category, 'icon', $icons[$index % count($icons)]);
                        ?>
                        <div class="category-card" style="--card-accent: {{ $accent }}">
                            <div class="category-icon-wrap">
                                <i class="ti {{ $icon }}" aria-hidden="true"></i>
                            </div>
                            <div class="category-name">{{ data_get($category, 'name', '-') }}</div>
                            <div class="category-count">{{ data_get($category, 'shop.name', '-') }}</div>
                            <div class="category-count">{{ data_get($category, 'products_count', 0) }} {{ $isRestaurantCategories ? 'وجبة' : 'منتج' }}</div>
                            <div class="category-actions">
                                <a href="{{ route('categories.show', $category) }}" class="action-btn" aria-label="عرض">
                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('categories.edit', $category) }}" class="action-btn" aria-label="تعديل">
                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                </a>
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('{{ $isRestaurantCategories ? 'هل تريد حذف هذا القسم؟' : 'هل تريد حذف هذه الفئة؟' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn" aria-label="حذف">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ti ti-category-off" aria-hidden="true"></i>
                            {{ $isRestaurantCategories ? 'لا توجد أقسام منيو لعرضها' : 'لا توجد فئات لعرضها' }}
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('categorySearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#categoriesGrid .category-card').forEach(card => {
                card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
