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
    document.addEventListener('click', event => { const button = event.target.closest('[data-add]'); if (!button) return; const found = cart.find(item => item.id === button.dataset.add); if (found) found.quantity += 1; else cart.push({id:button.dataset.add,name:button.dataset.name,price:Number(button.dataset.price),quantity:1}); renderCart(); document.getElementById('cartButton').animate([{transform:'scale(1)'},{transform:'scale(1.08)'},{transform:'scale(1)'}],{duration:260}); });
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
