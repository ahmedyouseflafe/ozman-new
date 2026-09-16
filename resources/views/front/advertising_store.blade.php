@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $copy = match ($locale) {
        'he' => [
            'services' => 'שירותי פרסום ודפוס', 'browse' => 'לכל החנויות', 'sections' => 'קטגוריות שירות',
            'contact' => 'בירור בוואטסאפ', 'call' => 'התקשרו לפרטים', 'quote' => 'מחיר לפי הצעת מחיר',
            'from' => 'החל מ־', 'empty' => 'אין שירותים זמינים כרגע.', 'empty_section' => 'אין כרגע שירותים בקטגוריה זו.',
            'other' => 'שירותים נוספים', 'available' => 'פתוח לפניות', 'min_quantity' => 'מינימום הזמנה',
            'days' => 'ימי עבודה', 'production' => 'זמן הכנה', 'social' => 'רשתות חברתיות',
            'service_hint' => 'שירות מותאם אישית לפי הבקשה שלכם.',
        ],
        'en' => [
            'services' => 'Advertising & print services', 'browse' => 'Browse all stores', 'sections' => 'Service categories',
            'contact' => 'Ask on WhatsApp', 'call' => 'Call for details', 'quote' => 'Price on request',
            'from' => 'From', 'empty' => 'No services are available yet.', 'empty_section' => 'No services in this category yet.',
            'other' => 'Other services', 'available' => 'Open for inquiries', 'min_quantity' => 'Minimum order',
            'days' => 'business days', 'production' => 'Production time', 'social' => 'Social media',
            'service_hint' => 'A custom service tailored to your request.',
        ],
        default => [
            'services' => 'خدمات دعائية وطباعة', 'browse' => 'تصفح جميع المحلات', 'sections' => 'أقسام الخدمات',
            'contact' => 'استفسر عبر واتساب', 'call' => 'اتصل للاستفسار', 'quote' => 'السعر حسب الطلب',
            'from' => 'من', 'empty' => 'لا توجد خدمات متاحة حالياً.', 'empty_section' => 'لا توجد خدمات في هذا القسم حالياً.',
            'other' => 'خدمات أخرى', 'available' => 'متاح للاستفسارات', 'min_quantity' => 'أقل كمية',
            'days' => 'أيام عمل', 'production' => 'مدة التجهيز', 'social' => 'منصات التواصل الاجتماعي',
            'service_hint' => 'خدمة مخصصة حسب طلبك.',
        ],
    };
    $mediaUrl = fn (?string $path) => ! filled($path) ? '' : (
        preg_match('/^https?:\/\//i', $path) ? $path : asset($path)
    );
    $shopImage = $mediaUrl($shop->banner ?: $shop->logo) ?: asset('images/logo.svg');
    $logo = $mediaUrl($shop->logo) ?: asset('images/logo.svg');
    $canonical = route('advertising.store', $shop);
    $description = $shop->description ?: $copy['services'].' — '.$shop->name;
    $categoriesForPage = $categories->map(function ($category) {
        return [
            'key' => (string) $category->id,
            'name' => $category->localized('name'),
            'image' => $category->image ?: $category->products->first(fn ($product) => filled($product->main_image))?->main_image,
            'background' => $category->background_video,
            'products' => $category->products,
        ];
    });
    if ($uncategorizedProducts->isNotEmpty()) {
        $categoriesForPage->push([
            'key' => 'other', 'name' => $copy['other'],
            'image' => $uncategorizedProducts->first(fn ($product) => filled($product->main_image))?->main_image,
            'background' => null, 'products' => $uncategorizedProducts,
        ]);
    }
    $social = $shop->social;
    $contactNumber = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $social?->whatsapp));
    $callNumber = preg_replace('/[^\d+]/', '', (string) $shop->phone);
    $socialProfiles = collect([
        ['label' => 'Facebook', 'icon' => 'ti-brand-facebook', 'value' => $social?->facebook, 'base' => 'https://facebook.com/'],
        ['label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'value' => $social?->instagram, 'base' => 'https://instagram.com/'],
        ['label' => 'TikTok', 'icon' => 'ti-brand-tiktok', 'value' => $social?->tiktok, 'base' => 'https://tiktok.com/@'],
        ['label' => 'YouTube', 'icon' => 'ti-brand-youtube', 'value' => $social?->youtube, 'base' => 'https://youtube.com/@'],
        ['label' => 'Telegram', 'icon' => 'ti-brand-telegram', 'value' => $social?->telegram, 'base' => 'https://t.me/'],
        ['label' => 'Snapchat', 'icon' => 'ti-brand-snapchat', 'value' => $social?->snapchat, 'base' => 'https://snapchat.com/add/'],
    ])->filter(fn ($profile) => filled($profile['value']))->map(function ($profile) {
        $value = trim($profile['value']);
        $profile['url'] = preg_match('/^https?:\/\//i', $value)
            ? $value
            : $profile['base'].ltrim($value, '@/');
        return $profile;
    });
    if ($contactNumber) {
        $socialProfiles->push(['label' => 'WhatsApp', 'icon' => 'ti-brand-whatsapp', 'url' => 'https://wa.me/'.$contactNumber]);
    }
    $hasStories = $shop->stories()->where('expires_at', '>', now())->exists();
    $youtubeEmbed = function (?string $url): ?string {
        if (! preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]+)/', (string) $url, $match)) {
            return null;
        }
        return 'https://www.youtube.com/embed/'.$match[1].'?mute=1&playsinline=1&rel=0';
    };
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    @include('front.partials.seo', [
        'title' => $shop->name.' | Ozman',
        'description' => $description,
        'canonical' => $canonical,
        'image' => $shopImage,
        'schema' => [
            '@context' => 'https://schema.org', '@type' => 'Store', 'name' => $shop->name,
            'url' => $canonical, 'description' => $description, 'image' => $shopImage,
            'telephone' => $shop->phone, 'address' => $shop->address,
        ],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08def4;--green:#27dd86;--bg:#05070a;--card:#10151a;--border:rgba(150,174,190,.18);--muted:#9ca9b4}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:radial-gradient(circle at 85% 5%,rgba(8,222,244,.11),transparent 27%),radial-gradient(circle at 8% 30%,rgba(101,42,255,.1),transparent 25%),var(--bg);color:#fff;font-family:Cairo,Arial,sans-serif}button{font:inherit}
        .shell{width:min(1380px,calc(100% - 32px));margin:auto;padding:20px 0 55px}
        .restaurant-hero-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(310px,380px);align-items:stretch;gap:16px;direction:ltr}
        .restaurant-display-screen,.hero{position:relative;min-width:0;min-height:310px;overflow:hidden;border:1px solid var(--border);border-radius:28px;box-shadow:0 20px 60px rgba(0,0,0,.28)}
        .restaurant-display-screen{background:linear-gradient(145deg,#071318,#020608 70%);isolation:isolate}
        .restaurant-display-screen:before{content:"";position:absolute;z-index:3;inset:0;border-radius:inherit;border:5px solid rgba(2,8,11,.86);box-shadow:inset 0 0 0 1px rgba(8,222,244,.15);pointer-events:none}
        .restaurant-display-slider,.restaurant-display-slide{position:absolute;inset:0}.restaurant-display-slide{opacity:0;pointer-events:none;background:#020607;transition:opacity .55s ease}.restaurant-display-slide.active{opacity:1;pointer-events:auto}
        .restaurant-display-slide img,.restaurant-display-slide video,.restaurant-display-slide iframe{display:block;width:100%;height:100%;border:0;object-fit:cover}.restaurant-display-slide.is-logo img{object-fit:contain;padding:22px}
        .restaurant-display-shade{position:absolute;z-index:1;inset:0;background:linear-gradient(180deg,rgba(1,7,9,.04),transparent 58%,rgba(1,7,9,.32));pointer-events:none}
        .hero{background:linear-gradient(110deg,rgba(15,18,28,.96),rgba(5,25,28,.9));direction:rtl}.hero:after{content:"";position:absolute;width:360px;height:360px;left:-100px;top:-180px;border-radius:50%;background:rgba(8,222,244,.12);filter:blur(70px);pointer-events:none}
        .hero-tools{position:absolute;z-index:4;top:18px;right:20px;display:flex;align-items:center;gap:10px}
        .ozman-directory-link{min-height:43px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:5px 9px;border:1px solid rgba(8,222,244,.24);border-radius:13px;background:rgba(3,10,14,.9);color:#d8e7ed;font-size:10px;font-weight:800;text-decoration:none;white-space:nowrap}.ozman-directory-link img{width:30px;height:30px;object-fit:contain;border-radius:9px}.ozman-directory-link:hover{color:var(--cyan);border-color:var(--cyan)}
        .brand{position:absolute;z-index:2;right:30px;bottom:24px;display:flex;align-items:center;gap:25px;direction:rtl}.logo-stack{display:flex;flex-direction:column;align-items:center;gap:10px}.shop-logo{display:block;width:166px;height:166px;border:2px solid var(--cyan);border-radius:34px;background:#020607;object-fit:contain;box-shadow:0 0 28px rgba(8,222,244,.3)}
        .story-trigger{border:0;background:none;padding:0;color:inherit}.story-trigger.has-story{cursor:pointer}.story-trigger.has-story .shop-logo{border:4px solid var(--green)}.story-trigger:focus-visible{outline:3px solid #fff;outline-offset:5px}.shop-name{max-width:180px;color:#fff;font-size:13px;text-align:center;line-height:1.4}
        .availability{display:inline-flex;align-items:center;gap:7px;padding:8px 15px;border:1px solid var(--green);border-radius:999px;background:rgba(39,221,134,.09);color:var(--green);font-size:13px;font-weight:900}.availability:after{content:"";width:8px;height:8px;border-radius:50%;background:currentColor;box-shadow:0 0 12px currentColor}
        .social-links{display:grid;grid-template-columns:repeat(2,38px);gap:8px}.social-link{width:38px;height:38px;display:grid;place-items:center;border:1px solid rgba(8,222,244,.24);border-radius:50%;background:rgba(3,12,17,.9);color:#dceaf0;font-size:18px;text-decoration:none}.social-link:hover,.social-link:focus-visible{border-color:var(--cyan);color:var(--cyan);outline:none}
        .layout{margin-top:13px;padding-top:13px;border-top:1px solid var(--border)}.menu-panel{min-height:390px;padding:12px;border:1px solid var(--border);border-radius:28px;background:linear-gradient(145deg,rgba(18,23,27,.96),rgba(11,16,18,.98))}
        .menu-browser{display:grid;grid-template-columns:minmax(0,1fr) 120px;gap:12px;direction:ltr}.category-rail{grid-column:2;grid-row:1;display:flex;flex-direction:column;align-items:center;gap:12px;position:sticky;top:14px;max-height:calc(100vh - 28px);overflow:auto;padding:6px 3px;scrollbar-width:thin}
        .category-tab{display:flex;flex-direction:column;align-items:center;gap:6px;width:100%;padding:6px 3px;border:1px solid transparent;border-radius:17px;background:none;color:var(--muted);font-size:11px;font-weight:900;line-height:1.35;cursor:pointer}.category-tab.active{color:#fff;background:rgba(8,222,244,.07);border-color:rgba(8,222,244,.25)}.category-tab img,.category-icon{width:72px;height:72px;display:grid;place-items:center;border:2px solid rgba(140,165,175,.3);border-radius:50%;object-fit:cover;background:#080d10;color:var(--cyan);font-size:28px}.category-tab.active img,.category-tab.active .category-icon{border-color:var(--cyan);box-shadow:0 0 17px rgba(8,222,244,.38)}.category-tab span:last-child{max-width:105px;text-align:center}
        .category-content{grid-column:1;grid-row:1;min-width:0;position:relative;overflow:hidden;border-radius:20px;direction:rtl}.category-content:before{content:"";position:absolute;inset:0;z-index:0;background:radial-gradient(circle at 35% 20%,rgba(8,222,244,.09),transparent 50%);pointer-events:none}.category-background{position:absolute;inset:0;width:100%;height:100%;min-height:420px;object-fit:cover;opacity:.17;pointer-events:none}.category-pane{position:relative;z-index:1;min-height:350px}.category-pane[hidden]{display:none!important}.services{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;padding:8px}
        .service-card{position:relative;min-width:0;overflow:hidden;padding:9px;border:1px solid rgba(150,174,190,.18);border-radius:20px;background:rgba(15,20,24,.94);box-shadow:0 10px 30px rgba(0,0,0,.2)}.service-image{display:grid;place-items:center;width:100%;aspect-ratio:1.12;overflow:hidden;border:1px solid rgba(8,222,244,.22);border-radius:14px;background:#05090b;color:var(--cyan);font-size:54px}.service-image img{width:100%;height:100%;object-fit:cover}.service-body{padding:10px 5px 3px}.service-body h2{margin:0 0 5px;font-size:16px;line-height:1.5}.service-body p{min-height:44px;margin:0;color:var(--muted);font-size:12px;line-height:1.7}.service-details{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}.service-details span{padding:3px 7px;border:1px solid rgba(8,222,244,.17);border-radius:999px;color:#c2dce2;font-size:10px}.service-bottom{display:flex;align-items:center;justify-content:space-between;gap:7px;margin-top:12px;padding-top:9px;border-top:1px solid var(--border)}.price{color:var(--cyan);font-size:13px;font-weight:900;white-space:nowrap}.contact-btn{display:inline-flex;align-items:center;justify-content:center;gap:4px;min-height:33px;padding:5px 9px;border-radius:10px;background:var(--cyan);color:#001318;font-size:11px;font-weight:900;text-decoration:none;text-align:center}.contact-btn:hover{background:#49eaff}.contact-btn.is-call{background:rgba(8,222,244,.13);border:1px solid var(--cyan);color:var(--cyan)}
        .empty{display:flex;align-items:center;justify-content:center;gap:10px;min-height:240px;color:var(--muted);font-weight:700}.empty i{font-size:26px;color:var(--cyan)}
        @media(max-width:720px){.shell{width:calc(100% - 18px);padding-top:9px}.restaurant-hero-layout{grid-template-columns:minmax(0,1fr) 148px;gap:7px}.restaurant-display-screen,.hero{min-height:380px;border-radius:21px}.hero-tools{top:0;right:0;width:100%;flex-direction:column;align-items:stretch;gap:8px}.ozman-directory-link{width:100%;min-height:35px;padding:4px 6px;font-size:8px}.ozman-directory-link img{width:25px;height:25px}.brand{top:88px;right:2px;bottom:2px;width:calc(100% - 4px);flex-direction:column;justify-content:flex-start;gap:8px}.logo-stack{width:100%;align-items:stretch}.story-trigger,.shop-logo{width:100%}.shop-logo{height:auto;aspect-ratio:1;border-radius:25px}.social-links{display:flex;flex-wrap:wrap;justify-content:center;gap:5px;width:100%}.social-link{width:29px;height:29px;font-size:14px}.availability{position:absolute;right:0;bottom:0;padding:7px 9px;font-size:10px}.layout{margin-top:7px;padding-top:7px}.menu-panel{padding:7px;border-radius:20px}.menu-browser{grid-template-columns:minmax(0,1fr) 100px;gap:3px}.category-rail{top:8px;gap:8px}.category-tab img,.category-icon{width:57px;height:57px;font-size:24px}.category-tab span:last-child{max-width:90px;font-size:10px}.services{grid-template-columns:1fr;gap:12px;padding:4px}.service-body h2{font-size:14px}.service-bottom{align-items:flex-start;flex-direction:column}.contact-btn{width:100%}}
        @media(max-width:390px){.restaurant-hero-layout{grid-template-columns:minmax(0,1fr) 135px}.menu-browser{grid-template-columns:minmax(0,1fr) 82px}.category-tab img,.category-icon{width:48px;height:48px}.category-tab span:last-child{font-size:9px}.service-card{padding:6px}.service-body{padding-inline:3px}.price{white-space:normal}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.restaurant-display-slide{transition:none}}
    </style>
</head>
<body>
    <main class="shell">
        <div class="restaurant-hero-layout">
            <section class="restaurant-display-screen" aria-label="{{ $copy['services'] }}">
                <div class="restaurant-display-slider" data-display-slider>
                    @forelse($displayItems as $item)
                        @php
                            $embed = $item->type === 'youtube' ? $youtubeEmbed($item->media) : null;
                        @endphp
                        <article class="restaurant-display-slide {{ $loop->first ? 'active' : '' }}" data-duration="{{ max(1, (int) ($item->duration ?? 8)) * 1000 }}">
                            @if($item->type === 'video')
                                <video src="{{ $mediaUrl($item->media) }}" muted playsinline loop preload="metadata"></video>
                            @elseif($embed)
                                <iframe src="{{ $embed }}" title="{{ $item->title ?: $copy['services'] }}" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
                            @else
                                <img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title ?: $shop->name }}">
                            @endif
                        </article>
                    @empty
                        <article class="restaurant-display-slide active {{ $shop->banner ? '' : 'is-logo' }}" data-duration="10000">
                            <img src="{{ $shopImage }}" alt="{{ $shop->name }}">
                        </article>
                    @endforelse
                </div>
                <div class="restaurant-display-shade" aria-hidden="true"></div>
            </section>

            <header class="hero">
                <div class="hero-tools">
                    @include('front.partials.public_language_switcher')
                    <a class="ozman-directory-link" href="{{ route('front.home') }}">
                        <img src="{{ $ozmanLogo ? $mediaUrl($ozmanLogo) : asset('ozman-favicon.png') }}" alt="" aria-hidden="true">
                        <span>{{ $copy['browse'] }}</span>
                    </a>
                </div>
                <div class="brand">
                    <div class="logo-stack">
                        <button type="button" class="story-trigger {{ $hasStories ? 'has-story' : '' }}"
                            data-shop-story-trigger data-story-shop-id="{{ $shop->id }}"
                            aria-label="{{ $shop->name }}" @disabled(! $hasStories)>
                            <img class="shop-logo" src="{{ $logo }}" alt="{{ $shop->name }}">
                        </button>
                        @unless($shop->logo)<strong class="shop-name">{{ $shop->name }}</strong>@endunless
                        <span class="availability">{{ $copy['available'] }}</span>
                    </div>
                    @if($socialProfiles->isNotEmpty())
                        <nav class="social-links" aria-label="{{ $copy['social'] }}">
                            @foreach($socialProfiles as $profile)
                                <a class="social-link" href="{{ $profile['url'] }}" target="_blank" rel="noopener noreferrer"
                                    aria-label="{{ $profile['label'] }}" title="{{ $profile['label'] }}">
                                    <i class="ti {{ $profile['icon'] }}" aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </nav>
                    @endif
                </div>
            </header>
        </div>

        <div class="layout">
            <section class="menu-panel" aria-label="{{ $copy['sections'] }}">
                @if($categoriesForPage->isNotEmpty())
                    <div class="menu-browser">
                        <nav class="category-rail" aria-label="{{ $copy['sections'] }}">
                            @foreach($categoriesForPage as $category)
                                <button type="button" class="category-tab {{ $loop->first ? 'active' : '' }}"
                                    data-category-key="{{ $category['key'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                    @if($category['image'])
                                        <img src="{{ $mediaUrl($category['image']) }}" alt="" loading="lazy">
                                    @else
                                        <span class="category-icon"><i class="ti ti-printer" aria-hidden="true"></i></span>
                                    @endif
                                    <span>{{ $category['name'] }}</span>
                                </button>
                            @endforeach
                        </nav>
                        <div class="category-content">
                            @foreach($categoriesForPage as $category)
                                <section class="category-pane" data-category-pane="{{ $category['key'] }}" @if(!$loop->first) hidden @endif>
                                    @if($category['background'])
                                        <video class="category-background" src="{{ $mediaUrl($category['background']) }}" muted loop playsinline preload="none"></video>
                                    @endif
                                    <div class="services">
                                        @forelse($category['products'] as $product)
                                            @php
                                                $serviceName = $product->localized('name');
                                                $serviceMessage = match ($locale) {
                                                    'he' => "שלום, אשמח לקבל פרטים על {$serviceName} מ-{$shop->name}",
                                                    'en' => "Hello, I'd like details about {$serviceName} from {$shop->name}",
                                                    default => "مرحباً، أريد الاستفسار عن خدمة {$serviceName} من {$shop->name}",
                                                };
                                                $contactUrl = $contactNumber ? 'https://wa.me/'.$contactNumber.'?text='.rawurlencode($serviceMessage) : null;
                                                $basePrice = (float) $product->price;
                                                $discountPrice = (float) $product->discount_price;
                                                $price = $discountPrice > 0 && $discountPrice < $basePrice ? $discountPrice : $basePrice;
                                                $attributes = $product->catalog_attributes ?? [];
                                            @endphp
                                            <article class="service-card">
                                                <div class="service-image">
                                                    @if($product->main_image)
                                                        <img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $serviceName }}" loading="lazy">
                                                    @else
                                                        <i class="ti ti-printer" aria-hidden="true"></i>
                                                    @endif
                                                </div>
                                                <div class="service-body">
                                                    <h2>{{ $serviceName }}</h2>
                                                    <p>{{ $product->localized('description') ?: $copy['service_hint'] }}</p>
                                                    <div class="service-details">
                                                        @if(filled(data_get($attributes, 'material')))<span>{{ data_get($attributes, 'material') }}</span>@endif
                                                        @if(filled(data_get($attributes, 'minimum_quantity')))<span>{{ $copy['min_quantity'] }}: {{ data_get($attributes, 'minimum_quantity') }}</span>@endif
                                                        @if(filled(data_get($attributes, 'production_time')))<span>{{ $copy['production'] }}: {{ data_get($attributes, 'production_time') }} {{ $copy['days'] }}</span>@endif
                                                    </div>
                                                    <div class="service-bottom">
                                                        <span class="price">{{ $price > 0 ? $copy['from'].' '.number_format($price, 2).' ₪' : $copy['quote'] }}</span>
                                                        @if($contactUrl)
                                                            <a class="contact-btn" href="{{ $contactUrl }}" target="_blank" rel="noopener noreferrer">
                                                                <i class="ti ti-brand-whatsapp" aria-hidden="true"></i>{{ $copy['contact'] }}
                                                            </a>
                                                        @elseif($callNumber)
                                                            <a class="contact-btn is-call" href="tel:{{ $callNumber }}"><i class="ti ti-phone" aria-hidden="true"></i>{{ $copy['call'] }}</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </article>
                                        @empty
                                            <div class="empty"><i class="ti ti-printer-off" aria-hidden="true"></i>{{ $copy['empty_section'] }}</div>
                                        @endforelse
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty"><i class="ti ti-printer-off" aria-hidden="true"></i>{{ $copy['empty'] }}</div>
                @endif
            </section>
        </div>
    </main>
    @include('front.shop_stories', ['showStoryList' => false])
    <script>
        (() => {
            const slider = document.querySelector('[data-display-slider]');
            const slides = [...(slider?.querySelectorAll('.restaurant-display-slide') || [])];
            let index = 0;
            const showSlide = next => {
                slides[index]?.querySelector('video')?.pause();
                slides[index]?.classList.remove('active');
                index = next;
                slides[index]?.classList.add('active');
                slides[index]?.querySelector('video')?.play().catch(() => {});
            };
            slides[0]?.querySelector('video')?.play().catch(() => {});
            if (slides.length > 1) {
                const schedule = () => window.setTimeout(() => {
                    showSlide((index + 1) % slides.length);
                    schedule();
                }, Number(slides[index]?.dataset.duration) || 8000);
                schedule();
            }

            const tabs = [...document.querySelectorAll('[data-category-key]')];
            const panes = [...document.querySelectorAll('[data-category-pane]')];
            const selectCategory = key => {
                tabs.forEach(tab => {
                    const active = tab.dataset.categoryKey === key;
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-pressed', String(active));
                });
                panes.forEach(pane => {
                    const active = pane.dataset.categoryPane === key;
                    pane.hidden = !active;
                    const video = pane.querySelector('.category-background');
                    if (video) active ? video.play().catch(() => {}) : video.pause();
                });
            };
            tabs.forEach(tab => tab.addEventListener('click', () => selectCategory(tab.dataset.categoryKey)));
            if (tabs[0]) selectCategory(tabs[0].dataset.categoryKey);
        })();
    </script>
</body>
</html>
