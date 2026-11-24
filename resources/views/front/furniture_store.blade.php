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
        $isVideo = fn ($path) => \Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::lower((string) $path), ['.mp4', '.webm', '.ogg', '.mov']);
        $shopLogo = $mediaUrl($shop->logo ?: $shop->banner);
        $shopBanner = $mediaUrl($shop->banner ?: $shop->logo);
        $whatsappNumber = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $shop->phone));
    @endphp
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    @include('front.partials.seo', [
        'title' => $shop->name.' | أثاث وأدوات منزلية',
        'description' => $shop->description ?: 'استكشف الأثاث والأدوات المنزلية من '.$shop->name,
        'canonical' => route('furniture.store', $shop),
        'image' => $shopBanner,
        'schema' => ['@context' => 'https://schema.org', '@type' => 'FurnitureStore', 'name' => $shop->name, 'url' => route('furniture.store', $shop), 'image' => $shopBanner, 'telephone' => $shop->phone],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.furniture_store')
    @viewStyle('front.furniture_store-2')
@include('front.partials.catalog_checkout')
</head>
<body>
@include('front.partials.shop_bottom_screens', ['screenPlacement' => 'top'])
<main class="furniture-shell">
    <section class="hero">
        <div class="hero-media">@forelse($displayItems as $item)<div class="slide {{ $loop->first ? 'active' : '' }}" data-slide>@if($isVideo($item->media))<video src="{{ $mediaUrl($item->media) }}" muted playsinline autoplay loop></video>@else<img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title ?: $shop->name }}">@endif</div>@empty<div class="hero-fallback"></div>@endforelse</div>
        <div class="hero-top"><a class="all-stores" href="{{ route('home') }}"><i class="ti ti-arrow-right"></i> {{ __('كل المحلات') }}</a>@include('front.partials.public_language_switcher')</div>
        <div class="hero-copy"><span class="eyebrow"><i class="ti ti-armchair-2"></i> HOME, MADE YOUR WAY</span><h1>{{ __('كل زاوية في بيتك تستحق حكاية أجمل.') }}</h1><p>{{ $shop->description ?: __('أثاث وأدوات منزلية مختارة لتبني بيتًا يشبهك؛ قطعة وراء قطعة.') }}</p><div class="hero-actions"><a href="#catalog"><i class="ti ti-layout-grid"></i> {{ __('استكشف حسب الغرفة') }}</a><a class="secondary" href="#roomLook"><i class="ti ti-sparkles"></i> {{ __('شوف اللوك الكامل') }}</a><button class="ar-launch" id="openArDemo" type="button"><i class="ti ti-cube-scan"></i> {{ __('جرّب قطعة في غرفتك') }}</button></div></div>
        <div class="brand-stamp"><img src="{{ $shopLogo }}" alt="{{ $shop->name }}"><span><strong>{{ $shop->name }}</strong><span>{{ __('اختيارات للبيت والحياة') }}</span></span></div>
    </section>
    <section class="room-strip"><article class="room-idea"><i class="ti ti-armchair-2"></i><small>START WITH A ROOM</small><strong>{{ __('رتّب غرفتك، مش بس سلتك') }}</strong><p>{{ __('اكتشف القطع حسب الغرفة التي تريد تجديدها.') }}</p></article><article class="room-idea"><i class="ti ti-ruler-measure"></i><small>MEASURE WITH CONFIDENCE</small><strong>{{ __('الأبعاد واضحة من البداية') }}</strong><p>{{ __('شوف مقاس وخامة كل قطعة قبل ما تقرر.') }}</p></article><article class="room-idea"><i class="ti ti-package-import"></i><small>COMPLETE THE CORNER</small><strong>{{ __('خد اللوك كامل بضغطة') }}</strong><p>{{ __('اختيارات منسقة تساعدك تبدأ بسرعة.') }}</p></article></section>
    <section class="studio" id="roomLook"><header class="studio-head"><div><span class="eyebrow"><i class="ti ti-sparkles"></i> SHOP THE LOOK</span><h2>{{ __('ركن اليوم: دفء وبساطة') }}</h2><p>{{ __('اضغط على أي قطعة لتضيفها، أو خذ اللوك كاملًا.') }}</p></div><span class="studio-count">{{ __(':count قطع منسّقة', ['count' => $showroomProducts->count()]) }}</span></header><div class="look-stage">@forelse($showroomProducts as $product)<div class="look-item"><img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $product->localized('name') }}"><button type="button" data-add data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ (float)($product->discount_price ?: $product->price) }}">{{ $product->localized('name') }}</button></div>@empty<div class="empty">{{ __('أضف منتجات مع صور لتظهر داخل ركن الإلهام.') }}</div>@endforelse</div><footer class="studio-foot"><div><strong>{{ __('كل قطعة هنا تقدر تشتريها لحالها.') }}</strong><span>{{ __('أو اجمع القطع اللي عجبتك في السلة.') }}</span></div><button class="add-look" type="button" id="addRoomLook"><i class="ti ti-plus"></i> {{ __('أضف قطع اللوك') }}</button></footer></section>
    <section class="catalog" id="catalog"><header class="catalog-head"><div><span class="eyebrow"><i class="ti ti-home"></i> SHOP BY ROOM</span><h2>{{ __('اختار الغرفة… وخلي البيت يكمل') }}</h2><p>أثاث، تنظيم، وإكسسوارات منزلية من {{ $shop->name }}</p></div><span class="studio-count">{{ __(':count منتج', ['count' => $categories->sum(fn($category) => $category->products->count()) + $uncategorizedProducts->count()]) }}</span></header><div class="catalog-layout"><nav class="rooms">@forelse($categories as $category)@php($categoryImage=$category->image ?: $category->products->first()?->main_image)<button class="room-tab {{ $loop->first ? 'active' : '' }}" type="button" data-tab="{{ $category->id }}"><figure>@if($categoryImage)<img src="{{ $mediaUrl($categoryImage) }}" alt="">@else<i class="ti ti-armchair-2"></i>@endif</figure><span>{{ $category->localized('name') }}</span></button>@empty<button class="room-tab active" type="button" data-tab="items"><figure><i class="ti ti-home"></i></figure><span>{{ __('كل القطع') }}</span></button>@endforelse
        @if($categories->isNotEmpty() && $uncategorizedProducts->isNotEmpty())<button class="room-tab" type="button" data-tab="others"><figure><i class="ti ti-lamp-2"></i></figure><span>{{ __('إضافات للبيت') }}</span></button>@endif</nav><div class="catalog-content">@forelse($categories as $category)<section class="pane" data-pane="{{ $category->id }}" @if(! $loop->first) hidden @endif><header class="pane-head"><h3>{{ $category->localized('name') }}</h3><small>{{ __(':count قطعة', ['count' => $category->products->count()]) }}</small></header><div class="products">@forelse($category->products as $product)@include('front.partials.furniture_product_card',['product'=>$product,'mediaUrl'=>$mediaUrl])@empty<div class="empty">{{ __('قريبًا قطع جديدة لهذا الركن.') }}</div>@endforelse</div></section>@empty<section class="pane" data-pane="items"><header class="pane-head"><h3>{{ __('اختيارات البيت') }}</h3><small>{{ __(':count قطعة', ['count' => $uncategorizedProducts->count()]) }}</small></header><div class="products">@forelse($uncategorizedProducts as $product)@include('front.partials.furniture_product_card',['product'=>$product,'mediaUrl'=>$mediaUrl])@empty<div class="empty">{{ __('أضف منتجات الأثاث من لوحة التحكم لتظهر هنا.') }}</div>@endforelse</div></section>@endforelse
        @if($categories->isNotEmpty() && $uncategorizedProducts->isNotEmpty())<section class="pane" data-pane="others" hidden><header class="pane-head"><h3>{{ __('إضافات للبيت') }}</h3><small>{{ __(':count قطعة', ['count' => $uncategorizedProducts->count()]) }}</small></header><div class="products">@foreach($uncategorizedProducts as $product)@include('front.partials.furniture_product_card',['product'=>$product,'mediaUrl'=>$mediaUrl])@endforeach</div></section>@endif</div></div></section>
</main>
<button class="cart-button" id="cartButton" type="button"><i class="ti ti-shopping-bag"></i><span><strong><span id="cartCount">0</span> {{ __('قطع في السلة') }}</strong><small id="cartTotal">0.00 ₪</small></span></button>
<div class="layer" id="cartLayer"><section class="sheet"><header class="sheet-head"><h2>{{ __('سلة البيت الجديد') }}</h2><button class="close" id="cartClose" type="button">×</button></header><div class="items" id="cartItems"></div><div class="total"><span>{{ __('المجموع') }}</span><span id="sheetTotal">0.00 ₪</span></div>@include('front.partials.loyalty_hint', ['total' => '#sheetTotal'])<div class="fields"><input id="customerName" placeholder="{{ __('اسمك') }}"><input id="customerPhone" inputmode="tel" placeholder="{{ __('رقم الجوال أو واتساب') }}"></div><button class="send" id="sendOrder" type="button"><i class="ti ti-brand-whatsapp"></i> {{ __('أرسل طلبك عبر واتساب') }}</button></section></div>
<div class="lightbox" id="lightbox"><button type="button">×</button><img src="" alt=""></div>
<div class="ar-demo-layer" id="arDemoLayer" aria-hidden="true">
    <section class="ar-demo-card" role="dialog" aria-modal="true" aria-labelledby="arDemoTitle">
        <header class="ar-demo-head"><div><small><i class="ti ti-sparkles"></i> AR DEMO</small><h2 id="arDemoTitle">{{ __('حط قطعة أثاث في غرفتك') }}</h2></div><button class="ar-demo-close" id="closeArDemo" type="button" aria-label="{{ __('إغلاق') }}">×</button></header>
        <model-viewer class="ar-model" src="https://raw.githubusercontent.com/KhronosGroup/glTF-Sample-Assets/main/Models/SheenChair/glTF-Binary/SheenChair.glb" alt="{{ __('قطعة أثاث ثلاثية الأبعاد للتجربة') }}" ar ar-modes="webxr scene-viewer quick-look" ar-placement="floor" ar-scale="fixed" camera-controls shadow-intensity="1" interaction-prompt="auto">
            <button slot="ar-button" class="ar-place-button" type="button"><i class="ti ti-camera"></i> {{ __('افتح الكاميرا وضعها عندك') }}</button>
        </model-viewer>
        <p class="ar-demo-note"><i class="ti ti-info-circle"></i> {{ __('هذه تجربة بقطعة ثلاثية الأبعاد. حرّك الكاميرا باتجاه الأرض أو طاولة، ثم اضغط المكان المناسب لتثبيتها.') }}</p>
    </section>
</div>
<script type="module" src="https://unpkg.com/@google/model-viewer@4.4.0/dist/model-viewer.min.js"></script>
@viewScript('front.furniture_store')
<script>
(() => {
    const opener=document.getElementById('openArDemo'),layer=document.getElementById('arDemoLayer'),closer=document.getElementById('closeArDemo');
    if(!opener||!layer||!closer)return;
    const close=()=>{layer.classList.remove('open');layer.setAttribute('aria-hidden','true');};
    opener.addEventListener('click',()=>{layer.classList.add('open');layer.setAttribute('aria-hidden','false');});
    closer.addEventListener('click',close);
    layer.addEventListener('click',event=>{if(event.target===layer)close();});
    document.addEventListener('keydown',event=>{if(event.key==='Escape')close();});
})();
</script>
@include('front.partials.shop_bottom_screens', ['screenPlacement' => 'bottom'])
@include('front.partials.page_share', ['title' => $shop->name, 'url' => $shop->publicUrl()])
</body>
</html>
