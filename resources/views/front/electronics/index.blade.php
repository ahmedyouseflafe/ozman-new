<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop])
    @php
        $electronicsCanonical = route('electronics.store', $shop);
        $electronicsDescription = $shop->description ?: "تصفح أحدث الأجهزة والأسعار في {$shop->name} عبر Ozman.";
        $electronicsImage = $shop->banner
            ? asset(\Illuminate\Support\Str::startsWith($shop->banner, 'storage/') ? $shop->banner : 'storage/'.$shop->banner)
            : ($shop->logo ? asset($shop->logo) : asset('images/logo.svg'));
    @endphp
    @include('front.partials.seo', [
        'title' => $shop->name.' | متجر إلكترونيات',
        'description' => $electronicsDescription,
        'canonical' => $electronicsCanonical,
        'image' => $electronicsImage,
        'schema' => [
            '@context' => 'https://schema.org', '@type' => 'Store', 'name' => $shop->name,
            'url' => $electronicsCanonical, 'description' => $electronicsDescription,
            'image' => $electronicsImage, 'telephone' => $shop->phone, 'address' => $shop->address,
        ],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.electronics.index')
</head>
<body>
@include('front.partials.shop_bottom_screens', ['screenPlacement' => 'top'])
@php
    $bannerUrl = $shop->banner ? (\Illuminate\Support\Str::startsWith($shop->banner,['http://','https://','storage/']) ? asset($shop->banner) : asset('storage/'.$shop->banner)) : '';
    $listedProducts = $products;
    $categoryItems = $categories->map(fn($category) => [
        'key' => (string)$category->id,
        'name' => $category->localized('name'),
        'image' => $category->image ?: $listedProducts->first(fn($product) => $product->category_id === $category->id && filled($product->main_image))?->main_image,
        'background' => $category->background_video,
        'products' => $listedProducts->where('category_id',$category->id)->values(),
    ]);
    $uncategorized = $listedProducts->whereNull('category_id')->values();
    if($uncategorized->isNotEmpty()) $categoryItems->push(['key'=>'other','name'=>'أجهزة أخرى','image'=>$uncategorized->first()?->main_image,'background'=>null,'products'=>$uncategorized]);
    $requestedCategory = (string)request('category','');
    $activeKey = $categoryItems->contains(fn($item)=>$item['key']===$requestedCategory) ? $requestedCategory : (string)($categoryItems->first()['key'] ?? '');
    $mediaUrl = fn($path) => filled($path) ? (\Illuminate\Support\Str::startsWith($path,['http://','https://','storage/']) ? asset($path) : asset('storage/'.$path)) : '';
@endphp
<header class="hero" style="--hero-image:url('{{ $bannerUrl }}')">
    <div class="wrap hero-content"><div><span class="hero-kicker">{{ __('● تجربة تسوق تقنية متكاملة') }}</span><h1>{{ $shop->name }}</h1><p>{{ $shop->description ?: 'اكتشف أحدث الأجهزة، قارن المواصفات والأسعار، واختر الجهاز الأنسب لك.' }}</p><div class="hero-actions"><a class="hero-cta" href="#devicesCatalog">{{ __('استكشف الأجهزة') }}</a><span class="hero-note">{{ __('مواصفات واضحة وأسعار مباشرة') }}</span></div></div>@if($heroDevices->isNotEmpty())<div class="hero-showcase" id="heroShowcase">@foreach($heroDevices as $heroIndex=>$heroDevice)@php $heroPrice=$heroDevice->variants->where('quantity','>',0)->min(fn($variant)=>(float)($variant->price??$heroDevice->discount_price??$heroDevice->price))??$heroDevice->discount_price??$heroDevice->price; @endphp<a class="hero-slide @if($heroIndex===0) active @endif" href="{{ route('electronics.product',[$shop,$heroDevice]) }}" data-hero-slide><div>@if($heroDevice->main_image)<img src="{{ $mediaUrl($heroDevice->main_image) }}" alt="{{ $heroDevice->localized('name') }}">@else<span class="showcase-placeholder"><i class="ti ti-device-mobile"></i></span>@endif</div><div class="showcase-copy"><small>جهاز {{ $heroIndex+1 }} من {{ $heroDevices->count() }}</small><b>{{ $heroDevice->localized('name') }}</b><span>{{ __('شاهد المواصفات والخيارات المتوفرة ←') }}</span><span class="showcase-price">من {{ number_format((float)$heroPrice,2) }} ₪</span></div></a>@endforeach @if($heroDevices->count()>1)<div class="hero-controls"><button type="button" class="hero-arrow" id="heroPrev" aria-label="{{ __('السابق') }}">‹</button><div class="hero-dots">@foreach($heroDevices as $heroIndex=>$heroDevice)<button type="button" class="hero-dot @if($heroIndex===0) active @endif" data-hero-dot="{{ $heroIndex }}" aria-label="الجهاز {{ $heroIndex+1 }}"></button>@endforeach</div><button type="button" class="hero-arrow" id="heroNext" aria-label="{{ __('التالي') }}">›</button></div>@endif</div>@endif</div>
</header>
<main class="wrap">
    <form class="filters" method="GET">
        <input name="q" value="{{ request('q') }}" placeholder="{{ __('ابحث عن جهاز أو موديل...') }}">
        <select name="brand"><option value="">{{ __('كل الماركات') }}</option>@foreach($brands as $brand)<option @selected(request('brand')===$brand)>{{ $brand }}</option>@endforeach</select>
        <select name="storage"><option value="">{{ __('كل السعات') }}</option>@foreach($storages as $storage)<option @selected(request('storage')===$storage)>{{ $storage }}</option>@endforeach</select>
        <select name="condition"><option value="">{{ __('جديد ومستعمل') }}</option><option @selected(request('condition')==='جديد')>{{ __('جديد') }}</option><option @selected(request('condition')==='مستعمل')>{{ __('مستعمل') }}</option></select>
        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="{{ __('أقل سعر') }}">
        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="{{ __('أعلى سعر') }}">
        <button>{{ __('تصفية') }}</button>
    </form>
    <section class="catalog" id="devicesCatalog">
        <header class="catalog-head"><div><h2>{{ __('الأجهزة') }}</h2><p>{{ __('اختر الفئة ثم اضغط على الجهاز لعرض التفاصيل والشراء.') }}</p></div></header>
        @if($categoryItems->isNotEmpty())
            <div class="device-browser">
                <nav class="category-rail" id="categoryRail" aria-label="{{ __('فئات الأجهزة') }}">
                    @foreach($categoryItems as $category)
                        <button type="button" class="category-tab @if($category['key']===$activeKey) active @endif" data-category-key="{{ $category['key'] }}">
                            @if($category['image'])<img src="{{ $mediaUrl($category['image']) }}" alt="" loading="lazy">@else<span class="category-icon"><i class="ti ti-device-mobile"></i></span>@endif
                            <span>{{ $category['name'] }}</span>
                        </button>
                    @endforeach
                </nav>
                <div class="category-content" id="categoryContent">
                    <video class="category-background" id="categoryBackground" autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video>
                    @foreach($categoryItems as $category)
                        <section class="category-pane" data-category-pane="{{ $category['key'] }}" @if($category['background']) data-background-video="{{ $mediaUrl($category['background']) }}" @endif @if($category['image']) data-background-poster="{{ $mediaUrl($category['image']) }}" @endif @if($category['key']!==$activeKey) hidden @endif>
                            <h3 class="category-title">{{ $category['name'] }}</h3>
                            <div class="devices">
                                @forelse($category['products'] as $product)
                                    @php
                                        $attrs=$product->catalog_attributes??[];$available=$product->variants->where('quantity','>',0);
                                        $from=$available->min(fn($variant)=>(float)($variant->price??$product->discount_price??$product->price));
                                        $compare=['id'=>$product->id,'name'=>$product->localized('name'),'image'=>$mediaUrl($product->main_image) ?: asset('images/logo.svg'),'url'=>route('electronics.product',[$shop,$product]),'brand'=>data_get($attrs,'brand'),'model'=>data_get($attrs,'model'),'condition'=>data_get($attrs,'condition'),'network'=>data_get($attrs,'network'),'screen'=>data_get($attrs,'screen_size'),'processor'=>data_get($attrs,'processor'),'battery'=>data_get($attrs,'battery'),'battery_health'=>data_get($attrs,'battery_health'),'cameras'=>data_get($attrs,'cameras'),'warranty'=>data_get($attrs,'warranty_months'),'storage'=>$available->pluck('storage')->filter()->unique()->values()->implode('، '),'ram'=>$available->pluck('ram')->filter()->unique()->values()->implode('، '),'colors'=>$available->pluck('color_name')->filter()->unique()->values()->implode('، '),'price'=>$from];
                                    @endphp
                                    <article class="device" data-device data-local-page="{{ intdiv($loop->index,12)+1 }}" @if($loop->index>=12) hidden @endif data-compare="{{ e(json_encode($compare,JSON_UNESCAPED_UNICODE)) }}" tabindex="0">
                                        <form class="device-actions" method="POST" action="{{ route('electronics.compare.toggle', [$shop, $product]) }}">@csrf<button type="submit" class="circle-action compare @if($comparisonProducts->contains('id',$product->id)) selected @endif" aria-label="@if($comparisonProducts->contains('id',$product->id)) إزالة من المقارنة @else إضافة للمقارنة @endif"><i class="ti ti-arrows-diff"></i></button></form>
                                        <a href="{{ route('electronics.product',[$shop,$product]) }}"><span class="device-portrait">@include('front.partials.product_like', ['product' => $product, 'side' => 'right'])<img class="device-image" src="{{ $mediaUrl($product->main_image) ?: asset('images/logo.svg') }}" alt="{{ $product->localized('name') }}" loading="lazy"></span><span class="device-name">{{ $product->localized('name') }}</span><div class="device-meta">{{ data_get($attrs,'brand') }} · {{ data_get($attrs,'condition') }}</div><div class="device-price">من {{ number_format((float)$from,2) }} ₪</div></a>
                                    </article>
                                @empty<div class="empty"><div><i class="ti ti-device-mobile-off" style="font-size:44px"></i><br>{{ __('لا توجد أجهزة في هذه الفئة ضمن نتائج البحث الحالية.') }}</div></div>@endforelse
                            </div>
                            @if($category['products']->count()>12)<nav class="catalog-pagination category-pagination" data-category-pagination aria-label="صفحات {{ $category['name'] }}"><button type="button" class="page-link" data-page-move="-1">{{ __('السابق') }}</button><span class="page-results" data-page-status></span><div style="display:flex;gap:5px" data-page-numbers></div><button type="button" class="page-link" data-page-move="1">{{ __('التالي') }}</button></nav>@endif
                        </section>
                    @endforeach
                </div>
            </div>
        @else<div class="empty">{{ __('لا توجد فئات أو أجهزة مطابقة للفلاتر.') }}</div>@endif
    </section>
</main>
@if($comparisonProducts->isNotEmpty())
<aside class="compare-bar show" id="compareBar" aria-live="polite"><div class="compare-summary"><strong>{{ __('المقارنة') }}</strong><small>{{ $comparisonProducts->count() === 1 ? 'اختر جهازاً آخر' : $comparisonProducts->count().' من 3 أجهزة' }}</small></div><div class="compare-list">@foreach($comparisonProducts as $item)<div class="compare-item"><img src="{{ $mediaUrl($item->main_image) ?: asset('images/logo.svg') }}" alt=""><span><b>{{ $item->localized('name') }}</b></span></div>@endforeach</div><form method="POST" action="{{ route('electronics.compare.clear',$shop) }}">@csrf<button type="submit" class="compare-clear">{{ __('مسح الكل') }}</button></form>@if($comparisonProducts->count() >= 2)<a class="compare-primary" href="{{ route('electronics.compare',$shop) }}">{{ __('قارن الآن') }}</a>@else<button type="button" class="compare-primary" disabled>{{ __('اختر جهازاً آخر') }}</button>@endif</aside>
@endif
@viewScript('front.electronics.index')
@viewScript('front.electronics.index-2')
@include('front.partials.shop_bottom_screens', ['screenPlacement' => 'bottom'])
@include('front.partials.page_share', ['title' => $shop->name, 'url' => $shop->publicUrl()])
</body>
</html>
