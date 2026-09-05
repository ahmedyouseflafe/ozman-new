<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he'], true) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoShopName = $shop?->name ?? 'Ozman';
        $seoDescription = $shop?->description ?: ($shop
            ? "تصفح منتجات وعروض {$seoShopName} عبر Ozman."
            : 'اكتشف المتاجر والمنتجات والعروض والمطاعم المحلية عبر منصة Ozman.');
        $seoImage = $shop?->banner
            ? asset(\Illuminate\Support\Str::startsWith($shop->banner, 'storage/') ? $shop->banner : 'storage/'.$shop->banner)
            : ($shop?->logo ? asset($shop->logo) : asset('images/logo.svg'));
        $seoCanonical = $shop ? route('front.shop.slug', $shop) : route('home');
        $seoSchema = [
            '@context' => 'https://schema.org',
            '@type' => $shop ? 'Store' : 'WebSite',
            'name' => $seoShopName,
            'url' => $seoCanonical,
            'description' => $seoDescription,
            'image' => $seoImage,
        ];
    @endphp
    @include('front.partials.seo', [
        'title' => $shop ? $seoShopName.' | Ozman' : 'Ozman | متاجر ومنتجات محلية',
        'description' => $seoDescription,
        'canonical' => $seoCanonical,
        'image' => $seoImage,
        'schema' => $seoSchema,
    ])
    <link rel="stylesheet" href="{{ route('front.assets', ['file' => 'style.css']) }}?v={{ hash_file('sha256', base_path('public/style.css')) }}">
    <!-- Font Awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <style>
        .logo-dropdown>.dropdown-item:nth-child(n+3) {
            display: none
        }
    </style>

</head>

<body>
    @php
        $shopName = $shop?->name ?? 'Ozman';
        $shopLogo = $shop?->logo ? asset($shop->logo) : asset('images/logo.svg');
        $ozmanName = $ozmanShop?->name ?? 'Ozman';
        $ozmanLogo = $ozmanShop?->logo ? asset($ozmanShop->logo) : asset('images/logo.svg');
        $locale = app()->getLocale();
        $ozmanWelcomeText = __('أهلا بك في Ozman - اكتشف فئاتنا ومنتجاتنا المميزة');
        $welcomeText = __('أهلا بك في :shop - اكتشف أقسام ومنتجات المتجر', ['shop' => $shopName]);
        $languageLabels = ['ar' => 'العربية', 'he' => 'עברית', 'en' => 'English'];
        $frontLabels = [
            'cartEmpty' => __('السلة فارغة حاليا'),
            'noPrice' => __('بدون سعر'),
            'items' => __('عدد المنتجات'),
            'total' => __('المجموع'),
            'discount' => __('الخصم'),
            'shekel' => __('شيكل'),
            'addAnotherForDiscount' => __('أضف منتج آخر إلى السلة واحصل على خصم 5% من المبلغ الإجمالي'),
            'discountApplied' => __('تم تفعيل خصم 5% على المبلغ الإجمالي'),
            'quickSearchEmpty' => __('اكتب اسم المنتج أو المحل لعرض النتائج'),
            'noSearchResults' => __('لا توجد نتائج مطابقة'),
            'cartIsEmpty' => __('السلة فارغة'),
            'chooseProductsBeforePayment' => __('اختار منتجاتك قبل الدفع'),
            'customerSaved' => __('تم حفظ بيانات العميل'),
            'locationLoading' => __('جاري تحديد موقعك...'),
            'locationDetected' => __('تم تحديد موقعك على الخريطة.'),
            'locationUnsupported' => __('المتصفح لا يدعم تحديد الموقع تلقائيا.'),
            'locationDenied' => __('لم نقدر نحدد الموقع. تأكد من السماح للموقع بالوصول للّوكيشن.'),
            'savedLocationLoaded' => __('تم تحميل الموقع المحفوظ مسبقا.'),
            'shop' => __('المحل'),
            'agent' => __('الوكيل'),
            'distributor' => __('الموزع'),
            'agents' => __('الوكلاء'),
            'distributors' => __('الموزعون'),
            'noAgents' => __('لا يوجد وكلاء بعد'),
            'noDistributors' => __('لا يوجد موزعون بعد'),
            'departments' => __('أقسام'),
            'directionsTo' => __('الوصول إلى :subject'),
            'directionsLinkTo' => __('انقر فوق الخارطة للوصول إلى :subject عبر GPS'),
            'backToProducts' => __('عودة للمنتجات'),
        ];
        $paymentMethodLabels = [
            'bank_transfer' => __('تحويل بنكي'),
            'wallet' => __('محفظة إلكترونية'),
            'cash' => __('كاش / عند الاستلام'),
            'other' => __('أخرى'),
        ];
        $shopPaymentDetails = [
            'method' => $shop?->payment_method,
            'method_label' => $paymentMethodLabels[$shop?->payment_method] ?? $shop?->payment_method,
            'provider' => $shop?->payment_provider,
            'account_holder' => $shop?->payment_account_holder,
            'account_number' => $shop?->payment_account_number,
            'iban' => $shop?->payment_iban,
            'wallet_number' => $shop?->payment_wallet_number,
            'notes' => $shop?->payment_notes,
        ];
        $hasShopPaymentDetails = collect($shopPaymentDetails)
            ->except(['method_label'])
            ->filter(fn($value) => filled($value))
            ->isNotEmpty();
        $normalizeSocialUrl = function (?string $url): ?string {
            if (!filled($url)) {
                return null;
            }

            $url = trim($url);

            if (str_starts_with($url, '@')) {
                return null;
            }

            if (preg_match('/^https?:\/\//i', $url)) {
                return $url;
            }

            return 'https://' . ltrim($url, '/');
        };
        $normalizeWhatsappUrl = function (?string $value): ?string {
            if (!filled($value)) {
                return null;
            }

            $value = trim($value);
            if (preg_match('/^https?:\/\//i', $value)) {
                return $value;
            }

            $digits = preg_replace('/\D+/', '', $value);

            return $digits ? 'https://wa.me/' . $digits : null;
        };

        $socialLinksFor = function ($targetShop) use ($normalizeSocialUrl, $normalizeWhatsappUrl) {
            $social = optional($targetShop?->social);

            return collect([
                [
                    'title' => __('فيسبوك'),
                    'icon' => 'fab fa-facebook-f',
                    'url' => $normalizeSocialUrl($social->facebook),
                ],
                ['title' => __('تويتر'), 'icon' => 'fab fa-twitter', 'url' => $normalizeSocialUrl($social->twitter)],
                [
                    'title' => __('انستجرام'),
                    'icon' => 'fab fa-instagram',
                    'url' => $normalizeSocialUrl($social->instagram),
                ],
                ['title' => __('تيك توك'), 'icon' => 'fab fa-tiktok', 'url' => $normalizeSocialUrl($social->tiktok)],
                ['title' => __('تلجرام'), 'icon' => 'fab fa-telegram', 'url' => $normalizeSocialUrl($social->telegram)],
                ['title' => __('يوتيوب'), 'icon' => 'fab fa-youtube', 'url' => $normalizeSocialUrl($social->youtube)],
                [
                    'title' => __('سناب شات'),
                    'icon' => 'fab fa-snapchat',
                    'url' => $normalizeSocialUrl($social->snapchat),
                ],
                [
                    'title' => __('واتساب'),
                    'icon' => 'fab fa-whatsapp',
                    'url' => $normalizeWhatsappUrl($social->whatsapp ?: $targetShop?->whatsapp),
                ],
            ])
                ->filter(fn($item) => filled($item['url']))
                ->values();
        };

        $ozmanSocialLinks = $socialLinksFor($ozmanShop);
        $shopSocialLinks = $socialLinksFor($shop);
        $mediaUrl = function (?string $path): string {
            if (!filled($path)) {
                return '';
            }

            if (preg_match('/^https?:\/\//i', $path)) {
                return $path;
            }

            return asset($path);
        };
        $youtubeEmbedUrl = function (?string $url): string {
            if (!filled($url)) {
                return '';
            }

            if (
                preg_match(
                    '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]+)/',
                    $url,
                    $matches,
                )
            ) {
                return 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=0&mute=1&playsinline=1&rel=0&enablejsapi=1';
            }

            return $url;
        };
        $topOzmanScreens = collect($ozmanScreens ?? [])
            ->filter(fn($item) => ($item->placement ?? 'top') !== 'bottom')
            ->values();
        $bottomOzmanScreens = collect($ozmanScreens ?? [])
            ->filter(fn($item) => ($item->placement ?? 'top') === 'bottom')
            ->values();
        $ozmanDisplayItems = $topOzmanScreens
            ->merge($ozmanAdvertisements ?? [])
            ->filter(fn($item) => filled($item->media))
            ->values();
        $shopDisplaySource = collect($shop?->advertisements ?? []);
        if ($shop?->id && $ozmanShop?->id && (int) $shop->id === (int) $ozmanShop->id) {
            $shopDisplaySource = $bottomOzmanScreens->merge($shopDisplaySource);
        }
        $shopDisplayItems = $shopDisplaySource->filter(fn($item) => filled($item->media))->values();
        $authenticatedMerchantShop = auth()->user()?->isShopOwner()
            ? auth()
                ->user()
                ->shops()
                ->where('is_active', true)
                ->with(['distributor', 'distributorMarketer.distributor'])
                ->first()
            : null;
        $authenticatedMerchantDistributor =
            $authenticatedMerchantShop?->distributor ?: $authenticatedMerchantShop?->distributorMarketer?->distributor;
        $authenticatedMerchantWhatsapp = preg_replace(
            '/\D+/',
            '',
            $authenticatedMerchantDistributor?->whatsapp ?: $authenticatedMerchantDistributor?->phone ?: '',
        );
        $authenticatedMerchantPayload = $authenticatedMerchantShop
            ? [
                'authenticated' => true,
                'shop_id' => $authenticatedMerchantShop->id,
                'shop_name' => $authenticatedMerchantShop->name,
                'distributor_id' => $authenticatedMerchantDistributor?->id,
                'distributor_name' => $authenticatedMerchantDistributor?->name,
                'whatsapp_number' => $authenticatedMerchantWhatsapp,
                'customer_name' => auth()->user()?->name ?: $authenticatedMerchantShop->name,
                'customer_phone' => auth()->user()?->phone ?: $authenticatedMerchantShop->phone,
                'customer_whatsapp' => $authenticatedMerchantShop->whatsapp ?: $authenticatedMerchantShop->phone,
                'customer_address' => $authenticatedMerchantShop->address,
                'customer_latitude' => $authenticatedMerchantShop->latitude,
                'customer_longitude' => $authenticatedMerchantShop->longitude,
                'customer_map_link' =>
                    $authenticatedMerchantShop->latitude !== null && $authenticatedMerchantShop->longitude !== null
                        ? 'https://www.google.com/maps?q=' .
                            $authenticatedMerchantShop->latitude .
                            ',' .
                            $authenticatedMerchantShop->longitude
                        : null,
            ]
            : null;
    @endphp

    @unless ($isDashboardPreview ?? false)
        <header>
            <div class="header-right-group">
                <div class="logo-container">
                    <div class="logo">
                        <img src="{{ $ozmanLogo }}" alt="{{ $ozmanName }} Logo" class="logo-img">
                        <div class="logo-dropdown">
                            @include('front.logo_dropdown', [
                                'agents' => $ozmanShop?->agents ?? collect(),
                                'distributors' => $ozmanShop?->distributors ?? collect(),
                                'shopLogo' => $ozmanLogo,
                                'shopName' => $ozmanName,
                            ])
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <div class="location-btn location-btn-trigger">
                            <i class="fas fa-map-marker-alt"></i> {{ __('حدد موقعك') }}
                        </div>
                        <div class="language-switcher">
                            <button type="button" class="location-btn language-switcher-btn">
                                <i class="fas fa-globe"></i> {{ $languageLabels[$locale] ?? 'Language' }}
                            </button>
                            <div class="language-switcher-menu">
                                @foreach ($languageLabels as $languageCode => $languageName)
                                    <a href="{{ route('lang.switch', $languageCode) }}"
                                        class="{{ $locale === $languageCode ? 'active' : '' }}">{{ $languageName }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Social icons -->
                <div class="social-icons-vertical">
                    @forelse($ozmanSocialLinks as $socialLink)
                        <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" class="social-icon"
                            title="{{ $socialLink['title'] }}" aria-label="{{ $socialLink['title'] }}">
                            <i class="{{ $socialLink['icon'] }}"></i>
                        </a>
                    @empty
                        <span class="social-icon social-icon-muted" title="{{ __('لا توجد روابط تواصل') }}">
                            <i class="fas fa-share-nodes"></i>
                        </span>
                    @endforelse
                </div>
            </div>

            <div class="display-screen glass header-display-screen">
                @if ($ozmanDisplayItems->isNotEmpty())
                    <div class="media-story-slider" data-media-story>
                        @foreach ($ozmanDisplayItems as $item)
                            <article class="media-story-slide {{ $loop->first ? 'active' : '' }}"
                                data-duration="{{ max((int) ($item->duration ?? 8), 1) * 1000 }}">
                                @if ($item->type === 'video')
                                    <video src="{{ $mediaUrl($item->media) }}" muted playsinline loop></video>
                                @elseif($item->type === 'youtube')
                                    <iframe src="{{ $youtubeEmbedUrl($item->media) }}" title="{{ $item->title }}"
                                        allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                                @else
                                    <img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title }}">
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="story-slider">
                        <span class="welcome-msg">{{ $ozmanWelcomeText }}</span>
                        <span class="welcome-msg">{{ $ozmanWelcomeText }}</span>
                    </div>
                @endif
            </div>
        </header>

        <main>
            <!-- Top Products Carousel Section -->
            <section class="carousel-3d-section animate">
                <h2
                    style="color: var(--primary-color); margin-bottom: 20px; font-weight: 900; text-shadow: 0 0 10px var(--primary-color);">
                    {{ __('فئات Ozman') }}</h2>
                <div class="carousel-3d-container" id="carouselProducts">
                    @forelse($ozmanCategories as $category)
                        @php($categoryTitle = $category->localized('name'))
                        <div class="carousel-item-3d prod-item" data-index="{{ $loop->index }}"
                            data-ozman-category="{{ $categoryTitle }}" data-product-name="{{ $categoryTitle }}">
                            <div class="card-3d">
                                <img src="{{ $category->image ? asset($category->image) : asset('images/logo.svg') }}"
                                    alt="{{ $categoryTitle }}">
                            </div>
                            <span>{{ $categoryTitle }}</span>
                        </div>
                    @empty
                        <div class="carousel-item-3d prod-item" data-index="0" data-product-name="Ozman">
                            <div class="card-3d"><img src="{{ asset('images/logo.svg') }}" alt="Ozman"></div>
                            <span>Ozman</span>
                        </div>
                    @endforelse
                </div>
            </section>

            <hr class="section-divider">
        @else
            <main>
            @endunless

            <!-- Infinite Vertical Carousel Section -->
            <header>
                <div class="header-right-group">
                    <div class="logo-container">
                        <div class="logo">
                            <img src="{{ $shopLogo }}" alt="{{ $shopName }} Logo" class="logo-img"
                                id="activeShopLogo">
                            <div class="logo-dropdown" id="activeShopLogoDropdown">
                                @include('front.logo_dropdown', [
                                    'agents' => $shop?->agents ?? collect(),
                                    'distributors' => $shop?->distributors ?? collect(),
                                    'shopLogo' => $shopLogo,
                                    'shopName' => $shopName,
                                ])
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <div class="location-btn location-btn-trigger">
                                <i class="fas fa-map-marker-alt"></i> {{ __('حدد موقعك') }}
                            </div>
                            <div class="language-switcher">
                                <button type="button" class="location-btn language-switcher-btn">
                                    <i class="fas fa-globe"></i> {{ $languageLabels[$locale] ?? 'Language' }}
                                </button>
                                <div class="language-switcher-menu">
                                    @foreach ($languageLabels as $languageCode => $languageName)
                                        <a href="{{ route('lang.switch', $languageCode) }}"
                                            class="{{ $locale === $languageCode ? 'active' : '' }}">{{ $languageName }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Social icons -->
                    <div class="social-icons-vertical" id="activeShopSocials">
                        @forelse($shopSocialLinks as $socialLink)
                            <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer"
                                class="social-icon" title="{{ $socialLink['title'] }}"
                                aria-label="{{ $socialLink['title'] }}">
                                <i class="{{ $socialLink['icon'] }}"></i>
                            </a>
                        @empty
                            <span class="social-icon social-icon-muted" title="{{ __('لا توجد روابط تواصل') }}">
                                <i class="fas fa-share-nodes"></i>
                            </span>
                        @endforelse
                    </div>
                </div>

                <div class="display-screen glass header-display-screen" id="activeShopDisplay"
                    data-empty-text-template="{{ __('أهلا بك في :shop - اكتشف أقسام ومنتجات المتجر') }}">
                    @if ($shopDisplayItems->isNotEmpty())
                        <div class="media-story-slider" data-media-story>
                            @foreach ($shopDisplayItems as $item)
                                <article class="media-story-slide {{ $loop->first ? 'active' : '' }}"
                                    data-duration="{{ max((int) ($item->duration ?? 8), 1) * 1000 }}">
                                    @if ($item->type === 'video')
                                        <video src="{{ $mediaUrl($item->media) }}" muted playsinline loop></video>
                                    @elseif($item->type === 'youtube')
                                        <iframe src="{{ $youtubeEmbedUrl($item->media) }}"
                                            title="{{ $item->title }}"
                                            allow="autoplay; encrypted-media; picture-in-picture"
                                            allowfullscreen></iframe>
                                    @else
                                        <img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title }}">
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="story-slider">
                            <span class="welcome-msg">{{ $welcomeText }}</span>
                            <span class="welcome-msg">{{ $welcomeText }}</span>
                        </div>
                    @endif
                </div>
            </header>


            <hr class="section-divider">

            <!-- Radial Category Selection Section -->
            @include('front.shop_stories')
            <nav id="shopSectionNavigation" class="shop-section-navigation" aria-label="{{ __('أقسام المتاجر') }}" hidden>
                <strong id="shopSectionNavigationTitle" aria-live="polite"></strong>
                <button type="button" id="backToShopSections" aria-controls="sideVTrack">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    {{ __('عودة للأقسام') }}
                </button>
            </nav>
            <section class="radial-section animate" style="margin-right: 60px; margin-bottom: 60px;">
                <div class="side-nav-vertical v-carousel-container" id="sideVCarousel">
                    <div class="side-circles-list v-carousel-track" id="sideVTrack">
                        <!-- Items will be generated by JS -->
                    </div>
                </div>

                <div class="radial-container" style="position: relative;">
                    <div class="shop-directions-panel" id="shopDirectionsPanel" hidden>
                        <div>
                            <span data-directions-label>{{ __('الوصول إلى المحل') }}</span>
                            <strong id="shopDirectionsTitle">{{ __('اختر المحل لعرض الاتجاهات') }}</strong>
                        </div>
                        <a href="#" target="_blank" rel="noopener noreferrer" id="shopDirectionsLink">
                            <i class="fas fa-map-location-dot"></i>
                            <span data-directions-link-text>{{ __('انقر فوق الخارطة للوصول للمحل عبر GPS') }}</span>
                        </a>
                    </div>

                    <!-- Floating Info/Header Panel when showing scattered products -->
                    <div class="products-scatter-header" id="productsScatterHeader"
                        style="display: none; flex-direction: column; gap: 15px; width: 100%; position: absolute; top: -10px; left: 0; padding: 0 40px; z-index: 30; direction: rtl;">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h3 id="productsScatterTitle"
                                style="padding: 12px 30px; border-radius: 20px; color: var(--primary-color); border: 1px solid var(--glass-border); background: rgba(0,0,0,0.85); margin: 0; font-size: 1.4rem; font-weight: 900; box-shadow: 0 0 15px rgba(0, 229, 255, 0.2);">
                                {{ __('اسم القسم') }}</h3>
                            <button class="back-btn" id="backToDeptsBtn" style="direction: rtl;">
                                <i class="fas fa-chevron-right"></i>
                                {{ __('عودة للأقسام') }}
                            </button>
                        </div>
                        <!-- Product summary -->
                        <div id="productsScatterDesc"
                            style="display: none; width: max-content; max-width: 80%; padding: 10px 25px; border-radius: 15px; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.08); color: rgba(255,255,255,0.85); font-size: 0.95rem; line-height: 1.5; box-shadow: 0 5px 15px rgba(0,0,0,0.2); animation: fadeInUp 0.4s ease; text-align: right;">
                        </div>
                    </div>

                    <div class="watch-grid-wrapper" id="watchGridWrapper" style="width: 100%; height: 650px;">
                        <div class="watch-grid-container" id="watchGridContainer">
                            <div class="watch-grid-track" id="watchGridTrack">
                                <!-- Items will be generated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                @unless ($isDashboardPreview ?? false)
                    <div class="purchase-wheels-side v-carousel-container" id="purchaseWheelsCarousel"
                        aria-label="{{ __('عجلات جوائز الشراء') }}" hidden>
                        <div class="purchase-wheels-track v-carousel-track" id="purchaseWheelsTrack"></div>
                    </div>
                @endunless
            </section>
        </main>

        <nav class="bottom-nav">
            <div class="nav-icons">
                <div class="nav-btn" id="navHomeBtn" title="{{ __('الرئيسية') }}"><i class="fas fa-home"></i></div>
                <div class="nav-btn" id="navSearchBtn" title="{{ __('البحث') }}"><i class="fas fa-search"></i>
                </div>
                <div class="nav-btn" id="navCartBtn" title="{{ __('سلة المشتريات') }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count-badge" id="cartCountBadge">0</span>
                </div>
                <!-- Chatbot and WhatsApp actions -->
                <div class="nav-btn" id="chatbotToggleBtn" title="{{ __('المساعد الذكي') }}"
                    style="position: relative;">
                    <i class="fas fa-comments"></i>
                    <span
                        style="position: absolute; top: -3px; right: -3px; width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; box-shadow: 0 0 5px var(--primary-color);"></span>
                </div>
                <div class="nav-btn" id="raffleCardOpenBtn" title="{{ __('بطاقات السحب') }}">
                    <i class="fas fa-ticket"></i>
                </div>
                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $shop?->whatsapp ?: $shop?->phone ?: '970599000000') }}"
                    target="_blank" class="nav-btn" id="whatsappQuickBtn"
                    title="{{ __('تواصل مباشرة عبر واتساب') }}"
                    style="color: #25d366; text-decoration: none; display: flex; align-items: center;">
                    <i class="fab fa-whatsapp"></i>
                </a>
                @if ($authenticatedMerchantShop)
                    <form method="POST" action="{{ route('logout') }}" style="display:flex">
                        @csrf
                        <button type="submit" class="nav-btn" title="{{ __('تسجيل خروج صاحب المتجر') }}"
                            style="border:0;background:none;color:#00e5ff">
                            <i class="fas fa-store-circle-xmark"></i>
                        </button>
                    </form>
                @else
                    <a href="{{ route('merchant.login', ['redirect' => request()->getRequestUri()]) }}"
                        class="nav-btn" title="{{ __('دخول صاحب المتجر') }}"
                        style="color:#00e5ff;text-decoration:none;display:flex;align-items:center">
                        <i class="fas fa-store"></i>
                    </a>
                @endif
            </div>
            <div class="buy-btn" id="customerLoginOpenBtn">{{ __('اطلب الآن') }}</div>
        </nav>

        <div class="front-search-panel" id="frontSearchPanel" aria-hidden="true">
            <div class="front-search-card">
                <div class="front-search-head">
                    <div class="front-search-title">
                        <i class="fas fa-search"></i>
                        <span>{{ __('بحث سريع') }}</span>
                    </div>
                    <button type="button" class="front-search-close" id="frontSearchClose"
                        aria-label="{{ __('إغلاق البحث') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <label class="front-search-input-wrap" for="frontSearchInput">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" id="frontSearchInput"
                        placeholder="{{ __('ابحث باسم المنتج أو المحل...') }}" autocomplete="off">
                </label>

                <div class="front-search-results" id="frontSearchResults">
                    <div class="front-search-empty">{{ __('اكتب اسم المنتج أو المحل لعرض النتائج') }}</div>
                </div>
            </div>
        </div>

        <div class="cart-panel" id="cartPanel" aria-hidden="true">
            <div class="cart-card">
                <div class="cart-head">
                    <div>
                        <div class="cart-kicker">{{ __('سلة المشتريات') }}</div>
                        <h3>{{ __('طلباتك الحالية') }}</h3>
                    </div>
                    <button type="button" class="cart-close" id="cartCloseBtn"
                        aria-label="{{ __('إغلاق السلة') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="cart-items" id="cartItems">
                    <div class="cart-empty">{{ __('السلة فارغة حاليا') }}</div>
                </div>

                <div class="cart-promo" id="cartPromoBox" hidden>
                    <i class="fas fa-tags"></i>
                    <span
                        id="cartPromoText">{{ __('أضف منتج آخر إلى السلة واحصل على خصم 5% من المبلغ الإجمالي') }}</span>
                </div>

                <div class="cart-summary">
                    <div>
                        <span>{{ __('عدد المنتجات') }}</span>
                        <strong id="cartItemsCount">0</strong>
                    </div>
                    <div>
                        <span>{{ __('الخصم') }}</span>
                        <strong id="cartDiscount">0</strong>
                    </div>
                    <div>
                        <span>{{ __('المجموع') }}</span>
                        <strong id="cartTotal">0</strong>
                    </div>
                </div>

                <div class="cart-actions">
                    <button type="button" class="cart-clear-btn" id="cartClearBtn">{{ __('تفريغ السلة') }}</button>
                    <button type="button" class="cart-checkout-btn" id="cartCheckoutBtn">
                        <i class="fab fa-whatsapp"></i>
                        {{ __('إتمام الطلب') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="unit-choice-modal" id="unitChoiceModal" aria-hidden="true">
            <div class="unit-choice-card">
                <button type="button" class="unit-choice-close" id="unitChoiceClose"
                    aria-label="{{ __('إغلاق') }}">
                    <i class="fas fa-times"></i>
                </button>
                <div class="unit-choice-head">
                    <div class="unit-choice-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <div>
                        <div class="unit-choice-kicker">{{ __('اختيار طريقة الإضافة') }}</div>
                        <h3 id="unitChoiceTitle">{{ __('إضافة المنتج للسلة') }}</h3>
                        <p>{{ __('اختر نوع السعر المناسب قبل إضافة المنتج إلى السلة') }}</p>
                    </div>
                </div>
                <div class="unit-choice-options" id="unitChoiceOptions"></div>
                <div class="unit-choice-actions">
                    <button type="button" class="cart-clear-btn" id="unitChoiceCancel">{{ __('إلغاء') }}</button>
                </div>
            </div>
        </div>

        @unless ($isDashboardPreview ?? false)
            <div class="modal-overlay visitor-registration-modal" id="visitorRegistrationModal" aria-hidden="true">
                <div class="visitor-registration-card glass">
                    <div class="visitor-registration-head">
                        <span>{{ __('أهلا بك في Ozman') }}</span>
                        <h3>{{ __('سجل دخولك لتحصل على خصمك الأول') }}</h3>
                    </div>

                    <div class="visitor-type-picker" role="group" aria-label="{{ __('نوع التسجيل') }}">
                        <button type="button" class="visitor-type-btn active" data-visitor-type="customer">
                            <i class="fas fa-user"></i>
                            <strong>{{ __('عميل') }}</strong>
                        </button>
                        <button type="button" class="visitor-type-btn" data-visitor-type="merchant">
                            <i class="fas fa-store"></i>
                            <strong>{{ __('صاحب متجر') }}</strong>
                        </button>
                    </div>

                    <form class="visitor-registration-form" id="visitorRegistrationForm">
                        <input type="hidden" name="type" id="visitorTypeInput" value="customer">

                        <label class="customer-field">
                            <span>{{ __('الاسم') }}</span>
                            <input type="text" name="name" placeholder="{{ __('اكتب اسمك') }}" required>
                        </label>

                        <label class="customer-field">
                            <span>{{ __('رقم الهاتف') }}</span>
                            <input type="tel" name="phone" placeholder="05xxxxxxxx" inputmode="tel"
                                maxlength="16"
                                pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                                title="{{ __('أدخل رقم جوال صحيح مثل 0591234567') }}" dir="ltr" required>
                        </label>

                        <div class="visitor-merchant-fields" id="visitorMerchantFields" hidden>
                            <label class="customer-field">
                                <span>{{ __('اسم المتجر') }}</span>
                                <input type="text" name="shop_name" placeholder="{{ __('اسم المحل أو المتجر') }}">
                            </label>

                            <label class="customer-field">
                                <span>{{ __('الملف الضريبي') }}</span>
                                <input type="text" name="tax_file"
                                    placeholder="{{ __('رقم أو اسم الملف الضريبي') }}">
                            </label>

                            <label class="customer-field">
                                <span>{{ __('اللوكيشن الخاص بالمحل') }}</span>
                                <input type="hidden" name="business_location" id="visitorBusinessLocation">
                                <div class="visitor-location-row">
                                    <button type="button" class="customer-map-btn"
                                        id="detectVisitorBusinessLocationBtn">
                                        <i class="fas fa-crosshairs"></i>
                                        {{ __('حدد لوكيشن المحل') }}
                                    </button>
                                    <span
                                        id="visitorBusinessLocationStatus">{{ __('اضغط لتحديد لوكيشن المحل من الخريطة') }}</span>
                                </div>
                                <iframe class="visitor-location-map" id="visitorBusinessMapFrame"
                                    title="{{ __('لوكيشن المحل') }}" loading="lazy" hidden></iframe>
                            </label>
                        </div>

                        <label class="customer-field">
                            <span>{{ __('مكان السكن') }}</span>
                            <textarea name="residence_address" rows="2" placeholder="{{ __('المدينة، الحي، أقرب علامة') }}" required></textarea>
                        </label>

                        <input type="hidden" name="latitude" id="visitorLatitude">
                        <input type="hidden" name="longitude" id="visitorLongitude">
                        <input type="hidden" name="map_link" id="visitorMapLink">

                        <div class="visitor-registration-message" id="visitorRegistrationMessage" aria-live="polite">
                        </div>

                        <div class="merchant-approval-notice" id="merchantApprovalNotice" hidden>
                            <i class="fas fa-shield-check"></i>
                            <span>{{ __('بعد حفظ البيانات سيتم فتح واتساب لإرسال طلبك. سنقوم بالتحقق من حساب صاحب المتجر، ولن تتمكن من الشراء حتى نقبل الطلب.') }}</span>
                        </div>

                        <a class="cart-checkout-btn" id="merchantApprovalWhatsapp" href="#" target="_blank"
                            rel="noopener noreferrer" hidden>
                            <i class="fab fa-whatsapp"></i>
                            {{ __('فتح واتساب لاعتماد حساب صاحب المتجر') }}
                        </a>

                        <button type="submit" class="cart-checkout-btn visitor-submit-btn">
                            <i class="fas fa-check"></i>
                            {{ __('حفظ ومتابعة') }}
                        </button>
                    </form>
                </div>
            </div>
        @endunless

        @unless ($isDashboardPreview ?? false)
            <div class="modal-overlay reward-wheel-modal" id="rewardWheelModal" aria-hidden="true">
                <div class="reward-wheel-card glass">
                    <div class="reward-wheel-head">
                        <span>{{ __('خصمك الأول') }}</span>
                        <h3 id="rewardWheelTitle">
                            {{ $customerSignupWheel['title'] ?? __('لف العجلة واحصل على خصمك الأول') }}</h3>
                    </div>

                    <div class="reward-wheel-stage">
                        <div class="reward-wheel-pointer"></div>
                        <button type="button" class="reward-wheel" id="rewardWheelSpinBtn"
                            aria-label="{{ __('لف عجلة الخصم') }}">
                            <div class="reward-wheel-labels" id="rewardWheelLabels"></div>
                            <span class="reward-wheel-center">{{ __('لف') }}</span>
                        </button>
                    </div>

                    <p class="reward-wheel-note">{{ __('اضغط على العجلة مرة واحدة لتعرف خصمك الأول.') }}</p>
                </div>
            </div>

            <div class="modal-overlay reward-result-modal" id="rewardResultModal" aria-hidden="true">
                <div class="reward-result-card glass">
                    <div class="reward-result-circle">
                        <i class="fas fa-gift"></i>
                        <img src="" alt="" class="reward-result-image" id="rewardResultImage" hidden>
                        <span>{{ __('مبروك') }}</span>
                        <strong id="rewardResultText">{{ __('حصلت على خصمك الأول') }}</strong>
                    </div>

                    <button type="button" class="cart-checkout-btn reward-result-btn" id="closeRewardResultBtn">
                        <i class="fas fa-cart-shopping"></i>
                        {{ __('متابعة التسوق') }}
                    </button>
                    <button type="button" class="cart-checkout-btn reward-result-btn" id="sendRewardGiftBtn" hidden>
                        <i class="fab fa-whatsapp"></i>
                        {{ __('أرسل الجائزة للمتجر لتثبيتها مع طلبك') }}
                    </button>
                    <div class="reward-social-follow" id="rewardSocialFollow" hidden>
                        <strong>{{ __('تابع صفحات Ozman وادخل السحب على جوائز البثوث المباشرة.') }}</strong>
                        <div class="reward-social-links" id="rewardSocialLinks"></div>
                    </div>
                </div>
            </div>
        @endunless

        <div class="modal-overlay customer-login-modal" id="customerLoginModal" aria-hidden="true">
            <div class="modal-content glass customer-login-card">
                <div class="modal-header">
                    <h3><i class="fas fa-user-check"></i> {{ __('تسجيل بيانات العميل') }}</h3>
                    <span class="close-modal" id="closeCustomerLoginModal">&times;</span>
                </div>

                <form class="customer-login-form" id="customerLoginForm">
                    <div class="customer-fields-grid">
                        <label class="customer-field">
                            <span>{{ __('اسم العميل') }}</span>
                            <input type="text" id="customerName" name="name"
                                placeholder="{{ __('اكتب اسمك') }}" required>
                        </label>

                        <label class="customer-field">
                            <span>{{ __('رقم الهاتف') }}</span>
                            <input type="tel" id="customerPhone" name="phone" placeholder="05xxxxxxxx"
                                inputmode="tel" maxlength="16"
                                pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                                title="{{ __('أدخل رقم جوال صحيح مثل 0591234567') }}" dir="ltr" required>
                        </label>

                        <label class="customer-field">
                            <span>{{ __('رقم الواتس اب') }}</span>
                            <input type="tel" id="customerWhatsapp" name="whatsapp"
                                placeholder="{{ __('رقم واتساب للتواصل') }}" inputmode="tel" maxlength="16"
                                pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                                title="{{ __('أدخل رقم واتساب صحيح مثل 0591234567') }}" dir="ltr" required>
                        </label>

                        <label class="customer-field">
                            <span>{{ __('العنوان / اللوكيشن') }}</span>
                            <textarea id="customerAddress" name="address" rows="3"
                                placeholder="{{ __('اكتب المدينة، الحي، أقرب علامة') }}"></textarea>
                        </label>
                    </div>

                    <div class="customer-map-box">
                        <div class="customer-map-head">
                            <div>
                                <strong>{{ __('تحديد الموقع على الخريطة') }}</strong>
                                <span
                                    id="customerLocationStatus">{{ __('اضغط على زر تحديد موقعي لاختيار موقعك الحالي.') }}</span>
                            </div>
                            <button type="button" class="customer-map-btn" id="detectCustomerLocationBtn">
                                <i class="fas fa-crosshairs"></i>
                                {{ __('حدد موقعي') }}
                            </button>
                        </div>
                        <iframe id="customerMapFrame" src="about:blank" width="100%" height="230"
                            style="border:0;" loading="lazy" allowfullscreen></iframe>
                        <input type="hidden" id="customerLatitude" name="latitude">
                        <input type="hidden" id="customerLongitude" name="longitude">
                        <input type="hidden" id="customerMapLink" name="map_link">
                    </div>

                    <div class="customer-login-actions">
                        <button type="button" class="cart-clear-btn" id="saveCustomerOnlyBtn">
                            <i class="fas fa-credit-card"></i>
                            {{ __('الدفع الفوري') }}
                        </button>
                        <button type="submit" class="cart-checkout-btn">
                            <i class="fab fa-whatsapp"></i>
                            {{ __('إرسال الطلب') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay instant-payment-modal" id="instantPaymentModal" aria-hidden="true">
            <div class="modal-content glass instant-payment-card">
                <div class="modal-header">
                    <h3><i class="fas fa-wallet"></i> {{ __('الدفع الفوري') }}</h3>
                    <span class="close-modal" id="closeInstantPaymentModal">&times;</span>
                </div>

                <div class="instant-payment-summary">
                    <div>
                        <span>{{ __('إجمالي الطلب') }}</span>
                        <strong id="instantPaymentTotal">0 {{ __('شيكل') }}</strong>
                    </div>
                    <div>
                        <span>{{ __('عدد المنتجات') }}</span>
                        <strong id="instantPaymentCount">0</strong>
                    </div>
                </div>

                <form class="instant-payment-form" id="instantPaymentForm">
                    <div class="shop-payment-details">
                        <div class="shop-payment-head">
                            <i class="fas fa-building-columns"></i>
                            <div>
                                <span>{{ __('حساب الدفع الخاص بالمتجر') }}</span>
                                <strong>{{ $shopName }}</strong>
                            </div>
                        </div>

                        @if ($hasShopPaymentDetails)
                            <div class="shop-payment-grid">
                                <div>
                                    <span>{{ __('طريقة الدفع') }}</span>
                                    <strong>{{ $shopPaymentDetails['method_label'] ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>{{ __('البنك أو مزود الدفع') }}</span>
                                    <strong>{{ $shopPaymentDetails['provider'] ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>{{ __('اسم صاحب الحساب') }}</span>
                                    <strong>{{ $shopPaymentDetails['account_holder'] ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>{{ __('رقم الحساب') }}</span>
                                    <strong
                                        dir="ltr">{{ $shopPaymentDetails['account_number'] ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>IBAN</span>
                                    <strong dir="ltr">{{ $shopPaymentDetails['iban'] ?: '-' }}</strong>
                                </div>
                                <div>
                                    <span>{{ __('رقم المحفظة') }}</span>
                                    <strong dir="ltr">{{ $shopPaymentDetails['wallet_number'] ?: '-' }}</strong>
                                </div>
                                @if ($shopPaymentDetails['notes'])
                                    <div class="shop-payment-notes">
                                        <span>{{ __('ملاحظات الدفع') }}</span>
                                        <strong>{{ $shopPaymentDetails['notes'] }}</strong>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="payment-paypal-note">
                                <i class="fas fa-circle-info"></i>
                                {{ __('لم يتم إضافة معلومات دفع لهذا المتجر بعد. سيتم إرسال الطلب للمتجر للتواصل معك.') }}
                            </div>
                        @endif
                    </div>

                    <div class="customer-login-actions">
                        <button type="button" class="cart-clear-btn"
                            id="backToCustomerDataBtn">{{ __('رجوع للبيانات') }}</button>
                        <button type="submit" class="cart-checkout-btn">
                            <i class="fas fa-lock"></i>
                            {{ __('تأكيد الدفع') }} 
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay raffle-card-modal" id="raffleCardModal" aria-hidden="true">
            <div class="raffle-card-panel glass">
                <button type="button" class="raffle-card-close" id="raffleCardCloseBtn"
                    aria-label="{{ __('إغلاق') }}">
                    <i class="fas fa-times"></i>
                </button>
                <div class="raffle-card-head">
                    <div class="raffle-card-icon"><i class="fas fa-ticket"></i></div>
                    <div>
                        <span>{{ __('بطاقات السحب') }}</span>
                        <h3>{{ __('تحقق من رقم بطاقتك') }}</h3>
                        <p>{{ __('اكتب رقم البطاقة أو امسح QR الموجود عليها لمعرفة نتيجة السحب الفوري.') }}</p>
                    </div>
                </div>
                <form class="raffle-card-form" id="raffleCardForm">
                    <label for="raffleCardNumber">{{ __('رقم البطاقة') }}</label>
                    <input type="text" inputmode="numeric" maxlength="6" pattern="\d{6}" id="raffleCardNumber"
                        name="card_number" placeholder="000000" dir="ltr" required>
                    <button type="submit" class="cart-checkout-btn">
                        <i class="fas fa-magnifying-glass"></i>
                        {{ __('تحقق من البطاقة') }}
                    </button>
                    <button type="button" class="cart-checkout-btn raffle-scan-btn" id="raffleCardScanBtn">
                        <i class="fas fa-qrcode"></i>
                        {{ __('مسح البطاقة بالكاميرا') }}
                    </button>
                </form>
                <div class="raffle-scanner" id="raffleScanner" hidden>
                    <div class="raffle-scanner-frame">
                        <video id="raffleScannerVideo" playsinline muted></video>
                        <span class="raffle-scan-corner corner-1"></span>
                        <span class="raffle-scan-corner corner-2"></span>
                        <span class="raffle-scan-corner corner-3"></span>
                        <span class="raffle-scan-corner corner-4"></span>
                    </div>
                    <div class="raffle-scanner-actions">
                        <span id="raffleScannerStatus">{{ __('وجه الكاميرا نحو QR البطاقة') }}</span>
                        <button type="button" id="raffleScannerStopBtn">{{ __('إيقاف المسح') }}</button>
                    </div>
                </div>
                <div class="raffle-card-result" id="raffleCardResult" hidden></div>
            </div>
        </div>

        <script>
            window.OZMAN_FRONT_DATA = @json($frontData);
            window.OZMAN_FRONT_CONFIG = {
                shopId: @json($shop?->id),
                shopWhatsapp: @json(preg_replace('/\D+/', '', $shop?->whatsapp ?: $shop?->phone ?: '970599000000')),
                customerLoginUrl: @json(route('customer.login')),
                csrfRefreshUrl: @json(route('csrf.refresh')),
                visitorRegistrationUrl: @json(route('visitor-registrations.store')),
                visitorRegistrationStatusUrlTemplate: @json(url('/visitor-registrations/status/__TOKEN__')),
                hebrewTtsUrl: @json(route('tts.hebrew')),
                arabicTtsUrl: @json(route('tts.arabic')),
                orderStoreUrl: @json(route('front-orders.store')),
                raffleCheckUrl: @json(route('raffle.check')),
                raffleWhatsapp: @json(preg_replace(
                        '/\D+/',
                        '',
                        $raffleSettings['whatsapp'] ?? '' ?: ($shop?->whatsapp ?: $shop?->phone ?: '970599000000'))),
                raffleSocialLinks: @json($ozmanSocialLinks->reject(fn($link) => ($link['icon'] ?? '') === 'fab fa-whatsapp')->values()),
                orderRewardUrlTemplate: @json(url('/front-orders/__ORDER__/reward')),
                orderSpinRewardUrlTemplate: @json(url('/front-orders/__ORDER__/spin-reward')),
                showVisitorRegistration: @json(!($isDashboardPreview ?? false) && !$authenticatedMerchantShop),
                forceVisitorRegistration: {{ in_array(request('type'), ['customer', 'merchant'], true) ? 'true' : 'false' }},
                initialVisitorType: @json(request('type') === 'merchant' ? 'merchant' : 'customer'),
                locale: @json(app()->getLocale()),
                labels: @json($frontLabels),
                payment: @json($shopPaymentDetails),
                rewardWheel: @json($customerSignupWheel),
                purchaseRewardWheels: @json($purchaseRewardWheels ?? []),
                initialPersonContext: @json($initialPersonContext ?? null),
                marketingContext: @json($marketingContext ?? null),
                merchantAccount: @json($authenticatedMerchantPayload),

            };
        </script>
        <script src="{{ route('front.assets', ['file' => 'script.js']) }}?v={{ hash_file('sha256', base_path('public/script.js')) }}"></script>

        <!-- Location Modal -->
        <div class="modal-overlay" id="locationModal">
            <div class="modal-content glass nearest-shops-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-map-marker-alt"></i> {{ __('المتاجر الأقرب إليك') }}</h3>
                    <span class="close-modal" id="closeLocationModal">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="nearest-type-section">
                        <strong>{{ __('شو بدك تلاقي قريب منك؟') }}</strong>
                        <div class="nearest-type-picker" id="nearestShopTypePicker" role="group"
                            aria-label="{{ __('اختر نوع المتجر') }}"></div>
                    </div>
                    <div class="nearest-location-status" id="nearestLocationStatus">
                        {{ __('اختر نوع المتجر أولًا، ثم حدد موقعك لعرض الأماكن الأقرب إليك.') }}
                    </div>
                    <div class="nearest-gps-layout">
                        <div class="nearest-map-card">
                            <iframe id="nearestMapFrame" src="about:blank" title="{{ __('خريطة الوصول للمحل') }}"
                                loading="lazy" allowfullscreen></iframe>
                            <div class="nearest-route-overlay" id="nearestRouteOverlay" hidden>
                                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                    <path class="nearest-route-shadow" d="M18 78 C28 48, 55 58, 82 22"></path>
                                    <path class="nearest-route-line" d="M18 78 C28 48, 55 58, 82 22"></path>
                                </svg>
                                <span class="nearest-route-pin nearest-route-start">
                                    <i class="fas fa-location-crosshairs"></i>
                                    {{ __('موقعك') }}
                                </span>
                                <span class="nearest-route-pin nearest-route-end">
                                    <i class="fas fa-store"></i>
                                    {{ __('المحل') }}
                                </span>
                                <span class="nearest-route-badge"
                                    id="nearestRouteBadge">{{ __('مسار تقريبي') }}</span>
                            </div>
                            <div class="nearest-map-info">
                                <div>
                                    <span>{{ __('المحل المختار') }}</span>
                                    <strong id="nearestSelectedShopTitle">{{ __('اختر محلا من القائمة') }}</strong>
                                    <small
                                        id="nearestSelectedShopMeta">{{ __('سيظهر مسار GPS هنا بعد تحديد موقعك.') }}</small>
                                </div>
                                <a href="#" target="_blank" rel="noopener noreferrer" id="nearestGpsLink">
                                    <i class="fas fa-location-arrow"></i>
                                    {{ __('فتح GPS') }}
                                </a>
                            </div>
                        </div>

                        <div class="nearest-shops-list" id="nearestShopsList"></div>
                    </div>
                    <div class="location-actions">
                        <button class="buy-btn" type="button" style="width: 100%; margin-top: 15px;"
                            id="confirmLocationBtn">
                            <i class="fas fa-crosshairs"></i>
                            {{ __('حدد موقعي واعرض المتاجر') }}
                        </button>
                        <button class="buy-btn nearest-show-shop-btn" type="button" id="nearestShowShopBtn">
                            <i class="fas fa-store"></i>
                            {{ __('عرض أقسام المحل') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Gallery Modal -->
        <div class="product-gallery-modal" id="productGalleryModal">
            <div class="product-modal-card">
                <span class="product-modal-close" id="closeProductModal">&times;</span>

                <!-- Left Side: Interactive Gallery -->
                <div class="product-gallery-container">
                    <div class="main-gallery-view">
                        <img id="modalMainImg" src="" alt="Product Main View">
                    </div>
                    <div class="thumbnails-row" id="modalThumbnailsRow">
                        <!-- Thumbnails generated dynamically by JS -->
                    </div>
                </div>

                <!-- Right Side: Details & Actions -->
                <div class="product-modal-info">
                    <div>
                        <h2 class="product-modal-title" id="modalProductTitle">{{ __('اسم المنتج') }}</h2>
                        <div class="product-modal-price" id="modalProductPrice">75 {{ __('شيكل') }}</div>
                        <div class="product-packaging-prices" id="modalPackagingPrices" hidden></div>
                        <p class="product-modal-description" id="modalProductDesc">
                            {{ __('منتج مميز من متجر Ozman.') }}
                        </p>
                        <ul class="product-features-list">
                            <!-- Will be generated by JS -->
                        </ul>
                    </div>

                    <div class="product-modal-actions">
                        <div class="qty-selector">
                            <button class="qty-btn" id="qtyMinus"><i class="fas fa-minus"></i></button>
                            <span class="qty-val" id="qtyVal">1</span>
                            <button class="qty-btn" id="qtyPlus"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="modal-action-buttons">
                            <button class="modal-btn-cart" id="modalAddToCartBtn">
                                <i class="fas fa-cart-plus"></i>
                                {{ __('أضف إلى السلة') }}
                            </button>
                            <button class="modal-btn-whatsapp" id="modalWhatsappBtn">
                                <i class="fab fa-whatsapp"></i> {{ __('اطلب عبر واتساب') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Glassmorphic Chatbot Widget -->
        <div class="chatbot-widget" id="chatbotWidget">
            <div class="chatbot-header">
                <button class="chatbot-close" id="closeChatbotBtn">
                    <i class="fas fa-chevron-right"></i> {{ __('عودة') }}
                </button>
                <div class="chatbot-info">
                    <div class="chatbot-avatar">
                        <i class="fas fa-robot"></i>
                        <span class="online-indicator"></span>
                    </div>
                    <h4>{{ __('مساعد Ozman الذكي') }}</h4>
                    <p>{{ __('متصل الآن') }}</p>
                </div>
                <!-- Blank div to balance flexbox layout for centered info -->
                <div style="width: 55px;"></div>
            </div>

            <div class="chatbot-messages" id="chatbotMessages">
                <div class="chat-message bot">
                    {{ __('أهلا بك في Ozman! أنا مساعدك الذكي. كيف يمكنني مساعدتك اليوم؟') }}
                </div>
                <!-- Quick Options / Smart Suggestion Tags -->
                <div class="chat-options-container">
                    <button class="chat-option-btn" data-reply="جسم">{{ __('منتجات العناية بالجسم') }}</button>
                    <button class="chat-option-btn" data-reply="شعر">{{ __('منتجات العناية بالشعر') }}</button>
                    <button class="chat-option-btn" data-reply="وجه">{{ __('منتجات العناية بالوجه') }}</button>
                    <button class="chat-option-btn" data-reply="طلب">{{ __('كيف أقوم بالطلب والتوصيل؟') }}</button>
                    <button class="chat-option-btn" data-reply="دعم">{{ __('التحدث مباشرة مع الدعم') }}</button>
                </div>
            </div>

            <div class="chatbot-input-area">
                <button class="imessage-plus-btn"><i class="fas fa-plus"></i></button>
                <div class="imessage-input-wrapper">
                    <input type="text" id="chatbotInput" placeholder="{{ __('اكتب رسالتك') }}"
                        dir="{{ in_array(app()->getLocale(), ['ar', 'he'], true) ? 'rtl' : 'ltr' }}">
                    <button id="chatbotSendBtn" class="chatbot-send-btn">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                </div>
            </div>
        </div>
</body>

</html>
