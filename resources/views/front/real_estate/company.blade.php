@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $copy = match ($locale) {
        'he' => [
            'services' => 'מבנים ניידים ופתרונות בנייה', 'browse' => 'לכל החנויות', 'sections' => 'קטגוריות השירות',
            'contact' => 'יצירת קשר ב-WhatsApp', 'call' => 'התקשרו לפרטים', 'available' => 'פתוח לפניות', 'social' => 'רשתות חברתיות',
            'empty' => 'אין שירותים זמינים כרגע.', 'empty_section' => 'אין שירותים בקטגוריה זו כרגע.',
            'service_hint' => 'שירות המותאם לצרכים שלכם. פנו אלינו לפרטים.',
        ],
        'en' => [
            'services' => 'Mobile buildings and construction solutions', 'browse' => 'Browse all stores', 'sections' => 'Service categories',
            'contact' => 'Contact via WhatsApp', 'call' => 'Call for details', 'available' => 'Open for inquiries', 'social' => 'Social media',
            'empty' => 'No services are available yet.', 'empty_section' => 'No services in this category yet.',
            'service_hint' => 'A service tailored to your needs. Contact the company for details.',
        ],
        default => [
            'services' => 'المباني المتنقلة وحلول البناء', 'browse' => 'تصفح جميع المحلات', 'sections' => 'أقسام الخدمات',
            'contact' => 'تواصل عن طريق الواتساب', 'call' => 'اتصل للاستفسار', 'available' => 'متاح للاستفسارات', 'social' => 'منصات التواصل الاجتماعي',
            'empty' => 'لا توجد خدمات متاحة حالياً.', 'empty_section' => 'لا توجد خدمات في هذا القسم حالياً.',
            'service_hint' => 'خدمة مخصصة حسب احتياجك. تواصل مع الشركة للتفاصيل.',
        ],
    };
    $mediaUrl = fn (?string $path) => ! filled($path) ? '' : (preg_match('/^https?:\/\//i', $path) ? $path : asset($path));
    $shopImage = $mediaUrl($shop->banner ?: $shop->logo) ?: asset('images/logo.svg');
    $logo = $mediaUrl($shop->logo) ?: asset('images/logo.svg');
    $canonical = route('real-estate.company', $shop);
    $description = $shop->description ?: $copy['services'].' — '.$shop->name;
    $social = $shop->social;
    $whatsappDigits = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $social?->whatsapp ?: $shop->phone)) ?: '';
    if (str_starts_with($whatsappDigits, '00')) {
        $whatsappDigits = substr($whatsappDigits, 2);
    } elseif (str_starts_with($whatsappDigits, '0')) {
        $countryCode = preg_replace('/\D+/', '', (string) config('services.whatsapp_cloud.default_country_code', '972')) ?: '972';
        $whatsappDigits = $countryCode.ltrim($whatsappDigits, '0');
    }
    $callNumber = preg_replace('/[^\d+]/', '', (string) $shop->phone);
    $generalMessage = match ($locale) {
        'he' => 'שלום, אשמח לקבל פרטים על השירותים של '.$shop->name.'.',
        'en' => 'Hello, I would like to ask about the services of '.$shop->name.'.',
        default => 'مرحباً، أرغب بالاستفسار عن خدمات '.$shop->name.'.',
    };
    $generalWhatsappUrl = $whatsappDigits ? 'https://wa.me/'.$whatsappDigits.'?text='.rawurlencode($generalMessage) : null;
    $socialProfiles = collect([
        ['label' => 'Facebook', 'icon' => 'ti-brand-facebook', 'value' => $social?->facebook, 'base' => 'https://facebook.com/'],
        ['label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'value' => $social?->instagram, 'base' => 'https://instagram.com/'],
        ['label' => 'TikTok', 'icon' => 'ti-brand-tiktok', 'value' => $social?->tiktok, 'base' => 'https://tiktok.com/@'],
        ['label' => 'YouTube', 'icon' => 'ti-brand-youtube', 'value' => $social?->youtube, 'base' => 'https://youtube.com/@'],
        ['label' => 'Telegram', 'icon' => 'ti-brand-telegram', 'value' => $social?->telegram, 'base' => 'https://t.me/'],
        ['label' => 'Snapchat', 'icon' => 'ti-brand-snapchat', 'value' => $social?->snapchat, 'base' => 'https://snapchat.com/add/'],
    ])->filter(fn ($profile) => filled($profile['value']))->map(function ($profile) {
        $value = trim($profile['value']);
        $profile['url'] = preg_match('/^https?:\/\//i', $value) ? $value : $profile['base'].ltrim($value, '@/');
        return $profile;
    });
    if ($generalWhatsappUrl) $socialProfiles->push(['label' => 'WhatsApp', 'icon' => 'ti-brand-whatsapp', 'url' => $generalWhatsappUrl]);
    $hasStories = $shop->stories()->where('expires_at', '>', now())->exists();
    $youtubeEmbed = function (?string $url): ?string {
        return preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]+)/', (string) $url, $match)
            ? 'https://www.youtube.com/embed/'.$match[1].'?mute=1&playsinline=1&rel=0' : null;
    };
    $categoryIcons = ['home' => 'ti-home', 'bath' => 'ti-bath', 'shield' => 'ti-shield', 'caravan' => 'ti-caravan', 'tools' => 'ti-tools'];
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    @include('front.partials.seo', ['title' => $shop->name.' | Ozman', 'description' => $description, 'canonical' => $canonical, 'image' => $shopImage, 'schema' => ['@context' => 'https://schema.org', '@type' => 'Store', 'name' => $shop->name, 'url' => $canonical, 'description' => $description, 'image' => $shopImage, 'telephone' => $shop->phone, 'address' => $shop->address]])
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cyan:#08def4;--green:#27dd86;--bg:#05070a;--card:#10151a;--border:rgba(150,174,190,.18);--muted:#9ca9b4}*{box-sizing:border-box}html{width:100%;max-width:100%;overflow-x:hidden;scroll-behavior:smooth}body{width:100%;max-width:100%;margin:0;overflow-x:hidden;background:radial-gradient(circle at 85% 5%,rgba(8,222,244,.11),transparent 27%),radial-gradient(circle at 8% 30%,rgba(101,42,255,.1),transparent 25%),var(--bg);color:#fff;font-family:Cairo,Arial,sans-serif}button{font:inherit}.shell{width:min(1380px,calc(100% - 32px));max-width:100%;margin:auto;padding:20px 0 55px}
        .company-hero-layout{display:grid;max-width:100%;grid-template-columns:minmax(0,1fr) minmax(310px,380px);align-items:stretch;gap:16px;direction:ltr}.company-display-screen,.hero{position:relative;min-width:0;min-height:310px;overflow:hidden;border:1px solid var(--border);border-radius:28px;box-shadow:0 20px 60px rgba(0,0,0,.28)}.company-display-screen{background:linear-gradient(145deg,#071318,#020608 70%);isolation:isolate}.company-display-screen:before{content:"";position:absolute;z-index:3;inset:0;border-radius:inherit;border:5px solid rgba(2,8,11,.86);box-shadow:inset 0 0 0 1px rgba(8,222,244,.15);pointer-events:none}.company-display-slider,.company-display-slide{position:absolute;inset:0}.company-display-slide{opacity:0;pointer-events:none;background:#020607;transition:opacity .55s ease}.company-display-slide.active{opacity:1;pointer-events:auto}.company-display-slide img,.company-display-slide video,.company-display-slide iframe{display:block;width:100%;max-width:100%;height:100%;border:0;object-fit:cover}.company-display-slide.is-logo img{object-fit:contain;padding:22px}.company-display-shade{position:absolute;z-index:1;inset:0;background:linear-gradient(180deg,rgba(1,7,9,.04),transparent 58%,rgba(1,7,9,.32));pointer-events:none}
        .hero{background:linear-gradient(110deg,rgba(15,18,28,.96),rgba(5,25,28,.9));direction:rtl}.hero:after{content:"";position:absolute;width:360px;height:360px;left:-100px;top:-180px;border-radius:50%;background:rgba(8,222,244,.12);filter:blur(70px);pointer-events:none}.hero-tools{position:absolute;z-index:4;top:18px;right:20px;display:flex;align-items:center;gap:10px}.hero .public-language-switcher{padding:4px}.hero .public-language-switcher a{padding:5px 7px;font-size:10px}.ozman-directory-link{min-height:43px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:5px 9px;border:1px solid rgba(8,222,244,.24);border-radius:13px;background:rgba(3,10,14,.9);color:#d8e7ed;font-size:10px;font-weight:800;text-decoration:none;white-space:nowrap}.ozman-directory-link img{width:30px;height:30px;object-fit:contain;border-radius:9px}.ozman-directory-link:hover{color:var(--cyan);border-color:var(--cyan)}
        .brand{position:absolute;z-index:2;right:30px;bottom:24px;display:flex;align-items:center;gap:25px;direction:rtl}.logo-stack{display:flex;flex-direction:column;align-items:center;gap:9px}.shop-logo{display:block;width:166px;height:166px;border:2px solid var(--cyan);border-radius:34px;background:#020607;object-fit:contain;box-shadow:0 0 28px rgba(8,222,244,.3)}.story-trigger{border:0;background:none;padding:0;color:inherit}.story-trigger.has-story{cursor:pointer}.story-trigger.has-story .shop-logo{border:4px solid var(--green)}.story-trigger:focus-visible{outline:3px solid #fff;outline-offset:5px}.shop-name{max-width:190px;color:#fff;font-size:12px;text-align:center;line-height:1.4}.availability{display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border:1px solid var(--green);border-radius:999px;background:rgba(39,221,134,.09);color:var(--green);font-size:11px;font-weight:900}.availability:after{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 12px currentColor}.main-contact-btn{display:inline-flex;min-height:36px;align-items:center;justify-content:center;gap:6px;padding:6px 11px;border-radius:11px;background:#25d366;color:#03150c;font-size:10px;font-weight:900;text-decoration:none}.main-contact-btn i{font-size:16px}.social-links{display:grid;grid-template-columns:repeat(2,38px);gap:8px}.social-link{width:38px;height:38px;display:grid;place-items:center;border:1px solid rgba(8,222,244,.24);border-radius:50%;background:rgba(3,12,17,.9);color:#dceaf0;font-size:18px;text-decoration:none}.social-link:hover,.social-link:focus-visible{border-color:var(--cyan);color:var(--cyan);outline:none}
        .layout{max-width:100%;margin-top:13px;padding-top:13px;border-top:1px solid var(--border)}.service-panel{min-height:390px;max-width:100%;padding:12px;border:1px solid var(--border);border-radius:28px;background:linear-gradient(145deg,rgba(18,23,27,.96),rgba(11,16,18,.98))}.service-browser{display:grid;max-width:100%;grid-template-columns:minmax(0,1fr) 120px;gap:12px;direction:ltr}.category-rail{grid-column:2;grid-row:1;display:flex;flex-direction:column;align-items:center;gap:12px;position:sticky;top:14px;max-height:calc(100vh - 28px);overflow:auto;padding:6px 3px;scrollbar-width:thin}.category-tab{display:flex;flex-direction:column;align-items:center;gap:6px;width:100%;min-width:0;padding:6px 3px;border:1px solid transparent;border-radius:17px;background:none;color:var(--muted);font-size:11px;font-weight:900;line-height:1.35;cursor:pointer}.category-tab.active{color:#fff;background:rgba(8,222,244,.07);border-color:rgba(8,222,244,.25)}.category-tab img,.category-icon{width:72px;height:72px;display:grid;place-items:center;border:2px solid rgba(140,165,175,.3);border-radius:50%;object-fit:cover;background:#080d10;color:var(--cyan);font-size:28px}.category-tab.active img,.category-tab.active .category-icon{border-color:var(--cyan);box-shadow:0 0 17px rgba(8,222,244,.38)}.category-tab span:last-child{max-width:105px;overflow-wrap:anywhere;text-align:center}
        .category-content{grid-column:1;grid-row:1;min-width:0;max-width:100%;position:relative;overflow:hidden;border-radius:20px;direction:rtl}.category-content:before{content:"";position:absolute;inset:0;z-index:0;background:radial-gradient(circle at 35% 20%,rgba(8,222,244,.09),transparent 50%);pointer-events:none}.category-background{position:absolute;inset:0;width:100%;height:100%;min-height:420px;object-fit:cover;opacity:.11;pointer-events:none}.category-pane{position:relative;z-index:1;min-width:0;min-height:350px}.category-pane[hidden]{display:none!important}.services{display:grid;max-width:100%;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;padding:8px}.service-card{position:relative;min-width:0;max-width:100%;overflow:hidden;padding:9px;border:1px solid rgba(150,174,190,.18);border-radius:20px;background:rgba(15,20,24,.94);box-shadow:0 10px 30px rgba(0,0,0,.2)}.service-image{display:grid;place-items:center;width:100%;aspect-ratio:1.12;overflow:hidden;border:1px solid rgba(8,222,244,.22);border-radius:14px;background:#05090b;color:var(--cyan);font-size:54px}.service-image img{display:block;width:100%;max-width:100%;height:100%;object-fit:cover}.service-body{padding:10px 5px 3px}.service-body h2{margin:0 0 5px;overflow-wrap:anywhere;font-size:16px;line-height:1.5}.service-body p{min-height:44px;margin:0;overflow-wrap:anywhere;color:var(--muted);font-size:12px;line-height:1.7}.service-bottom{margin-top:12px;padding-top:9px;border-top:1px solid var(--border)}.service-contact{display:inline-flex;width:100%;min-height:36px;align-items:center;justify-content:center;gap:5px;padding:6px 10px;border-radius:10px;background:var(--cyan);color:#001318;font-size:11px;font-weight:900;text-decoration:none;text-align:center}.service-contact:hover{background:#49eaff}.service-contact.is-call{border:1px solid var(--cyan);background:rgba(8,222,244,.13);color:var(--cyan)}.empty{display:flex;align-items:center;justify-content:center;gap:10px;min-height:240px;color:var(--muted);font-weight:700}.empty i{font-size:26px;color:var(--cyan)}
        @media(max-width:720px){.shell{width:calc(100% - 18px);padding-top:9px}.company-hero-layout{grid-template-columns:minmax(0,1fr) 148px;gap:7px}.company-display-screen,.hero{min-height:390px;border-radius:21px}.hero-tools{top:0;right:0;width:100%;flex-direction:column;align-items:stretch;gap:7px}.hero .public-language-switcher{width:100%;justify-content:center;padding:3px}.hero .public-language-switcher a{padding:4px 5px;font-size:8px}.ozman-directory-link{width:100%;min-height:35px;padding:4px 5px;font-size:8px}.ozman-directory-link img{width:24px;height:24px}.brand{top:86px;right:2px;bottom:2px;width:calc(100% - 4px);flex-direction:column;justify-content:flex-start;gap:6px}.logo-stack{width:100%;align-items:stretch}.story-trigger,.shop-logo{width:100%}.shop-logo{height:auto;aspect-ratio:1;border-radius:25px}.shop-name{max-width:100%;font-size:9px}.availability{align-self:center;padding:6px 8px;font-size:9px}.main-contact-btn{width:100%;min-height:34px;padding:5px;font-size:8px}.social-links{display:flex;flex-wrap:wrap;justify-content:center;gap:5px;width:100%}.social-link{width:28px;height:28px;font-size:14px}.layout{margin-top:7px;padding-top:7px}.service-panel{padding:7px;border-radius:20px}.service-browser{grid-template-columns:minmax(0,1fr) 100px;gap:3px}.category-rail{top:8px;gap:8px}.category-tab img,.category-icon{width:57px;height:57px;font-size:24px}.category-tab span:last-child{max-width:90px;font-size:10px}.services{grid-template-columns:1fr;gap:12px;padding:4px}.service-body h2{font-size:14px}}
        @media(max-width:390px){.company-hero-layout{grid-template-columns:minmax(0,1fr) 135px}.service-browser{grid-template-columns:minmax(0,1fr) 82px}.category-tab img,.category-icon{width:48px;height:48px}.category-tab span:last-child{font-size:9px}.service-card{padding:6px}.service-body{padding-inline:3px}.hero .public-language-switcher a{padding-inline:4px;font-size:7px}.main-contact-btn{font-size:7.5px}}@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.company-display-slide{transition:none}}
    </style>
</head>
<body>
<main class="shell">
    <div class="company-hero-layout">
        <section class="company-display-screen" aria-label="{{ $copy['services'] }}">
            <div class="company-display-slider" data-display-slider>
                @forelse($displayItems as $item)
                    @php
                        $embed = $item->type === 'youtube' ? $youtubeEmbed($item->media) : null;
                    @endphp
                    <article class="company-display-slide {{ $loop->first ? 'active' : '' }}" data-duration="{{ max(1, (int) ($item->duration ?? 8)) * 1000 }}">
                        @if($item->type === 'video')<video src="{{ $mediaUrl($item->media) }}" muted playsinline loop preload="metadata"></video>
                        @elseif($embed)<iframe src="{{ $embed }}" title="{{ $item->title ?: $copy['services'] }}" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
                        @else<img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title ?: $shop->name }}">@endif
                    </article>
                @empty
                    <article class="company-display-slide active {{ $shop->banner ? '' : 'is-logo' }}" data-duration="10000"><img src="{{ $shopImage }}" alt="{{ $shop->name }}"></article>
                @endforelse
            </div>
            <div class="company-display-shade" aria-hidden="true"></div>
        </section>
        <header class="hero">
            <div class="hero-tools">
                @include('front.partials.public_language_switcher')
                <a class="ozman-directory-link" href="{{ route('front.home') }}"><img src="{{ $ozmanLogo ? $mediaUrl($ozmanLogo) : asset('ozman-favicon.png') }}" alt="" aria-hidden="true"><span>{{ $copy['browse'] }}</span></a>
            </div>
            <div class="brand">
                <div class="logo-stack">
                    <button type="button" class="story-trigger {{ $hasStories ? 'has-story' : '' }}" data-shop-story-trigger data-story-shop-id="{{ $shop->id }}" aria-label="{{ $shop->name }}" @disabled(! $hasStories)><img class="shop-logo" src="{{ $logo }}" alt="{{ $shop->name }}"></button>
                    <strong class="shop-name">{{ $shop->name }}</strong><span class="availability">{{ $copy['available'] }}</span>
                    @if($generalWhatsappUrl)<a class="main-contact-btn" href="{{ $generalWhatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>{{ $copy['contact'] }}</a>@endif
                </div>
                @if($socialProfiles->isNotEmpty())<nav class="social-links" aria-label="{{ $copy['social'] }}">@foreach($socialProfiles as $profile)<a class="social-link" href="{{ $profile['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $profile['label'] }}" title="{{ $profile['label'] }}"><i class="ti {{ $profile['icon'] }}" aria-hidden="true"></i></a>@endforeach</nav>@endif
            </div>
        </header>
    </div>
    <div class="layout"><section class="service-panel" aria-label="{{ $copy['sections'] }}">
        @if($categories->isNotEmpty())
            <div class="service-browser">
                <nav class="category-rail" aria-label="{{ $copy['sections'] }}">
                    @foreach($categories as $category)
                        <button type="button" class="category-tab {{ $loop->first ? 'active' : '' }}" data-category-key="{{ $category->slug }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            @if($category->imageUrl())<img src="{{ $category->imageUrl() }}" alt="" loading="lazy">@else<span class="category-icon"><i class="ti {{ $categoryIcons[$category->icon_key] ?? 'ti-building' }}" aria-hidden="true"></i></span>@endif
                            <span>{{ $category->localized('name') }}</span>
                        </button>
                    @endforeach
                </nav>
                <div class="category-content">
                    @foreach($categories as $category)
                        <section class="category-pane" data-category-pane="{{ $category->slug }}" @if(!$loop->first) hidden @endif>
                            @if($category->imageUrl())<img class="category-background" src="{{ $category->imageUrl() }}" alt="" loading="lazy">@endif
                            <div class="services">
                                @forelse($category->services as $service)
                                    @php
                                        $serviceName = $service->localized('name');
                                        $serviceMessage = match ($locale) {
                                            'he' => 'שלום, אשמח לקבל פרטים על '.$serviceName.' מ-'.$shop->name.'.',
                                            'en' => 'Hello, I would like details about '.$serviceName.' from '.$shop->name.'.',
                                            default => 'مرحباً، أريد الاستفسار عن '.$serviceName.' من '.$shop->name.'.',
                                        };
                                        $serviceWhatsappUrl = $whatsappDigits ? 'https://wa.me/'.$whatsappDigits.'?text='.rawurlencode($serviceMessage) : null;
                                    @endphp
                                    <article class="service-card" data-service-key="{{ $service->slug }}">
                                        <div class="service-image">@if($service->imageUrl())<img src="{{ $service->imageUrl() }}" alt="{{ $serviceName }}" loading="lazy">@else<i class="ti {{ $categoryIcons[$category->icon_key] ?? 'ti-building' }}" aria-hidden="true"></i>@endif</div>
                                        <div class="service-body"><h2>{{ $serviceName }}</h2><p>{{ $service->localized('short_description') ?: $copy['service_hint'] }}</p><div class="service-bottom">
                                            @if($serviceWhatsappUrl)<a class="service-contact" href="{{ $serviceWhatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>{{ $copy['contact'] }}</a>
                                            @elseif($callNumber)<a class="service-contact is-call" href="tel:{{ $callNumber }}"><i class="ti ti-phone" aria-hidden="true"></i>{{ $copy['call'] }}</a>@endif
                                        </div></div>
                                    </article>
                                @empty<div class="empty"><i class="ti ti-building-off" aria-hidden="true"></i>{{ $copy['empty_section'] }}</div>@endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        @else<div class="empty"><i class="ti ti-building-off" aria-hidden="true"></i>{{ $copy['empty'] }}</div>@endif
    </section></div>
</main>
@include('front.shop_stories', ['showStoryList' => false])
<script>
(() => {
    const slider = document.querySelector('[data-display-slider]');
    const slides = [...(slider?.querySelectorAll('.company-display-slide') || [])];
    let slideIndex = 0;
    const showSlide = next => { slides[slideIndex]?.querySelector('video')?.pause(); slides[slideIndex]?.classList.remove('active'); slideIndex = next; slides[slideIndex]?.classList.add('active'); slides[slideIndex]?.querySelector('video')?.play().catch(() => {}); };
    slides[0]?.querySelector('video')?.play().catch(() => {});
    if (slides.length > 1) { const schedule = () => window.setTimeout(() => { showSlide((slideIndex + 1) % slides.length); schedule(); }, Number(slides[slideIndex]?.dataset.duration) || 8000); schedule(); }
    const tabs = [...document.querySelectorAll('[data-category-key]')];
    const panes = [...document.querySelectorAll('[data-category-pane]')];
    const selectCategory = (key, updateUrl = false) => {
        if (!tabs.some(tab => tab.dataset.categoryKey === key)) key = tabs[0]?.dataset.categoryKey;
        if (!key) return;
        tabs.forEach(tab => { const active = tab.dataset.categoryKey === key; tab.classList.toggle('active', active); tab.setAttribute('aria-pressed', String(active)); });
        panes.forEach(pane => pane.hidden = pane.dataset.categoryPane !== key);
        if (updateUrl) { const url = new URL(window.location.href); url.searchParams.set('category', key); url.searchParams.delete('service'); history.replaceState({}, '', url); }
    };
    tabs.forEach(tab => tab.addEventListener('click', () => selectCategory(tab.dataset.categoryKey, true)));
    const params = new URLSearchParams(window.location.search);
    selectCategory(params.get('category') || tabs[0]?.dataset.categoryKey);
    const requestedService = params.get('service');
    if (requestedService) window.setTimeout(() => document.querySelector(`[data-service-key="${CSS.escape(requestedService)}"]`)?.scrollIntoView({ block: 'center' }), 100);
})();
</script>
</body>
</html>
