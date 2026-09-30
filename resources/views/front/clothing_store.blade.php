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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--ink:#101011;--charcoal:#19191c;--bone:#f5f1ea;--acid:#d6ff38;--lilac:#c0a7ff;--muted:#a5a3aa;--line:#ffffff1c}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;overflow-x:hidden;background:radial-gradient(circle at 12% 10%,#9072ff26,transparent 25%),#101011;color:var(--bone);font-family:Cairo,Arial,sans-serif}button,input{font:inherit}button{cursor:pointer}a{color:inherit;text-decoration:none}.fashion-page{width:min(1450px,calc(100% - 32px));margin:auto;padding:16px 0 110px}.fashion-hero{position:relative;display:grid;grid-template-columns:1fr .93fr;min-height:min(700px,calc(100vh - 34px));overflow:hidden;border:1px solid var(--line);border-radius:34px;background:#1a1a1d}.fashion-hero-media{position:relative;min-height:430px;background:#2a2a2e}.fashion-hero-media img{width:100%;height:100%;object-fit:cover;filter:grayscale(.25) contrast(1.05)}.fashion-hero-media:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent 55%,#18181bcc),linear-gradient(0deg,#171719a8,transparent 58%)}.fashion-hero-copy{position:relative;display:flex;flex-direction:column;align-items:flex-start;justify-content:end;padding:clamp(28px,6vw,84px);background:radial-gradient(circle at 84% 17%,#d6ff3830,transparent 18%),#18181b}.hero-top{position:absolute;z-index:5;top:20px;right:22px;left:22px;display:flex;align-items:center;justify-content:space-between;gap:10px}.back-home{display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border:1px solid #ffffff34;border-radius:999px;background:#131316d9;color:#faf5ec;font-size:10px;font-weight:900}.season{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border:1px solid #d6ff3877;border-radius:999px;color:var(--acid);font-size:10px;font-weight:900;letter-spacing:.12em}.fashion-hero h1{max-width:600px;margin:17px 0 8px;font-family:'DM Serif Display',Cairo,serif;font-size:clamp(49px,6vw,92px);line-height:.93;letter-spacing:-.025em}.fashion-hero h1 em{color:var(--acid);font-weight:400}.fashion-hero p{max-width:480px;margin:0;color:#d0cdd0;font-size:13px;line-height:1.95}.hero-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:23px}.hero-actions a{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:46px;padding:0 16px;border-radius:13px;background:var(--acid);color:#151516;font-size:11px;font-weight:900}.hero-actions a.alt{border:1px solid #ffffff38;background:#252529;color:var(--bone)}.brand-signature{position:absolute;z-index:3;bottom:28px;left:28px;display:flex;align-items:center;gap:9px;padding:7px 14px 7px 7px;border:1px solid #ffffff3b;border-radius:999px;background:#101012d2;backdrop-filter:blur(12px)}.brand-signature img{width:48px;height:48px;border-radius:50%;background:#fff;object-fit:contain}.brand-signature strong{display:block;font-size:12px}.brand-signature span{display:block;color:#aaa6ad;font-size:9px}.ticker{display:flex;gap:30px;overflow:hidden;padding:12px 0;color:#151516;background:var(--acid);font-size:11px;font-weight:900;white-space:nowrap}.ticker span{animation:ticker 20s linear infinite}@keyframes ticker{to{transform:translateX(100%)}}.fashion-intro{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin:16px 0}.intro-tile{position:relative;min-height:142px;overflow:hidden;padding:18px;border:1px solid var(--line);border-radius:22px;background:#1b1b1e}.intro-tile i{position:absolute;left:15px;bottom:12px;color:#ffffff1c;font-size:58px}.intro-tile small{color:var(--acid);font-size:9px;font-weight:900;letter-spacing:.1em}.intro-tile strong{position:relative;display:block;margin-top:8px;font-size:18px}.intro-tile p{position:relative;max-width:78%;margin:4px 0 0;color:#aaa7ae;font-size:10px;line-height:1.75}.lookbook{margin-top:23px;padding:24px;border:1px solid var(--line);border-radius:32px;background:linear-gradient(135deg,#202024,#151518)}.section-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:17px}.section-head small{color:var(--acid);font-size:10px;font-weight:900;letter-spacing:.1em}.section-head h2{margin:4px 0 0;font-family:'DM Serif Display',Cairo,serif;font-size:37px}.section-head p{max-width:350px;margin:0;color:#aaa7ae;font-size:11px;line-height:1.8}.look-grid{display:grid;grid-template-columns:1.15fr .85fr .85fr;gap:12px;min-height:420px}.look-card{position:relative;min-height:300px;overflow:hidden;border-radius:22px;background:#29292d}.look-card:first-child{grid-row:span 2}.look-card img{width:100%;height:100%;object-fit:cover;transition:.5s}.look-card:hover img{transform:scale(1.06)}.look-card:after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,#101011e8,transparent 56%)}.look-card-info{position:absolute;z-index:2;right:16px;bottom:15px;left:16px}.look-card-info h3{margin:0;font-size:18px}.look-card-info span{display:block;margin:4px 0 9px;color:#d4d1d5;font-size:10px}.look-card-info button{display:inline-flex;align-items:center;gap:6px;min-height:34px;padding:0 10px;border:0;border-radius:10px;background:#f4f1ec;color:#171719;font-size:10px;font-weight:900}.look-fallback{display:grid;place-items:center;width:100%;height:100%;background:radial-gradient(circle at 55% 30%,#d6ff3833,transparent 24%),linear-gradient(145deg,#3e304c,#16171b);color:#d6ff38;font-size:60px}.catalog{margin-top:18px;padding:22px;border:1px solid var(--line);border-radius:32px;background:#151518}.catalog-head{display:flex;align-items:end;justify-content:space-between;gap:14px}.catalog-head h2{margin:4px 0 0;font-family:'DM Serif Display',Cairo,serif;font-size:37px}.catalog-head p{margin:0;color:#aaa7ae;font-size:11px}.filters{display:flex;gap:7px;flex-wrap:wrap;margin:19px 0}.filters button{min-height:35px;padding:0 12px;border:1px solid #ffffff25;border-radius:999px;background:#1f1f23;color:#bebbc1;font-size:10px;font-weight:800}.filters button.active,.filters button:hover{border-color:var(--acid);background:var(--acid);color:#151516}.fashion-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px}.fashion-card{overflow:hidden;border:1px solid #ffffff1b;border-radius:20px;background:#202024;transition:.22s}.fashion-card:hover{transform:translateY(-5px);border-color:#d6ff3888;box-shadow:0 18px 32px #0006}.fashion-photo{position:relative;display:block;width:100%;padding:0;border:0;aspect-ratio:4/5;background:#2a2a2d;cursor:zoom-in}.fashion-photo img{width:100%;height:100%;object-fit:cover}.fashion-photo .badge{position:absolute;top:10px;right:10px;padding:4px 7px;border-radius:999px;background:#d6ff38;color:#151516;font-size:8px;font-weight:900}.fashion-info{padding:11px}.fashion-brand{display:block;min-height:13px;color:var(--acid);font-size:8px;font-weight:900}.fashion-name{min-height:42px;margin:3px 0;font-size:13px;line-height:1.6}.fashion-meta{display:flex;gap:5px;min-height:22px;overflow:hidden}.fashion-meta span{flex:0 0 auto;padding:3px 6px;border:1px solid #ffffff20;border-radius:999px;color:#bcb8c0;font-size:8px}.fashion-bottom{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;padding-top:9px;border-top:1px solid #ffffff18}.fashion-price{color:#fff5e3;font-size:14px;font-weight:900}.fashion-add{display:inline-flex;align-items:center;justify-content:center;width:35px;height:35px;border:0;border-radius:11px;background:var(--acid);color:#151516;font-size:18px}.empty{display:grid;place-items:center;min-height:220px;grid-column:1/-1;border:1px dashed #ffffff32;border-radius:22px;color:#aaa7ae;text-align:center}.bag{position:fixed;z-index:50;right:18px;bottom:16px;display:flex;align-items:center;gap:9px;padding:7px 12px 7px 8px;border:1px solid #d6ff3899;border-radius:17px;background:#f7f3ec;color:#151516;box-shadow:0 15px 36px #0009}.bag i{display:grid;place-items:center;width:39px;height:39px;border-radius:12px;background:#18181b;color:var(--acid);font-size:18px}.bag strong,.bag small{display:block}.bag strong{font-size:10px}.bag small{color:#5c5961;font-size:9px}.overlay{position:fixed;z-index:100;inset:0;display:none;align-items:end;justify-content:center;padding:15px;background:#000b;backdrop-filter:blur(7px)}.overlay.open{display:flex}.sheet{width:min(520px,100%);max-height:86vh;overflow:auto;padding:19px;border:1px solid #ffffff3c;border-radius:25px;background:#f5f1ea;color:#19191c}.sheet-head{display:flex;align-items:center;justify-content:space-between}.sheet h2{margin:0;font-size:20px}.x{display:grid;place-items:center;width:34px;height:34px;border:1px solid #232326;border-radius:50%;background:transparent;color:#19191c;font-size:20px}.cart-items{display:grid;gap:7px;margin:13px 0}.cart-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px;border-radius:12px;background:#e5ded2;font-size:11px}.remove{border:0;background:none;color:#b7384b;font-size:18px}.total{display:flex;justify-content:space-between;padding-top:11px;border-top:1px solid #2c2c2c25;font-weight:900}.buyer{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:14px 0}.buyer input{min-width:0;height:43px;border:1px solid #29292d44;border-radius:11px;padding:0 10px;background:#fff;color:#171719}.send{width:100%;min-height:46px;border:0;border-radius:13px;background:#1b1b1e;color:var(--acid);font-weight:900}.quick{align-items:center;padding:14px}.quick-card{position:relative;display:grid;grid-template-columns:1fr .8fr;max-width:800px;overflow:hidden;border:1px solid #ffffff3d;border-radius:25px;background:#19191d;color:#f6f1e9}.quick-card img{width:100%;height:100%;min-height:390px;object-fit:cover;background:#2c2c30}.quick-copy{padding:30px 23px}.quick-copy .x{position:absolute;z-index:2;top:12px;left:12px;border-color:#ffffff55;background:#101011bb;color:#fff}.quick-copy small{color:var(--acid);font-weight:900}.quick-copy h2{margin:7px 0;font-size:27px;line-height:1.35}.quick-copy p{color:#aeabb2;font-size:11px;line-height:1.8}.option-label{display:block;margin:15px 0 6px;font-size:10px;font-weight:900}.options{display:flex;gap:6px;flex-wrap:wrap}.options button{min-width:35px;min-height:31px;border:1px solid #ffffff35;border-radius:9px;background:#25252a;color:#fff;font-size:10px}.options button.active{border-color:var(--acid);background:var(--acid);color:#151516}.quick-add{width:100%;min-height:43px;margin-top:18px;border:0;border-radius:12px;background:var(--acid);color:#171719;font-weight:900}.fashion-page .public-language-switcher{border-color:#ffffff2d;background:#161619dc}.fashion-page .public-language-switcher a{color:#cbc7ce}.fashion-page .public-language-switcher a.active{background:var(--acid);color:#171719}@media(max-width:760px){.fashion-page{width:calc(100% - 18px);padding-top:9px}.fashion-hero{grid-template-columns:1fr;min-height:650px;border-radius:24px}.fashion-hero-media{min-height:300px}.fashion-hero-copy{padding:25px 20px 32px}.fashion-hero h1{font-size:52px}.hero-top{top:10px;right:10px;left:10px}.brand-signature{display:none}.fashion-intro{grid-template-columns:1fr;gap:8px;margin:10px 0}.intro-tile{min-height:92px;padding:13px}.lookbook,.catalog{padding:12px 9px;border-radius:23px}.section-head,.catalog-head{display:block;padding:4px 5px}.section-head h2,.catalog-head h2{font-size:28px}.section-head p{margin-top:7px}.look-grid{grid-template-columns:1fr 1fr;min-height:0;gap:8px}.look-card{min-height:200px;border-radius:17px}.look-card:first-child{grid-column:1/-1;grid-row:auto;min-height:310px}.look-card-info{right:11px;bottom:11px;left:11px}.fashion-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.fashion-info{padding:9px}.fashion-name{font-size:11px}.buyer{grid-template-columns:1fr}.quick-card{grid-template-columns:1fr;max-height:90vh;overflow:auto}.quick-card img{min-height:260px;max-height:42vh}.quick-copy{padding:20px}.bag{right:9px;bottom:9px}}@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important;animation:none!important}}
    </style>
</head>
<body>
<main class="fashion-page">
    <section class="fashion-hero">
        <div class="fashion-hero-media"><img src="{{ $heroImage }}" alt="{{ $shop->name }}"></div>
        <div class="fashion-hero-copy">
            <div class="hero-top"><a class="back-home" href="{{ route('home') }}"><i class="ti ti-arrow-right"></i> كل المحلات</a>@include('front.partials.public_language_switcher')</div>
            <span class="season"><i class="ti ti-sparkles"></i> NEW SEASON / 2026</span>
            <h1>لبسك مش بس قطعة.<br><em>هذا ستايلك.</em></h1>
            <p>{{ $shop->description ?: 'اختيارات جريئة، قطع يومية، وتفاصيل تخليك تطلع بطريقتك.' }}</p>
            <div class="hero-actions"><a href="#catalog"><i class="ti ti-hanger-2"></i> شوف المجموعة</a><a class="alt" href="#lookbook"><i class="ti ti-aperture"></i> اللوك بوك</a><button class="demo-try" id="demoTryOn" type="button" data-image="{{ asset('images/virtual-tryon/demo-oversized-black-tee.png') }}"><i class="ti ti-scan"></i> جرّب تيشيرت بالكاميرا</button></div>
        </div>
        <div class="brand-signature"><img src="{{ $mediaUrl($shop->logo ?: $shop->banner) }}" alt="{{ $shop->name }}"><div><strong>{{ $shop->name }}</strong><span>CURATED FOR YOUR NEXT LOOK</span></div></div>
    </section>
    <div class="ticker" aria-hidden="true"><span>NEW DROP — STYLE IT YOUR WAY — {{ $shop->name }} — NEW DROP — STYLE IT YOUR WAY — {{ $shop->name }} —</span></div>
    <section class="fashion-intro"><article class="intro-tile"><i class="ti ti-shirt"></i><small>THE RIGHT FIT</small><strong>اختار المقاس بثقة</strong><p>المقاسات والألوان المتاحة تظهر لك قبل ما تضيف للسلة.</p></article><article class="intro-tile"><i class="ti ti-sparkles"></i><small>STYLE THE LOOK</small><strong>شوف القطع كلوك</strong><p>مش بس منتجات جنب بعض؛ رتبناها كإلهام للّبسة.</p></article><article class="intro-tile"><i class="ti ti-shopping-bag"></i><small>READY TO GO</small><strong>سلة سريعة وواضحة</strong><p>اختار مقاسك ولونك، وبعدها ابعت الطلب للمحل مباشرة.</p></article></section>
    <section class="lookbook" id="lookbook"><header class="section-head"><div><small>THE EDIT / LOOKBOOK</small><h2>اختيارات هذا الأسبوع</h2></div><p>كل صورة توصلك للقطعة نفسها. اضغط على القطعة، اختار التفاصيل، وخلي اللوك يبدأ منك.</p></header><div class="look-grid">@forelse($lookProducts as $product)@php($attrs=$product->catalog_attributes ?: [])<article class="look-card"><button class="fashion-photo preview" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ (float)($product->discount_price ?: $product->price) }}" data-image="{{ $mediaUrl($product->main_image) }}" data-description="{{ $product->localized('description') }}" data-sizes="{{ e(json_encode(data_get($attrs,'sizes',[]))) }}" data-colors="{{ e(json_encode(data_get($attrs,'colors',[]))) }}"><img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $product->localized('name') }}"></button><div class="look-card-info"><h3>{{ $product->localized('name') }}</h3><span>{{ number_format((float)($product->discount_price ?: $product->price),2) }} ₪</span><button class="preview" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ (float)($product->discount_price ?: $product->price) }}" data-image="{{ $mediaUrl($product->main_image) }}" data-description="{{ $product->localized('description') }}" data-sizes="{{ e(json_encode(data_get($attrs,'sizes',[]))) }}" data-colors="{{ e(json_encode(data_get($attrs,'colors',[]))) }}"><i class="ti ti-sparkles"></i> شوف التفاصيل</button></div></article>@empty<div class="empty"><div><i class="ti ti-camera-off"></i><p>أضف منتجات مع صور حتى يظهر اللوك بوك هنا.</p></div></div>@endforelse</div></section>
    <section class="catalog" id="catalog"><header class="catalog-head"><div><small>SHOP THE DROP</small><h2>كل القطع، على ذوقك</h2></div><p>{{ $allProducts->count() }} قطعة متاحة من {{ $shop->name }}</p></header><nav class="filters"><button class="active" type="button" data-filter="all">كل المجموعة</button>@foreach($categories as $category)<button type="button" data-filter="cat-{{ $category->id }}">{{ $category->localized('name') }}</button>@endforeach @if($uncategorizedProducts->isNotEmpty())<button type="button" data-filter="uncategorized">إضافات جديدة</button>@endif</nav><div class="fashion-grid">@forelse($allProducts as $product)@php($attrs=$product->catalog_attributes ?: [])@php($sizes=data_get($attrs,'sizes',[]))@php($colors=data_get($attrs,'colors',[]))@php($sizes=is_array($sizes)?$sizes:array_filter(array_map('trim',explode(',',(string)$sizes))))@php($colors=is_array($colors)?$colors:array_filter(array_map('trim',explode(',',(string)$colors))))@php($price=(float)($product->discount_price ?: $product->price))<article class="fashion-card" data-card="{{ $product->category_id ? 'cat-'.$product->category_id : 'uncategorized' }}"><button class="fashion-photo preview" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ $price }}" data-image="{{ $mediaUrl($product->main_image) }}" data-description="{{ $product->localized('description') }}" data-sizes="{{ e(json_encode($sizes)) }}" data-colors="{{ e(json_encode($colors)) }}"><img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $product->localized('name') }}" loading="lazy">@if($product->is_featured)<span class="badge">اختيارنا</span>@endif</button><div class="fashion-info"><span class="fashion-brand">{{ data_get($attrs,'brand') ?: 'NEW EDIT' }}</span><h3 class="fashion-name">{{ $product->localized('name') }}</h3><div class="fashion-meta">@foreach(array_slice($sizes,0,3) as $size)<span>{{ $size }}</span>@endforeach @if(empty($sizes) && data_get($attrs,'material'))<span>{{ data_get($attrs,'material') }}</span>@endif</div><div class="fashion-bottom"><strong class="fashion-price">{{ number_format($price,2) }} ₪</strong><button class="fashion-add direct-add" type="button" data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ $price }}" aria-label="أضف {{ $product->localized('name') }}"><i class="ti ti-plus"></i></button></div></div></article>@empty<div class="empty"><div><i class="ti ti-hanger-off"></i><p>أضف منتجات الملابس من لوحة التحكم لتظهر المجموعة هنا.</p></div></div>@endforelse</div></section>
</main>
<button class="bag" id="bag" type="button"><i class="ti ti-shopping-bag"></i><span><strong><span id="bagCount">0</span> قطعة في السلة</strong><small id="bagTotal">0.00 ₪</small></span></button>
<div class="overlay" id="bagLayer"><section class="sheet"><header class="sheet-head"><h2>سلتك</h2><button class="x" type="button" data-close="bagLayer">×</button></header><div class="cart-items" id="cartItems"></div><div class="total"><span>المجموع</span><span id="sheetTotal">0.00 ₪</span></div><div class="buyer"><input id="buyerName" placeholder="الاسم"><input id="buyerPhone" inputmode="tel" placeholder="رقم الجوال"></div><button class="send" id="sendOrder" type="button"><i class="ti ti-brand-whatsapp"></i> إرسال الطلب للمحل</button></section></div>
<div class="overlay quick" id="quickLayer"><section class="quick-card"><button class="x" type="button" data-close="quickLayer">×</button><img id="quickImage" src="" alt=""><div class="quick-copy"><small>PIECE DETAILS</small><h2 id="quickName"></h2><strong id="quickPrice"></strong><p id="quickDescription"></p><span class="option-label" id="sizeLabel">المقاس</span><div class="options" id="sizeOptions"></div><span class="option-label" id="colorLabel">اللون</span><div class="options" id="colorOptions"></div><button class="try-on" id="tryOn" type="button"><i class="ti ti-scan"></i> جرّبها عليك بالكاميرا <small>BETA</small></button><button class="quick-add" id="quickAdd" type="button"><i class="ti ti-shopping-bag-plus"></i> أضف للسلة</button></div></section></div>
<div class="overlay try-on-overlay" id="tryOnLayer"><section class="try-on-card"><header><div><small>OZMAN VIRTUAL FIT / BETA</small><h2>جرّب القطعة عليك</h2></div><button class="x" type="button" data-close="tryOnLayer">×</button></header><div class="try-stage"><video id="tryVideo" autoplay playsinline muted></video><canvas id="tryCanvas"></canvas><div class="try-hud"><span><i class="ti ti-scan"></i> ثبّت كتفيك داخل الإطار</span><strong id="tryStatus">جاري تجهيز الكاميرا…</strong></div></div><p>تجربة مرئية مباشرة: تتحرك القطعة مع كتفيك. أفضل نتيجة تكون بصور المنتج بخلفية شفافة.</p></section></div>
<style>.demo-try{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:46px;padding:0 16px;border:1px solid #d6ff38;border-radius:13px;background:#151516;color:var(--acid);font-size:11px;font-weight:900}.demo-try i{font-size:17px}.try-on{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;min-height:42px;margin-top:13px;border:1px solid #d6ff38;background:#252529;color:#f9f5ed;border-radius:12px;font-size:11px;font-weight:900}.try-on i{color:var(--acid);font-size:17px}.try-on small{padding:2px 5px;border-radius:5px;background:var(--acid);color:#171719;font-size:7px;letter-spacing:.08em}.try-on-overlay{align-items:center}.try-on-card{width:min(660px,100%);overflow:hidden;border:1px solid #d6ff388c;border-radius:25px;background:#161618;color:#f5f1ea;box-shadow:0 25px 80px #000b}.try-on-card header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #ffffff18}.try-on-card header small{color:var(--acid);font-size:8px;font-weight:900;letter-spacing:.1em}.try-on-card h2{margin:4px 0 0;font-size:19px}.try-on-card header .x{border-color:#ffffff4d;color:#fff}.try-stage{position:relative;aspect-ratio:3/4;overflow:hidden;background:radial-gradient(circle at 50% 20%,#d6ff3824,transparent 33%),#09090a}.try-stage video,.try-stage canvas{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}.try-stage canvas{pointer-events:none}.try-hud{position:absolute;z-index:2;right:14px;bottom:14px;left:14px;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 11px;border:1px solid #ffffff37;border-radius:12px;background:#101011bd;backdrop-filter:blur(10px);font-size:9px}.try-hud span{color:#e6e3e9}.try-hud i{color:var(--acid);font-size:15px;vertical-align:middle}.try-hud strong{color:var(--acid);font-size:9px}.try-on-card>p{margin:0;padding:11px 18px;color:#aaa7ae;font-size:9px;line-height:1.7}@media(max-width:520px){.demo-try{width:100%}.try-on-card{border-radius:19px}.try-hud{align-items:flex-start;flex-direction:column}.try-stage{aspect-ratio:9/13}}</style>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js"></script>
<script>
(() => {const cart=[],money=v=>Number(v||0).toFixed(2)+' ₪',$=id=>document.getElementById(id),toList=values=>Array.isArray(values)?values:(typeof values==='string'?values.split(',').map(value=>value.trim()).filter(Boolean):[]);let selected=null,size='',color='';const render=()=>{const total=cart.reduce((sum,item)=>sum+item.price*item.qty,0);$('bagCount').textContent=cart.reduce((sum,item)=>sum+item.qty,0);$('bagTotal').textContent=$('sheetTotal').textContent=money(total);$('cartItems').innerHTML=cart.length?cart.map(item=>'<div class="cart-row"><span>'+item.qty+'× '+item.name+'</span><span>'+money(item.price*item.qty)+' <button class="remove" data-remove="'+item.key+'">×</button></span></div>').join(''):'<div class="empty" style="min-height:120px">السلة فاضية — اختار قطعة تعجبك.</div>'};const add=item=>{const name=item.name+(item.size?' · '+item.size:'')+(item.color?' · '+item.color:'');const key=item.id+'|'+(item.size||'')+'|'+(item.color||'');const existing=cart.find(entry=>entry.key===key);if(existing)existing.qty++;else cart.push({key,id:item.id,name,price:Number(item.price),qty:1});render();$('bag').animate([{transform:'scale(1)'},{transform:'scale(1.18)'},{transform:'scale(1)'}],{duration:480,easing:'ease-out'})};const open=layer=>layer.classList.add('open'),close=layer=>layer.classList.remove('open');document.querySelectorAll('[data-close]').forEach(button=>button.onclick=()=>close($(button.dataset.close)));document.querySelectorAll('.overlay').forEach(layer=>layer.onclick=e=>{if(e.target===layer)close(layer)});$('bag').onclick=()=>open($('bagLayer'));document.querySelectorAll('[data-filter]').forEach(button=>button.onclick=()=>{document.querySelectorAll('[data-filter]').forEach(item=>item.classList.toggle('active',item===button));document.querySelectorAll('[data-card]').forEach(card=>card.hidden=button.dataset.filter!=='all'&&card.dataset.card!==button.dataset.filter)});document.querySelectorAll('.direct-add').forEach(button=>button.onclick=()=>add(button.dataset));const makeOptions=(target,values,kind)=>{target.innerHTML='';values.forEach((value,index)=>{const button=document.createElement('button');button.type='button';button.textContent=value;button.classList.toggle('active',index===0);button.onclick=()=>{[...target.children].forEach(x=>x.classList.toggle('active',x===button));if(kind==='size')size=value;else color=value};target.append(button)});if(kind==='size')size=values[0]||'';else color=values[0]||''};document.querySelectorAll('.preview').forEach(button=>button.onclick=()=>{selected=button.dataset;size='';color='';$('quickImage').src=selected.image;$('quickImage').alt=selected.name;$('quickName').textContent=selected.name;$('quickPrice').textContent=money(selected.price);$('quickDescription').textContent=selected.description||'قطعة مختارة لتكمل ستايلك.';let sizes=[],colors=[];try{sizes=JSON.parse(selected.sizes||'[]')}catch(_){ }try{colors=JSON.parse(selected.colors||'[]')}catch(_){ }sizes=toList(sizes);colors=toList(colors);$('sizeLabel').hidden=!sizes.length;$('sizeOptions').hidden=!sizes.length;$('colorLabel').hidden=!colors.length;$('colorOptions').hidden=!colors.length;makeOptions($('sizeOptions'),sizes,'size');makeOptions($('colorOptions'),colors,'color');open($('quickLayer'))});$('quickAdd').onclick=()=>{if(!selected)return;add({...selected,size,color});close($('quickLayer'))};$('cartItems').onclick=e=>{const button=e.target.closest('[data-remove]');if(!button)return;const index=cart.findIndex(item=>item.key===button.dataset.remove);if(index>-1)cart.splice(index,1);render()};$('sendOrder').onclick=()=>{if(!cart.length)return;const name=$('buyerName').value.trim(),phone=$('buyerPhone').value.trim();if(!name||!phone){alert('اكتب الاسم ورقم الجوال أولاً.');return}const number=@json($whatsappNumber);if(!number){alert('لا يوجد رقم واتساب للمحل بعد.');return}const lines=cart.map(item=>'- '+item.qty+'× '+item.name+' : '+money(item.price*item.qty));const total=cart.reduce((sum,item)=>sum+item.price*item.qty,0);window.open('https://wa.me/'+number+'?text='+encodeURIComponent('طلب أزياء جديد من '+name+'\nرقم التواصل: '+phone+'\n\n'+lines.join('\n')+'\n\nالمجموع: '+money(total)),'_blank','noopener')};render()})();
</script>
<script>
(() => {
    const layer = document.getElementById('tryOnLayer');
    const video = document.getElementById('tryVideo');
    const canvas = document.getElementById('tryCanvas');
    const status = document.getElementById('tryStatus');
    const trigger = document.getElementById('tryOn');
    const closeButtons = [...document.querySelectorAll('[data-close="tryOnLayer"]')];
    const ctx = canvas.getContext('2d');
    let stream = null, pose = null, garment = null, frame = null, active = false;

    const setStatus = text => { status.textContent = text; };
    const stop = () => {
        active = false;
        if (frame) cancelAnimationFrame(frame);
        frame = null;
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    };
    const makeTransparent = source => {
        const surface = document.createElement('canvas');
        surface.width = source.naturalWidth || source.width;
        surface.height = source.naturalHeight || source.height;
        const surfaceCtx = surface.getContext('2d', { willReadFrequently: true });
        surfaceCtx.drawImage(source, 0, 0, surface.width, surface.height);
        try {
            const pixels = surfaceCtx.getImageData(0, 0, surface.width, surface.height);
            for (let i = 0; i < pixels.data.length; i += 4) {
                const [r, g, b] = [pixels.data[i], pixels.data[i + 1], pixels.data[i + 2]];
                if (r > 238 && g > 238 && b > 238) pixels.data[i + 3] = 0;
            }
            surfaceCtx.putImageData(pixels, 0, 0);
        } catch (_) { /* Image remains intact if a remote image blocks pixel access. */ }
        return surface;
    };
    const prepareCanvas = () => {
        if (!video.videoWidth || !video.videoHeight) return false;
        if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
        }
        return true;
    };
    const drawFallback = () => {
        if (!garment || !prepareCanvas()) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const width = canvas.width * .66;
        const height = width * (garment.height / garment.width);
        ctx.globalAlpha = .92;
        ctx.drawImage(garment, (canvas.width - width) / 2, canvas.height * .21, width, height);
        ctx.globalAlpha = 1;
    };
    const drawGarment = results => {
        if (!active || !garment) return;
        if (!prepareCanvas()) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const points = results.poseLandmarks && results.poseLandmarks[0];
        if (!points || !points[11] || !points[12] || points[11].visibility < .45 || points[12].visibility < .45) {
            drawFallback();
            setStatus('اظهر كتفيك للكاميرا حتى تثبت القطعة');
            return;
        }
        const left = points[11], right = points[12];
        const shoulderWidth = Math.hypot((right.x - left.x) * canvas.width, (right.y - left.y) * canvas.height);
        const width = Math.max(100, shoulderWidth * 1.7);
        const height = width * (garment.height / garment.width);
        const centerX = ((left.x + right.x) / 2) * canvas.width;
        const centerY = ((left.y + right.y) / 2) * canvas.height;
        const angle = Math.atan2((right.y - left.y) * canvas.height, (right.x - left.x) * canvas.width);
        ctx.save();
        ctx.translate(centerX, centerY + height * .28);
        ctx.rotate(angle);
        ctx.globalAlpha = .92;
        ctx.drawImage(garment, -width / 2, -height * .42, width, height);
        ctx.restore();
        setStatus('القطعة تتبع حركتك الآن');
    };
    const loop = async () => {
        if (!active) return;
        if (video.readyState >= 2 && pose) {
            try { await pose.send({ image: video }); }
            catch (_) { drawFallback(); setStatus('ظهرت القطعة — حرّك كتفيك لتفعيل التتبع'); }
        }
        frame = requestAnimationFrame(loop);
    };
    const start = async (demoImage = '') => {
        const imageUrl = demoImage || document.getElementById('quickImage').src;
        if (!imageUrl) return;
        layer.classList.add('open');
        setStatus('اسمح للكاميرا ليبدأ القياس');
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => { garment = makeTransparent(image); drawFallback(); };
        image.onerror = () => { garment = null; setStatus('تعذر تحميل صورة القطعة'); };
        image.src = imageUrl;
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } }, audio: false });
            video.srcObject = stream;
            await video.play();
            video.onloadeddata = drawFallback;
            if (!window.Pose) throw new Error('Pose library unavailable');
            pose = new Pose({ locateFile: file => 'https://cdn.jsdelivr.net/npm/@mediapipe/pose/' + file });
            pose.setOptions({ modelComplexity: 0, smoothLandmarks: true, enableSegmentation: false, minDetectionConfidence: .55, minTrackingConfidence: .55 });
            pose.onResults(drawGarment);
            active = true;
            setStatus('ابحث عن كتفيك…');
            loop();
        } catch (error) {
            setStatus('لازم تسمح للكاميرا حتى تشتغل التجربة');
        }
    };
    trigger.addEventListener('click', () => start());
    document.getElementById('demoTryOn').addEventListener('click', event => start(event.currentTarget.dataset.image));
    closeButtons.forEach(button => button.addEventListener('click', stop));
    layer.addEventListener('click', event => { if (event.target === layer) { layer.classList.remove('open'); stop(); } });
})();
</script>
</body>
</html>
