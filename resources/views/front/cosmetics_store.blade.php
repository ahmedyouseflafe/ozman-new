@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $mediaUrl = fn (?string $path) => ! filled($path) ? '' : (preg_match('/^https?:\/\//i', $path) || str_starts_with($path, 'storage/') ? asset($path) : asset('storage/'.$path));
    $shopLogo = $mediaUrl($shop->logo ?: $shop->banner) ?: asset('images/logo.svg');
    $shopImage = $mediaUrl($shop->banner ?: $shop->logo) ?: asset('images/logo.svg');
    $whatsapp = preg_replace('/\D+/', '', (string) ($shop->whatsapp ?: $shop->phone));
    $categoriesForPage = $categories->map(fn ($category) => [
        'key' => (string) $category->id,
        'name' => $category->localized('name'),
        'image' => $category->image ?: $category->products->first(fn ($product) => filled($product->main_image))?->main_image,
        'products' => $category->products,
    ]);
    if ($uncategorizedProducts->isNotEmpty()) {
        $categoriesForPage->push(['key' => 'other', 'name' => __('منتجات أخرى'), 'image' => $uncategorizedProducts->first()?->main_image, 'products' => $uncategorizedProducts]);
    }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--rose:#ff80b8;--gold:#e8c276;--ink:#08060b;--panel:#15101a;--line:rgba(255,205,228,.17);--muted:#c4b5c6}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;overflow-x:hidden;background:radial-gradient(circle at 85% 0,rgba(255,87,166,.16),transparent 25%),radial-gradient(circle at 5% 28%,rgba(189,138,255,.13),transparent 27%),var(--ink);color:#fff;font-family:Cairo,Arial,sans-serif}a{color:inherit;text-decoration:none}button{font:inherit}.cosmetics-shell{width:min(1380px,calc(100% - 32px));margin:auto;padding:20px 0 100px}.cosmetics-hero{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,370px);gap:16px;direction:ltr}.beauty-display,.beauty-brand{position:relative;min-width:0;min-height:325px;overflow:hidden;border:1px solid var(--line);border-radius:30px}.beauty-display{isolation:isolate;background:#030205;box-shadow:0 24px 65px rgba(0,0,0,.34)}.beauty-display:after{content:"";position:absolute;z-index:3;inset:0;border:5px solid rgba(8,4,10,.72);border-radius:inherit;box-shadow:inset 0 0 0 1px rgba(255,128,184,.22);pointer-events:none}.beauty-display-slider,.beauty-display-slide{position:absolute;inset:0}.beauty-display-slide{opacity:0;pointer-events:none;background:#020204;transition:opacity .6s ease}.beauty-display-slide.active{opacity:1;pointer-events:auto}.beauty-display-slide img,.beauty-display-slide iframe{display:block;width:100%;height:100%;border:0;object-fit:cover}.beauty-display-slide video{display:block;width:100%;height:100%;border:0;object-fit:contain;background:#000}.beauty-display-slide.is-logo img{object-fit:contain;padding:28px}.beauty-display-shade{position:absolute;z-index:1;inset:0;background:linear-gradient(90deg,rgba(8,3,10,.36),transparent 56%,rgba(8,3,10,.42));pointer-events:none}.beauty-brand{direction:rtl;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:11px;padding:22px;background:linear-gradient(145deg,rgba(38,19,38,.96),rgba(14,11,18,.98));text-align:center}.beauty-brand:before{content:"";position:absolute;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(255,128,184,.21),transparent 67%);filter:blur(8px);pointer-events:none}.brand-tools{position:absolute;z-index:2;top:15px;right:16px;left:16px;display:flex;justify-content:space-between;align-items:center}.directory-link{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid rgba(232,194,118,.36);border-radius:999px;background:rgba(9,6,10,.66);color:#f8dd9c;font-size:10px;font-weight:900}.directory-link img{width:23px;height:23px;border-radius:50%;object-fit:contain}.brand-logo{position:relative;width:156px;height:156px;border:2px solid var(--gold);border-radius:50%;background:#050306;object-fit:contain;box-shadow:0 0 0 8px rgba(255,128,184,.06),0 0 35px rgba(255,128,184,.28)}.brand-name{position:relative;margin:4px 0 0;font-family:'Playfair Display',Cairo,serif;font-size:25px;line-height:1.25}.brand-description{position:relative;max-width:270px;margin:0;color:var(--muted);font-size:11px;line-height:1.8}.brand-status{position:relative;display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border:1px solid #5ce6a7;border-radius:999px;color:#5ce6a7;background:rgba(58,210,137,.08);font-size:11px;font-weight:900}.brand-status:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 9px currentColor}.beauty-socials{position:relative;display:flex;gap:7px;justify-content:center;flex-wrap:wrap}.beauty-social{display:grid;place-items:center;width:32px;height:32px;border:1px solid var(--line);border-radius:50%;background:rgba(8,5,9,.7);color:#f8d6e7;font-size:16px}.beauty-social:hover{border-color:var(--rose);color:var(--rose)}.catalog-panel{margin-top:16px;padding:16px;border:1px solid var(--line);border-radius:30px;background:linear-gradient(145deg,rgba(28,17,29,.92),rgba(10,8,14,.98));box-shadow:0 18px 55px rgba(0,0,0,.2)}.catalog-heading{display:flex;align-items:end;justify-content:space-between;gap:12px;margin:3px 5px 16px;direction:rtl}.catalog-heading h1{margin:0;font-family:'Playfair Display',Cairo,serif;font-size:31px;color:#ffe5f1}.catalog-heading p{margin:4px 0 0;color:var(--muted);font-size:12px}.beauty-browser{display:grid;grid-template-columns:112px minmax(0,1fr);gap:15px;direction:ltr}.beauty-categories{grid-column:1;display:flex;flex-direction:column;align-items:center;gap:11px;max-height:660px;padding:4px;overflow:auto;scrollbar-width:thin}.beauty-category{width:100%;border:0;background:none;color:#b6a5b7;cursor:pointer}.beauty-category span{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;font-weight:800;text-align:center}.beauty-category-image{display:grid;place-items:center;width:72px;height:72px;margin:0 auto 5px;overflow:hidden;border:2px solid rgba(255,255,255,.13);border-radius:50%;background:#0b070d;color:var(--rose);font-size:27px;transition:.2s}.beauty-category-image img{width:100%;height:100%;object-fit:cover}.beauty-category.active{color:#fff}.beauty-category.active .beauty-category-image{border-color:var(--rose);box-shadow:0 0 22px rgba(255,128,184,.48);transform:scale(1.06)}.beauty-content{grid-column:2;min-width:0;direction:rtl}.beauty-pane[hidden]{display:none}.beauty-pane-head{display:flex;justify-content:space-between;align-items:center;margin:1px 3px 13px}.beauty-pane-head h2{margin:0;color:#f7d8e7;font-size:21px}.beauty-pane-head span{padding:4px 9px;border:1px solid rgba(232,194,118,.28);border-radius:999px;color:var(--gold);font-size:10px}.beauty-products{display:grid;grid-template-columns:repeat(auto-fill,minmax(195px,1fr));gap:13px}.beauty-product{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:21px;background:linear-gradient(145deg,rgba(32,22,34,.98),rgba(12,9,15,.98));transition:transform .22s ease,border-color .22s ease}.beauty-product:hover{transform:translateY(-5px);border-color:rgba(255,128,184,.65)}.product-picture{display:block;position:relative;aspect-ratio:1/1.04;overflow:hidden;background:radial-gradient(circle at 50% 35%,rgba(255,191,219,.18),transparent 44%),#080509}.product-picture img{width:100%;height:100%;object-fit:cover;transition:transform .45s ease}.beauty-product:hover .product-picture img{transform:scale(1.06)}.product-badge{position:absolute;top:9px;right:9px;padding:4px 8px;border-radius:999px;background:rgba(12,7,13,.85);border:1px solid rgba(232,194,118,.45);color:var(--gold);font-size:9px;font-weight:900}.product-body{padding:11px}.product-brand{min-height:15px;color:var(--rose);font-size:10px;font-weight:800}.product-name{margin:2px 0 6px;font-size:14px;line-height:1.5}.product-details{min-height:28px;color:var(--muted);font-size:10px;line-height:1.5}.product-bottom{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:10px;padding-top:9px;border-top:1px solid var(--line)}.product-price{color:#ffe2a0;font-size:14px;font-weight:900}.add-beauty-cart{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-width:96px;height:34px;padding:0 9px;border:0;border-radius:11px;background:linear-gradient(135deg,#ff86bb,#bd78ff);color:#210615;cursor:pointer;font-size:10px;font-weight:900}.add-beauty-cart svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.empty-products{display:grid;place-items:center;min-height:260px;border:1px dashed var(--line);border-radius:20px;color:var(--muted);font-size:13px}.beauty-cart{position:fixed;z-index:30;right:20px;bottom:18px;display:flex;align-items:center;gap:10px;min-height:52px;padding:7px 9px 7px 15px;border:1px solid rgba(255,128,184,.48);border-radius:17px;background:rgba(22,10,24,.93);box-shadow:0 12px 35px rgba(0,0,0,.38);backdrop-filter:blur(15px);direction:rtl}.beauty-cart strong{font-size:12px}.beauty-cart small{display:block;color:var(--muted);font-size:10px}.beauty-cart button{min-width:43px;height:38px;border:0;border-radius:11px;background:linear-gradient(135deg,#ff86bb,#bd78ff);color:#260617;font-weight:900;cursor:pointer}.cart-sheet{position:fixed;z-index:40;inset:0;display:none;align-items:end;justify-content:center;padding:15px;background:rgba(0,0,0,.56)}.cart-sheet.open{display:flex}.cart-card{width:min(460px,100%);max-height:78vh;overflow:auto;padding:18px;border:1px solid rgba(255,128,184,.34);border-radius:24px;background:#160d19;box-shadow:0 20px 70px #000}.cart-card h2{margin:0 0 12px;font-size:20px}.cart-items{display:grid;gap:8px}.cart-row{display:flex;align-items:center;justify-content:space-between;gap:9px;padding:9px;border-radius:13px;background:#241527;font-size:12px}.cart-row button{border:0;background:none;color:#ff99c5;font-size:18px;cursor:pointer}.cart-total{display:flex;justify-content:space-between;margin:14px 0;color:#ffe2a0;font-weight:900}.cart-actions{display:grid;grid-template-columns:1fr auto;gap:8px}.cart-actions button,.cart-actions a{display:flex;align-items:center;justify-content:center;min-height:43px;border:0;border-radius:12px;background:linear-gradient(135deg,#ff86bb,#bd78ff);color:#260617;font-weight:900;cursor:pointer}.cart-actions button{padding:0 13px;background:#302035;color:#fff}.cart-actions a.disabled{opacity:.45;pointer-events:none}@media(max-width:720px){.cosmetics-shell{width:calc(100% - 18px);padding:9px 0 86px}.cosmetics-hero{grid-template-columns:minmax(0,1fr) 145px;gap:7px}.beauty-display,.beauty-brand{min-height:385px;border-radius:22px}.brand-tools{top:6px;right:6px;left:6px}.brand-tools .public-language-switcher{padding:3px}.brand-tools .public-language-switcher a{padding:4px;font-size:8px}.directory-link{padding:4px;font-size:7px}.directory-link img{width:20px;height:20px}.brand-logo{width:105px;height:105px;margin-top:35px}.brand-name{font-size:16px}.brand-description{font-size:8px}.brand-status{padding:5px 8px;font-size:9px}.beauty-social{width:27px;height:27px;font-size:14px}.catalog-panel{margin-top:8px;padding:8px;border-radius:21px}.catalog-heading{margin:3px 5px 10px}.catalog-heading h1{font-size:23px}.catalog-heading p{font-size:9px}.beauty-browser{grid-template-columns:78px minmax(0,1fr);gap:5px}.beauty-categories{gap:8px}.beauty-category-image{width:52px;height:52px;font-size:20px}.beauty-category span{font-size:8px}.beauty-products{grid-template-columns:1fr;gap:10px}.beauty-product{display:grid;grid-template-columns:108px minmax(0,1fr)}.product-picture{grid-row:1/3;aspect-ratio:auto;min-height:145px}.product-body{padding:9px}.product-name{font-size:12px}.beauty-cart{right:10px;bottom:10px;min-height:46px}.beauty-cart small{display:none}.beauty-cart button{height:34px}.beauty-pane-head h2{font-size:16px}}
    </style>
    <style>
        /* Keep the purchase action readable even if an icon font is unavailable. */
        .beauty-product .add-beauty-cart{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-width:112px;height:38px;padding:0 12px;font-size:11px;font-weight:900;line-height:1;white-space:nowrap}
        .beauty-product .add-beauty-cart i{display:none}
        .beauty-product .add-beauty-cart::before{content:"+";font-size:21px;font-weight:500;line-height:1}
        .beauty-product .add-beauty-cart::after{content:"أضف للسلة"}
        @media(max-width:720px){.beauty-product .add-beauty-cart{min-width:102px;height:35px;padding:0 9px;font-size:10px}}
    </style>
    <style>
        .beauty-product.has-product-video{cursor:pointer}
        .beauty-product.has-product-video .product-picture::after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,rgba(8,3,10,.52),transparent 46%);pointer-events:none}
        .beauty-video-hint{position:absolute;z-index:4;bottom:9px;right:9px;display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid rgba(255,226,160,.65);border-radius:999px;background:rgba(10,5,12,.86);color:#ffe2a0;font:inherit;font-size:9px;font-weight:900;cursor:pointer;box-shadow:0 5px 14px rgba(0,0,0,.3)}.beauty-video-hint:hover,.beauty-video-hint:focus-visible{border-color:var(--rose);color:#fff;outline:none}
        .beauty-product-preview .beauty-display-product-title{position:absolute;z-index:2;right:18px;bottom:17px;max-width:72%;padding:7px 11px;border:1px solid rgba(255,205,228,.36);border-radius:999px;background:rgba(15,7,17,.78);color:#fff;font-size:12px;font-weight:900;direction:rtl}
        @media(max-width:720px){.beauty-video-hint{right:6px;bottom:6px;padding:4px 6px;font-size:8px}.beauty-product-preview .beauty-display-product-title{right:12px;bottom:12px;font-size:9px}}
    </style>
    <style>
        /* Cosmetics keeps the language control quiet so the brand remains the hero. */
        .beauty-brand .public-language-switcher{padding:3px!important;border-color:rgba(255,205,228,.24)!important;border-radius:11px!important;background:rgba(26,15,29,.94)!important;box-shadow:none!important}
        .beauty-brand .public-language-switcher a{padding:4px 6px!important;color:#d2bdce!important;font-size:9px!important}
        .beauty-brand .public-language-switcher a.active{background:rgba(255,128,184,.2)!important;color:#ffe8f2!important}
        .beauty-brand .public-language-icon{font-size:10px!important;color:#e8c276}
        .beauty-brand .brand-logo{margin-top:28px}
        @media(max-width:720px){
            .beauty-brand .brand-tools{top:7px;right:7px;left:7px;display:flex;flex-direction:column;align-items:stretch;gap:5px}
            .beauty-brand .public-language-switcher{width:100%;justify-content:center;padding:3px!important}
            .beauty-brand .public-language-switcher a{padding:4px 5px!important;font-size:8px!important}
            .beauty-brand .directory-link{width:100%;min-height:28px;justify-content:center;padding:4px 7px;font-size:8px}
            .beauty-brand .directory-link img{width:19px;height:19px}
            .beauty-brand .brand-logo{margin-top:76px}
        }
    </style>
    <style>
        /* Purchase rewards and raffle cards inherit the cosmetics palette. */
        .beauty-reward-tools{position:fixed;z-index:29;left:20px;bottom:18px;display:grid;gap:8px;direction:rtl}.beauty-reward-tool{display:flex;align-items:center;gap:9px;min-width:178px;padding:9px 12px;border:1px solid rgba(232,194,118,.45);border-radius:16px;background:rgba(30,14,31,.94);box-shadow:0 12px 35px rgba(0,0,0,.36);color:#fff;cursor:pointer;text-align:right;backdrop-filter:blur(15px)}.beauty-reward-tool>span:first-child{display:grid;place-items:center;width:31px;height:31px;border-radius:11px;background:linear-gradient(135deg,#f7cc75,#ff86bb);color:#260617;font-size:17px}.beauty-reward-tool strong{display:block;font-size:11px}.beauty-reward-tool small{display:block;margin-top:1px;color:#dbc5d6;font-size:9px}.beauty-modal{position:fixed;z-index:80;inset:0;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(3,1,5,.76);backdrop-filter:blur(8px);direction:rtl}.beauty-modal.open{display:flex}.beauty-modal-card{position:relative;width:min(470px,100%);max-height:min(760px,calc(100vh - 36px));overflow:auto;padding:26px;border:1px solid rgba(255,128,184,.48);border-radius:28px;background:radial-gradient(circle at 50% 0,rgba(255,128,184,.16),transparent 40%),linear-gradient(145deg,#211225,#0e0911);box-shadow:0 25px 80px #000;text-align:center}.beauty-modal-close{position:absolute;top:12px;left:12px;display:grid;place-items:center;width:34px;height:34px;border:1px solid var(--line);border-radius:50%;background:#2b1b2e;color:#fff;cursor:pointer;font-size:20px}.beauty-modal-kicker{margin:0;color:var(--gold);font-size:11px;font-weight:900}.beauty-modal-card h2{margin:5px 0 6px;font-family:'Playfair Display',Cairo,serif;font-size:27px}.beauty-modal-intro{margin:0 auto 17px;max-width:340px;color:var(--muted);font-size:12px;line-height:1.8}.beauty-wheel-wrap{position:relative;width:min(282px,76vw);aspect-ratio:1;margin:4px auto 19px}.beauty-wheel-wrap:after{content:"";position:absolute;z-index:4;top:-5px;left:calc(50% - 12px);width:0;height:0;border-right:12px solid transparent;border-left:12px solid transparent;border-top:27px solid var(--gold);filter:drop-shadow(0 2px 2px #000)}.beauty-wheel{position:relative;width:100%;height:100%;overflow:hidden;border:8px solid #f2c56f;border-radius:50%;box-shadow:0 0 0 6px rgba(255,128,184,.15),0 15px 38px rgba(0,0,0,.5);transition:transform 4.2s cubic-bezier(.16,.75,.16,1);cursor:pointer}.beauty-wheel:after{content:"لف العجلة";position:absolute;inset:calc(50% - 43px);display:grid;place-items:center;border:4px solid #f4d48d;border-radius:50%;background:#2b1530;color:#fff;font-family:Cairo,Arial,sans-serif;font-size:12px;font-weight:900;box-shadow:0 3px 15px rgba(0,0,0,.45)}.beauty-wheel:disabled{cursor:wait}.beauty-wheel-label{position:absolute;top:9%;left:50%;width:43%;transform-origin:0 116px;color:#261123;font-size:10px;font-weight:900;text-align:center;line-height:1.2}.beauty-wheel-result{min-height:42px;padding:10px 13px;border:1px dashed rgba(232,194,118,.45);border-radius:15px;color:#ffe9c6;font-size:12px;font-weight:800}.beauty-wheel-result.win{border-style:solid;border-color:#6ae6b4;background:rgba(50,202,133,.09);color:#89ffc6}.beauty-form{display:grid;gap:10px;text-align:right}.beauty-field{display:grid;gap:5px;color:#ffe3ef;font-size:11px;font-weight:800}.beauty-field input{width:100%;min-height:46px;padding:0 13px;border:1px solid rgba(255,205,228,.22);border-radius:13px;outline:0;background:#100b13;color:#fff;font:inherit}.beauty-field input:focus{border-color:var(--rose);box-shadow:0 0 0 3px rgba(255,128,184,.11)}.beauty-card-number{letter-spacing:8px;text-align:center;font-size:22px!important;font-weight:900}.beauty-form-action{display:flex;align-items:center;justify-content:center;min-height:45px;border:0;border-radius:13px;background:linear-gradient(135deg,#ff86bb,#bd78ff);color:#260617;font:inherit;font-size:13px;font-weight:900;cursor:pointer}.beauty-scan-action{border:1px solid rgba(232,194,118,.45);background:transparent;color:#f8d99c}.beauty-raffle-result{display:none;margin-top:13px;padding:12px;border:1px solid rgba(232,194,118,.38);border-radius:15px;background:rgba(9,5,11,.5);text-align:center}.beauty-raffle-result.show{display:block}.beauty-raffle-result strong{display:block;color:#ffe4a5;font-size:14px}.beauty-raffle-result p{margin:5px 0 0;color:#e5d4e1;font-size:11px;line-height:1.7}.beauty-raffle-result img{display:block;width:92px;height:92px;margin:9px auto 0;border-radius:13px;object-fit:cover}.beauty-scanner{display:none;margin-top:12px}.beauty-scanner.open{display:block}.beauty-scanner video{display:block;width:100%;max-height:220px;border:1px solid rgba(232,194,118,.4);border-radius:15px;background:#000;object-fit:cover}@media(max-width:720px){.beauty-reward-tools{left:10px;bottom:10px;gap:6px}.beauty-reward-tool{min-width:0;padding:7px 9px;border-radius:13px}.beauty-reward-tool>span:first-child{width:29px;height:29px}.beauty-reward-tool strong{font-size:10px}.beauty-reward-tool small{display:none}.beauty-modal{align-items:end;padding:9px}.beauty-modal-card{max-height:88vh;padding:23px 16px 17px;border-radius:23px}.beauty-modal-card h2{font-size:23px}.beauty-wheel-label{transform-origin:0 97px;font-size:8px}}
    </style>
    <style>
        /* Product clips open in their own gallery-style viewer, separate from the store display. */
        .beauty-product-video-modal{position:fixed;z-index:110;inset:0;display:none;align-items:center;justify-content:center;padding:30px 88px;background:rgba(3,1,5,.84);backdrop-filter:blur(13px);direction:rtl}.beauty-product-video-modal.open{display:flex}.beauty-product-video-dialog{position:relative;width:auto;max-width:100%;max-height:calc(100vh - 60px);background:transparent}.beauty-product-video-dialog video{display:block;width:auto;height:auto;max-width:100%;max-height:calc(100vh - 60px);min-height:0;background:#000;object-fit:contain}.beauty-product-video-meta{display:none}.beauty-product-video-close{position:fixed;z-index:1;top:30px;left:32px;display:grid;place-items:center;width:52px;height:52px;border:1px solid rgba(255,205,228,.34);border-radius:50%;background:rgba(19,10,22,.88);color:#fff;font-size:30px;cursor:pointer}.beauty-product-video-close:hover{border-color:var(--rose);color:var(--rose)}@media(max-width:720px){.beauty-product-video-modal{padding:12px}.beauty-product-video-dialog{max-height:calc(100vh - 24px)}.beauty-product-video-dialog video{max-height:calc(100vh - 24px)}.beauty-product-video-close{top:18px;left:18px;width:42px;height:42px;font-size:25px}}
    </style>
    <style>
        .product-price-stack{display:inline-flex;min-width:82px;flex-direction:column;align-items:flex-end;justify-content:center;gap:2px;white-space:nowrap;line-height:1.1}.product-price-stack del{display:block;color:#f0accb;font-size:11px;font-weight:800;text-decoration-thickness:2px;text-decoration-color:#ff80b8}.product-price-stack ins{display:block;color:#ffe2a0;font-size:14px;font-weight:900;text-decoration:none}
    </style>
    <style>
        .beauty-booking-link{position:relative;display:inline-flex;align-items:center;gap:7px;margin-top:2px;padding:8px 13px;border:1px solid var(--gold);border-radius:999px;background:rgba(232,194,118,.08);color:#ffe0a1;font-size:11px;font-weight:900}.beauty-booking-link:hover{border-color:var(--rose);background:rgba(255,128,184,.14);color:#fff}@media(max-width:720px){.beauty-booking-link{padding:6px 9px;font-size:9px}}
    </style>
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
            <div class="brand-tools">@include('front.partials.public_language_switcher')<a class="directory-link" href="{{ route('front.home') }}"><img src="{{ $ozmanLogo ? $mediaUrl($ozmanLogo) : asset('ozman-favicon.png') }}" alt="">{{ __('كل المحلات') }}</a></div>
            <img class="brand-logo" src="{{ $shopLogo }}" alt="{{ $shop->name }}"><h1 class="brand-name">{{ $shop->name }}</h1>
            <p class="brand-description">{{ $shop->description ?: __('عطور مختارة ومنتجات عناية وجمال لكل تفاصيلك.') }}</p><span class="brand-status">{{ __('متاح للطلبات') }}</span><a class="beauty-booking-link" href="{{ route('cosmetics.booking', $shop) }}"><i class="ti ti-calendar-heart"></i> احجزي موعد في الصالون</a>
            @if($socialLinks->isNotEmpty())<nav class="beauty-socials" aria-label="{{ __('التواصل الاجتماعي') }}">@foreach($socialLinks as $link)<a class="beauty-social" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}"><i class="ti {{ $link['icon'] }}"></i></a>@endforeach</nav>@endif
        </header>
    </div>
    <section class="catalog-panel" id="beautyCatalog">
        @if($categoriesForPage->isNotEmpty())<div class="beauty-browser"><nav class="beauty-categories" aria-label="{{ __('أقسام الكوزماتيكس') }}">@foreach($categoriesForPage as $category)<button type="button" class="beauty-category {{ $loop->first ? 'active' : '' }}" data-beauty-category="{{ $category['key'] }}"><span class="beauty-category-image">@if($category['image'])<img src="{{ $mediaUrl($category['image']) }}" alt="">@else<i class="ti ti-sparkles"></i>@endif</span><span>{{ $category['name'] }}</span></button>@endforeach</nav>
        <div class="beauty-content">@foreach($categoriesForPage as $category)<section class="beauty-pane" data-beauty-pane="{{ $category['key'] }}" @if(!$loop->first) hidden @endif><header class="beauty-pane-head"><h2>{{ $category['name'] }}</h2><span>{{ $category['products']->count() }} {{ __('منتج') }}</span></header><div class="beauty-products">@forelse($category['products'] as $product)@php $attrs = $product->catalog_attributes ?? []; @endphp<article class="beauty-product"><div class="product-picture">@if($product->main_image)<img src="{{ $mediaUrl($product->main_image) }}" alt="{{ $product->localized('name') }}" loading="lazy">@else<i class="ti ti-sparkles" style="font-size:48px;position:absolute;inset:0;display:grid;place-items:center;color:var(--rose)"></i>@endif @if($product->is_featured)<span class="product-badge">{{ __('مميز') }}</span>@endif</div><div class="product-body"><div class="product-brand">{{ data_get($attrs,'brand') ?: __('اختيار الجمال') }}</div><h3 class="product-name">{{ $product->localized('name') }}</h3><div class="product-details">{{ collect([data_get($attrs,'volume'), collect(data_get($attrs,'shades', []))->filter()->implode(' · ')])->filter()->implode(' — ') ?: ($product->localized('description') ?: __('منتج عناية مختار بعناية.')) }}</div><div class="product-bottom"><strong class="product-price">{{ number_format((float)($product->discount_price ?: $product->price), 2) }} ₪</strong><button class="add-beauty-cart" type="button" data-add-product data-id="{{ $product->id }}" data-name="{{ $product->localized('name') }}" data-price="{{ (float)($product->discount_price ?: $product->price) }}" aria-label="{{ __('أضف إلى السلة') }}"><i class="ti ti-bag-plus"></i></button></div></div></article>@empty<div class="empty-products">{{ __('لا توجد منتجات في هذا القسم حاليًا.') }}</div>@endforelse</div></section>@endforeach</div></div>@else<div class="empty-products">{{ __('أضف أقسام ومنتجات المتجر لتظهر هنا.') }}</div>@endif
    </section>
</main>
<aside class="beauty-cart" aria-live="polite"><div><strong id="beautyCartCount">0 {{ __('منتجات في السلة') }}</strong><small id="beautyCartTotal">0.00 ₪</small></div><button type="button" id="openBeautyCart"><i class="ti ti-shopping-bag"></i></button></aside>
<div class="cart-sheet" id="beautyCartSheet" aria-hidden="true"><section class="cart-card" role="dialog" aria-modal="true" aria-label="{{ __('سلة الطلب') }}"><h2>{{ __('سلة طلبك') }}</h2><div class="cart-items" id="beautyCartItems"></div><div class="cart-total"><span>{{ __('المجموع') }}</span><span id="beautyCartSheetTotal">0.00 ₪</span></div><div class="cart-actions"><a id="beautyWhatsappOrder" class="disabled" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp"></i>&nbsp; {{ __('إرسال الطلب واتساب') }}</a><button type="button" id="closeBeautyCart">{{ __('إغلاق') }}</button></div></section></div>
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
<div class="beauty-product-video-modal" id="beautyProductVideoModal" aria-hidden="true">
    <button type="button" class="beauty-product-video-close" id="beautyProductVideoClose" aria-label="إغلاق الفيديو">×</button>
    <section class="beauty-product-video-dialog" role="dialog" aria-modal="true" aria-labelledby="beautyProductVideoTitle">
        <video id="beautyProductVideoPlayer" controls playsinline preload="metadata"></video>
        <div class="beauty-product-video-meta"><strong id="beautyProductVideoTitle"></strong><span>الصوت مفعّل</span></div>
    </section>
</div>
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
(() => {const slider=document.querySelector('[data-beauty-display]'),slides=[...(slider?.querySelectorAll('.beauty-display-slide')||[])];let current=0,timer;const play=()=>slides[current]?.querySelector('video')?.play().catch(()=>{});const show=next=>{slides[current]?.querySelector('video')?.pause();slides[current]?.classList.remove('active');current=next%slides.length;slides[current]?.classList.add('active');play()};play();if(slides.length>1){const loop=()=>{timer=setTimeout(()=>{show(current+1);loop()},Number(slides[current]?.dataset.duration)||8000)};loop()}document.querySelectorAll('[data-beauty-category]').forEach(button=>button.addEventListener('click',()=>{const key=button.dataset.beautyCategory;document.querySelectorAll('[data-beauty-category]').forEach(item=>item.classList.toggle('active',item===button));document.querySelectorAll('[data-beauty-pane]').forEach(pane=>pane.hidden=pane.dataset.beautyPane!==key)}));const cart=[],$=id=>document.getElementById(id),fmt=value=>`${Number(value).toFixed(2)} ₪`,sync=()=>{const count=cart.reduce((n,item)=>n+item.qty,0),total=cart.reduce((n,item)=>n+item.qty*item.price,0);$('beautyCartCount').textContent=`${count} ${count===1?'منتج في السلة':'منتجات في السلة'}`;$('beautyCartTotal').textContent=fmt(total);$('beautyCartSheetTotal').textContent=fmt(total);$('beautyCartItems').innerHTML=cart.length?cart.map(item=>`<div class="cart-row"><span>${item.name} × ${item.qty}</span><span>${fmt(item.price*item.qty)} <button data-remove="${item.id}" aria-label="حذف">×</button></span></div>`).join(''):'<div class="empty-products" style="min-height:120px">السلة فارغة حاليًا.</div>';const message=cart.map(item=>`• ${item.name} × ${item.qty} — ${fmt(item.price*item.qty)}`).join('%0A');const order=$('beautyWhatsappOrder');order.href=cart.length&&@json((bool)$whatsapp)?`https://wa.me/{{ $whatsapp }}?text=${encodeURIComponent('مرحباً، أود طلب المنتجات التالية من {{ $shop->name }}:%0A'+message+'%0A%0Aالمجموع: '+fmt(total))}`:'#';order.classList.toggle('disabled',!cart.length||!@json((bool)$whatsapp));document.querySelectorAll('[data-remove]').forEach(button=>button.onclick=()=>{const index=cart.findIndex(item=>String(item.id)===button.dataset.remove);if(index>-1)cart.splice(index,1);sync()})};document.querySelectorAll('[data-add-product]').forEach(button=>button.onclick=()=>{const item=cart.find(item=>String(item.id)===button.dataset.id);if(item)item.qty++;else cart.push({id:button.dataset.id,name:button.dataset.name,price:Number(button.dataset.price),qty:1});sync()});$('openBeautyCart').onclick=()=>{$('beautyCartSheet').classList.add('open');$('beautyCartSheet').setAttribute('aria-hidden','false');sync()};$('closeBeautyCart').onclick=()=>{$('beautyCartSheet').classList.remove('open');$('beautyCartSheet').setAttribute('aria-hidden','true')};$('beautyCartSheet').onclick=e=>{if(e.target===$('beautyCartSheet'))$('closeBeautyCart').click()};sync()})();
</script>
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
    $('beautyWhatsappOrder')?.addEventListener('click', async event => {
        event.preventDefault();
        if (!rewardCart.length) return;
        const name = $('beautyCustomerName')?.value.trim();
        const whatsapp = $('beautyCustomerWhatsapp')?.value.trim();
        if (!name || !whatsapp) { alert('أدخل الاسم ورقم الجوال أو واتساب لإرسال الطلب.'); return; }
        const value = total();
        const rewardWheel = matchingWheel(value);
        const orderLink = $('beautyWhatsappOrder');
        orderLink.style.pointerEvents = 'none';
        try {
            localStorage.setItem('beautyCustomer', JSON.stringify({name, whatsapp}));
            const order = await request(config.orderUrl, {shop_id: config.shopId, customer_name: name, customer_phone: whatsapp, customer_whatsapp: whatsapp, items: rewardCart.map(item => ({name: item.name, price: String(item.price), qty: item.qty})), subtotal: value, total: value, order_channel: 'whatsapp', visitor_type: 'customer', reward_wheel_id: rewardWheel?.id || null});
            const message = rewardCart.map(item => `• ${item.name} × ${item.qty} — ${format(item.price * item.qty)}`).join('\n');
            if (config.whatsapp) window.open(`https://wa.me/${config.whatsapp}?text=${encodeURIComponent(`مرحباً، أود طلب المنتجات التالية من {{ $shop->name }}:\n${message}\n\nالمجموع: ${format(value)}`)}`, '_blank', 'noopener');
            if (rewardWheel) {
                orderContext = {orderId: order.order_id, wheel: rewardWheel};
                renderWheel(rewardWheel);
                wheel.disabled = false;
                wheelIntro.textContent = `${rewardWheel.title} — تم تسجيل طلبك، لف العجلة الآن واكتشف جائزتك.`;
                $('closeBeautyCart')?.click();
                openModal('beautyWheelModal');
            }
        } catch (error) { alert(error.message); }
        finally { orderLink.style.pointerEvents = ''; }
    });

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
<script>
(() => {
    const clips = window.beautyProductVideos || {};
    const modal = document.getElementById('beautyProductVideoModal');
    const player = document.getElementById('beautyProductVideoPlayer');
    const title = document.getElementById('beautyProductVideoTitle');
    const close = () => {
        if (!modal) return;
        player.pause();
        player.removeAttribute('src');
        player.load();
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };
    const open = productId => {
        const clip = clips[String(productId)];
        if (!clip || !modal) return;
        player.src = clip.url;
        player.muted = false;
        player.volume = 1;
        player.loop = true;
        title.textContent = clip.title;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        player.play().catch(() => {});
    };
    document.addEventListener('click', event => {
        const trigger = event.target.closest('.beauty-video-hint');
        if (!trigger) return;
        const productId = trigger.closest('.beauty-product')?.querySelector('[data-add-product]')?.dataset.id;
        if (!productId || !clips[String(productId)]) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        open(productId);
    }, true);
    document.getElementById('beautyProductVideoClose')?.addEventListener('click', close);
    modal?.addEventListener('click', event => { if (event.target === modal) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal?.classList.contains('open')) close(); });
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
</body></html>
