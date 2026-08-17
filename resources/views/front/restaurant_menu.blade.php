<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>منيو {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --cyan: #08def4;
            --cyan-soft: rgba(8, 222, 244, .11);
            --green: #27dd86;
            --red: #ff6678;
            --bg: #05070a;
            --card: #10151a;
            --card2: #0a0e12;
            --border: rgba(150, 174, 190, .18);
            --muted: #9ca9b4
        }

        * {
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            background: radial-gradient(circle at 85% 5%, rgba(8, 222, 244, .11), transparent 27%), radial-gradient(circle at 8% 30%, rgba(101, 42, 255, .1), transparent 25%), var(--bg);
            color: #fff;
            font-family: "Cairo", Arial, sans-serif
        }

        button,
        input,
        select,
        textarea {
            font: inherit
        }

        .shell {
            width: min(1380px, calc(100% - 32px));
            margin: auto;
            padding: 20px 0 55px
        }

        .hero {
            position: relative;
            overflow: hidden;
            min-height: 235px;
            border: 1px solid var(--border);
            border-radius: 28px;
            background: linear-gradient(110deg, rgba(15, 18, 28, .96), rgba(5, 25, 28, .9));
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .28)
        }

        .hero:after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: rgba(8, 222, 244, .12);
            filter: blur(70px);
            left: -100px;
            top: -180px
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 1
        }

        .logo {
            width: 105px;
            height: 105px;
            border-radius: 24px;
            object-fit: cover;
            border: 2px solid var(--cyan);
            box-shadow: 0 0 28px rgba(8, 222, 244, .3)
        }

        .brand-kicker {
            color: var(--cyan);
            font-size: 13px;
            font-weight: 900
        }

        .hero h1 {
            font-size: clamp(28px, 4vw, 48px);
            margin: 3px 0 5px
        }

        .hero p {
            color: var(--muted);
            margin: 0;
            font-weight: 600
        }

        .service-badge {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 17px;
            border-radius: 16px;
            background: var(--cyan-soft);
            border: 1px solid rgba(8, 222, 244, .25);
            color: var(--cyan);
            font-weight: 800
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 365px;
            gap: 20px;
            margin-top: 22px
        }

        .menu-panel,
        .cart {
            border: 1px solid var(--border);
            border-radius: 25px;
            background: linear-gradient(145deg, rgba(17, 21, 27, .94), rgba(8, 13, 16, .94));
            padding: 22px
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px
        }

        .section-head h2 {
            font-size: 26px;
            margin: 0
        }

        .section-head p {
            color: var(--muted);
            font-size: 13px;
            margin: 2px 0
        }

        .count {
            display: inline-grid;
            place-items: center;
            min-width: 43px;
            height: 43px;
            border-radius: 14px;
            background: var(--cyan-soft);
            border: 1px solid rgba(8, 222, 244, .25);
            color: var(--cyan);
            font-weight: 900
        }

        .category {
            margin-bottom: 27px
        }

        .menu-browser {
            display: grid;
            grid-template-columns: 112px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
            min-height: 520px
        }

        .category-rail {
            position: sticky;
            top: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: calc(100vh - 36px);
            overflow-y: auto;
            overscroll-behavior: contain;
            scroll-snap-type: y mandatory;
            scrollbar-width: none;
            padding: calc(50vh - 100px) 5px;
            border-radius: 22px;
            background: rgba(3, 7, 10, .58)
        }

        .category-rail::-webkit-scrollbar {
            display: none
        }

        .category-tab {
            flex: 0 0 auto;
            scroll-snap-align: center;
            border: 1px solid transparent;
            border-radius: 18px;
            background: transparent;
            color: var(--muted);
            padding: 8px 5px 10px;
            cursor: pointer;
            transition: transform .24s, color .24s, background .24s, border-color .24s
        }

        .category-tab img,
        .category-tab-icon {
            display: grid;
            place-items: center;
            width: 68px;
            height: 68px;
            margin: auto;
            border-radius: 50%;
            object-fit: cover;
            background: #161d23;
            border: 3px solid rgba(255, 255, 255, .08);
            font-size: 29px;
            transition: .24s
        }

        .category-tab span:last-child {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            line-height: 1.45;
            font-weight: 800
        }

        .category-tab.active {
            color: #fff;
            background: var(--cyan-soft);
            border-color: rgba(8, 222, 244, .35);
            transform: scale(1.03)
        }

        .category-tab.active img,
        .category-tab.active .category-tab-icon {
            border-color: var(--cyan);
            box-shadow: 0 0 0 4px rgba(8, 222, 244, .1), 0 0 24px rgba(8, 222, 244, .25)
        }

        .category-pane {
            animation: categoryReveal .28s ease
        }

        .category-pane[hidden] {
            display: none
        }

        @keyframes categoryReveal {
            from { opacity: 0; transform: translateY(10px) }
            to { opacity: 1; transform: translateY(0) }
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 19px;
            margin: 0 0 13px
        }

        .category-title:before {
            content: "";
            width: 5px;
            height: 24px;
            border-radius: 10px;
            background: var(--cyan);
            box-shadow: 0 0 12px var(--cyan)
        }

        .meals {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px
        }

        .meal {
            display: grid;
            grid-template-columns: 130px minmax(0, 1fr);
            gap: 15px;
            min-height: 165px;
            border: 1px solid var(--border);
            border-radius: 19px;
            background: rgba(5, 9, 12, .75);
            padding: 12px;
            transition: .25s
        }

        .meal:hover {
            transform: translateY(-3px);
            border-color: rgba(8, 222, 244, .38);
            box-shadow: 0 12px 35px rgba(0, 0, 0, .24)
        }

        .meal-image {
            width: 130px;
            height: 140px;
            border-radius: 15px;
            object-fit: cover;
            background: #161d23
        }

        .meal-body {
            display: flex;
            flex-direction: column;
            min-width: 0
        }

        .meal h3 {
            font-size: 18px;
            margin: 2px 0
        }

        .meal-desc {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
            margin: 0 0 9px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden
        }

        .meal-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 9px;
            margin-top: auto
        }

        .price {
            color: var(--cyan);
            font-size: 18px;
            font-weight: 900
        }

        .add-btn,
        .primary-btn {
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #09dcec, #1cc0ef);
            color: #001114;
            font-weight: 900;
            padding: 9px 13px;
            cursor: pointer
        }

        .add-btn:hover,
        .primary-btn:hover {
            filter: brightness(1.08)
        }

        .cart {
            position: sticky;
            top: 18px;
            height: max-content
        }

        .cart-title {
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .cart h2 {
            margin: 0
        }

        .cart-items {
            max-height: 310px;
            overflow: auto;
            margin: 15px 0
        }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 28px 10px
        }

        .empty i {
            display: block;
            font-size: 42px;
            color: #52606c;
            margin-bottom: 7px
        }

        .cart-item {
            border: 1px solid var(--border);
            background: rgba(3, 7, 10, .65);
            border-radius: 14px;
            padding: 11px;
            margin-bottom: 9px
        }

        .cart-item-head {
            display: flex;
            justify-content: space-between;
            gap: 8px
        }

        .cart-item strong {
            font-size: 14px
        }

        .cart-item small {
            display: block;
            color: var(--muted);
            line-height: 1.7
        }

        .remove {
            border: 0;
            background: transparent;
            color: var(--red);
            cursor: pointer
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin-bottom: 13px
        }

        .cart-total strong {
            color: var(--cyan);
            font-size: 23px
        }

        .field {
            width: 100%;
            min-height: 47px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #070b0f;
            color: #fff;
            padding: 10px 13px;
            outline: 0;
            margin-bottom: 9px
        }

        .field:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(8, 222, 244, .08)
        }

        textarea.field {
            resize: vertical;
            min-height: 75px
        }

        .fields-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px
        }

        .location-box {
            border: 1px solid rgba(8, 222, 244, .24);
            border-radius: 15px;
            background: var(--cyan-soft);
            padding: 11px;
            margin-bottom: 9px
        }

        .location-btn {
            width: 100%;
            border: 1px solid rgba(8, 222, 244, .35);
            border-radius: 12px;
            background: #07151a;
            color: var(--cyan);
            padding: 10px;
            font-weight: 900;
            cursor: pointer
        }

        .location-status {
            color: var(--muted);
            font-size: 12px;
            margin: 7px 2px 0
        }

        .location-status.ready {
            color: var(--green)
        }

        .primary-btn {
            width: 100%;
            min-height: 50px;
            font-size: 15px
        }

        .message {
            font-size: 13px;
            line-height: 1.7;
            margin: 10px 0 0
        }

        .message.error {
            color: #ff9eaa
        }

        .message.success {
            color: #6df0ab
        }

        dialog {
            width: min(560px, calc(100% - 24px));
            max-height: 90vh;
            overflow: auto;
            border: 1px solid rgba(8, 222, 244, .35);
            border-radius: 24px;
            background: linear-gradient(145deg, #141923, #071114);
            color: #fff;
            padding: 0;
            box-shadow: 0 25px 100px rgba(0, 0, 0, .65)
        }

        dialog::backdrop {
            background: rgba(0, 3, 6, .82);
            backdrop-filter: blur(8px)
        }

        .modal-head {
            position: sticky;
            top: 0;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
            background: rgba(13, 18, 23, .97)
        }

        .modal-head h3 {
            margin: 0;
            font-size: 22px
        }

        .close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: #151b20;
            color: #fff;
            cursor: pointer
        }

        .modal-body {
            padding: 20px 22px
        }

        .option-section {
            margin-bottom: 19px
        }

        .option-section h4 {
            margin: 0 0 10px
        }

        .choices {
            display: grid;
            gap: 8px
        }

        .choice {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #080d11;
            padding: 11px 13px;
            cursor: pointer
        }

        .choice input {
            accent-color: var(--cyan)
        }

        .choice-price {
            color: var(--cyan);
            font-weight: 800
        }

        .qty-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
            align-items: end
        }

        .modal-actions {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 9px
        }

        .secondary {
            border: 1px solid var(--border);
            background: #171c21;
            color: #fff;
            border-radius: 12px;
            padding: 10px 17px;
            cursor: pointer
        }

        .mobile-cart {
            display: none;
            position: fixed;
            right: 12px;
            left: 12px;
            bottom: 12px;
            z-index: 15;
            min-height: 55px;
            border: 0;
            border-radius: 17px;
            background: linear-gradient(135deg, #09dcec, #1cc0ef);
            color: #001114;
            font-weight: 900;
            box-shadow: 0 10px 35px rgba(8, 222, 244, .3)
        }

        @media(max-width:1050px) {
            .layout {
                grid-template-columns: 1fr
            }

            .cart {
                position: relative;
                top: auto
            }

            .meals {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-width:720px) {
            .shell {
                width: calc(100% - 18px);
                padding-top: 9px
            }

            .hero {
                min-height: auto;
                border-radius: 21px;
                padding: 20px
            }

            .brand {
                align-items: flex-start
            }

            .logo {
                width: 75px;
                height: 75px;
                border-radius: 18px
            }

            .service-badge {
                display: none
            }

            .layout {
                margin-top: 12px
            }

            .menu-panel,
            .cart {
                padding: 14px;
                border-radius: 20px
            }

            .menu-panel {
                padding-inline: 9px
            }

            .menu-browser {
                grid-template-columns: 86px minmax(0, 1fr);
                gap: 9px;
                min-height: 480px
            }

            .category-rail {
                top: 8px;
                max-height: calc(100vh - 76px);
                padding: calc(50vh - 90px) 3px
            }

            .category-tab {
                border-radius: 15px;
                padding-inline: 2px
            }

            .category-tab img,
            .category-tab-icon {
                width: 57px;
                height: 57px;
                font-size: 24px
            }

            .category-tab span:last-child {
                font-size: 10px
            }

            .category-title {
                font-size: 17px
            }

            .meals {
                grid-template-columns: 1fr
            }

            .meal {
                grid-template-columns: 1fr;
                min-height: 0;
                padding: 9px
            }

            .meal-image {
                width: 100%;
                height: 125px
            }

            .meal h3 {
                font-size: 16px
            }

            .meal-bottom {
                align-items: stretch;
                flex-direction: column
            }

            .add-btn {
                width: 100%
            }

            .fields-row {
                grid-template-columns: 1fr
            }

            .cart {
                margin-bottom: 65px
            }

            .mobile-cart {
                display: block
            }

            .qty-row {
                grid-template-columns: 1fr
            }

            .modal-actions {
                grid-template-columns: 1fr
            }

            .secondary {
                width: 100%
            }
        }
    </style>
</head>

<body>
    @php
        $restaurantProducts = $products
            ->map(function ($product) {
                $attributes = $product->catalog_attributes ?? [];
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) ($product->discount_price ?: $product->price),
                    'sizes' => $attributes['meal_size_prices'] ?? [],
                    'addons' => $attributes['addon_prices'] ?? [],
                    'ingredients' => $attributes['removable_ingredients'] ?? [],
                ];
            })
            ->values();
        $restaurantCategories = $categories
            ->map(function ($category) use ($products) {
                return [
                    'key' => (string) $category->id,
                    'name' => $category->name,
                    'image' => $category->image ?: $products->first(fn($product) => $product->category_id === $category->id && filled($product->main_image))?->main_image,
                    'products' => $products->where('category_id', $category->id)->values(),
                ];
            })
            ->values();
        $uncategorizedProducts = $products->whereNull('category_id')->values();
        if ($uncategorizedProducts->isNotEmpty()) {
            $restaurantCategories->push([
                'key' => 'uncategorized',
                'name' => 'الوجبات',
                'image' => $uncategorizedProducts->first(fn($product) => filled($product->main_image))?->main_image,
                'products' => $uncategorizedProducts,
            ]);
        }
    @endphp
    <div class="shell">
        <header class="hero">
            <div class="brand">
                @if ($shop->logo)
                    <img class="logo" src="{{ asset($shop->logo) }}" alt="{{ $shop->name }}">
                @endif
                <div>
                    <div class="brand-kicker">منيو المطعم</div>
                    <h1>{{ $shop->name }}</h1>
                    <p>{{ $table ? 'أنت تطلب الآن من ' . $table->name : 'اختر وجبتك، خصصها وأرسل طلبك للمطعم' }}</p>
                </div>
            </div>
            <div class="service-badge"><i
                    class="ti {{ $table ? 'ti-tools-kitchen-2' : 'ti-truck-delivery' }}"></i>{{ $table ? 'طلب من داخل المطعم' : 'توصيل أو استلام' }}
            </div>
        </header>

        <div class="layout">
            <section class="menu-panel">
                <div class="section-head">
                    <div>
                        <h2>قائمة الطعام</h2>
                        <p>اضغط على الوجبة لاختيار الحجم والإضافات.</p>
                    </div><span class="count">{{ $products->count() }}</span>
                </div>
                @if($restaurantCategories->isNotEmpty())
                    <div class="menu-browser" id="menuBrowser">
                        <nav class="category-rail" id="categoryRail" aria-label="أقسام المنيو">
                            @foreach($restaurantCategories as $category)
                                <button type="button" class="category-tab @if($loop->first) active @endif"
                                    data-category-key="{{ $category['key'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                    @if($category['image'])
                                        <img src="{{ asset($category['image']) }}" alt="" loading="lazy">
                                    @else
                                        <span class="category-tab-icon"><i class="ti ti-tools-kitchen-2"></i></span>
                                    @endif
                                    <span>{{ $category['name'] }}</span>
                                </button>
                            @endforeach
                        </nav>
                        <div class="category-content" id="categoryContent">
                            @foreach($restaurantCategories as $category)
                                <section class="category category-pane" data-category-pane="{{ $category['key'] }}" @if(!$loop->first) hidden @endif>
                                    <h3 class="category-title">{{ $category['name'] }}</h3>
                                    <div class="meals">
                            @forelse ($category['products'] as $product)
                                <article class="meal">
                                    @if ($product->main_image)
                                        <img class="meal-image" src="{{ asset($product->main_image) }}"
                                        alt="{{ $product->name }}">@else<div class="meal-image"></div>
                                    @endif
                                    <div class="meal-body">
                                        <h3>{{ $product->name }}</h3>
                                        <p class="meal-desc">
                                            {{ $product->description ?: 'وجبة طازجة محضرة حسب طلبك.' }}</p>
                                        <div class="meal-bottom"><span class="price">من
                                                {{ number_format((float) ($product->discount_price ?: $product->price), 2) }}
                                                ₪</span><button class="add-btn"
                                                data-product-id="{{ $product->id }}"><i class="ti ti-plus"></i>
                                                إضافة</button></div>
                                    </div>
                                </article>
                            @empty
                                <div class="empty"><i class="ti ti-tools-kitchen-off"></i>لا توجد وجبات في هذا القسم حالياً.</div>
                            @endforelse
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty"><i class="ti ti-tools-kitchen-off"></i>لا توجد وجبات متاحة حالياً.</div>
                @endif
            </section>

            <aside class="cart" id="cartPanel">
                <div class="cart-title">
                    <h2>طلبك</h2><span class="count" id="cartCount">0</span>
                </div>
                <div class="cart-items" id="cartItems"></div>
                <div class="cart-total"><span>المجموع</span><strong><span id="total">0.00</span> ₪</strong></div>
                @unless ($table)
                    <select class="field" id="type">
                        <option value="delivery">توصيل إلى العنوان</option>
                        <option value="pickup">استلام من المطعم</option>
                </select>@else<input type="hidden" id="type" value="dine_in">
                @endunless
                <div class="fields-row"><input class="field" id="name"
                        placeholder="{{ $table ? 'اسم صاحب الطلب' : 'الاسم' }}"><input class="field" id="phone"
                        inputmode="tel" placeholder="رقم الهاتف"></div>
                @unless ($table)
                    <input class="field" id="address" placeholder="عنوان التوصيل بالتفصيل">
                    <div class="location-box" id="locationBox"><button class="location-btn" type="button"
                            id="detectLocation"><i class="ti ti-current-location"></i> حدد موقعي للتوصيل</button>
                        <p class="location-status" id="locationStatus">يجب تحديد موقعك على الخريطة لإرسال طلب التوصيل.</p>
                    </div>
                @else
                    <input type="hidden" id="address">
                @endunless
                <input type="hidden" id="latitude"><input type="hidden" id="longitude">
                <textarea class="field" id="orderNotes" placeholder="ملاحظات عامة للمطعم"></textarea>
                <button class="primary-btn" id="send"><i class="ti ti-send"></i> تأكيد وإرسال الطلب</button>
                <p class="message" id="message"></p>
            </aside>
        </div>
    </div>

    <button class="mobile-cart" id="mobileCart"><i class="ti ti-shopping-bag"></i> عرض الطلب (<span
            id="mobileCount">0</span>) — <span id="mobileTotal">0.00</span> ₪</button>
    <dialog id="mealModal">
        <div class="modal-head">
            <h3 id="modalName"></h3><button class="close" id="modalClose"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body">
            <section class="option-section" id="sizesSection">
                <h4>اختر حجم الوجبة</h4>
                <div class="choices" id="sizes"></div>
            </section>
            <section class="option-section" id="addonsSection">
                <h4>إضافات على الوجبة</h4>
                <div class="choices" id="addons"></div>
            </section>
            <section class="option-section" id="excludedSection">
                <h4>حذف مكونات</h4>
                <div class="choices" id="excluded"></div>
            </section>
            <div class="qty-row"><label>الكمية<input class="field" id="qty" type="number" min="1"
                        max="100" value="1"></label>
                <textarea class="field" id="notes" placeholder="ملاحظات خاصة بهذه الوجبة"></textarea>
            </div>
            <div class="modal-actions"><button class="primary-btn" id="confirm"><i
                        class="ti ti-shopping-bag-plus"></i> إضافة إلى الطلب</button><button class="secondary"
                    id="modalCancel">إلغاء</button></div>
        </div>
    </dialog>
    <script>
        (() => {
            const products = new Map(@json($restaurantProducts).map(product => [Number(product.id), product]));
            const cart = [];
            let current = null;
            const $ = id => document.getElementById(id);
            const modal = $('mealModal');
            const parseOptions = values => Object.fromEntries((values || []).map(value => {
                const [name, price] = String(value).split(':', 2);
                return [name.trim(), Number(price) || 0];
            }).filter(([name]) => name));
            const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [char]));

            const categoryRail = $('categoryRail');
            const categoryTabs = [...document.querySelectorAll('.category-tab')];
            const categoryPanes = [...document.querySelectorAll('[data-category-pane]')];
            let activeCategory = categoryTabs[0]?.dataset.categoryKey;
            let categoryFrame = null;

            const activateCategory = (key, centerTab = false) => {
                if (!key || key === activeCategory && !centerTab) return;
                activeCategory = key;
                categoryTabs.forEach(tab => {
                    const active = tab.dataset.categoryKey === key;
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-pressed', String(active));
                    if (active && centerTab) tab.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                categoryPanes.forEach(pane => pane.hidden = pane.dataset.categoryPane !== key);
            };

            categoryTabs.forEach(tab => tab.addEventListener('click', () =>
                activateCategory(tab.dataset.categoryKey, true)));

            categoryRail?.addEventListener('scroll', () => {
                cancelAnimationFrame(categoryFrame);
                categoryFrame = requestAnimationFrame(() => {
                    const railBox = categoryRail.getBoundingClientRect();
                    const center = railBox.top + railBox.height / 2;
                    const closest = categoryTabs.reduce((best, tab) => {
                        const box = tab.getBoundingClientRect();
                        const distance = Math.abs(box.top + box.height / 2 - center);
                        return !best || distance < best.distance ? { tab, distance } : best;
                    }, null);
                    if (closest) activateCategory(closest.tab.dataset.categoryKey);
                });
            }, { passive: true });

            document.querySelectorAll('.add-btn').forEach(button => button.addEventListener('click', () => {
                current = products.get(Number(button.dataset.productId));
                if (!current) return;
                $('modalName').textContent = current.name;
                $('qty').value = 1;
                $('notes').value = '';
                const sizes = parseOptions(current.sizes),
                    addons = parseOptions(current.addons);
                $('sizesSection').hidden = !Object.keys(sizes).length;
                $('addonsSection').hidden = !Object.keys(addons).length;
                $('excludedSection').hidden = !(current.ingredients || []).length;
                $('sizes').innerHTML = Object.entries(sizes).map(([name, price], index) =>
                    `<label class="choice"><span><input type="radio" name="meal_size" value="${escapeHtml(name)}" data-price="${price}" ${index===0?'checked':''}> ${escapeHtml(name)}</span><span class="choice-price">${price.toFixed(2)} ₪</span></label>`
                    ).join('');
                $('addons').innerHTML = Object.entries(addons).map(([name, price]) =>
                    `<label class="choice"><span><input type="checkbox" value="${escapeHtml(name)}" data-price="${price}"> ${escapeHtml(name)}</span><span class="choice-price">+${price.toFixed(2)} ₪</span></label>`
                    ).join('');
                $('excluded').innerHTML = (current.ingredients || []).map(name =>
                    `<label class="choice"><span><input type="checkbox" value="${escapeHtml(name)}"> بدون ${escapeHtml(name)}</span></label>`
                    ).join('');
                modal.showModal();
            }));
            const closeModal = () => modal.close();
            $('modalClose').onclick = closeModal;
            $('modalCancel').onclick = closeModal;
            $('confirm').onclick = () => {
                const selectedSize = $('sizes').querySelector(':checked');
                const selectedAddons = [...$('addons').querySelectorAll(':checked')];
                const unit = selectedSize ? Number(selectedSize.dataset.price) : Number(current.price);
                const addonTotal = selectedAddons.reduce((sum, input) => sum + Number(input.dataset.price), 0);
                cart.push({
                    product_id: current.id,
                    name: current.name,
                    qty: Math.max(1, Number($('qty').value) || 1),
                    size: selectedSize?.value || null,
                    addons: selectedAddons.map(input => input.value),
                    excluded: [...$('excluded').querySelectorAll(':checked')].map(input => input.value),
                    notes: $('notes').value,
                    unit: unit + addonTotal
                });
                closeModal();
                render();
            };
            window.removeRestaurantCartItem = index => {
                cart.splice(index, 1);
                render();
            };

            function render() {
                $('cartItems').innerHTML = cart.length ? cart.map((item, index) =>
                    `<article class="cart-item"><div class="cart-item-head"><strong>${item.qty}× ${escapeHtml(item.name)}</strong><button class="remove" onclick="removeRestaurantCartItem(${index})"><i class="ti ti-trash"></i></button></div><small>${escapeHtml(item.size||'')} ${item.addons.length?'• '+escapeHtml(item.addons.join('، ')):''}</small><span class="price">${(item.unit*item.qty).toFixed(2)} ₪</span></article>`
                    ).join('') : '<div class="empty"><i class="ti ti-shopping-bag"></i>لم تضف أي وجبة بعد</div>';
                const total = cart.reduce((sum, item) => sum + item.unit * item.qty, 0);
                $('total').textContent = $('mobileTotal').textContent = total.toFixed(2);
                $('cartCount').textContent = $('mobileCount').textContent = cart.reduce((sum, item) => sum + item.qty,
                    0);
            }
            $('mobileCart').onclick = () => {
                $('cartPanel').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                })
            };
            const type = $('type'),
                address = $('address'),
                locationBox = $('locationBox');
            type?.addEventListener('change', () => {
                if (address && address.type !== 'hidden') {
                    const delivery = type.value === 'delivery';
                    address.hidden = !delivery;
                    address.required = delivery;
                    if (locationBox) locationBox.hidden = !delivery
                }
            });
            type?.dispatchEvent(new Event('change'));
            $('detectLocation')?.addEventListener('click', () => {
                const status = $('locationStatus');
                if (!navigator.geolocation) {
                    status.textContent = 'المتصفح لا يدعم تحديد الموقع.';
                    status.classList.remove('ready');
                    return
                }
                status.textContent = 'جاري تحديد موقعك بدقة...';
                status.classList.remove('ready');
                navigator.geolocation.getCurrentPosition(position => {
                    $('latitude').value = position.coords.latitude.toFixed(7);
                    $('longitude').value = position.coords.longitude.toFixed(7);
                    status.innerHTML =
                        `<i class="ti ti-circle-check"></i> تم تحديد موقعك بنجاح — <a style="color:var(--cyan)" target="_blank" href="https://www.google.com/maps?q=${$('latitude').value},${$('longitude').value}">فتح الموقع</a>`;
                    status.classList.add('ready');
                }, () => {
                    status.textContent =
                        'تعذر تحديد الموقع. اسمح للموقع بالوصول إلى اللوكيشن ثم حاول مجدداً.';
                    status.classList.remove('ready')
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0
                });
            });
            $('send').onclick = async () => {
                const message = $('message');
                message.className = 'message';
                message.textContent = '';
                if (!cart.length) {
                    message.classList.add('error');
                    message.textContent = 'أضف وجبة واحدة على الأقل.';
                    return
                }
                if (type.value === 'delivery' && (!$('latitude').value || !$('longitude').value)) {
                    message.classList.add('error');
                    message.textContent = 'حدد موقعك للتوصيل قبل إرسال الطلب.';
                    return
                }
                const payload = {
                    order_type: type.value,
                    table_code: @json($table?->code),
                    customer_name: $('name').value,
                    customer_phone: $('phone').value,
                    customer_address: address.value,
                    latitude: $('latitude').value || null,
                    longitude: $('longitude').value || null,
                    customer_notes: $('orderNotes').value,
                    items: cart
                };
                try {
                    const response = await fetch(@json(route('restaurant.orders.store', $shop)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                        throw new Error(errors || data.message || 'تحقق من البيانات')
                    }
                    cart.length = 0;
                    render();
                    message.classList.add('success');
                    message.textContent = `تم إرسال طلبك بنجاح. رقم الطلب: ${data.order_number}`;
                } catch (error) {
                    message.classList.add('error');
                    message.textContent = error.message || 'تعذر إرسال الطلب، حاول مرة أخرى.'
                }
            };
            render();
        })();
    </script>
</body>

</html>
