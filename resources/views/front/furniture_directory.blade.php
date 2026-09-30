<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @php
        $assetUrl = function ($path, $fallback = 'images/logo.svg') {
            if (! filled($path)) return asset($fallback);
            return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://', 'storage/']) ? asset($path) : asset('storage/'.$path);
        };
        $featuredShop = $shops->first();
    @endphp
    @include('front.partials.seo', [
        'title' => 'معرض البيت | أثاث وأدوات منزلية',
        'description' => 'استكشف معارض الأثاث والأدوات المنزلية، واختر القطع حسب الغرفة والستايل.',
        'canonical' => route('furniture.directory'),
        'image' => $featuredShop ? $assetUrl($featuredShop->banner ?: $featuredShop->logo) : asset('images/logo.svg'),
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--ink:#161914;--ink-2:#202119;--paper:#f8f1e5;--sand:#e3cda7;--clay:#b76f48;--sage:#9aaa78;--line:#f9e6c43d;--muted:#cbc2b5}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;overflow-x:hidden;background:radial-gradient(circle at 87% -10%,#bd805736,transparent 30%),radial-gradient(circle at 3% 40%,#9baa7830,transparent 28%),var(--ink);color:var(--paper);font-family:Cairo,Arial,sans-serif}.shell{width:min(1440px,calc(100% - 36px));margin:auto;padding:18px 0 72px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.back{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid var(--line);border-radius:999px;background:#1d2019;color:#edd9ae;text-decoration:none;font-size:11px;font-weight:900}.identity{display:flex;align-items:center;gap:10px}.identity-mark{display:grid;place-items:center;width:42px;height:42px;border:1px solid #ebcd9380;border-radius:15px;background:linear-gradient(145deg,#403121,#1b2019);color:#f4d492;font-size:22px}.identity strong{display:block;font-family:'DM Serif Display',Cairo,serif;font-size:21px;line-height:1}.identity span{display:block;margin-top:3px;color:#bdb6aa;font-size:9px;font-weight:700;letter-spacing:.1em}.hero{position:relative;display:grid;grid-template-columns:1.05fr .95fr;min-height:475px;overflow:hidden;border:1px solid var(--line);border-radius:36px;background:#272217;box-shadow:0 28px 80px #0008}.hero-visual{position:relative;overflow:hidden;background:linear-gradient(125deg,#625143,#29291e 55%,#726049)}.hero-visual:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#20201711,rgba(16,18,14,.3)),linear-gradient(0deg,#17191488,transparent 54%)}.hero-visual img{width:100%;height:100%;object-fit:cover;filter:saturate(.82) contrast(.94)}.hero-visual .fallback{position:absolute;inset:0;background:radial-gradient(ellipse at 59% 39%,#e4b78b52 0 10%,transparent 10.5%),radial-gradient(ellipse at 39% 76%,#abc08f44 0 16%,transparent 16.5%),linear-gradient(125deg,#594536,#23261e)}.arch{position:absolute;z-index:2;left:10%;bottom:-4%;width:44%;height:74%;border:12px solid #f1d8ab77;border-bottom:0;border-radius:230px 230px 0 0;box-shadow:inset 0 0 0 9px #24271f55}.floor{position:absolute;z-index:3;bottom:13%;left:7%;right:7%;height:3px;background:#f4ddb074;box-shadow:0 48px 0 #f4ddb02b}.hero-copy{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:clamp(28px,6vw,78px);background:linear-gradient(140deg,#25251c,#151813)}.eyebrow{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border:1px solid #e5c48d77;border-radius:999px;background:#2e2b1d;color:#f4d48f;font-size:10px;font-weight:900;letter-spacing:.08em}.hero h1{max-width:560px;margin:15px 0 12px;font-family:'DM Serif Display',Cairo,serif;font-size:clamp(43px,5.4vw,74px);line-height:1.04}.hero p{max-width:500px;margin:0;color:#d7cec0;font-size:14px;line-height:2}.hero-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:22px}.hero-actions a{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:46px;padding:0 16px;border-radius:14px;background:var(--paper);color:#2d2118;text-decoration:none;font-size:11px;font-weight:900}.hero-actions a.alt{border:1px solid #efce9766;background:#30352a;color:#f6dba6}.hero-note{display:flex;align-items:center;gap:9px;margin-top:24px;color:#c2baad;font-size:10px}.hero-note i{display:grid;place-items:center;width:27px;height:27px;border-radius:50%;background:#9aaa78;color:#1d2519}.intro-grid{display:grid;grid-template-columns:1.1fr .9fr .9fr;gap:12px;margin:16px 0}.intro{position:relative;min-height:126px;overflow:hidden;padding:18px;border:1px solid var(--line);border-radius:25px;background:linear-gradient(145deg,#28271e,#1b1d18)}.intro:after{content:"";position:absolute;left:-30px;bottom:-70px;width:160px;height:160px;border-radius:50%;background:#c77b5135;filter:blur(7px)}.intro:nth-child(2):after{background:#a7b68a35}.intro:nth-child(3):after{background:#dbbd7330}.intro i{position:absolute;z-index:1;left:14px;bottom:8px;color:#f5d9a847;font-size:56px}.intro small,.section-kicker{color:#e8c882;font-size:10px;font-weight:900;letter-spacing:.08em}.intro strong{position:relative;z-index:2;display:block;margin-top:7px;font-size:17px}.intro p{position:relative;z-index:2;max-width:80%;margin:4px 0 0;color:#c8c0b4;font-size:10px;line-height:1.7}.directory{padding:25px;border:1px solid var(--line);border-radius:34px;background:linear-gradient(145deg,#22231b,#151713)}.directory-head{display:flex;align-items:end;justify-content:space-between;gap:18px}.directory-head h2{margin:5px 0 0;font-family:'DM Serif Display',Cairo,serif;font-size:37px;line-height:1.15}.directory-head p{max-width:410px;margin:0;color:#c8c0b4;font-size:11px;line-height:1.9}.mood-filter{display:flex;gap:7px;flex-wrap:wrap;margin:20px 0}.mood-filter button{min-height:36px;padding:0 13px;border:1px solid #eed8ac42;border-radius:999px;background:#181a16;color:#cfc6b8;font-family:inherit;font-size:10px;font-weight:900;cursor:pointer;transition:.2s}.mood-filter button.active,.mood-filter button:hover{border-color:#eccb8b;background:#e8c98f;color:#2b2017}.shop-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,390px));justify-content:start;gap:16px}.showroom{position:relative;display:block;overflow:hidden;min-height:430px;border:1px solid #f5e2bf38;border-radius:27px;background:#2a291f;color:inherit;text-decoration:none;transition:.25s;cursor:pointer}.showroom:hover{transform:translateY(-6px);border-color:#ebc782;box-shadow:0 20px 35px #0006}.showroom:focus-visible{outline:3px solid #f1d08f;outline-offset:4px}.showroom-banner{position:absolute;inset:0 0 42% 0;background:#31352a}.showroom-banner:after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,#25271f,transparent 72%)}.showroom-banner img{width:100%;height:100%;object-fit:cover;filter:brightness(.78) saturate(.85)}.showroom-banner .banner-fallback{width:100%;height:100%;background:radial-gradient(circle at 72% 32%,#d3a06d52,transparent 23%),linear-gradient(130deg,#66503d,#303428)}.brand-disc{position:absolute;z-index:4;top:calc(58% - 48px);right:19px;display:grid;place-items:center;width:82px;height:82px;overflow:hidden;border:4px solid #f7e8cc;border-radius:28px;background:#fbf5e9;box-shadow:0 10px 25px #0008}.brand-disc img{width:100%;height:100%;object-fit:contain}.showroom-body{position:absolute;inset:58% 0 0;padding:45px 19px 16px;background:linear-gradient(160deg,#292a20,#1b1d18)}.showroom-body h3{max-width:74%;margin:0;color:#fff6e8;font-size:21px}.showroom-body p{display:-webkit-box;min-height:36px;margin:4px 0 10px;overflow:hidden;color:#c8c0b2;font-size:10px;line-height:1.75;-webkit-box-orient:vertical;-webkit-line-clamp:2}.category-pills{display:flex;gap:5px;min-height:25px;overflow:hidden}.category-pills span{flex:0 0 auto;padding:4px 7px;border:1px solid #ead2a252;border-radius:999px;color:#e8c98b;font-size:8px;font-weight:800}.showroom-footer{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:12px}.shop-link{display:inline-flex;align-items:center;gap:6px;padding:9px 11px;border-radius:11px;background:#f2d39a;color:#2b2117;font-size:10px;font-weight:900}.items-count{color:#bfb7a9;font-size:9px;font-weight:800}.empty{display:grid;place-items:center;min-height:280px;grid-column:1/-1;padding:30px;border:1px dashed #ecd1a75c;border-radius:24px;color:#cac0b1;text-align:center}.empty i{display:block;margin-bottom:10px;color:#ebc987;font-size:44px}.mini-look{position:absolute;z-index:3;top:calc(58% - 37px);left:18px;display:flex;align-items:end;direction:ltr}.mini-look img{width:54px;height:54px;margin-left:-10px;border:2px solid #f6e7ca;border-radius:16px;background:#22251d;object-fit:cover;box-shadow:0 6px 12px #0007}.mini-look img:first-child{margin-left:0}.footer-note{display:flex;align-items:center;justify-content:center;gap:8px;margin:18px 0 0;color:#ada698;font-size:10px}.footer-note i{color:#dfb76f}.lang{position:absolute;z-index:10;top:12px;left:12px}@media(max-width:800px){.shell{width:calc(100% - 18px);padding-top:10px}.identity strong{font-size:18px}.hero{grid-template-columns:1fr;min-height:580px;border-radius:26px}.hero-visual{min-height:230px}.hero-copy{padding:25px 20px 30px}.hero h1{font-size:44px}.hero p{font-size:11px}.intro-grid{grid-template-columns:1fr;gap:8px;margin:10px 0}.intro{min-height:92px;padding:13px}.intro strong{font-size:15px}.directory{padding:14px 9px;border-radius:25px}.directory-head{display:block;padding:4px 7px}.directory-head h2{font-size:28px}.directory-head p{margin-top:7px}.mood-filter{padding:0 7px;margin:14px 0}.mood-filter button{min-height:31px;padding:0 10px;font-size:8px}.shop-grid{grid-template-columns:1fr;gap:10px}.showroom{min-height:400px;border-radius:22px}.showroom-body{padding:40px 14px 14px}.brand-disc{right:14px}.mini-look{left:14px}.topbar .back{font-size:9px}.lang{top:8px;left:8px}}@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <a class="back" href="{{ route('home') }}"><i class="ti ti-arrow-right"></i> كل الأقسام</a>
        <div class="identity"><div class="identity-mark"><i class="ti ti-armchair-2"></i></div><div><strong>بيت وذوق</strong><span>FURNITURE &amp; HOME EDIT</span></div></div>
    </header>

    <section class="hero">
        <div class="hero-visual">
            @if($featuredShop?->banner)<img src="{{ $assetUrl($featuredShop->banner) }}" alt="{{ $featuredShop->name }}">@else<div class="fallback"></div>@endif
            <div class="arch"></div><div class="floor"></div>
        </div>
        <div class="hero-copy">
            <span class="eyebrow"><i class="ti ti-sparkles"></i> HOME, CURATED FOR YOU</span>
            <h1>ابدأ بالغرفة… وخلّي البيت يحكي عنك.</h1>
            <p>مساحة مستقلة لمعارض الأثاث والأدوات المنزلية. اختَر ستايلك، استكشف القطع، وبعدها ادخل معرض المحل الذي يناسبك.</p>
            <div class="hero-actions"><a href="#showrooms"><i class="ti ti-building-store"></i> استكشف المعارض</a><a class="alt" href="#moods"><i class="ti ti-layout-grid"></i> اختَر حسب الستايل</a></div>
            <div class="hero-note"><i class="ti ti-bulb"></i><span>فكرة الصفحة: تتسوّق حسب شكل الغرفة، مش فقط حسب اسم المنتج.</span></div>
        </div>
        <div class="lang">@include('front.partials.public_language_switcher')</div>
    </section>

    <section class="intro-grid" id="moods">
        <article class="intro"><i class="ti ti-armchair-2"></i><small>START WITH A ROOM</small><strong>رتّب غرفة كاملة</strong><p>غرفة الضيوف، النوم، السفرة أو زاوية المكتب.</p></article>
        <article class="intro"><i class="ti ti-palette"></i><small>SHOP THE MOOD</small><strong>اختَر المزاج أولاً</strong><p>دافئ، مودرن، طبيعي، أو بسيط وهادئ.</p></article>
        <article class="intro"><i class="ti ti-ruler-measure"></i><small>PLAN SMART</small><strong>قارن قبل القرار</strong><p>ادخل للمعرض وشوف القطع المتاحة بوضوح.</p></article>
    </section>

    <section class="directory" id="showrooms">
        <header class="directory-head"><div><span class="section-kicker">THE HOME DISTRICT</span><h2>معارض تختار منها شكل بيتك</h2></div><p>كل بطاقة هي باب لمعرض مستقل بتصميمه ومنتجاته. اختَر محلّك من خلال الصور والأنواع التي يعرضها.</p></header>
        <nav class="mood-filter" aria-label="فلترة معارض الأثاث">
            <button class="active" type="button" data-filter="all">كل المعارض</button>
            <button type="button" data-filter="living">غرف الجلوس</button>
            <button type="button" data-filter="bedroom">غرف النوم</button>
            <button type="button" data-filter="dining">السفرة والمطبخ</button>
            <button type="button" data-filter="office">مكتب وتنظيم</button>
            <button type="button" data-filter="decor">ديكور وإكسسوارات</button>
        </nav>
        <div class="shop-grid" id="shopGrid">
            @forelse($shops as $shop)
                @php
                    $categoryText = $shop->categories->pluck('name')->filter()->implode(' ');
                    $searchText = \Illuminate\Support\Str::lower($shop->name.' '.$shop->description.' '.$categoryText);
                    $tags = collect([
                        str_contains($searchText, 'نوم') || str_contains($searchText, 'سرير') ? 'bedroom' : null,
                        str_contains($searchText, 'مكتب') || str_contains($searchText, 'تنظيم') ? 'office' : null,
                        str_contains($searchText, 'سفرة') || str_contains($searchText, 'مطبخ') ? 'dining' : null,
                        str_contains($searchText, 'ديكور') || str_contains($searchText, 'إكسسوار') ? 'decor' : null,
                    ])->filter()->push('living')->unique()->implode(' ');
                    $previewProducts = $shop->products->take(3);
                @endphp
                <a class="showroom" href="{{ $shop->publicUrl() }}" data-tags="{{ $tags }}" aria-label="دخول معرض {{ $shop->name }}">
                    <div class="showroom-banner">@if($shop->banner)<img src="{{ $assetUrl($shop->banner) }}" alt="{{ $shop->name }}">@else<div class="banner-fallback"></div>@endif</div>
                    <div class="brand-disc"><img src="{{ $assetUrl($shop->logo ?: $shop->banner) }}" alt="{{ $shop->name }}"></div>
                    @if($previewProducts->isNotEmpty())<div class="mini-look">@foreach($previewProducts as $product)<img src="{{ $assetUrl($product->main_image) }}" alt="{{ $product->localized('name') }}">@endforeach</div>@endif
                    <div class="showroom-body">
                        <h3>{{ $shop->name }}</h3>
                        <p>{{ $shop->description ?: 'معرض أثاث وأدوات منزلية؛ ادخل واستكشف القطع المتاحة فيه.' }}</p>
                        <div class="category-pills">@forelse($shop->categories->take(4) as $category)<span>{{ $category->localized('name') }}</span>@empty<span>أثاث وديكور</span>@endforelse</div>
                        <div class="showroom-footer"><span class="shop-link">دخول المعرض <i class="ti ti-arrow-left"></i></span><span class="items-count">{{ $shop->products->count() }} معاينة منتجات</span></div>
                    </div>
                </a>
            @empty
                <div class="empty"><div><i class="ti ti-armchair-2"></i><strong>معارض الأثاث قادمة قريبًا</strong><p>أضف محلاً بنوع «أثاث وأدوات كهربائية» ليظهر تلقائيًا داخل هذه المساحة.</p></div></div>
            @endforelse
        </div>
        <p class="footer-note"><i class="ti ti-sparkles"></i> كل معرض له صفحته الخاصة؛ هذي المساحة فقط تساعد العميل يختار من وين يبدأ.</p>
    </section>
</main>
<script>
(() => {
    const buttons=[...document.querySelectorAll('[data-filter]')], cards=[...document.querySelectorAll('[data-tags]')];
    buttons.forEach(button=>button.addEventListener('click',()=>{
        buttons.forEach(item=>item.classList.toggle('active',item===button));
        cards.forEach(card=>{ const visible=button.dataset.filter==='all'||card.dataset.tags.split(' ').includes(button.dataset.filter); card.hidden=!visible; });
    }));
})();
</script>
</body>
</html>
