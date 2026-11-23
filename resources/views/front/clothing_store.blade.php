<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    @php
        $mediaUrl = function ($path, $fallback = 'images/logo.svg') {
            if (! filled($path)) return asset($fallback);
            return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'storage/']) ? asset($path) : asset('storage/'.$path);
        };
        $heroMedia = $displayItems->first()?->media ?: ($shop->banner ?: $shop->logo);
        $heroImage = $mediaUrl($heroMedia);
        $lookProducts = $allProducts->take(3)->values();
        $whatsappNumber = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $shop->phone));
        $hasProducts = $allProducts->isNotEmpty();
        $seasonNow = now('Asia/Jerusalem');
        $seasonLabel = in_array($seasonNow->month, [3, 4, 5, 6, 7, 8], true) ? 'SPRING/SUMMER' : 'FALL/WINTER';
        $normalizeSocialProfile = function (?string $value, string $base, bool $withAt = false): ?string {
            if (! filled($value)) return null;
            $value = trim($value);
            if (preg_match('/^https?:\/\//i', $value)) return $value;
            if (preg_match('/^(?:www\.)?[\w.-]+\.[a-z]{2,}\//i', $value)) return 'https://'.ltrim($value, '/');
            $handle = ltrim($value, '@/');
            return $handle === '' ? null : $base.($withAt ? '@' : '').$handle;
        };
        $storeSocial = optional($shop->social);
        $storeSocialLinks = collect([
            ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'ti-brand-facebook', 'url' => $normalizeSocialProfile($storeSocial->facebook, 'https://facebook.com/')],
            ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'url' => $normalizeSocialProfile($storeSocial->instagram, 'https://instagram.com/')],
            ['key' => 'tiktok', 'label' => 'TikTok', 'icon' => 'ti-brand-tiktok', 'url' => $normalizeSocialProfile($storeSocial->tiktok, 'https://tiktok.com/', true)],
            ['key' => 'telegram', 'label' => 'Telegram', 'icon' => 'ti-brand-telegram', 'url' => $normalizeSocialProfile($storeSocial->telegram, 'https://t.me/')],
            ['key' => 'snapchat', 'label' => 'Snapchat', 'icon' => 'ti-brand-snapchat', 'url' => $normalizeSocialProfile($storeSocial->snapchat, 'https://snapchat.com/add/')],
            ['key' => 'twitter', 'label' => 'X', 'icon' => 'ti-brand-x', 'url' => $normalizeSocialProfile($storeSocial->twitter, 'https://x.com/')],
            ['key' => 'youtube', 'label' => 'YouTube', 'icon' => 'ti-brand-youtube', 'url' => $normalizeSocialProfile($storeSocial->youtube, 'https://youtube.com/', true)],
        ])->filter(fn ($link) => filled($link['url']))->values();
        $storeWhatsappValue = trim((string) ($storeSocial->whatsapp ?: $shop->whatsapp));
        $storeWhatsappDigits = preg_replace('/\D+/', '', $storeWhatsappValue);
        $storeWhatsappUrl = preg_match('/^https?:\/\//i', $storeWhatsappValue)
            ? $storeWhatsappValue
            : ($storeWhatsappDigits ? 'https://wa.me/'.$storeWhatsappDigits : null);
        $storePhoneTel = filled($shop->phone) ? preg_replace('/[^\d+]/', '', (string) $shop->phone) : null;
        $storeAddress = implode('، ', array_filter([trim((string) $shop->city), trim((string) $shop->address)]));
        $storeMapUrl = is_numeric($shop->latitude) && is_numeric($shop->longitude)
            ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($shop->latitude.','.$shop->longitude)
            : null;
        $hasStoreInfo = $storeWhatsappUrl || $storePhoneTel || $storeAddress !== '' || $storeMapUrl || $openTime || $closeTime;
    @endphp
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    @include('front.partials.seo', [
        'title' => $shop->name.' | أزياء',
        'description' => $shop->description ?: 'اكتشف أحدث الأزياء من '.$shop->name,
        'canonical' => route('clothing.store', $shop),
        'image' => $heroImage,
        'schema' => ['@context' => 'https://schema.org', '@type' => 'ClothingStore', 'name' => $shop->name, 'url' => route('clothing.store', $shop), 'image' => $heroImage, 'telephone' => $shop->phone],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.clothing_store')
@include('front.partials.catalog_checkout')
</head>
<body>
@include('front.partials.shop_bottom_screens', ['screenPlacement' => 'top'])
<main class="fashion-page">
    <section class="fashion-hero">
        <div class="fashion-hero-media"><img src="{{ $heroImage }}" alt="{{ $shop->name }}"></div>
        <div class="fashion-hero-copy">
            <div class="hero-top"><a class="back-home" href="{{ route('home') }}"><i class="ti ti-arrow-right"></i> {{ __('كل المحلات') }}</a>@include('front.partials.public_language_switcher')</div>
            <span class="season"><i class="ti ti-sparkles"></i> NEW SEASON · {{ $seasonLabel }} {{ $seasonNow->year }}</span>
            <h1>{{ __('لبسك مش بس قطعة.') }}<br><em>{{ __('هذا ستايلك.') }}</em></h1>
            <p>{{ $shop->description ?: 'اختيارات جريئة، قطع يومية، وتفاصيل تخليك تطلع بطريقتك.' }}</p>
            @if($hasProducts)<div class="hero-actions"><a href="#catalog"><i class="ti ti-hanger-2"></i> {{ __('شوف المجموعة') }}</a><a class="alt" href="#lookbook"><i class="ti ti-aperture"></i> {{ __('اللوك بوك') }}</a><button class="demo-try" id="demoTryOn" type="button" data-image="{{ route('virtual-tryon.demo-garment', ['v' => 'ivory-navy-3']) }}"><i class="ti ti-scan"></i> {{ __('جرّب تيشيرت بالكاميرا') }}</button></div>@endif
            <div class="brand-signature"><img src="{{ $mediaUrl($shop->logo ?: $shop->banner) }}" alt="{{ $shop->name }}"><div><strong>{{ $shop->name }}</strong><span>CURATED FOR YOUR NEXT LOOK</span></div></div>
        </div>
    </section>
    <div class="ticker" aria-hidden="true"><span>NEW DROP — STYLE IT YOUR WAY — {{ $shop->name }} — NEW DROP — STYLE IT YOUR WAY — {{ $shop->name }} —</span></div>
    @if($hasStoreInfo)
    <section class="store-info" aria-label="{{ __('معلومات المحل') }}">
        <div class="store-info-main">
            <small>VISIT THE STORE</small>
            <h2>{{ __('معلومات المحل') }}</h2>
            <div class="store-info-meta">
                @if(! is_null($isOpenNow))<span class="store-status {{ $isOpenNow ? 'is-open' : 'is-closed' }}"><i aria-hidden="true"></i>{{ $isOpenNow ? __('مفتوح الآن') : __('مغلق') }}</span>@endif
                @if($openTime || $closeTime)
                    <span><i class="ti ti-clock" aria-hidden="true"></i>{{ __('أوقات الدوام') }}:
                        @if($openTime && $closeTime)<bdi dir="ltr">{{ $openTime }} – {{ $closeTime }}</bdi>
                        @elseif($openTime){{ __('من الساعة') }} <bdi dir="ltr">{{ $openTime }}</bdi>
                        @else{{ __('حتى الساعة') }} <bdi dir="ltr">{{ $closeTime }}</bdi>@endif
                    </span>
                @endif
                @if($storeAddress !== '')<span><i class="ti ti-map-pin" aria-hidden="true"></i>{{ $storeAddress }}</span>@endif
            </div>
        </div>
        @if($storeWhatsappUrl || $storePhoneTel || $storeMapUrl)
        <div class="store-info-actions">
            @if($storeWhatsappUrl)<a class="is-whatsapp" href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i>{{ __('واتساب') }}</a>@endif
            @if($storePhoneTel)<a href="tel:{{ $storePhoneTel }}"><i class="ti ti-phone" aria-hidden="true"></i>{{ __('اتصال') }}</a>@endif
            @if($storeMapUrl)<a href="{{ $storeMapUrl }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-map-2" aria-hidden="true"></i>{{ __('افتح على الخريطة') }}</a>@endif
        </div>
        @endif
    </section>
    @endif
    <section class="fashion-intro"><article class="intro-tile"><i class="ti ti-shirt"></i><small>THE RIGHT FIT</small><strong>{{ __('اختار المقاس بثقة') }}</strong><p>{{ __('المقاسات والألوان المتاحة تظهر لك قبل ما تضيف للسلة.') }}</p></article><article class="intro-tile"><i class="ti ti-sparkles"></i><small>STYLE THE LOOK</small><strong>{{ __('شوف القطع كلوك') }}</strong><p>{{ __('مش بس منتجات جنب بعض؛ رتبناها كإلهام للّبسة.') }}</p></article><article class="intro-tile"><i class="ti ti-shopping-bag"></i><small>READY TO GO</small><strong>{{ __('سلة سريعة وواضحة') }}</strong><p>{{ __('اختار مقاسك ولونك، وبعدها ابعت الطلب للمحل مباشرة.') }}</p></article></section>
    <section class="lookbook" id="lookbook"><header class="section-head"><div><small>THE EDIT / LOOKBOOK</small><h2>{{ __('اختيارات هذا الأسبوع') }}</h2></div><p>{{ __('كل صورة توصلك للقطعة نفسها. اضغط على القطعة، اختار التفاصيل، وخلي اللوك يبدأ منك.') }}</p></header><div class="look-grid">@forelse($lookProducts as $product)@php($attrs=$product->catalog_attributes ?: [])<article class="look-card">@include('front.partials.product_like', ['product' => $product])<button class="fashion-photo preview" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ (float)($product->discount_price ?: $product->price) }}" data-image="{{ $mediaUrl($product->main_image) }}" data-description="{{ $product->localized('description') }}" data-sizes="{{ json_encode(data_get($attrs,'sizes',[])) }}" data-colors="{{ json_encode(data_get($attrs,'colors',[])) }}"><img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $product->localized('name') }}"></button><div class="look-card-info"><h3>{{ $product->localized('name') }}</h3><span>{{ number_format((float)($product->discount_price ?: $product->price),2) }} ₪</span><button class="preview" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ (float)($product->discount_price ?: $product->price) }}" data-image="{{ $mediaUrl($product->main_image) }}" data-description="{{ $product->localized('description') }}" data-sizes="{{ json_encode(data_get($attrs,'sizes',[])) }}" data-colors="{{ json_encode(data_get($attrs,'colors',[])) }}"><i class="ti ti-sparkles"></i> {{ __('شوف التفاصيل') }}</button></div></article>@empty @if($canManage)<div class="empty"><div><i class="ti ti-camera-off"></i><p>{{ __('أضف منتجات مع صور حتى يظهر اللوك بوك هنا.') }}</p></div></div>@else<div class="empty empty-soon"><div><i class="ti ti-sparkles"></i><strong>{{ __('المجموعة الجديدة قريباً') }}</strong><p>{{ __('تابعنا عشان تكون أول من يشوفها') }}</p></div></div>@endif @endforelse</div></section>
    <section class="catalog" id="catalog"><header class="catalog-head"><div><small>SHOP THE DROP</small><h2>{{ __('كل القطع، على ذوقك') }}</h2></div><p>{{ $allProducts->count() }} قطعة متاحة من {{ $shop->name }}</p></header><nav class="filters"><button class="active" type="button" data-filter="all">{{ __('كل المجموعة') }}</button>@foreach($categories as $category)<button type="button" data-filter="cat-{{ $category->id }}">{{ $category->localized('name') }}</button>@endforeach @if($uncategorizedProducts->isNotEmpty())<button type="button" data-filter="uncategorized">{{ __('إضافات جديدة') }}</button>@endif</nav><div class="fashion-grid">@forelse($allProducts as $product)@php($attrs=$product->catalog_attributes ?: [])@php($sizes=data_get($attrs,'sizes',[]))@php($colors=data_get($attrs,'colors',[]))@php($sizes=is_array($sizes)?$sizes:array_filter(array_map('trim',explode(',',(string)$sizes))))@php($colors=is_array($colors)?$colors:array_filter(array_map('trim',explode(',',(string)$colors))))@php($price=(float)($product->discount_price ?: $product->price))<article class="fashion-card" data-card="{{ $product->category_id ? 'cat-'.$product->category_id : 'uncategorized' }}">@include('front.partials.product_like', ['product' => $product])<button class="fashion-photo preview" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ $price }}" data-image="{{ $mediaUrl($product->main_image) }}" data-description="{{ $product->localized('description') }}" data-sizes="{{ json_encode(array_values($sizes)) }}" data-colors="{{ json_encode(array_values($colors)) }}"><img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $product->localized('name') }}" loading="lazy">@if($product->is_featured)<span class="badge">{{ __('اختيارنا') }}</span>@endif</button><div class="fashion-info"><span class="fashion-brand">{{ data_get($attrs,'brand') ?: 'NEW EDIT' }}</span><h3 class="fashion-name">{{ $product->localized('name') }}</h3><div class="fashion-meta">@foreach(array_slice($sizes,0,3) as $size)<span>{{ $size }}</span>@endforeach @if(empty($sizes) && data_get($attrs,'material'))<span>{{ data_get($attrs,'material') }}</span>@endif</div><div class="fashion-bottom"><strong class="fashion-price">{{ number_format($price,2) }} ₪</strong>@if(count($sizes) > 1 || count($colors) > 1)<button class="fashion-add preview" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ $price }}" data-image="{{ $mediaUrl($product->main_image) }}" data-description="{{ $product->localized('description') }}" data-sizes="{{ json_encode(array_values($sizes)) }}" data-colors="{{ json_encode(array_values($colors)) }}" aria-label="أضف {{ $product->localized('name') }}"><i class="ti ti-plus"></i></button>@else<button class="fashion-add direct-add" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ $price }}" data-size="{{ \Illuminate\Support\Arr::first($sizes) }}" data-color="{{ \Illuminate\Support\Arr::first($colors) }}" aria-label="أضف {{ $product->localized('name') }}"><i class="ti ti-plus"></i></button>@endif</div></div></article>@empty @if($canManage)<div class="empty"><div><i class="ti ti-hanger-off"></i><p>{{ __('أضف منتجات الملابس من لوحة التحكم لتظهر المجموعة هنا.') }}</p></div></div>@else<div class="empty empty-soon"><div><i class="ti ti-sparkles"></i><strong>{{ __('المجموعة الجديدة قريباً') }}</strong><p>{{ __('تابعنا عشان تكون أول من يشوفها') }}</p></div></div>@endif @endforelse</div></section>
    <footer class="store-footer">
        <div class="store-footer-top">
            <div class="store-footer-brand">
                <img src="{{ $mediaUrl($shop->logo ?: $shop->banner) }}" alt="{{ $shop->name }}">
                <div>
                    <strong>{{ $shop->name }}</strong>
                    @if($storeAddress !== '')<span><i class="ti ti-map-pin" aria-hidden="true"></i>{{ $storeAddress }}</span>@endif
                </div>
            </div>
            @if($storeSocialLinks->isNotEmpty())
            <nav class="store-footer-social" aria-label="{{ __('تابعنا') }}">
                @foreach($storeSocialLinks as $socialLink)
                    <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $socialLink['label'] }}" title="{{ $socialLink['label'] }}"><i class="ti {{ $socialLink['icon'] }}" aria-hidden="true"></i></a>
                @endforeach
            </nav>
            @endif
        </div>
        <div class="store-footer-bottom">
            <a class="store-footer-all" href="{{ route('front.home') }}"><i class="ti ti-building-store" aria-hidden="true"></i>{{ __('كل المحلات') }}</a>
            <p>{!! __('ضمن منصة :platform', ['platform' => '<a href="'.e(route('home')).'">Ozman</a>']) !!}</p>
        </div>
    </footer>
</main>
<button class="bag" id="bag" type="button"><i class="ti ti-shopping-bag"></i><span><strong><span id="bagCount">0</span> {{ __('قطعة في السلة') }}</strong><small id="bagTotal">0.00 ₪</small></span></button>
<div class="overlay" id="bagLayer"><section class="sheet"><header class="sheet-head"><h2>{{ __('سلتك') }}</h2><button class="x" type="button" data-close="bagLayer">×</button></header><div class="cart-items" id="cartItems"></div><div class="total"><span>{{ __('المجموع') }}</span><span id="sheetTotal">0.00 ₪</span></div>@include('front.partials.loyalty_hint', ['total' => '#sheetTotal'])<div class="buyer"><input id="buyerName" placeholder="{{ __('الاسم') }}"><input id="buyerPhone" inputmode="tel" placeholder="{{ __('رقم الجوال') }}"></div><button class="send" id="sendOrder" type="button"><i class="ti ti-brand-whatsapp"></i> {{ __('إرسال الطلب للمحل') }}</button></section></div>
<div class="overlay quick" id="quickLayer"><section class="quick-card"><button class="x" type="button" data-close="quickLayer">×</button><img id="quickImage" src="" alt=""><div class="quick-copy"><small>PIECE DETAILS</small><h2 id="quickName"></h2><strong id="quickPrice"></strong><p id="quickDescription"></p><span class="option-label" id="sizeLabel">{{ __('المقاس') }}</span><div class="options" id="sizeOptions"></div><span class="option-label" id="colorLabel">{{ __('اللون') }}</span><div class="options" id="colorOptions"></div><button class="try-on" id="tryOn" type="button"><i class="ti ti-scan"></i> {{ __('جرّبها عليك بالكاميرا') }} <small>BETA</small></button><button class="quick-add" id="quickAdd" type="button"><i class="ti ti-shopping-bag-plus"></i> {{ __('أضف للسلة') }}</button></div></section></div>
@include('front.partials.clothing_photo_tryon')
<style>
.fashion-hero-copy .hero-top{position:static;width:100%;flex-wrap:wrap;margin-bottom:24px}
.fashion-hero-copy .brand-signature{position:static;display:flex;max-width:100%;margin-top:24px;flex-shrink:0}
.brand-signature strong{overflow-wrap:anywhere}
.fashion-hero-copy{min-width:0}
</style>
<style>.try-guide-toggle{display:flex;align-items:center;gap:8px;padding:10px 18px 0;color:#d8d5dd;font-size:11px;cursor:pointer}.try-guide-toggle input{accent-color:#d6ff38}</style>
@viewStyle('front.clothing_store-2')
@viewStyle('front.clothing_store-3')
@viewStyle('front.clothing_store-4')

@viewScript('front.clothing_store')
<script type="module">
{!! file_get_contents(base_path('public/clothing-photo-tryon.js')) !!}
</script>
@include('front.partials.shop_bottom_screens', ['screenPlacement' => 'bottom'])
@include('front.partials.page_share', ['title' => $shop->name, 'url' => $shop->publicUrl()])
</body>
</html>
