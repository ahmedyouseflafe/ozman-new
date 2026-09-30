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
        'title' => $shop->name.' | حلويات وكيكات',
        'description' => $shop->description ?: 'تصفح الكيك والحلويات والمخبوزات الطازجة من '.$shop->name,
        'canonical' => route('sweets.store', $shop),
        'image' => $shopBanner,
        'schema' => ['@context' => 'https://schema.org', '@type' => 'Bakery', 'name' => $shop->name, 'url' => route('sweets.store', $shop), 'image' => $shopBanner, 'telephone' => $shop->phone],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--cream:#fff6e7;--gold:#f2c26c;--caramel:#bf6d35;--rose:#e9788b;--ink:#150908;--panel:#29100e;--line:rgba(255,224,177,.2);--muted:#ddc3b3}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:radial-gradient(circle at 85% 0,rgba(198,111,48,.24),transparent 24%),radial-gradient(circle at 4% 35%,rgba(226,115,137,.15),transparent 30%),#140908;color:var(--cream);font-family:Cairo,Arial,sans-serif}a{color:inherit;text-decoration:none}button,input{font:inherit}.shell{width:min(1380px,calc(100% - 32px));margin:auto;padding:18px 0 96px}.hero{display:grid;grid-template-columns:minmax(0,1fr) 355px;min-height:350px;overflow:hidden;border:1px solid var(--line);border-radius:30px;background:#1b0908;box-shadow:0 24px 65px #0006;direction:ltr}.hero-media{position:relative;min-height:350px;background:#090403}.hero-media:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#16070699,transparent 53%,#16070655);pointer-events:none}.slide{position:absolute;inset:0;opacity:0;transition:opacity .65s}.slide.active{opacity:1}.slide img,.slide video{display:block;width:100%;height:100%;object-fit:cover}.fallback{position:absolute;inset:0;background:linear-gradient(90deg,#19070644,#19070688),url('{{ $shopBanner }}') center/cover}.brand{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:24px 20px;text-align:center;direction:rtl;background:radial-gradient(circle at 50% 17%,rgba(245,180,91,.2),transparent 31%),linear-gradient(145deg,#3c1825,#19090b)}.tools{position:absolute;top:13px;right:13px;left:13px;display:flex;justify-content:space-between;align-items:center;gap:8px}.back{display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border:1px solid #f2c26c77;border-radius:999px;background:#160706aa;color:#f6d88f;font-size:10px;font-weight:900}.logo{width:128px;height:128px;object-fit:contain;border:3px solid var(--gold);border-radius:50%;background:#170807;box-shadow:0 0 0 8px #f2c26c13,0 0 38px #c06f3577}.brand h1{margin:0;font-family:'Playfair Display',Cairo,serif;font-size:30px;line-height:1.2}.brand p{max-width:270px;margin:0;color:var(--muted);font-size:11px;line-height:1.85}.open{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border:1px solid #66dca0;border-radius:999px;color:#8dffc2;background:#35cf8411;font-size:10px;font-weight:900}.open:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 10px currentColor}.cta{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 15px;border-radius:13px;background:linear-gradient(135deg,var(--gold),var(--rose));color:#30100c;font-size:12px;font-weight:900}.catalog{margin-top:16px;padding:17px;border:1px solid var(--line);border-radius:30px;background:linear-gradient(145deg,#3a1714e8,#180909f5);box-shadow:0 18px 50px #0004}.catalog-header{display:flex;align-items:end;justify-content:space-between;gap:10px;margin:2px 4px 16px}.eyebrow{display:block;margin-bottom:3px;color:#f1bd6d;font-size:10px;font-weight:900;letter-spacing:.08em}.catalog h2{margin:0;font-family:'Playfair Display',Cairo,serif;font-size:28px}.catalog-header p{margin:4px 0 0;color:var(--muted);font-size:11px}.count{padding:5px 9px;border:1px solid #f2c26c55;border-radius:999px;color:#f9d78e;font-size:10px;font-weight:900}.layout{display:grid;grid-template-columns:108px minmax(0,1fr);gap:15px;direction:ltr}.categories{display:flex;flex-direction:column;gap:11px;max-height:710px;padding:4px;overflow:auto;scrollbar-color:var(--caramel) transparent}.category{border:0;background:none;color:#c7a79a;cursor:pointer}.category figure{display:grid;place-items:center;width:72px;height:72px;overflow:hidden;margin:0 auto 5px;border:2px solid #fff2;border-radius:50%;background:#26100d;color:var(--gold);font-size:27px;transition:.2s}.category img{width:100%;height:100%;object-fit:cover}.category span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:9px;font-weight:800;text-align:center}.category.active{color:#fff}.category.active figure{border-color:var(--gold);box-shadow:0 0 22px #f2c26c77;transform:scale(1.07)}.content{min-width:0;direction:rtl}.pane[hidden]{display:none}.pane-head{display:flex;align-items:center;justify-content:space-between;margin:2px 3px 13px}.pane-head h3{margin:0;font-size:21px}.pane-head small{color:#edc379;font-size:10px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:13px}.card{overflow:hidden;border:1px solid var(--line);border-radius:21px;background:linear-gradient(145deg,#351614,#190a09);transition:.22s}.card:hover{transform:translateY(-5px);border-color:#f2c26ca8}.picture{position:relative;display:block;width:100%;padding:0;border:0;aspect-ratio:1/.9;overflow:hidden;background:#170807;cursor:zoom-in}.picture img{width:100%;height:100%;object-fit:cover;transition:.35s}.card:hover .picture img{transform:scale(1.06)}.badge{position:absolute;top:9px;right:9px;padding:4px 8px;border:1px solid #fff1;border-radius:999px;background:#351411dd;color:#ffe3a5;font-size:9px;font-weight:900}.body{padding:11px}.small-brand{min-height:14px;color:#f28f9e;font-size:9px;font-weight:800}.name{min-height:41px;margin:2px 0 5px;font-size:13px;line-height:1.55}.description{min-height:30px;margin:0;color:var(--muted);font-size:9px;line-height:1.6}.foot{display:flex;align-items:center;justify-content:space-between;gap:7px;margin-top:10px;padding-top:9px;border-top:1px solid var(--line)}.prices{display:grid;gap:1px}.was{color:#bd9488;font-size:9px;text-decoration:line-through}.price{color:#ffe09a;font-size:14px;font-weight:900}.add{min-width:89px;height:34px;padding:0 8px;border:0;border-radius:11px;background:linear-gradient(135deg,#f0a668,#e9759a);color:#30100c;font-size:10px;font-weight:900}.empty{display:grid;place-items:center;min-height:250px;border:1px dashed var(--line);border-radius:20px;color:var(--muted);font-size:13px;text-align:center}.cart-button{position:fixed;z-index:30;right:20px;bottom:18px;display:flex;align-items:center;gap:10px;padding:7px 10px 7px 15px;border:1px solid #f2c26c88;border-radius:17px;background:#351411ef;color:#fff;box-shadow:0 14px 35px #0007;backdrop-filter:blur(15px);cursor:pointer}.cart-button i{display:grid;place-items:center;width:39px;height:39px;border-radius:12px;background:linear-gradient(135deg,var(--gold),var(--rose));color:#30100c;font-size:18px}.cart-button strong{display:block;font-size:11px}.cart-button small{display:block;color:#ecd2c1;font-size:9px}.layer{position:fixed;z-index:50;inset:0;display:none;align-items:end;justify-content:center;padding:15px;background:#000a;backdrop-filter:blur(5px)}.layer.open{display:flex}.sheet{width:min(500px,100%);max-height:80vh;overflow:auto;padding:19px;border:1px solid #f2c26c88;border-radius:24px;background:linear-gradient(145deg,#3e1917,#1b0a09);direction:rtl}.sheet-head,.total{display:flex;align-items:center;justify-content:space-between}.sheet h2{margin:0;font-size:20px}.close{display:grid;place-items:center;width:33px;height:33px;border:1px solid var(--line);border-radius:50%;background:transparent;color:#fff;font-size:20px}.items{display:grid;gap:7px;margin:14px 0}.row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px;border-radius:12px;background:#12060588;font-size:11px}.remove{border:0;background:none;color:#fb8fa0;font-size:18px}.total{padding-top:11px;border-top:1px solid var(--line);color:#ffe2a3;font-weight:900}.fields{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:14px 0}.fields input{min-width:0;height:43px;padding:0 11px;border:1px solid var(--line);border-radius:11px;outline:0;background:#160807;color:#fff;font-size:11px}.send{width:100%;min-height:46px;border:0;border-radius:13px;background:linear-gradient(135deg,var(--gold),var(--rose));color:#2c0d0a;font-size:13px;font-weight:900}.lightbox{position:fixed;z-index:60;inset:0;display:none;align-items:center;justify-content:center;padding:24px;background:#000d}.lightbox.open{display:flex}.lightbox img{max-width:min(760px,100%);max-height:88vh;border:2px solid #f2c26c99;border-radius:20px;object-fit:contain}.lightbox button{position:absolute;top:20px;left:20px;width:40px;height:40px;border:1px solid var(--line);border-radius:50%;background:#351411;color:#fff;font-size:23px}@media(max-width:720px){.shell{width:calc(100% - 18px);padding-top:9px}.hero{grid-template-columns:minmax(0,1fr) 145px;min-height:385px;border-radius:22px}.hero-media{min-height:385px}.brand{padding:15px 8px}.tools{top:6px;right:6px;left:6px}.tools .public-language-switcher{padding:3px}.tools .public-language-switcher a{padding:4px;font-size:8px}.back{padding:4px;font-size:7px}.logo{width:96px;height:96px;margin-top:30px}.brand h1{font-size:17px}.brand p{font-size:8px}.open{padding:5px 7px;font-size:8px}.cta{min-height:34px;padding:0 7px;font-size:9px}.catalog{margin-top:8px;padding:8px;border-radius:21px}.catalog-header{margin:3px 4px 10px}.catalog h2{font-size:22px}.catalog-header p{font-size:9px}.count{padding:4px 7px;font-size:8px}.layout{grid-template-columns:76px minmax(0,1fr);gap:5px}.categories{gap:8px}.category figure{width:51px;height:51px;font-size:19px}.category span{font-size:8px}.grid{grid-template-columns:1fr;gap:10px}.card{display:grid;grid-template-columns:110px minmax(0,1fr)}.picture{grid-row:1/3;aspect-ratio:auto;min-height:145px}.body{padding:9px}.name{min-height:32px;font-size:12px}.description{display:none}.cart-button{right:10px;bottom:10px}.fields{grid-template-columns:1fr}}
    </style>
    <style>
        /* A pastry counter, not another generic storefront: paper notes, warm oven
           colors, a flavour rail and a display case for the baked goods. */
        body{background:radial-gradient(circle at 14% 4%,#ffd99a22,transparent 22%),radial-gradient(circle at 88% 26%,#ec889522,transparent 28%),#1c0c08}
        .hero{position:relative;display:block;min-height:510px;border-radius:38px;background:#35150e;isolation:isolate;overflow:hidden}
        .hero-media{position:absolute;inset:0;min-height:0;background:linear-gradient(130deg,#3c180f,#160705)}
        .hero-media:after{background:linear-gradient(90deg,#1c0805bb 0%,transparent 62%),linear-gradient(0deg,#1b0805cc,transparent 60%)}
        .fallback{background:radial-gradient(ellipse at 70% 35%,#f9c87855 0 10%,transparent 10.5%),radial-gradient(ellipse at 36% 70%,#f59c7455 0 15%,transparent 15.5%),radial-gradient(ellipse at 65% 75%,#b8543855 0 18%,transparent 18.5%),repeating-linear-gradient(135deg,#5c2415 0 13px,#461b12 13px 27px)}
        .fallback.with-banner{background:linear-gradient(90deg,#1c080566,#1c080599),var(--bakery-banner) center/cover}
        .brand{position:absolute;z-index:3;right:56px;bottom:38px;width:min(365px,calc(100% - 112px));min-height:360px;padding:66px 30px 28px;border:1px dashed #a96a3d;border-radius:9px 28px 22px 28px;color:#4a2216;background:linear-gradient(145deg,#fff5d8,#f7d6a7);box-shadow:18px 18px 0 #12060466,0 18px 46px #0008;transform:rotate(1.1deg)}
        .brand:before{content:"مخبوز اليوم بحب";position:absolute;top:18px;right:24px;padding:5px 10px;border-radius:3px;background:#bd5a42;color:#fff5dd;font-size:10px;font-weight:900;letter-spacing:.05em;transform:rotate(-2deg)}
        .brand:after{content:"";position:absolute;top:-8px;left:39%;width:88px;height:24px;background:#fff0c280;transform:rotate(-4deg)}
        .tools{top:16px;right:auto;left:18px;z-index:2}.back{border-color:#9b5b36;background:#fff4df;color:#5a2a19}.tools .public-language-switcher{background:#fff1d8;border-color:#b06c42}.tools .public-language-switcher a{color:#5a2a19}
        .logo{width:102px;height:102px;border-color:#a96237;background:#fff9e9;box-shadow:0 0 0 7px #c878451c,0 8px 0 #d8a06655}
        .brand h1{font-size:34px;color:#4a2115;text-shadow:none}.brand p{color:#7f503a;font-size:11px}.open{border-color:#48a576;color:#23734c;background:#e1f6de}.cta{border-radius:999px;background:#522017;color:#fff4d8;box-shadow:inset 0 -3px #2d0f0a}
        .bakery-moments{display:grid;grid-template-columns:1.1fr .9fr .9fr;gap:12px;margin:18px 0}.bakery-moment{position:relative;min-height:118px;padding:19px 20px;overflow:hidden;border:1px solid #f8cf9570;border-radius:23px;background:linear-gradient(135deg,#592318,#2c110d);box-shadow:0 12px 28px #0004}.bakery-moment:nth-child(2){background:linear-gradient(135deg,#3d2734,#29111a)}.bakery-moment:nth-child(3){background:linear-gradient(135deg,#56411d,#2a1b0e)}.bakery-moment i{position:absolute;left:17px;bottom:13px;color:#ffc66d55;font-size:58px}.bakery-moment small{display:block;color:#f6bd72;font-size:10px;font-weight:900;letter-spacing:.06em}.bakery-moment strong{display:block;position:relative;z-index:1;margin-top:6px;font-size:18px}.bakery-moment p{position:relative;z-index:1;margin:4px 0 0;color:#ebc9ae;font-size:10px}
        .catalog{padding:0;border:0;border-radius:0;background:transparent;box-shadow:none}.catalog-header{align-items:center;margin:22px 4px 13px}.catalog h2{font-size:32px;color:#ffe7b9}.eyebrow{color:#f6ba62}.count{background:#32120e;border-color:#d88a4b;color:#ffe0a0}
        .layout{display:block;direction:rtl}.categories{display:flex;flex-direction:row;align-items:stretch;gap:9px;max-height:none;padding:0 2px 13px;overflow-x:auto;overflow-y:hidden;scrollbar-width:thin}.category{flex:0 0 120px;padding:8px 6px;border:1px solid #f9d8ac2e;border-radius:18px;background:linear-gradient(135deg,#34120f,#230b09);transition:.2s}.category:hover{border-color:#efb96d}.category figure{width:52px;height:52px;margin:0 auto 4px;border-color:#f8ce9155;background:#4a1b12}.category span{font-size:10px}.category.active{border-color:#f2c26c;background:linear-gradient(135deg,#6d2b1b,#3c120e)}.category.active figure{box-shadow:0 0 0 4px #f2c26c1f,0 0 20px #f2c26c88;transform:none}
        .content{padding:18px;border:1px solid #f6cf9460;border-radius:29px;background:linear-gradient(145deg,#35140feb,#1a0807f2);box-shadow:0 20px 48px #0005}.pane-head{padding-bottom:12px;border-bottom:1px dashed #f7d7ae48}.pane-head h3{font-family:'Playfair Display',Cairo,serif;font-size:26px;color:#ffdfab}.grid{grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}.card{border-radius:25px;background:linear-gradient(165deg,#fff2d5,#d99b70);color:#4b2116;box-shadow:0 14px 25px #0004}.card:hover{border-color:#fff0c5;box-shadow:0 20px 35px #0007}.picture{aspect-ratio:1/.84;background:#7d3c23}.badge{border-color:#ffe5ad;background:#582117;color:#ffe4ad}.body{padding:12px}.small-brand{color:#a4493d}.name{color:#512015;font-size:14px}.description{color:#80523e}.foot{border-color:#a957442c}.price{color:#753119}.was{color:#a56b55}.add{border-radius:999px;background:#562015;color:#fff2d5;box-shadow:inset 0 -3px #2c100b}.empty{min-height:185px;border:1px dashed #efbf8155;background:radial-gradient(circle at 75% 28%,#f5bd7424,transparent 25%),#2d100d;color:#f2c8a6}.cart-button{border-color:#f0b96e;background:#fff0ce;color:#4b2015;box-shadow:0 14px 36px #0008}.cart-button i{background:#5b2317;color:#ffe8bb}.cart-button small{color:#8c543c}.sheet{border-color:#efbe78;background:linear-gradient(145deg,#fff1d5,#e2a077);color:#4b2116}.sheet .close{border-color:#a75e3e;color:#4b2116}.row{background:#fff7e799}.total{border-color:#a85d4333;color:#64301e}.fields input{border-color:#a65d443f;background:#fff9ea;color:#4b2116}.send{border-radius:999px;background:#5a2116;color:#fff1d3}.lightbox button{background:#fff0d5;color:#4b2116}
        @media(max-width:720px){.hero{min-height:475px;border-radius:25px}.brand{right:18px;bottom:23px;width:calc(100% - 36px);min-height:295px;padding:58px 18px 20px}.brand h1{font-size:25px}.brand p{font-size:9px}.logo{width:82px;height:82px}.tools{left:11px;top:12px}.bakery-moments{grid-template-columns:1fr;gap:8px;margin:10px 0}.bakery-moment{min-height:88px;padding:13px 15px}.bakery-moment strong{font-size:15px}.bakery-moment i{font-size:46px}.catalog-header{align-items:end}.catalog h2{font-size:24px}.categories{padding-bottom:9px}.category{flex-basis:94px;padding:6px 4px}.category figure{width:43px;height:43px}.content{padding:10px;border-radius:22px}.pane-head h3{font-size:20px}.grid{grid-template-columns:1fr;gap:10px}.card{grid-template-columns:116px minmax(0,1fr);border-radius:18px}.picture{min-height:142px}.description{display:none}}
    </style>
    <style>
        .cart-button.cart-flight-pop{animation:sweet-cart-pop .9s cubic-bezier(.16,.9,.2,1);box-shadow:0 14px 36px #0008,0 0 38px rgba(242,194,108,.82)}
        @keyframes sweet-cart-pop{0%,100%{transform:scale(1)}38%{transform:scale(1.27) rotate(-3deg)}70%{transform:scale(.96) rotate(1deg)}}
        .shopping-cart-flight{position:fixed;z-index:90;display:grid;place-items:center;overflow:visible;width:62px;height:62px;pointer-events:none;will-change:transform,opacity}
        .shopping-cart-flight-media{position:relative;z-index:2;display:grid;place-items:center;overflow:hidden;width:100%;height:100%;border:2px solid #fff5dd;border-radius:16px;background:#4a1b12;box-shadow:0 12px 24px rgba(237,117,154,.5)}
        .shopping-cart-flight-media img{width:100%;height:100%;object-fit:cover}.shopping-cart-flight-media i{font-size:27px;color:#ffe5a2}
        .shopping-cart-flight-stars{position:absolute;z-index:1;inset:0;transform:translate(var(--trail-x,0),var(--trail-y,0));pointer-events:none}
        .shopping-cart-flight-stars b{position:absolute;color:#ffe4a2;font-size:17px;line-height:1;text-shadow:0 0 10px #ef8c91;animation:cart-flight-sparkle .7s ease-in-out infinite alternate}
        .shopping-cart-flight-stars b:nth-child(1){left:7px;top:10px}.shopping-cart-flight-stars b:nth-child(2){left:23px;top:38px;font-size:12px;animation-delay:.16s}.shopping-cart-flight-stars b:nth-child(3){left:43px;top:19px;font-size:10px;animation-delay:.32s}
        @keyframes cart-flight-sparkle{from{opacity:.28;transform:scale(.55) rotate(0)}to{opacity:1;transform:scale(1.3) rotate(45deg)}}
        @media(prefers-reduced-motion:reduce){.cart-button.cart-flight-pop{animation:none}.shopping-cart-flight{display:none}}
    </style>
</head>
<body>
<main class="shell">
    <section class="hero">
        <div class="hero-media">
            @forelse($displayItems as $item)
                <div class="slide {{ $loop->first ? 'active' : '' }}" data-slide>
                    @if($isVideo($item->media))<video src="{{ $mediaUrl($item->media) }}" muted playsinline autoplay loop></video>
                    @else<img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title ?: $shop->name }}">@endif
                </div>
            @empty
                <div class="fallback"></div>
            @endforelse
        </div>
        <aside class="brand">
            <div class="tools"><a class="back" href="{{ route('home') }}"><i class="ti ti-arrow-right"></i> كل المحلات</a>@include('front.partials.public_language_switcher')</div>
            <img class="logo" src="{{ $shopLogo }}" alt="{{ $shop->name }}">
            <h1>{{ $shop->name }}</h1>
            <p>{{ $shop->description ?: 'كيكات، حلويات ومخبوزات طازجة تُجهز بحب لكل لحظة حلوة.' }}</p>
            <span class="open">طلباتنا متاحة الآن</span>
            <a class="cta" href="#catalog"><i class="ti ti-cookie"></i> اكتشف أشهى الأصناف</a>
        </aside>
    </section>
    <section class="bakery-moments" aria-label="تجربة المخبز">
        <article class="bakery-moment"><i class="ti ti-cake"></i><small>CELEBRATE SWEETLY</small><strong>كيكات للمناسبات</strong><p>تفاصيل حلوة تجعل كل احتفال أجمل.</p></article>
        <article class="bakery-moment"><i class="ti ti-bread"></i><small>FROM THE OVEN</small><strong>طازج من الفرن</strong><p>مخبوزات دافئة بطعم البيت الحقيقي.</p></article>
        <article class="bakery-moment"><i class="ti ti-ice-cream-2"></i><small>DAILY LITTLE JOY</small><strong>حلاوة اليوم</strong><p>اختار قطعة صغيرة تغيّر مزاجك.</p></article>
    </section>
    <section class="catalog" id="catalog">
        <header class="catalog-header"><div><span class="eyebrow">SWEET SELECTION</span><h2>اختار الحلو اللي نفسك فيه</h2><p>تشكيلة طازجة من {{ $shop->name }}</p></div><span class="count">{{ $categories->sum(fn($category) => $category->products->count()) + $uncategorizedProducts->count() }} صنف</span></header>
        <div class="layout">
            <nav class="categories" aria-label="أقسام الحلويات">
                @forelse($categories as $category)
                    @php($categoryImage = $category->image ?: $category->products->first()?->main_image)
                    <button class="category {{ $loop->first ? 'active' : '' }}" type="button" data-tab="{{ $category->id }}"><figure>@if($categoryImage)<img src="{{ $mediaUrl($categoryImage) }}" alt="">@else<i class="ti ti-cookie"></i>@endif</figure><span>{{ $category->localized('name') }}</span></button>
                @empty
                    <button class="category active" type="button" data-tab="items"><figure><i class="ti ti-cookie"></i></figure><span>الأصناف</span></button>
                @endforelse
                @if($categories->isNotEmpty() && $uncategorizedProducts->isNotEmpty())
                    <button class="category" type="button" data-tab="uncategorized"><figure><i class="ti ti-cookie"></i></figure><span>أصناف أخرى</span></button>
                @endif
            </nav>
            <div class="content">
                @forelse($categories as $category)
                    <section class="pane" data-pane="{{ $category->id }}" @if(! $loop->first) hidden @endif><header class="pane-head"><h3>{{ $category->localized('name') }}</h3><small>{{ $category->products->count() }} صنف</small></header><div class="grid">@forelse($category->products as $product) @include('front.partials.sweet_product_card', ['product' => $product, 'mediaUrl' => $mediaUrl, 'shop' => $shop]) @empty <div class="empty">قريبًا ستتوفر أصناف لذيذة في هذا القسم 🍰</div> @endforelse</div></section>
                @empty
                    <section class="pane" data-pane="items"><header class="pane-head"><h3>أصنافنا</h3><small>{{ $uncategorizedProducts->count() }} صنف</small></header><div class="grid">@forelse($uncategorizedProducts as $product) @include('front.partials.sweet_product_card', ['product' => $product, 'mediaUrl' => $mediaUrl, 'shop' => $shop]) @empty <div class="empty">أضف أصناف الحلويات من لوحة التحكم لتظهر هنا 🍰</div> @endforelse</div></section>
                @endforelse
                @if($categories->isNotEmpty() && $uncategorizedProducts->isNotEmpty())
                    <section class="pane" data-pane="uncategorized" hidden><header class="pane-head"><h3>أصناف أخرى</h3><small>{{ $uncategorizedProducts->count() }} صنف</small></header><div class="grid">@foreach($uncategorizedProducts as $product) @include('front.partials.sweet_product_card', ['product' => $product, 'mediaUrl' => $mediaUrl, 'shop' => $shop]) @endforeach</div></section>
                @endif
            </div>
        </div>
    </section>
</main>
<button class="cart-button" id="cartButton" type="button"><i class="ti ti-shopping-bag"></i><span><strong><span id="cartCount">0</span> أصناف في السلة</strong><small id="cartTotal">0.00 ₪</small></span></button>
<div class="layer" id="cartLayer"><section class="sheet"><header class="sheet-head"><h2>سلة الحلويات</h2><button class="close" id="cartClose" type="button">×</button></header><div class="items" id="cartItems"></div><div class="total"><span>المجموع</span><span id="sheetTotal">0.00 ₪</span></div><div class="fields"><input id="customerName" placeholder="اسمك"><input id="customerPhone" inputmode="tel" placeholder="رقم الجوال أو واتساب"></div><button class="send" id="sendOrder" type="button"><i class="ti ti-brand-whatsapp"></i> إرسال الطلب عبر واتساب</button></section></div>
<div class="lightbox" id="lightbox"><button type="button">×</button><img src="" alt=""></div>
<script>
(() => {
    const cart = [], money = value => Number(value || 0).toFixed(2) + ' ₪';
    const tabs = [...document.querySelectorAll('[data-tab]')], panes = [...document.querySelectorAll('[data-pane]')];
    tabs.forEach(tab => tab.onclick = () => { tabs.forEach(item => item.classList.toggle('active', item === tab)); panes.forEach(pane => pane.hidden = pane.dataset.pane !== tab.dataset.tab); });
    const count = document.getElementById('cartCount'), total = document.getElementById('cartTotal'), sheetTotal = document.getElementById('sheetTotal'), items = document.getElementById('cartItems');
    function renderCart() {
        const sum = cart.reduce((value, item) => value + item.price * item.quantity, 0);
        count.textContent = cart.reduce((value, item) => value + item.quantity, 0); total.textContent = money(sum); sheetTotal.textContent = money(sum);
        items.innerHTML = cart.length ? cart.map(item => '<div class="row"><span>' + item.quantity + '× ' + item.name + '</span><span>' + money(item.price * item.quantity) + ' <button class="remove" type="button" data-remove="' + item.id + '">×</button></span></div>').join('') : '<div class="empty" style="min-height:120px">السلة فارغة، اختار صنفًا لذيذًا أولًا 🍩</div>';
    }
    let cartPopTimer;
    const popCart = () => {
        const cartButton = document.getElementById('cartButton');
        clearTimeout(cartPopTimer);
        cartButton.classList.remove('cart-flight-pop');
        void cartButton.offsetWidth;
        cartButton.classList.add('cart-flight-pop');
        cartPopTimer = setTimeout(() => cartButton.classList.remove('cart-flight-pop'), 950);
    };
    const flyToSweetCart = button => {
        const cartButton = document.getElementById('cartButton');
        const source = button.closest('.card')?.querySelector('.picture');
        if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches || !source) {
            popCart();
            return;
        }
        const from = source.getBoundingClientRect(), to = cartButton.getBoundingClientRect();
        const size = Math.min(68, Math.max(46, Math.min(from.width, from.height) * .34));
        const flight = document.createElement('div');
        flight.className = 'shopping-cart-flight';
        flight.style.width = size + 'px'; flight.style.height = size + 'px';
        flight.style.left = (from.left + (from.width - size) / 2) + 'px';
        flight.style.top = (from.top + (from.height - size) / 2) + 'px';
        const media = document.createElement('span'); media.className = 'shopping-cart-flight-media';
        const image = source.querySelector('img');
        if (image?.currentSrc || image?.src) { const clone = document.createElement('img'); clone.src = image.currentSrc || image.src; clone.alt = ''; media.append(clone); }
        else media.innerHTML = '<i class="ti ti-shopping-bag"></i>';
        const stars = document.createElement('span'); stars.className = 'shopping-cart-flight-stars'; stars.innerHTML = '<b>✦</b><b>✧</b><b>✦</b>';
        flight.append(media, stars); document.body.append(flight);
        const x = to.left + to.width / 2 - (from.left + from.width / 2), y = to.top + to.height / 2 - (from.top + from.height / 2);
        const distance = Math.hypot(x, y) || 1;
        stars.style.setProperty('--trail-x', (-x / distance * 56) + 'px');
        stars.style.setProperty('--trail-y', (-y / distance * 56) + 'px');
        flight.animate([{transform:'translate(0,0) scale(1)',opacity:1},{transform:'translate(' + (x*.35) + 'px,' + (y*.18-74) + 'px) scale(.82) rotate(-9deg)',opacity:1,offset:.42},{transform:'translate(' + x + 'px,' + y + 'px) scale(.16) rotate(12deg)',opacity:.22}],{duration:5000,easing:'cubic-bezier(.16,.8,.24,1)',fill:'forwards'}).finished.catch(() => {}).finally(() => { flight.remove(); popCart(); });
    };
    document.addEventListener('click', event => { const button = event.target.closest('[data-add]'); if (!button) return; const found = cart.find(item => item.id === button.dataset.add); if (found) found.quantity += 1; else cart.push({id:button.dataset.add,name:button.dataset.name,price:Number(button.dataset.price),quantity:1}); renderCart(); flyToSweetCart(button); });
    items.onclick = event => { const button = event.target.closest('[data-remove]'); if (!button) return; const index = cart.findIndex(item => item.id === button.dataset.remove); if (index >= 0) cart.splice(index,1); renderCart(); };
    const layer = document.getElementById('cartLayer'); document.getElementById('cartButton').onclick = () => { layer.classList.add('open'); renderCart(); }; document.getElementById('cartClose').onclick = () => layer.classList.remove('open'); layer.onclick = event => { if (event.target === layer) layer.classList.remove('open'); };
    document.getElementById('sendOrder').onclick = () => { if (!cart.length) return; const name = document.getElementById('customerName').value.trim(), phone = document.getElementById('customerPhone').value.trim(); if (!name || !phone) { alert('اكتب الاسم ورقم الجوال أولًا.'); return; } const sum = cart.reduce((value, item) => value + item.price * item.quantity, 0); const lines = cart.map(item => '- ' + item.quantity + '× ' + item.name + ': ' + money(item.price * item.quantity)); const target = @json($whatsappNumber); if (!target) { alert('لا يوجد رقم واتساب مضاف للمحل بعد.'); return; } window.open('https://wa.me/' + target + '?text=' + encodeURIComponent('طلب جديد من ' + name + '\nرقم التواصل: ' + phone + '\n\n' + lines.join('\n') + '\n\nالمجموع: ' + money(sum)), '_blank', 'noopener'); };
    const lightbox = document.getElementById('lightbox'), image = lightbox.querySelector('img'); document.querySelectorAll('[data-image]').forEach(button => button.onclick = () => { image.src = button.dataset.image; lightbox.classList.add('open'); }); lightbox.onclick = event => { if (event.target === lightbox || event.target.tagName === 'BUTTON') lightbox.classList.remove('open'); };
    const slides = [...document.querySelectorAll('[data-slide]')]; let current = 0; if (slides.length > 1) setInterval(() => { slides[current].classList.remove('active'); current = (current + 1) % slides.length; slides[current].classList.add('active'); }, 6500);
    renderCart();
})();
</script>
</body>
</html>
