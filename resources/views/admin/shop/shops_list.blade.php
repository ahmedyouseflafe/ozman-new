<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shop->name }} - Ozman</title>
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
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .66);
            --dim: rgba(255, 255, 255, .42);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 16% 12%, rgba(112, 0, 255, .14), transparent 30%),
                radial-gradient(circle at 84% 6%, rgba(0, 229, 255, .14), transparent 32%),
                linear-gradient(180deg, #030303 0%, #050505 56%, #08020f 100%);
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
                linear-gradient(rgba(255, 255, 255, .024) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .024) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 82%);
        }

        .main {
            min-height: 100vh;
            margin-right: 245px;
            position: relative;
            z-index: 1;
        }

        .content {
            width: min(1280px, 100%);
            margin: 0 auto;
            padding: 28px 34px 46px;
        }

        .hero {
            min-height: 330px;
            border: 1px solid var(--border);
            border-radius: 30px;
            overflow: hidden;
            position: relative;
            background: #080808;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .42);
            margin-bottom: 22px;
        }

        .hero-media,
        .hero-fallback {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-fallback {
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at 30% 20%, rgba(0, 229, 255, .16), transparent 34%),
                radial-gradient(circle at 80% 16%, rgba(112, 0, 255, .18), transparent 30%),
                #080808;
            color: var(--primary);
            font-size: 64px;
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(0, 0, 0, .86), rgba(0, 0, 0, .42), rgba(0, 0, 0, .18));
        }

        .hero-content {
            position: relative;
            z-index: 1;
            min-height: 330px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding: 28px;
        }

        .identity {
            display: flex;
            align-items: flex-end;
            gap: 18px;
            min-width: 0;
        }

        .logo {
            width: 132px;
            height: 132px;
            border-radius: 28px;
            border: 3px solid var(--primary);
            background: #000;
            object-fit: cover;
            display: grid;
            place-items: center;
            color: var(--primary);
            font-size: 42px;
            box-shadow: 0 0 28px rgba(0, 229, 255, .32);
            flex-shrink: 0;
        }

        .kicker {
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 6px;
            text-shadow: 0 0 12px rgba(0, 229, 255, .45);
        }

        h1 {
            color: var(--primary);
            font-size: 38px;
            line-height: 1.12;
            font-weight: 900;
            text-shadow: 0 0 20px rgba(0, 229, 255, .44);
            overflow-wrap: anywhere;
        }

        .slug {
            color: var(--muted);
            margin-top: 8px;
            font-size: 13px;
            font-weight: 800;
        }

        .public-link-box {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            max-width: min(560px, 100%);
            padding: 8px;
            border: 1px solid rgba(0, 229, 255, .22);
            border-radius: 16px;
            background: rgba(0, 0, 0, .38);
        }

        .public-link-box input {
            flex: 1;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: rgba(255, 255, 255, .86);
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            direction: ltr;
            text-align: left;
        }

        .copy-link-btn {
            min-height: 34px;
            border: 1px solid rgba(0, 229, 255, .3);
            border-radius: 999px;
            padding: 0 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary);
            background: rgba(0, 229, 255, .08);
            font: inherit;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            transition: all .25s ease;
            white-space: nowrap;
        }

        .copy-link-btn:hover {
            background: var(--primary);
            color: #001014;
        }

        .shop-qr-card {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr);
            align-items: center;
            gap: 24px;
            margin-bottom: 22px;
            padding: 20px;
            border: 1px solid rgba(0, 229, 255, .34);
            border-radius: 26px;
            background:
                radial-gradient(circle at 12% 50%, rgba(0, 229, 255, .12), transparent 28%),
                linear-gradient(135deg, rgba(0, 229, 255, .05), rgba(112, 0, 255, .06)),
                rgba(0, 0, 0, .52);
            box-shadow: 0 16px 46px rgba(0, 0, 0, .28);
        }

        .shop-qr-image {
            display: block;
            width: 100%;
            aspect-ratio: 1;
            padding: 10px;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 0 22px rgba(0, 229, 255, .18);
        }

        .shop-qr-content {
            min-width: 0;
        }

        .shop-qr-title {
            color: var(--primary);
            font-size: 20px;
            font-weight: 900;
        }

        .shop-qr-hint {
            margin-top: 5px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .shop-qr-url {
            margin-top: 12px;
            padding: 10px 13px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 14px;
            background: rgba(0, 0, 0, .42);
            color: rgba(255, 255, 255, .76);
            font-size: 12px;
            font-weight: 800;
            direction: ltr;
            text-align: left;
            overflow-wrap: anywhere;
        }

        .shop-qr-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 14px;
        }

        .shop-qr-action {
            min-height: 36px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid rgba(0, 229, 255, .34);
            border-radius: 999px;
            color: #001014;
            background: var(--primary);
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
        }

        .shop-qr-action.secondary {
            color: var(--primary);
            background: rgba(0, 229, 255, .08);
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn {
            min-height: 44px;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #fff;
            background: rgba(255, 255, 255, .07);
            text-decoration: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            transition: all .25s ease;
        }

        .btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .btn-primary {
            border: 0;
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            box-shadow: 0 0 22px rgba(0, 229, 255, .34);
        }

        .btn-primary:hover { color: #001014; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .stat-card,
        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 24px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .3);
        }

        .stat-card {
            position: relative;
            min-height: 122px;
            padding: 18px;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            inset: auto 18px 0 18px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--stat-color, var(--primary)), transparent);
        }

        .stat-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
        }

        .stat-value {
            margin-top: 14px;
            color: var(--stat-color, var(--primary));
            font-size: 32px;
            line-height: 1;
            font-weight: 900;
            text-shadow: 0 0 16px rgba(0, 229, 255, .34);
        }

        .stat-icon {
            position: absolute;
            left: 16px;
            bottom: 12px;
            color: var(--stat-color, var(--primary));
            opacity: .2;
            font-size: 42px;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, .8fr);
            gap: 18px;
        }

        .panel { padding: 22px; }

        .panel + .panel { margin-top: 18px; }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            margin-bottom: 16px;
        }

        .panel-title {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            font-size: 17px;
            font-weight: 900;
        }

        .panel-title i {
            color: var(--primary);
            filter: drop-shadow(0 0 9px rgba(0, 229, 255, .45));
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .info-item {
            min-height: 92px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            background: rgba(0, 0, 0, .22);
            padding: 14px;
        }

        .label {
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 7px;
        }

        .value,
        .text-block {
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.65;
            overflow-wrap: anywhere;
        }

        .text-block { color: var(--muted); }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 32px;
            border-radius: 999px;
            padding: 0 12px;
            border: 1px solid currentColor;
            font-size: 12px;
            font-weight: 900;
        }

        .tag-g { color: var(--green); background: rgba(37, 211, 102, .1); }
        .tag-r { color: var(--danger); background: rgba(255, 59, 48, .1); }
        .tag-c { color: var(--primary); background: rgba(0, 229, 255, .09); }

        .social-grid,
        .category-grid,
        .relation-grid {
            display: grid;
            gap: 10px;
        }

        .social-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .category-grid,
        .relation-grid { grid-template-columns: 1fr; }

        .social-link,
        .category-card,
        .relation-card {
            min-height: 58px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 16px;
            background: rgba(0, 0, 0, .22);
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            font-weight: 900;
        }

        .social-link i,
        .category-card i,
        .relation-card i {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: #000;
            color: var(--primary);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(0, 229, 255, .35);
        }

        .category-copy,
        .relation-copy {
            min-width: 0;
            flex: 1;
        }

        .category-name,
        .relation-name {
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .category-sub,
        .relation-sub {
            color: var(--dim);
            font-size: 11px;
            font-weight: 800;
            margin-top: 2px;
        }

        .map-box {
            min-height: 180px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            background:
                linear-gradient(135deg, rgba(0, 229, 255, .08), rgba(112, 0, 255, .08)),
                rgba(0, 0, 0, .24);
            display: grid;
            place-items: center;
            text-align: center;
            padding: 18px;
            color: var(--muted);
            font-weight: 800;
        }

        .map-box i {
            display: block;
            color: var(--primary);
            font-size: 34px;
            margin-bottom: 8px;
        }

        .empty {
            border: 1px dashed rgba(255, 255, 255, .14);
            border-radius: 18px;
            padding: 18px;
            color: var(--dim);
            text-align: center;
            font-weight: 800;
        }

        @media(max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .layout { grid-template-columns: 1fr; }
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
            .content { padding: 20px 16px 34px; }
            .hero-content,
            .identity {
                align-items: flex-start;
                flex-direction: column;
            }
            .actions { justify-content: flex-start; }
            .btn { width: 100%; }
            .shop-qr-card { grid-template-columns: 150px minmax(0, 1fr); }
            h1 { font-size: 30px; }
        }

        @media(max-width: 680px) {
            .stats-grid,
            .info-grid,
            .social-grid {
                grid-template-columns: 1fr;
            }
            .logo { width: 112px; height: 112px; }
            .shop-qr-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .shop-qr-image {
                width: min(210px, 100%);
                margin: 0 auto;
            }
            .shop-qr-actions {
                justify-content: center;
            }
            .shop-qr-action {
                flex: 1 1 130px;
            }
        }
    </style>
</head>

<body>
    @php
        $currentUser = auth()->user();
        $canCreateCategories = $currentUser?->canAccessRouteName('categories.create');
        $canCreateAgents = $currentUser?->canAccessRouteName('agents.create');
        $canCreateDistributors = $currentUser?->canAccessRouteName('distributors.create');
        $canCreateAds = $currentUser?->canAccessRouteName('ads.create');
    @endphp
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض المتجر'])

            <div class="content">
                <section class="hero">
                    @if($shop->banner)
                        <img src="{{ asset($shop->banner) }}" class="hero-media" alt="{{ $shop->name }}">
                    @else
                        <div class="hero-fallback"><i class="ti ti-building-store" aria-hidden="true"></i></div>
                    @endif

                    <div class="hero-content">
                        <div class="identity">
                            @if($shop->logo)
                                <img src="{{ asset($shop->logo) }}" class="logo" alt="{{ $shop->name }}">
                            @else
                                <div class="logo"><i class="ti ti-building-store" aria-hidden="true"></i></div>
                            @endif

                            <div>
                                <div class="kicker">ملف المتجر</div>
                                <h1>{{ $shop->name }}</h1>
                                <div class="slug">{{ $shop->slug }}</div>
                                <div class="public-link-box">
                                    <input type="text" id="publicShopUrl" value="{{ $publicShopUrl }}" readonly>
                                    <button type="button" class="copy-link-btn" id="copyPublicShopUrl" data-copy-target="publicShopUrl">
                                        <i class="ti ti-copy" aria-hidden="true"></i>
                                        نسخ الرابط
                                    </button>
                                </div>
                                <div style="margin-top:12px">
                                    <span class="tag {{ $shop->is_active ? 'tag-g' : 'tag-r' }}">
                                        <i class="ti {{ $shop->is_active ? 'ti-circle-check' : 'ti-circle-x' }}" aria-hidden="true"></i>
                                        {{ $shop->is_active ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="actions">
                            <a href="{{ $publicShopUrl }}" target="_blank" rel="noopener" class="btn btn-primary">
                                <i class="ti ti-external-link" aria-hidden="true"></i>
                                فتح رابط المتجر
                            </a>
                            @if($canCreateCategories)
                            <a href="{{ route('categories.create', ['shop_id' => $shop->id]) }}" class="btn btn-primary">
                                <i class="ti ti-category-plus" aria-hidden="true"></i>
                                إضافة قسم
                            </a>
                            @endif
                            @if($canCreateAgents)
                            <a href="{{ route('agents.create', ['shop_id' => $shop->id]) }}" class="btn">
                                <i class="ti ti-user-star" aria-hidden="true"></i>
                                إضافة وكيل
                            </a>
                            @endif
                            @if($canCreateDistributors)
                            <a href="{{ route('distributors.create', ['shop_id' => $shop->id]) }}" class="btn">
                                <i class="ti ti-truck-delivery" aria-hidden="true"></i>
                                إضافة موزع
                            </a>
                            @endif
                            @if($canCreateAds)
                            <a href="{{ route('ads.create', ['shop_id' => $shop->id]) }}" class="btn">
                                <i class="ti ti-speakerphone" aria-hidden="true"></i>
                                إضافة إعلان
                            </a>
                            @endif
                            <a href="{{ route('shops.edit', $shop) }}" class="btn">
                                <i class="ti ti-edit" aria-hidden="true"></i>
                                تعديل المتجر
                            </a>
                            <a href="{{ route('shops') }}" class="btn">
                                <i class="ti ti-arrow-right" aria-hidden="true"></i>
                                رجوع
                            </a>
                        </div>
                    </div>
                </section>

                <section class="stats-grid" aria-label="إحصائيات المتجر">
                    <article class="stat-card" style="--stat-color: var(--primary)">
                        <div class="stat-label">المنتجات</div>
                        <div class="stat-value">{{ $shop->products_count ?? 0 }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--stat-color: var(--accent)">
                        <div class="stat-label">الأقسام</div>
                        <div class="stat-value">{{ $shop->categories_count ?? 0 }}</div>
                        <i class="ti ti-category stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--stat-color: var(--green)">
                        <div class="stat-label">حالة المتجر</div>
                        <div class="stat-value" style="font-size:24px">{{ $shop->is_active ? 'نشط' : 'متوقف' }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--stat-color: var(--yellow)">
                        <div class="stat-label">الدوام</div>
                        <div class="stat-value" style="font-size:22px">{{ $shop->open_time ?? '-' }}</div>
                        <i class="ti ti-clock stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="shop-qr-card" aria-labelledby="shopQrTitle">
                    <a href="{{ $publicShopUrl }}" target="_blank" rel="noopener">
                        <img src="{{ $shopQrCodeDataUri }}"
                            class="shop-qr-image"
                            alt="QR Code لمتجر {{ $shop->name }}">
                    </a>
                    <div class="shop-qr-content">
                        <h2 class="shop-qr-title" id="shopQrTitle">مشاركة متجر {{ $shop->name }}</h2>
                        <p class="shop-qr-hint">امسح الكود بكاميرا الهاتف لفتح صفحة المتجر مباشرة، أو نزّله للطباعة والمشاركة.</p>
                        <div class="shop-qr-url">{{ $publicShopUrl }}</div>
                        <div class="shop-qr-actions">
                            <button type="button"
                                class="shop-qr-action secondary copy-link-btn"
                                data-copy-target="publicShopUrl">
                                <i class="ti ti-copy" aria-hidden="true"></i>
                                نسخ الرابط
                            </button>
                            <a href="{{ $publicShopUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="shop-qr-action secondary">
                                <i class="ti ti-external-link" aria-hidden="true"></i>
                                فتح المتجر
                            </a>
                            <a href="{{ $shopQrCodeDataUri }}"
                                download="{{ $shop->slug }}-qr.svg"
                                class="shop-qr-action">
                                <i class="ti ti-download" aria-hidden="true"></i>
                                تنزيل QR
                            </a>
                        </div>
                    </div>
                </section>

                <div class="layout">
                    <div>
                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-address-book" aria-hidden="true"></i>
                                    معلومات التواصل
                                </h2>
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="label">الهاتف</div>
                                    <div class="value">{{ $shop->phone ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">واتساب</div>
                                    <div class="value">{{ $shop->whatsapp ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">البريد الإلكتروني</div>
                                    <div class="value">{{ $shop->email ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">تاريخ الإضافة</div>
                                    <div class="value">{{ optional($shop->created_at)->format('Y-m-d H:i') ?? '-' }}</div>
                                </div>
                            </div>
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-credit-card-pay" aria-hidden="true"></i>
                                    معلومات الدفع
                                </h2>
                            </div>

                            @if($shop->payment_method || $shop->payment_provider || $shop->payment_account_holder || $shop->payment_account_number || $shop->payment_iban || $shop->payment_wallet_number || $shop->payment_notes)
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="label">طريقة الدفع</div>
                                        <div class="value">{{ $paymentMethodLabels[$shop->payment_method] ?? $shop->payment_method ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">البنك أو مزود الدفع</div>
                                        <div class="value">{{ $shop->payment_provider ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">اسم صاحب الحساب</div>
                                        <div class="value">{{ $shop->payment_account_holder ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">رقم الحساب</div>
                                        <div class="value" dir="ltr">{{ $shop->payment_account_number ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">IBAN</div>
                                        <div class="value" dir="ltr">{{ $shop->payment_iban ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">رقم المحفظة</div>
                                        <div class="value" dir="ltr">{{ $shop->payment_wallet_number ?? '-' }}</div>
                                    </div>
                                    <div class="info-item" style="grid-column:1 / -1">
                                        <div class="label">ملاحظات الدفع</div>
                                        <div class="value">{{ $shop->payment_notes ?? '-' }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="empty">لم يتم حفظ معلومات دفع لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-user-star" aria-hidden="true"></i>
                                    وكلاء المتجر
                                </h2>
                                @if($canCreateAgents)
                                <a href="{{ route('agents.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->agents->isNotEmpty())
                                <div class="relation-grid">
                                    @foreach($shop->agents as $agent)
                                        <a href="{{ route('agents.show', $agent) }}" class="relation-card">
                                            <i class="ti ti-user-star" aria-hidden="true"></i>
                                            <span class="relation-copy">
                                                <span class="relation-name">{{ $agent->name }}</span>
                                                <span class="relation-sub">{{ $agent->phone ?? $agent->whatsapp ?? 'لا يوجد رقم تواصل' }}</span>
                                            </span>
                                            <span class="tag {{ $agent->is_active ? 'tag-g' : 'tag-r' }}">
                                                {{ $agent->is_active ? 'نشط' : 'متوقف' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا يوجد وكلاء لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-truck-delivery" aria-hidden="true"></i>
                                    موزعو المتجر
                                </h2>
                                @if($canCreateDistributors)
                                <a href="{{ route('distributors.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->distributors->isNotEmpty())
                                <div class="relation-grid">
                                    @foreach($shop->distributors as $distributor)
                                        <a href="{{ route('distributors.show', $distributor) }}" class="relation-card">
                                            <i class="ti ti-truck-delivery" aria-hidden="true"></i>
                                            <span class="relation-copy">
                                                <span class="relation-name">{{ $distributor->name }}</span>
                                                <span class="relation-sub">{{ $distributor->phone ?? $distributor->whatsapp ?? 'لا يوجد رقم تواصل' }}</span>
                                            </span>
                                            <span class="tag {{ $distributor->is_active ? 'tag-g' : 'tag-r' }}">
                                                {{ $distributor->is_active ? 'نشط' : 'متوقف' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا يوجد موزعون لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-speakerphone" aria-hidden="true"></i>
                                    إعلانات المتجر
                                </h2>
                                @if($canCreateAds)
                                <a href="{{ route('ads.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->advertisements->isNotEmpty())
                                <div class="relation-grid">
                                    @foreach($shop->advertisements as $ad)
                                        <a href="{{ route('ads.show', $ad) }}" class="relation-card">
                                            <i class="ti ti-speakerphone" aria-hidden="true"></i>
                                            <span class="relation-copy">
                                                <span class="relation-name">{{ $ad->title }}</span>
                                                <span class="relation-sub">{{ $ad->type }} - {{ $ad->duration }} ثانية</span>
                                            </span>
                                            <span class="tag {{ $ad->is_active ? 'tag-g' : 'tag-r' }}">
                                                {{ $ad->is_active ? 'نشط' : 'متوقف' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا توجد إعلانات لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-map-pin" aria-hidden="true"></i>
                                    الموقع والدوام
                                </h2>
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="label">المدينة</div>
                                    <div class="value">{{ $shop->city ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">الدولة</div>
                                    <div class="value">{{ $shop->country ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">العنوان</div>
                                    <div class="value">{{ $shop->address ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">ساعات العمل</div>
                                    <div class="value">{{ $shop->open_time ?? '-' }} - {{ $shop->close_time ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="height:12px"></div>

                            @if($shop->latitude && $shop->longitude)
                                <a class="map-box" href="https://www.google.com/maps?q={{ $shop->latitude }},{{ $shop->longitude }}" target="_blank" rel="noopener">
                                    <span>
                                        <i class="ti ti-map-search" aria-hidden="true"></i>
                                        {{ $shop->latitude }}, {{ $shop->longitude }}
                                        <br>
                                        فتح الموقع على الخريطة
                                    </span>
                                </a>
                            @else
                                <div class="map-box">
                                    <span>
                                        <i class="ti ti-map-off" aria-hidden="true"></i>
                                        لم يتم تحديد موقع المتجر بعد
                                    </span>
                                </div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-align-right" aria-hidden="true"></i>
                                    وصف المتجر
                                </h2>
                            </div>

                            <div class="text-block">{{ $shop->description ?: 'لا يوجد وصف لهذا المتجر.' }}</div>
                        </section>
                    </div>

                    <aside>
                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-category" aria-hidden="true"></i>
                                    أقسام المتجر
                                </h2>
                                @if($canCreateCategories)
                                <a href="{{ route('categories.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->categories->isNotEmpty())
                                <div class="category-grid">
                                    @foreach($shop->categories as $category)
                                        <a href="{{ route('categories.show', $category) }}" class="category-card">
                                            <i class="ti ti-category" aria-hidden="true"></i>
                                            <span class="category-copy">
                                                <span class="category-name">{{ $category->name }}</span>
                                                <span class="category-sub">{{ $category->products_count }} منتج</span>
                                            </span>
                                            <span class="tag {{ $category->is_active ? 'tag-g' : 'tag-r' }}">
                                                {{ $category->is_active ? 'نشط' : 'متوقف' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا توجد أقسام لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-share" aria-hidden="true"></i>
                                    روابط التواصل
                                </h2>
                            </div>

                            @if($socialLinks->isNotEmpty())
                                <div class="social-grid">
                                    @foreach($socialLinks as $link)
                                        <a class="social-link" href="{{ $link['url'] }}" target="_blank" rel="noopener">
                                            <i class="ti {{ $link['icon'] }}" aria-hidden="true"></i>
                                            <span>{{ $link['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا توجد روابط تواصل محفوظة.</div>
                            @endif
                        </section>
                    </aside>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-copy-target]').forEach((button) => {
            button.addEventListener('click', async function () {
                const target = document.getElementById(this.dataset.copyTarget);
                if (!target) return;

                const value = target.value;

                try {
                    await navigator.clipboard.writeText(value);
                } catch (error) {
                    target.select();
                    document.execCommand('copy');
                    target.blur();
                }

                const originalText = this.innerHTML;
                this.innerHTML = '<i class="ti ti-check" aria-hidden="true"></i> تم النسخ';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 1400);
            });
        });
    </script>
</body>

</html>
