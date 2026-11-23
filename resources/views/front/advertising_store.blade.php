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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.advertising_store')
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
                @include('front.partials.display_sound_toggle')
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
                                                    @include('front.partials.product_like', ['product' => $product])
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
    @viewScript('front.advertising_store')
@include('front.partials.shop_bottom_screens')
@include('front.partials.page_share', ['title' => $shop->name, 'url' => $shop->publicUrl()])
</body>
</html>
