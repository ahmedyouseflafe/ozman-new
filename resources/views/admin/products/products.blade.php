<!DOCTYPE html>
<html lang="ar" dir="rtl">

@php
    $isRestaurantContext = ($currentShop ?? null)?->catalog_type === 'restaurant';
    $catalogTerms = ($currentShop ?? null)?->catalogTerms() ?? config('catalog_terminology.general');
    $itemLabel = $catalogTerms['item_singular'];
    $itemsLabel = $catalogTerms['item_plural'];
    $addItemLabel = $catalogTerms['add_item'];
    $placeLabel = $catalogTerms['place_singular'];
@endphp

<head>
    <title>إدارة {{ $itemsLabel }} - Ozman</title>
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

        .restaurant-stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
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

        .filter-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
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
            min-width: 174px;
            padding: 0 16px;
            cursor: pointer;
        }

        .search-inp:focus,
        .filter-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .search-inp::placeholder { color: var(--dim); }

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
            min-width: 1420px;
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

        tbody tr { transition: background .25s ease; }
        tbody tr:hover { background: rgba(0, 229, 255, .055); }
        tr:last-child td { border-bottom: 0; }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 900;
            min-width: 230px;
        }

        .product-thumb {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            border: 1px solid rgba(0, 229, 255, .55);
            background: #000;
            display: grid;
            place-items: center;
            color: var(--primary);
            overflow: hidden;
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
            flex-shrink: 0;
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-sub {
            color: var(--dim);
            font-size: 11px;
            margin-top: 3px;
        }

        .price {
            color: var(--primary);
            font-weight: 900;
            text-shadow: 0 0 10px rgba(0, 229, 255, .28);
        }

        .discount {
            color: var(--green);
            font-weight: 900;
        }

        .rating {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--yellow);
            font-weight: 900;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 66px;
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
        .tag-c { color: var(--primary); background: rgba(0, 229, 255, .09); }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .actions form {
            display: inline-flex;
        }

        .actions > button {
            display: none;
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

        .meal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 18px;
        }

        .meal-card {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: linear-gradient(160deg, rgba(17, 29, 38, .95), rgba(8, 10, 18, .98));
            box-shadow: 0 16px 34px rgba(0, 0, 0, .3);
            transition: transform .25s ease, border-color .25s ease, opacity .25s ease;
        }

        .meal-card:hover {
            transform: translateY(-4px);
            border-color: rgba(0, 229, 255, .5);
        }

        .meal-card.is-inactive {
            opacity: .62;
        }

        .meal-card-form {
            display: block;
        }

        .meal-media {
            position: relative;
            height: 184px;
            background: #05070b;
            overflow: hidden;
        }

        .meal-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .meal-media .meal-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: var(--primary);
            font-size: 54px;
            background: radial-gradient(circle, rgba(0, 229, 255, .15), transparent 65%);
        }

        .meal-power {
            position: absolute;
            top: 10px;
            right: 10px;
            min-height: 34px;
            padding: 6px 10px;
            border: 1px solid rgba(37, 211, 102, .65);
            border-radius: 999px;
            background: rgba(4, 23, 18, .9);
            color: #73f3ad;
            font: inherit;
            font-size: 11px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            backdrop-filter: blur(10px);
        }

        .meal-power:not(.is-active) {
            border-color: rgba(255, 59, 48, .65);
            background: rgba(39, 8, 12, .9);
            color: #ff9b95;
        }

        .meal-card-body {
            padding: 14px;
        }

        .meal-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 13px;
        }

        .meal-name {
            color: #fff;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.5;
        }

        .meal-category {
            display: block;
            color: var(--dim);
            font-size: 11px;
            font-weight: 700;
            margin-top: 3px;
        }

        .meal-price-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 42px;
            gap: 8px;
            align-items: center;
        }

        .meal-price-input {
            width: 100%;
            min-width: 0;
            height: 42px;
            padding: 0 12px;
            border: 1px solid rgba(0, 229, 255, .26);
            border-radius: 12px;
            outline: 0;
            background: rgba(0, 0, 0, .3);
            color: #fff;
            font: inherit;
            font-weight: 900;
            direction: ltr;
        }

        .meal-price-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 14px rgba(0, 229, 255, .18);
        }

        .meal-save {
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #39bdf5);
            color: #001014;
            font-size: 20px;
            cursor: pointer;
        }

        .meal-quick-feedback {
            display: block;
            min-height: 18px;
            margin-top: 8px;
            color: var(--dim);
            font-size: 10px;
            font-weight: 800;
        }

        .meal-quick-feedback.is-success { color: var(--green); }
        .meal-quick-feedback.is-error { color: #ff9b95; }

        .meal-detail-link {
            width: 34px;
            height: 34px;
            border-radius: 10px;
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
            .filter-row { width: 100%; }
            .stats-grid { grid-template-columns: 1fr; }
            .restaurant-stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 14px;
            }
            .restaurant-stats-grid .stat-card {
                min-height: 94px;
                padding: 11px 9px;
                border-radius: 17px;
            }
            .restaurant-stats-grid .stat-label {
                font-size: 10px;
                line-height: 1.35;
            }
            .restaurant-stats-grid .stat-val {
                margin-top: 10px;
                font-size: 26px;
            }
            .restaurant-stats-grid .stat-icon {
                left: 9px;
                bottom: 9px;
                font-size: 28px;
            }
            .search-inp,
            .filter-select { width: 100%; }
            h1 { font-size: 28px; }
            .meal-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .meal-media { height: 132px; }
            .meal-card-body { padding: 11px; }
            .meal-name { font-size: 13px; }
            .meal-power { top: 7px; right: 7px; padding: 5px 7px; font-size: 10px; }
            .meal-price-row { grid-template-columns: minmax(0, 1fr) 38px; gap: 6px; }
            .meal-price-input, .meal-save { height: 38px; }
            .meal-save { width: 38px; }
        }
    </style>
</head>

<body>
    @php
        $productItems = collect($products ?? [
            [
                'name' => 'Cola الأصلية',
                'sku' => 'COLA-001',
                'category_name' => 'مشروبات',
                'shop_name' => 'Ozman الرئيسي',
                'price' => 15,
                'discount_price' => 12,
                'quantity' => 240,
                'rating' => 4.8,
                'is_featured' => true,
                'is_active' => true,
                'icon' => 'ti-bottle',
            ],
            [
                'name' => 'كريم العناية بالوجه',
                'sku' => 'CARE-112',
                'category_name' => 'العناية بالبشرة',
                'shop_name' => 'متجر الجمال الفاخر',
                'price' => 45,
                'discount_price' => null,
                'quantity' => 80,
                'rating' => 4.5,
                'is_featured' => false,
                'is_active' => true,
                'icon' => 'ti-droplet',
            ],
            [
                'name' => 'شامبو Cliven',
                'sku' => 'CLV-204',
                'category_name' => 'العناية بالشعر',
                'shop_name' => 'Healthy Shop',
                'price' => 28,
                'discount_price' => 22,
                'quantity' => 0,
                'rating' => 4.2,
                'is_featured' => true,
                'is_active' => false,
                'icon' => 'ti-spray',
            ],
            [
                'name' => 'ماسكرا للرجال',
                'sku' => 'MEN-018',
                'category_name' => 'العناية للرجال',
                'shop_name' => 'Ozman الرئيسي',
                'price' => 38,
                'discount_price' => null,
                'quantity' => 55,
                'rating' => 4.0,
                'is_featured' => false,
                'is_active' => true,
                'icon' => 'ti-gender-male',
            ],
        ]);

        $productsTotal = $productsCount ?? $productItems->count();
        $featuredTotal = $featuredProductsCount ?? $productItems->filter(fn($product) => (bool) data_get($product, 'is_featured'))->count();
        $outOfStockTotal = $outOfStockProductsCount ?? $productItems->filter(fn($product) => (int) data_get($product, 'quantity', 0) <= 0)->count();
        $averagePrice = $averageProductPrice ?? round($productItems->avg(fn($product) => (float) data_get($product, 'price', 0)) ?: 0);
        $categoryOptions = collect($categories ?? $productItems->pluck('category_name')->filter()->unique()->values());
        $currentUser = auth()->user();
        $currentUserAgentIds = $currentUser?->isAgent()
            ? \App\Models\Agent::query()
                ->where(function ($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id);

                    if ($currentUser->email) {
                        $query->orWhere('email', $currentUser->email);
                    }
                })
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all()
            : [];
        $canCreateProducts = $currentUser?->canAccessRouteName('products.create');
        $canEditProducts = $currentUser?->canAccessRouteName('products.edit');
        $canDeleteProducts = $currentUser?->canAccessRouteName('products.destroy');
        $canManageProduct = fn($product) => ($canEditProducts || $canDeleteProducts)
            && (! $currentUser?->isAgent() || in_array((int) data_get($product, 'agent_id'), $currentUserAgentIds, true));
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'إدارة ' . $itemsLabel])

            <div class="content">
                <section class="hero-strip" aria-label="شريط {{ $itemsLabel }}">
                    <div class="ticker">
                        @if($isRestaurantContext)
                            <span>إدارة وجبات {{ $currentShop->name }} من مكان واحد</span>
                            <span>تحكم بالفئات والأسعار والصور بسهولة</span>
                            <span>الوجبات النشطة تظهر في منيو المطعم مباشرة</span>
                            <span>لوحة وجبات مطعم {{ $currentShop->name }}</span>
                        @else
                            <span>إدارة منتجات Ozman بشكل حديث وسريع</span>
                            <span>تحكم بالفئات والأسعار والمخزون من مكان واحد</span>
                            <span>منتجات مميزة تظهر بواجهة المتجر مباشرة</span>
                            <span>إدارة منتجات Ozman بشكل حديث وسريع</span>
                        @endif
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">{{ $currentShop ? ($catalogTerms['dashboard_label'].' · '.$currentShop->name) : $placeLabel }}</div>
                        <h1>إدارة {{ $itemsLabel }}</h1>
                        <p>{{ $productsTotal }} من {{ $itemsLabel }} مع متابعة الأسعار والمخزون وحالة الظهور.</p>
                    </div>
                    @if($canCreateProducts)
                        <a href="{{ route('products.create', $currentShop ? ['shop_id' => $currentShop->id] : []) }}" class="btn-primary">
                            <i class="ti ti-plus" aria-hidden="true"></i>
                            {{ $addItemLabel }}
                        </a>
                    @endif
                </header>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <section class="stats-grid {{ $isRestaurantContext ? 'restaurant-stats-grid' : '' }}" aria-label="إحصائيات {{ $itemsLabel }}">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي {{ $itemsLabel }}</div>
                        <div class="stat-val">{{ $productsTotal }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--yellow)">
                        <div class="stat-label">{{ $itemsLabel }} المميزة</div>
                        <div class="stat-val">{{ $featuredTotal }}</div>
                        <i class="ti ti-star stat-icon" aria-hidden="true"></i>
                    </article>

                    @unless($isRestaurantContext)
                        <article class="stat-card" style="--card-color: var(--danger)">
                            <div class="stat-label">نفد المخزون</div>
                            <div class="stat-val">{{ $outOfStockTotal }}</div>
                            <i class="ti ti-alert-triangle stat-icon" aria-hidden="true"></i>
                        </article>
                    @endunless

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">متوسط السعر</div>
                        <div class="stat-val">{{ $averagePrice }}₪</div>
                        <i class="ti ti-coin stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            قائمة {{ $itemsLabel }}
                        </h2>

                        <div class="filter-row">
                            <select class="filter-select" id="catFilter" aria-label="فلترة الفئة">
                                <option value="">كل الفئات</option>
                                @foreach($categoryOptions as $category)
                                    <option value="{{ data_get($category, 'name', $category) }}">{{ data_get($category, 'name', $category) }}</option>
                                @endforeach
                            </select>

                            <div class="input-wrap">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input class="search-inp" id="prodSearch" type="search" placeholder="بحث بالاسم، المتجر، الفئة...">
                            </div>
                        </div>
                    </div>

                    @if($isRestaurantContext)
                        <div class="meal-grid" id="restaurantMeals">
                            @forelse($productItems as $product)
                                @php
                                    $categoryName = data_get($product, 'category.name', data_get($product, 'category_name', '-'));
                                    $image = data_get($product, 'main_image');
                                    $isActive = (bool) data_get($product, 'is_active', true);
                                    $canQuickManageMeal = $canManageProduct($product) && $canEditProducts;
                                @endphp
                                <article class="meal-card {{ $isActive ? '' : 'is-inactive' }}" data-cat="{{ $categoryName }}">
                                    @if($canQuickManageMeal)
                                        <form class="meal-card-form js-meal-quick-form" action="{{ route('products.quick-settings', $product) }}" method="post">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $isActive ? 1 : 0 }}">
                                            <div class="meal-media">
                                                @if($image)
                                                    <img src="{{ asset($image) }}" alt="{{ data_get($product, 'name', '') }}">
                                                @else
                                                    <span class="meal-placeholder"><i class="ti ti-tools-kitchen-2"></i></span>
                                                @endif
                                                <button type="button" class="meal-power {{ $isActive ? 'is-active' : '' }}" data-meal-toggle aria-label="{{ $isActive ? 'إيقاف الوجبة' : 'تشغيل الوجبة' }}">
                                                    <i class="ti {{ $isActive ? 'ti-power' : 'ti-power' }}"></i>
                                                    <span data-meal-power-copy>{{ $isActive ? 'متاحة' : 'موقوفة' }}</span>
                                                </button>
                                            </div>
                                            <div class="meal-card-body">
                                                <div class="meal-card-head">
                                                    <div>
                                                        <div class="meal-name">{{ data_get($product, 'name', '-') }}</div>
                                                        <span class="meal-category">{{ $categoryName }}</span>
                                                    </div>
                                                    <a href="{{ route('products.edit', $product) }}" class="icon-btn meal-detail-link" aria-label="تعديل كامل">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                </div>
                                                <div class="meal-price-row">
                                                    <input class="meal-price-input" type="number" name="price" min="0" max="99999999.99" step="0.01" value="{{ data_get($product, 'price', 0) }}" aria-label="سعر الوجبة">
                                                    <button class="meal-save" type="submit" aria-label="حفظ السعر"><i class="ti ti-check"></i></button>
                                                </div>
                                                <span class="meal-quick-feedback" aria-live="polite"></span>
                                            </div>
                                        </form>
                                    @else
                                        <div class="meal-media">
                                            @if($image)
                                                <img src="{{ asset($image) }}" alt="{{ data_get($product, 'name', '') }}">
                                            @else
                                                <span class="meal-placeholder"><i class="ti ti-tools-kitchen-2"></i></span>
                                            @endif
                                            <span class="meal-power {{ $isActive ? 'is-active' : '' }}"><i class="ti ti-power"></i>{{ $isActive ? 'متاحة' : 'موقوفة' }}</span>
                                        </div>
                                        <div class="meal-card-body">
                                            <div class="meal-name">{{ data_get($product, 'name', '-') }}</div>
                                            <span class="meal-category">{{ $categoryName }}</span>
                                            <div class="price" style="margin-top:12px">{{ data_get($product, 'price', 0) }}₪</div>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="empty-state">
                                    <i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>
                                    لا توجد وجبات لعرضها حاليًا
                                </div>
                            @endforelse
                        </div>
                    @else
                    <div class="table-wrap">
                        <table id="prodTable">
                            <thead>
                                <tr>
                                    <th>{{ $itemLabel }}</th>
                                    <th>الفئة</th>
                                    <th>المتجر</th>
                                    <th>السعر</th>
                                    <th>سعر الخصم</th>
                                    <th>سعر التاجر</th>
                                    <th>سعر العبوة</th>
                                    <th>سعر المشطاح</th>
                                    <th>سعر الكرتونة</th>
                                    @unless($isRestaurantContext)<th>الكمية</th>@endunless
                                    <th>التقييم</th>
                                    <th>مميز</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productItems as $product)
                                    @php
                                        $categoryName = data_get($product, 'category.name', data_get($product, 'category_name', '-'));
                                        $shopName = data_get($product, 'shop.name', data_get($product, 'shop_name', '-'));
                                        $image = data_get($product, 'main_image');
                                        $isActive = (bool) data_get($product, 'is_active', true);
                                        $isFeatured = (bool) data_get($product, 'is_featured', false);
                                        $quantity = (int) data_get($product, 'quantity', 0);
                                    @endphp
                                    <tr data-cat="{{ $categoryName }}">
                                        <td>
                                            <div class="product-cell">
                                                <span class="product-thumb">
                                                    @if($image)
                                                        <img src="{{ asset($image) }}" alt="">
                                                    @else
                                                        <i class="ti {{ data_get($product, 'icon', 'ti-package') }}" aria-hidden="true"></i>
                                                    @endif
                                                </span>
                                                <span>
                                                    {{ data_get($product, 'name', '-') }}
                                                    <span class="product-sub">{{ data_get($product, 'sku', data_get($product, 'slug', '-')) }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $categoryName }}</td>
                                        <td>{{ $shopName }}</td>
                                        <td class="price">{{ data_get($product, 'price', 0) }}₪</td>
                                        <td>
                                            @if(data_get($product, 'discount_price'))
                                                <span class="discount">{{ data_get($product, 'discount_price') }}₪</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(data_get($product, 'merchant_price'))
                                                <span class="discount">{{ data_get($product, 'merchant_price') }}₪</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(data_get($product, 'package_price'))
                                                <span class="discount">{{ data_get($product, 'package_price') }}₪</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(data_get($product, 'pallet_price'))
                                                <span class="discount">{{ data_get($product, 'pallet_price') }}₪</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if(data_get($product, 'carton_price'))
                                                <span class="discount">{{ data_get($product, 'carton_price') }}₪</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @unless($isRestaurantContext)
                                            <td><span class="tag {{ $quantity > 0 ? 'tag-c' : 'tag-r' }}">{{ $quantity }}</span></td>
                                        @endunless
                                        <td>
                                            <span class="rating">
                                                <i class="ti ti-star-filled" aria-hidden="true"></i>
                                                {{ data_get($product, 'rating', '-') }}
                                            </span>
                                        </td>
                                        <td><span class="tag {{ $isFeatured ? 'tag-y' : 'tag-r' }}">{{ $isFeatured ? 'نعم' : 'لا' }}</span></td>
                                        <td><span class="tag {{ $isActive && ($isRestaurantContext || $quantity > 0) ? 'tag-g' : 'tag-r' }}">{{ $isActive && ($isRestaurantContext || $quantity > 0) ? 'نشط' : 'متوقف' }}</span></td>
                                        <td>
                                            <div class="actions">
                                                <a href="{{ route('products.show', $product) }}" class="icon-btn" aria-label="عرض">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </a>
                                                @if($canManageProduct($product) && $canEditProducts)
                                                    <a href="{{ route('products.edit', $product) }}" class="icon-btn" aria-label="تعديل">
                                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                @if($canManageProduct($product) && $canDeleteProducts)
                                                    <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('هل تريد حذف: {{ $product->name }}؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="icon-btn" aria-label="حذف">
                                                            <i class="ti ti-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14">
                                            <div class="empty-state">
                                                <i class="ti ti-package-off" aria-hidden="true"></i>
                                                لا توجد {{ $itemsLabel }} لعرضها حاليًا
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif
                </section>
            </div>
        </main>
    </div>

    <script>
        const prodSearch = document.getElementById('prodSearch');
        const catFilter = document.getElementById('catFilter');
        const prodRows = document.querySelectorAll('#prodTable tbody tr, #restaurantMeals .meal-card');

        function filterProducts() {
            const query = (prodSearch?.value || '').trim().toLowerCase();
            const category = catFilter?.value || '';

            prodRows.forEach((row) => {
                const matchesQuery = row.textContent.toLowerCase().includes(query);
                const matchesCategory = !category || row.dataset.cat === category;
                row.style.display = matchesQuery && matchesCategory ? '' : 'none';
            });
        }

        prodSearch?.addEventListener('input', filterProducts);
        catFilter?.addEventListener('change', filterProducts);

        async function saveQuickMeal(form) {
            if (form.dataset.saving === 'true') return;

            const card = form.closest('.meal-card');
            const feedback = form.querySelector('.meal-quick-feedback');
            const activeInput = form.querySelector('input[name="is_active"]');
            const priceInput = form.querySelector('input[name="price"]');
            const powerButton = form.querySelector('[data-meal-toggle]');
            const saveButton = form.querySelector('.meal-save');
            const previousActive = activeInput?.value;
            const controls = [powerButton, saveButton].filter(Boolean);

            form.dataset.saving = 'true';
            controls.forEach(control => control.disabled = true);
            if (feedback) {
                feedback.textContent = 'جارٍ الحفظ...';
                feedback.className = 'meal-quick-feedback';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const validationError = Object.values(data.errors || {}).flat()[0];
                    throw new Error(validationError || data.message || 'تعذر حفظ الوجبة.');
                }

                const isActive = Boolean(data.is_active);
                activeInput.value = isActive ? '1' : '0';
                if (priceInput && data.price !== undefined) priceInput.value = data.price;
                card?.classList.toggle('is-inactive', !isActive);
                powerButton?.classList.toggle('is-active', isActive);
                powerButton?.setAttribute('aria-label', isActive ? 'إيقاف الوجبة' : 'تشغيل الوجبة');
                const powerCopy = powerButton?.querySelector('[data-meal-power-copy]');
                if (powerCopy) powerCopy.textContent = isActive ? 'متاحة' : 'موقوفة';
                if (feedback) {
                    feedback.textContent = 'تم الحفظ ✓';
                    feedback.classList.add('is-success');
                }
            } catch (error) {
                if (activeInput && previousActive !== undefined) activeInput.value = previousActive;
                if (feedback) {
                    feedback.textContent = error.message || 'تعذر حفظ الوجبة.';
                    feedback.classList.add('is-error');
                }
            } finally {
                delete form.dataset.saving;
                controls.forEach(control => control.disabled = false);
            }
        }

        document.querySelectorAll('.js-meal-quick-form').forEach(form => {
            form.addEventListener('submit', event => {
                event.preventDefault();
                saveQuickMeal(form);
            });

            form.querySelector('[data-meal-toggle]')?.addEventListener('click', () => {
                const activeInput = form.querySelector('input[name="is_active"]');
                if (!activeInput) return;
                activeInput.value = activeInput.value === '1' ? '0' : '1';
                saveQuickMeal(form);
            });
        });
    </script>
</body>

</html>
