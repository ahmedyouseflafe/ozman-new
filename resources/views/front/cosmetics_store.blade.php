@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $mediaUrl = fn (?string $path) => ! filled($path) ? '' : (preg_match('/^https?:\/\//i', $path) || str_starts_with($path, 'storage/') ? asset($path) : asset('storage/'.$path));
    $shopLogo = $mediaUrl($shop->logo ?: $shop->banner) ?: asset('images/logo.svg');
    $shopImage = $mediaUrl($shop->banner ?: $shop->logo) ?: asset('images/logo.svg');
    $whatsapp = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $shop->phone));
    $isElegance = $shop->slug === 'elegance-perfumes';
    $otherStoresLabel = match ($locale) { 'en' => 'Other stores', 'he' => 'חנויות נוספות', default => 'متاجر أخرى' };
    $shopLocationLabel = match ($locale) { 'en' => 'Store location', 'he' => 'מיקום החנות', default => 'لوكيشن المحل' };
    $shopLocationUnavailable = match ($locale) { 'en' => 'Store location has not been added yet.', 'he' => 'מיקום החנות טרם נוסף.', default => 'لم تتم إضافة موقع المحل بعد.' };
    $shopMapQuery = is_numeric($shop->latitude) && is_numeric($shop->longitude)
        ? $shop->latitude.','.$shop->longitude
        : (filled($shop->address) ? implode(', ', array_filter([$shop->name, $shop->address, $shop->city, $shop->country])) : null);
    $shopMapUrl = $shopMapQuery ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($shopMapQuery) : null;
    $categoriesForPage = $categories->map(fn ($category) => [
        'key' => (string) $category->id,
        'name' => $category->localized('name'),
        'image' => $category->image ?: $category->products->first(fn ($product) => filled($product->main_image))?->main_image,
        'products' => $category->products,
    ]);
    if ($uncategorizedProducts->isNotEmpty()) {
        $categoriesForPage->push(['key' => 'other', 'name' => __('منتجات أخرى'), 'image' => $uncategorizedProducts->first()?->main_image, 'products' => $uncategorizedProducts]);
    }
    $productGalleries = $categoriesForPage->flatMap(fn (array $category) => $category['products'])
        ->mapWithKeys(fn ($product) => [(string) $product->id => collect([$product->main_image])
            ->merge($product->images->sortBy('id')->pluck('image'))->filter()->map($mediaUrl)->unique()->values()->all()])->all();
    $social = $shop->social;
    $socialLinks = collect([
        ['label' => 'Facebook', 'icon' => 'ti-brand-facebook', 'value' => $social?->facebook, 'base' => 'https://facebook.com/'],
        ['label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'value' => $social?->instagram, 'base' => 'https://instagram.com/'],
        ['label' => 'TikTok', 'icon' => 'ti-brand-tiktok', 'value' => $social?->tiktok, 'base' => 'https://tiktok.com/@'],
    ])->filter(fn ($link) => filled($link['value']))->map(function ($link) {
        $value = trim($link['value']);
        $link['url'] = preg_match('/^https?:\/\//i', $value) ? $value : $link['base'].ltrim($value, '@/');
        return $link;
    });
    if ($whatsapp) $socialLinks->push(['label' => 'WhatsApp', 'icon' => 'ti-brand-whatsapp', 'url' => 'https://wa.me/'.$whatsapp]);
    $productVideos = $categoriesForPage
        ->flatMap(fn (array $category) => $category['products'])
        ->filter(fn ($product) => filled($product->video))
        ->mapWithKeys(fn ($product) => [(string) $product->id => [
            'url' => $mediaUrl($product->video),
            'title' => $product->localized('name'),
        ]])
        ->all();
    $productPrices = $categoriesForPage
        ->flatMap(fn (array $category) => $category['products'])
        ->mapWithKeys(fn ($product) => [(string) $product->id => [
            'original' => (float) $product->price,
            'discount' => $product->discount_price !== null ? (float) $product->discount_price : null,
        ]])
        ->all();
    $youtubeEmbed = function (?string $url): string {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]+)/', (string) $url, $match)) {
            return 'https://www.youtube.com/embed/'.$match[1].'?mute=1&playsinline=1&rel=0&enablejsapi=1';
        }
        return (string) $url;
    };
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    @include('front.partials.seo', [
        'title' => $shop->name.' | '. __('عطور وكوزماتيكس'),
        'description' => $shop->description ?: __('اكتشف العطور ومنتجات الجمال والعناية من :shop.', ['shop' => $shop->name]),
        'canonical' => route('cosmetics.store', $shop), 'image' => $shopImage,
        'schema' => ['@context' => 'https://schema.org', '@type' => 'BeautySalon', 'name' => $shop->name, 'url' => route('cosmetics.store', $shop), 'image' => $shopImage, 'telephone' => $shop->phone],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.cosmetics_store')
    @viewStyle('front.cosmetics_store-2')
    @viewStyle('front.cosmetics_store-3')
    @viewStyle('front.cosmetics_store-4')
    @viewStyle('front.cosmetics_store-5')
    @viewStyle('front.cosmetics_store-6')
    @viewStyle('front.cosmetics_store-7')
    @viewStyle('front.cosmetics_store-8')
    @viewStyle('front.cosmetics_store-9')
</head>
<body>
<main class="cosmetics-shell">
    <div class="cosmetics-hero">
        <section class="beauty-display" aria-label="{{ __('شاشة عروض :shop', ['shop' => $shop->name]) }}">
            <div class="beauty-display-slider" data-beauty-display>
                @forelse($displayItems as $item)
                    <article class="beauty-display-slide {{ $loop->first ? 'active' : '' }}" data-duration="{{ max(1, (int)($item->duration ?? 8))*1000 }}">
                        @if($item->type === 'video')<video src="{{ $mediaUrl($item->media) }}" muted playsinline loop preload="metadata"></video>
                        @elseif($item->type === 'youtube')<iframe src="{{ $youtubeEmbed($item->media) }}" title="{{ $item->title ?: $shop->name }}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                        @else<img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title ?: $shop->name }}">@endif
                    </article>
                @empty
                    <article class="beauty-display-slide active {{ $shop->banner ? '' : 'is-logo' }}" data-duration="10000"><img src="{{ $shopImage }}" alt="{{ $shop->name }}"></article>
                @endforelse
            </div>
            <div class="beauty-display-shade" aria-hidden="true"></div>
            @include('front.partials.display_sound_toggle')
        </section>
        <header class="beauty-brand">
            <div class="brand-tools">@include('front.partials.public_language_switcher')<a class="directory-link" href="{{ route('front.home') }}"><img src="{{ $ozmanLogo ? $mediaUrl($ozmanLogo) : asset('ozman-favicon.png') }}" alt="">{{ $isElegance ? $otherStoresLabel : __('كل المحلات') }}</a></div>
            <img class="brand-logo" src="{{ $shopLogo }}" alt="{{ $shop->name }}"><h1 class="brand-name">{{ $shop->name }}</h1>
            <p class="brand-description">{{ $shop->description ?: __('عطور مختارة ومنتجات عناية وجمال لكل تفاصيلك.') }}</p><span class="brand-status">{{ __('متاح للطلبات') }}</span>
            @if($isElegance)
                @if($shopMapUrl)
                    <a class="beauty-booking-link" href="{{ $shopMapUrl }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-map-pin"></i> {{ $shopLocationLabel }}</a>
                @else
                    <span class="beauty-booking-link" aria-disabled="true"><i class="ti ti-map-pin"></i> {{ $shopLocationLabel }}</span>
                    <small class="brand-description">{{ $shopLocationUnavailable }}</small>
                @endif
            @else
                <a class="beauty-booking-link" href="{{ route('cosmetics.booking', $shop) }}"><i class="ti ti-calendar-heart"></i> احجزي موعد في الصالون</a>
            @endif
            @if($socialLinks->isNotEmpty())<nav class="beauty-socials" aria-label="{{ __('التواصل الاجتماعي') }}">@foreach($socialLinks as $link)<a class="beauty-social" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}"><i class="ti {{ $link['icon'] }}"></i></a>@endforeach</nav>@endif
        </header>
    </div>
    @if($shop->slug === 'elegance-perfumes')
        @include('front.partials.watch_catalog_tryon')
    @endif
    <section class="catalog-panel" id="beautyCatalog">
        @if($categoriesForPage->isNotEmpty())<div class="beauty-browser"><nav class="beauty-categories" aria-label="{{ __('أقسام الكوزماتيكس') }}">@foreach($categoriesForPage as $category)<button type="button" class="beauty-category {{ $loop->first ? 'active' : '' }}" data-beauty-category="{{ $category['key'] }}"><span class="beauty-category-image">@if($category['image'])<img src="{{ $mediaUrl($category['image']) }}" alt="">@else<i class="ti ti-sparkles"></i>@endif</span><span>{{ $category['name'] }}</span></button>@endforeach</nav>
        <div class="beauty-content">@foreach($categoriesForPage as $category)<section class="beauty-pane" data-beauty-pane="{{ $category['key'] }}" @if(!$loop->first) hidden @endif><header class="beauty-pane-head"><h2>{{ $category['name'] }}</h2><span>{{ $category['products']->count() }} {{ __('منتج') }}</span></header><div class="beauty-products">@forelse($category['products'] as $product)@php $attrs = $product->catalog_attributes ?? []; @endphp<article class="beauty-product">@include('front.partials.product_like', ['product' => $product])<div class="product-picture {{ $product->main_image ? 'product-image-trigger' : '' }}" @if($product->main_image) data-product-image="{{ $mediaUrl($product->main_image) }}" data-product-title="{{ $product->localized('name') }}" role="button" tabindex="0" aria-label="تكبير صورة {{ $product->localized('name') }}" @endif>@if($product->main_image)<img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $product->localized('name') }}" loading="lazy">@else<i class="ti ti-sparkles" style="font-size:48px;position:absolute;inset:0;display:grid;place-items:center;color:var(--rose)"></i>@endif @if($product->is_featured)<span class="product-badge">{{ __('مميز') }}</span>@endif</div><div class="product-body"><div class="product-brand">{{ data_get($attrs,'brand') ?: __('اختيار الجمال') }}</div>@include('front.partials.cosmetics_product_text', ['product' => $product, 'shop' => $shop, 'attrs' => $attrs])<div class="product-bottom"><strong class="product-price">{{ number_format((float)($product->discount_price ?: $product->price), 2) }} ₪</strong><button class="add-beauty-cart" type="button" data-add-product data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ (float)($product->discount_price ?: $product->price) }}" aria-label="{{ __('أضف إلى السلة') }}"><i class="ti ti-bag-plus"></i></button></div></div></article>@empty<div class="empty-products">{{ __('لا توجد منتجات في هذا القسم حاليًا.') }}</div>@endforelse</div></section>@endforeach</div></div>@else<div class="empty-products">{{ __('أضف أقسام ومنتجات المتجر لتظهر هنا.') }}</div>@endif
    </section>
</main>
<aside class="beauty-cart" aria-live="polite"><div><strong id="beautyCartCount">0 {{ __('منتجات في السلة') }}</strong><small id="beautyCartTotal">0.00 ₪</small></div><button type="button" id="openBeautyCart"><i class="ti ti-shopping-bag"></i></button></aside>
<dialog class="cart-sheet beauty-viewport-modal" id="beautyCartSheet" aria-hidden="true" aria-label="{{ __('سلة الطلب') }}"><section class="cart-card"><button type="button" class="beauty-cart-close-top" id="closeBeautyCartTop" aria-label="{{ __('إغلاق') }}" autofocus>×</button><h2>{{ __('سلة طلبك') }}</h2><div class="cart-items" id="beautyCartItems"></div><div class="cart-total"><span>{{ __('المجموع') }}</span><span id="beautyCartSheetTotal">0.00 ₪</span></div>@include('front.partials.loyalty_hint', ['total' => '#beautyCartSheetTotal'])<div class="cart-actions"><a id="beautyWhatsappOrder" class="disabled" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp"></i>&nbsp; {{ __('إرسال الطلب واتساب') }}</a><button type="button" id="closeBeautyCart">{{ __('إغلاق') }}</button></div></section></dialog>
@if(!empty($purchaseRewardWheels) || $raffleCardsAvailable)
<aside class="beauty-reward-tools" aria-label="جوائز المتجر">
    @if(!empty($purchaseRewardWheels))<button type="button" class="beauty-reward-tool" id="beautyPurchaseWheelOpen"><span>◉</span><span><strong>عجلة الشراء</strong><small>ادخل على الجائزة بعد إتمام طلبك</small></span></button>@endif
    @if($raffleCardsAvailable)<button type="button" class="beauty-reward-tool" id="beautyRaffleOpen"><span>▣</span><span><strong>فحص بطاقة الربح</strong><small>أدخل رقم بطاقتك أو امسحها</small></span></button>@endif
</aside>
@endif
@if(!empty($purchaseRewardWheels))
<div class="beauty-modal" id="beautyWheelModal" aria-hidden="true"><section class="beauty-modal-card" role="dialog" aria-modal="true" aria-labelledby="beautyWheelTitle"><button type="button" class="beauty-modal-close" data-close-beauty-modal="beautyWheelModal" aria-label="إغلاق">×</button><p class="beauty-modal-kicker">مكافأة الشراء</p><h2 id="beautyWheelTitle">عجلة الحظ</h2><p class="beauty-modal-intro" id="beautyWheelIntro">أتمم طلبك أولاً، ثم لف العجلة لتكشف هديتك.</p><div class="beauty-wheel-wrap"><button type="button" class="beauty-wheel" id="beautyWheelSpin" disabled aria-label="لف عجلة الحظ"></button></div><div class="beauty-wheel-result" id="beautyWheelResult">الجائزة ستظهر هنا.</div></section></div>
@endif
@if($raffleCardsAvailable)
<div class="beauty-modal" id="beautyRaffleModal" aria-hidden="true"><section class="beauty-modal-card" role="dialog" aria-modal="true" aria-labelledby="beautyRaffleTitle"><button type="button" class="beauty-modal-close" data-close-beauty-modal="beautyRaffleModal" aria-label="إغلاق">×</button><p class="beauty-modal-kicker">بطاقات الربح</p><h2 id="beautyRaffleTitle">تحقق من رقم بطاقتك</h2><p class="beauty-modal-intro">اكتب رقم البطاقة من 6 أرقام أو امسح رمزها بالكاميرا.</p><form class="beauty-form" id="beautyRaffleForm"><label class="beauty-field">رقم البطاقة<input class="beauty-card-number" id="beautyRaffleNumber" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required></label><label class="beauty-field">الاسم<input id="beautyRaffleName" autocomplete="name" required></label><label class="beauty-field">الجوال أو واتساب<input id="beautyRaffleWhatsapp" inputmode="tel" autocomplete="tel" required></label><button type="submit" class="beauty-form-action">تحقق من البطاقة</button><button type="button" class="beauty-form-action beauty-scan-action" id="beautyStartScan">مسح البطاقة بالكاميرا</button></form><div class="beauty-scanner" id="beautyScanner"><video id="beautyScannerVideo" playsinline muted></video></div><div class="beauty-raffle-result" id="beautyRaffleResult"></div></section></div>
@endif
<dialog class="beauty-product-video-modal beauty-viewport-modal" id="beautyProductVideoModal" aria-hidden="true" aria-labelledby="beautyProductVideoTitle">
    <button type="button" class="beauty-product-video-close" id="beautyProductVideoClose" aria-label="إغلاق الفيديو">×</button>
    <section class="beauty-product-video-dialog">
        <video id="beautyProductVideoPlayer" controls playsinline preload="metadata"></video>
        <div class="beauty-product-video-meta"><strong id="beautyProductVideoTitle"></strong><span>الصوت مفعّل</span></div>
    </section>
</dialog>
@viewStyle('front.cosmetics_store-10')
@viewStyle('front.cosmetics_store-11')
<dialog class="beauty-product-image-modal beauty-viewport-modal" id="beautyProductImageModal" aria-hidden="true" aria-label="صورة المنتج المكبرة">
    <button type="button" class="beauty-product-image-close" id="beautyProductImageClose" aria-label="إغلاق الصورة">×</button>
    <section class="beauty-product-image-dialog">
        <img id="beautyProductImageViewer" src="" alt="">
        <nav class="beauty-gallery-controls" aria-label="صور المنتج">
            <button type="button" id="beautyImagePrev" aria-label="الصورة السابقة">&#10095;</button>
            <output id="beautyImageCount" aria-live="polite" dir="ltr"></output>
            <button type="button" id="beautyImageNext" aria-label="الصورة التالية">&#10094;</button>
        </nav>
    </section>
</dialog>
@include('front.partials.cosmetics_overlays')
@viewStyle('front.cosmetics_store-12')
@include('front.shop_stories', ['showStoryList' => false])
<script>
    window.beautyProductVideos = @json($productVideos);
    window.beautyProductPrices = @json($productPrices);

    (() => {
        const slider = document.querySelector('[data-beauty-display]');
        const videos = window.beautyProductVideos || {};
        if (!slider || !Object.keys(videos).length) return;

        const preview = document.createElement('article');
        preview.className = 'beauty-display-slide beauty-product-preview';
        preview.hidden = true;
        slider.parentElement.append(preview);

        let selectedId = null;
        const selectProductVideo = (id) => {
            const product = videos[id];
            if (!product) return;

            selectedId = String(id);
            slider.querySelectorAll('.beauty-display-slide').forEach(slide => {
                slide.classList.remove('active');
                slide.querySelector('video')?.pause();
            });

            const video = document.createElement('video');
            video.src = product.url;
            video.muted = true;
            video.playsInline = true;
            video.loop = true;
            video.preload = 'metadata';

            const title = document.createElement('div');
            title.className = 'beauty-display-product-title';
            title.textContent = product.title;
            preview.replaceChildren(video, title);
            preview.hidden = false;
            preview.classList.add('active');
            video.play().catch(() => {});
        };

        document.querySelectorAll('[data-add-product]').forEach(button => {
            const product = videos[button.dataset.id];
            if (!product) return;

            const card = button.closest('.beauty-product');
            const image = card?.querySelector('.product-picture');
            card?.classList.add('has-product-video');
            if (image && !image.querySelector('.beauty-video-hint')) {
                const hint = document.createElement('button');
                hint.type = 'button';
                hint.className = 'beauty-video-hint';
                hint.textContent = '▶ فيديو المنتج';
                hint.setAttribute('aria-label', `عرض فيديو ${product.title}`);
                hint.addEventListener('click', event => {
                    event.preventDefault();
                    event.stopPropagation();
                    selectProductVideo(button.dataset.id);
                });
                image.append(hint);
            }
            card?.addEventListener('click', () => selectProductVideo(button.dataset.id));
            button.addEventListener('click', event => event.stopPropagation());
        });

        new MutationObserver(() => {
            const gallerySlideIsActive = [...slider.querySelectorAll('.beauty-display-slide')]
                .some(slide => slide.classList.contains('active'));
            if (selectedId && (!preview.classList.contains('active') || gallerySlideIsActive)) selectProductVideo(selectedId);
        }).observe(slider.parentElement, {subtree: true, attributes: true, attributeFilter: ['class']});
    })();
</script>
<script>
(() => {const slider=document.querySelector('[data-beauty-display]'),slides=[...(slider?.querySelectorAll('.beauty-display-slide')||[])];let current=0,timer;const play=()=>slides[current]?.querySelector('video')?.play().catch(()=>{});const show=next=>{slides[current]?.querySelector('video')?.pause();slides[current]?.classList.remove('active');current=next%slides.length;slides[current]?.classList.add('active');play()};play();if(slides.length>1){const loop=()=>{timer=setTimeout(()=>{show(current+1);loop()},Number(slides[current]?.dataset.duration)||8000)};loop()}document.querySelectorAll('[data-beauty-category]').forEach(button=>button.addEventListener('click',()=>{const key=button.dataset.beautyCategory;document.querySelectorAll('[data-beauty-category]').forEach(item=>item.classList.toggle('active',item===button));document.querySelectorAll('[data-beauty-pane]').forEach(pane=>pane.hidden=pane.dataset.beautyPane!==key)}));const cart=window.OzmanBeautyCart=[],$=id=>document.getElementById(id),fmt=value=>`${Number(value).toFixed(2)} ₪`,sync=()=>{const count=cart.reduce((n,item)=>n+item.qty,0),total=cart.reduce((n,item)=>n+item.qty*item.price,0);$('beautyCartCount').textContent=`${count} ${count===1?'منتج في السلة':'منتجات في السلة'}`;$('beautyCartTotal').textContent=fmt(total);$('beautyCartSheetTotal').textContent=fmt(total);$('beautyCartItems').innerHTML=cart.length?cart.map(item=>`<div class="cart-row"><span>${item.name} × ${item.qty}</span><span>${fmt(item.price*item.qty)} <button data-remove="${item.id}" aria-label="حذف">×</button></span></div>`).join(''):'<div class="empty-products" style="min-height:120px">السلة فارغة حاليًا.</div>';const message=cart.map(item=>`• ${item.name} × ${item.qty} — ${fmt(item.price*item.qty)}`).join('%0A');const order=$('beautyWhatsappOrder');order.href=cart.length&&@json((bool)$whatsapp)?`https://wa.me/{{ $whatsapp }}?text=${encodeURIComponent('مرحباً، أود طلب المنتجات التالية من {{ $shop->name }}:%0A'+message+'%0A%0Aالمجموع: '+fmt(total))}`:'#';order.classList.toggle('disabled',!cart.length||!@json((bool)$whatsapp));document.querySelectorAll('[data-remove]').forEach(button=>button.onclick=()=>{const index=cart.findIndex(item=>String(item.id)===button.dataset.remove);if(index>-1)cart.splice(index,1);sync()});document.dispatchEvent(new Event('beauty:cart-changed'))};document.querySelectorAll('[data-add-product]').forEach(button=>button.onclick=()=>{const item=cart.find(item=>String(item.id)===button.dataset.id);if(item)item.qty++;else cart.push({id:button.dataset.id,name:button.dataset.name,price:Number(button.dataset.price),qty:1});sync()});$('openBeautyCart').onclick=()=>{sync();window.OzmanBeautyOverlay.open($('beautyCartSheet'))};$('closeBeautyCart').onclick=()=>{window.OzmanBeautyOverlay.close($('beautyCartSheet'))};$('beautyCartSheet').onclick=e=>{if(e.target===$('beautyCartSheet'))$('closeBeautyCart').click()};window.OzmanBeautyCartSync=sync;sync()})();
</script>
@viewScript('front.cosmetics_store')
@include('front.partials.coupon_field')
<script>
(() => {
    const config = {
        shopId: @json($shop->id),
        whatsapp: @json($whatsapp),
        wheels: @json($purchaseRewardWheels),
        orderUrl: @json(route('front-orders.store')),
        raffleUrl: @json(route('raffle.check')),
        spinUrl: @json(url('/front-orders')),
    };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const rewardCart = [];
    const $ = id => document.getElementById(id);
    const total = () => rewardCart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const format = value => `${Number(value).toFixed(2)} ₪`;
    const matchingWheel = value => config.wheels.find(wheel => value >= Number(wheel.min_order_total) && (wheel.max_order_total === null || value <= Number(wheel.max_order_total)));
    const openModal = id => { const modal = $(id); if (!modal) return; modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); };
    const closeModal = id => { const modal = $(id); if (!modal) return; modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };
    const request = async (url, body, method = 'POST') => {
        const response = await fetch(url, {method, headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(body)});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'تعذر إتمام العملية، حاول مرة أخرى.');
        return data;
    };

    document.addEventListener('click', event => {
        const add = event.target.closest('[data-add-product]');
        if (add) {
            const existing = rewardCart.find(item => String(item.id) === add.dataset.id);
            if (existing) existing.qty += 1;
            else rewardCart.push({id: add.dataset.id, name: add.dataset.name, price: Number(add.dataset.price), qty: 1});
        }
        const remove = event.target.closest('[data-remove]');
        if (remove) window.setTimeout(() => {
            const index = rewardCart.findIndex(item => String(item.id) === remove.dataset.remove);
            if (index > -1) rewardCart.splice(index, 1);
        });
    });

    document.querySelectorAll('[data-close-beauty-modal]').forEach(button => button.addEventListener('click', () => closeModal(button.dataset.closeBeautyModal)));
    document.querySelectorAll('.beauty-modal').forEach(modal => modal.addEventListener('click', event => { if (event.target === modal) closeModal(modal.id); }));

    let orderContext = null;
    const wheelButton = $('beautyPurchaseWheelOpen');
    const wheel = $('beautyWheelSpin');
    const wheelIntro = $('beautyWheelIntro');
    const wheelResult = $('beautyWheelResult');
    const renderWheel = wheelData => {
        if (!wheel || !wheelData) return;
        const segments = wheelData.segments || [];
        const fallback = ['#ff80b8', '#e8c276', '#bd78ff', '#91e8c0', '#f6a6d0', '#c997e7'];
        const unit = 360 / Math.max(segments.length, 1);
        wheel.style.background = `conic-gradient(${segments.map((segment, index) => `${segment.color || fallback[index % fallback.length]} ${index * unit}deg ${(index + 1) * unit}deg`).join(',')})`;
        wheel.style.transform = 'rotate(0deg)';
        wheel.replaceChildren(...segments.map((segment, index) => {
            const label = document.createElement('span');
            label.className = 'beauty-wheel-label';
            label.style.transform = `rotate(${index * unit + unit / 2}deg)`;
            label.textContent = segment.label;
            return label;
        }));
    };
    wheelButton?.addEventListener('click', () => {
        if (!orderContext) { $('openBeautyCart')?.click(); return; }
        openModal('beautyWheelModal');
    });
    wheel?.addEventListener('click', async () => {
        if (!orderContext || wheel.disabled) return;
        wheel.disabled = true;
        wheelResult.textContent = 'العجلة تدور…';
        wheelResult.classList.remove('win');
        try {
            const result = await request(`${config.spinUrl}/${orderContext.orderId}/spin-reward`, {});
            const segmentIndex = Number(result.segment_index || 0);
            const count = orderContext.wheel.segments.length;
            const degrees = 2160 + (360 - ((segmentIndex + .5) * (360 / count)));
            wheel.style.transform = `rotate(${degrees}deg)`;
            window.setTimeout(() => {
                wheelResult.textContent = `مبروك! ربحت: ${result.reward?.label || 'هدية مميزة'}`;
                wheelResult.classList.add('win');
            }, 4250);
        } catch (error) {
            wheelResult.textContent = error.message;
            wheel.disabled = false;
        }
    });

    @if($shop->slug !== 'elegance-perfumes')
    const cartCard = document.querySelector('.cart-card');
    const cartActions = document.querySelector('.cart-actions');
    if (cartCard && cartActions) {
        const customer = document.createElement('div');
        customer.className = 'beauty-form';
        customer.style.marginBottom = '12px';
        customer.innerHTML = '<label class="beauty-field">الاسم<input id="beautyCustomerName" autocomplete="name" required></label><label class="beauty-field">رقم الجوال أو واتساب<input id="beautyCustomerWhatsapp" inputmode="tel" autocomplete="tel" required></label>';
        cartActions.before(customer);
        const saved = JSON.parse(localStorage.getItem('beautyCustomer') || '{}');
        $('beautyCustomerName').value = saved.name || '';
        $('beautyCustomerWhatsapp').value = saved.whatsapp || '';
    }
    window.OzmanCoupon?.mount($('beautyWhatsappOrder')?.closest('.cart-actions') || $('beautyWhatsappOrder'));
    $('beautyWhatsappOrder')?.addEventListener('click', async event => {
        event.preventDefault();
        if (!rewardCart.length) return;
        const name = $('beautyCustomerName')?.value.trim();
        const whatsapp = $('beautyCustomerWhatsapp')?.value.trim();
        if (!name || !whatsapp) { alert('أدخل الاسم ورقم الجوال أو واتساب لإرسال الطلب.'); return; }
        const value = total();
        const rewardWheel = matchingWheel(value);
        const orderLink = $('beautyWhatsappOrder');
        // Open this synchronously from the customer click. Opening a new tab only
        // after awaiting the order request is blocked as a popup by many browsers.
        const whatsappWindow = config.whatsapp ? window.open('', '_blank') : null;
        if (whatsappWindow) {
            whatsappWindow.opener = null;
            whatsappWindow.document.title = 'جارٍ تجهيز طلب واتساب';
            whatsappWindow.document.body.innerHTML = '<p style="font-family:Arial,sans-serif;padding:24px">جارٍ تجهيز طلبك وفتح واتساب…</p>';
        }
        orderLink.style.pointerEvents = 'none';
        try {
            localStorage.setItem('beautyCustomer', JSON.stringify({name, whatsapp}));
            const order = await request(config.orderUrl, {shop_id: config.shopId, customer_name: name, customer_phone: whatsapp, customer_whatsapp: whatsapp, items: rewardCart.map(item => ({name: item.name, price: String(item.price), qty: item.qty})), subtotal: value, total: value, order_channel: 'whatsapp', visitor_type: 'customer', reward_wheel_id: rewardWheel?.id || null, coupon_code: window.OzmanCoupon?.code() || null});
            const message = rewardCart.map(item => `• ${item.name} × ${item.qty} — ${format(item.price * item.qty)}`).join('\n');
            if (config.whatsapp) {
                const whatsappUrl = `https://wa.me/${config.whatsapp}?text=${encodeURIComponent(`مرحباً، أود طلب المنتجات التالية من {{ $shop->name }}:\n${message}\n${order.coupon_discount > 0 ? `\nكود الخصم: -${format(order.coupon_discount)}` : ''}\nالمجموع: ${format(order.total ?? value)}`)}`;
                window.OzmanCoupon?.reset();
                if (whatsappWindow && !whatsappWindow.closed) whatsappWindow.location.replace(whatsappUrl);
                else window.location.assign(whatsappUrl);
            }
            if (rewardWheel) {
                orderContext = {orderId: order.order_id, wheel: rewardWheel};
                renderWheel(rewardWheel);
                wheel.disabled = false;
                wheelIntro.textContent = `${rewardWheel.title} — تم تسجيل طلبك، لف العجلة الآن واكتشف جائزتك.`;
                $('closeBeautyCart')?.click();
                openModal('beautyWheelModal');
            }
        } catch (error) {
            if (whatsappWindow && !whatsappWindow.closed) whatsappWindow.close();
            alert(error.message);
        }
        finally { orderLink.style.pointerEvents = ''; }
    });

    @endif
    const raffleButton = $('beautyRaffleOpen');
    raffleButton?.addEventListener('click', () => {
        const saved = JSON.parse(localStorage.getItem('beautyCustomer') || '{}');
        $('beautyRaffleName').value = saved.name || '';
        $('beautyRaffleWhatsapp').value = saved.whatsapp || '';
        openModal('beautyRaffleModal');
    });
    $('beautyRaffleForm')?.addEventListener('submit', async event => {
        event.preventDefault();
        const number = $('beautyRaffleNumber').value.replace(/\D/g, '');
        const resultBox = $('beautyRaffleResult');
        if (number.length !== 6) { resultBox.textContent = 'أدخل رقم البطاقة من 6 أرقام.'; resultBox.classList.add('show'); return; }
        try {
            const data = await request(config.raffleUrl, {card_number: number, customer: {name: $('beautyRaffleName').value.trim(), whatsapp: $('beautyRaffleWhatsapp').value.trim()}});
            localStorage.setItem('beautyCustomer', JSON.stringify({name: $('beautyRaffleName').value.trim(), whatsapp: $('beautyRaffleWhatsapp').value.trim()}));
            resultBox.innerHTML = `<strong>${data.title || 'تم التحقق'}</strong><p>${data.message || ''}</p>${data.prize_image ? `<img src="${data.prize_image}" alt="${data.prize_title || ''}">` : ''}`;
            resultBox.classList.add('show');
        } catch (error) { resultBox.innerHTML = `<strong>تنبيه</strong><p>${error.message}</p>`; resultBox.classList.add('show'); }
    });
    $('beautyStartScan')?.addEventListener('click', async () => {
        const box = $('beautyScanner'), video = $('beautyScannerVideo');
        if (!navigator.mediaDevices?.getUserMedia || !('BarcodeDetector' in window)) { alert('المسح بالكاميرا غير متاح في هذا المتصفح. أدخل الرقم يدويًا.'); return; }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: {ideal: 'environment'}}});
            box.classList.add('open'); video.srcObject = stream; await video.play();
            const detector = new BarcodeDetector({formats: ['qr_code', 'code_128', 'ean_13']});
            const scan = async () => {
                if (!box.classList.contains('open')) return;
                const codes = await detector.detect(video).catch(() => []);
                const value = codes[0]?.rawValue?.match(/\d{6}/)?.[0];
                if (value) { $('beautyRaffleNumber').value = value; stream.getTracks().forEach(track => track.stop()); video.srcObject = null; box.classList.remove('open'); return; }
                requestAnimationFrame(scan);
            };
            scan();
        } catch (error) { alert('تعذر تشغيل الكاميرا. تحقق من إذن الكاميرا ثم حاول مرة أخرى.'); }
    });
})();
</script>
@viewScript('front.cosmetics_store-2')
<script>
(() => {
    const modal = document.getElementById('beautyProductImageModal');
    const viewer = document.getElementById('beautyProductImageViewer');
    const closeButton = document.getElementById('beautyProductImageClose');
    const galleries = @json($productGalleries);
    const previous = document.getElementById('beautyImagePrev'), next = document.getElementById('beautyImageNext'), count = document.getElementById('beautyImageCount');
    let images = [], index = 0, opener = null, startX = null;
    const show = delta => {
        if (!images.length) return;
        index = (index + delta + images.length) % images.length;
        viewer.src = images[index];
        count.textContent = `${index + 1} / ${images.length}`;
        previous.hidden = next.hidden = images.length < 2;
    };
    document.querySelectorAll('.beauty-product').forEach(card => {
        const pictures = galleries[card.querySelector('[data-add-product]')?.dataset.id] || [];
        const picture = card.querySelector('.product-picture');
        if (!pictures.length || !picture) return;
        picture.dataset.productImage = pictures[0];
        picture.dataset.productTitle = card.querySelector('.product-name')?.textContent || '';
        picture.setAttribute('role', 'button'); picture.tabIndex = 0;
        picture.setAttribute('aria-label', 'عرض صور ' + picture.dataset.productTitle);
        if (pictures.length > 1) {
            const badge = document.createElement('span'); badge.className = 'beauty-gallery-badge';
            let selected = 0;
            const render = delta => {
                selected = (selected + delta + pictures.length) % pictures.length;
                picture.querySelector('img').src = pictures[selected];
                picture.dataset.productImage = pictures[selected];
                badge.textContent = `${selected + 1} / ${pictures.length}`;
            };
            if (!picture.querySelector('img')) {
                const image = document.createElement('img'); image.alt = picture.dataset.productTitle;
                picture.querySelector('i')?.remove(); picture.prepend(image);
            }
            badge.dir = 'ltr'; picture.append(badge); render(0);
            const frame = document.createElement('div'); frame.className = 'beauty-picture-frame';
            picture.before(frame); frame.append(picture);
            [-1, 1].forEach(delta => {
                const arrow = document.createElement('button'); arrow.type = 'button';
                arrow.className = 'beauty-card-arrow ' + (delta === 1 ? 'gallery-forward' : 'gallery-back');
                arrow.textContent = delta === 1 ? '❮' : '❯';
                arrow.setAttribute('aria-label', delta === 1 ? 'الصورة التالية' : 'الصورة السابقة');
                arrow.onclick = event => { event.preventDefault(); event.stopPropagation(); render(delta); };
                frame.append(arrow);
            });
            let touch = null, suppressClickUntil = 0;
            picture.addEventListener('touchstart', event => {
                touch = event.touches.length === 1 && !event.target.closest('button') ? {x:event.touches[0].clientX,y:event.touches[0].clientY} : null;
            }, {passive:true});
            picture.addEventListener('touchend', event => {
                if (touch) {
                    const dx = event.changedTouches[0].clientX - touch.x, dy = event.changedTouches[0].clientY - touch.y;
                    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) { render(dx < 0 ? 1 : -1); suppressClickUntil = Date.now() + 500; }
                }
                touch = null;
            }, {passive:true});
            picture.addEventListener('touchcancel', () => { touch = null; });
            picture.addEventListener('click', event => { if (Date.now() < suppressClickUntil) { event.preventDefault(); event.stopPropagation(); } }, true);
        }
    });
    previous.onclick = () => show(-1); next.onclick = () => show(1);
    viewer.addEventListener('touchstart', event => { startX = event.touches.length === 1 ? event.touches[0].clientX : null; }, {passive:true});
    viewer.addEventListener('touchend', event => {
        if (startX !== null && Math.abs(event.changedTouches[0].clientX - startX) > 45) show(event.changedTouches[0].clientX < startX ? 1 : -1);
        startX = null;
    }, {passive:true});
    const close = () => {
        if (!modal) return;
        window.OzmanBeautyOverlay.close(modal);
        viewer?.removeAttribute('src');
        if (viewer) viewer.alt = '';
        opener?.focus({preventScroll:true});
    };
    const open = trigger => {
        if (!modal || !viewer || !trigger.dataset.productImage) return;
        opener = trigger;
        images = galleries[trigger.closest('.beauty-product')?.querySelector('[data-add-product]')?.dataset.id] || [trigger.dataset.productImage];
        index = Math.max(0, images.indexOf(trigger.dataset.productImage)); show(0);
        viewer.alt = trigger.dataset.productTitle || 'صورة المنتج';
        window.OzmanBeautyOverlay.open(modal);
        closeButton.focus({preventScroll:true});
    };
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-product-image]');
        if (!trigger || event.target.closest('.beauty-video-hint, .beauty-card-arrow')) return;
        event.preventDefault();
        open(trigger);
    });
    document.addEventListener('keydown', event => {
        const trigger = event.target.closest?.('[data-product-image]');
        if (trigger && !event.target.closest('button') && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            open(trigger);
            return;
        }
        if (event.key === 'Escape' && modal?.classList.contains('open')) close();
        if (modal?.classList.contains('open')) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); show(event.key === 'ArrowLeft' ? 1 : -1); }
            if (event.key === 'Tab') {
                const buttons = [closeButton, previous, next].filter(button => !button.hidden);
                const first = buttons[0], last = buttons.at(-1);
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        }
    });
    closeButton?.addEventListener('click', close);
    modal?.addEventListener('click', event => { if (event.target === modal) close(); });
})();
</script>
<script>
(() => {
    const prices = window.beautyProductPrices || {};
    document.querySelectorAll('[data-add-product]').forEach(button => {
        const price = prices[String(button.dataset.id)];
        const target = button.closest('.product-bottom')?.querySelector('.product-price');
        if (!target || !price || price.discount === null || Number(price.discount) >= Number(price.original)) return;
        const oldPrice = document.createElement('del');
        oldPrice.textContent = `${Number(price.original).toFixed(2)} ₪`;
        const salePrice = document.createElement('ins');
        salePrice.textContent = `${Number(price.discount).toFixed(2)} ₪`;
        target.classList.add('product-price-stack');
        target.replaceChildren(oldPrice, salePrice);
    });
})();
</script>
@if($shop->slug === 'elegance-perfumes' && $watchProducts->isNotEmpty())
@include('front.partials.clothing_photo_tryon', ['watchTryOn' => true])
@include('front.partials.watch_tryon_styles')
<script type="module">
{!! file_get_contents(base_path('public/clothing-photo-tryon.js')) !!}
</script>
@endif
@include('front.partials.cosmetics_customer_rewards')
@include('front.partials.shop_bottom_screens')
@include('front.partials.page_share', ['title' => $shop->name, 'url' => $shop->publicUrl()])
</body></html>
