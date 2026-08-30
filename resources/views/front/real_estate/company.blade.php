<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @include('front.partials.seo', [
        'title' => $shop->name.' | عقارات',
        'description' => $shop->description ?: 'تصفح عقارات '.$shop->name.' المتاحة للبيع والإيجار.',
        'canonical' => route('real-estate.company', $shop),
        'image' => $shop->logo ? asset($shop->logo) : asset('images/logo.svg'),
        'schema' => ['@context' => 'https://schema.org', '@type' => 'RealEstateAgent', 'name' => $shop->name, 'url' => route('real-estate.company', $shop), 'telephone' => $shop->phone, 'address' => $shop->address],
    ])
    <style>
        *{box-sizing:border-box}body{margin:0;background:#081018;color:#f7fbff;font-family:Arial,Tahoma,sans-serif}.wrap{width:min(1180px,calc(100% - 28px));margin:auto}.hero{padding:46px 0 28px;border-bottom:1px solid #203240}.brand{display:flex;align-items:center;gap:16px}.brand img{width:78px;height:78px;border-radius:18px;object-fit:cover;background:#fff}.brand h1{margin:0 0 8px}.brand p{margin:0;color:#9db0bf}.properties{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;padding:28px 0}.card{overflow:hidden;border:1px solid #203240;border-radius:18px;background:#101b25;color:inherit;text-decoration:none}.card img,.placeholder{display:grid;place-items:center;width:100%;height:190px;object-fit:cover;background:#172631;color:#7290a4}.card-body{padding:16px}.card h2{margin:0 0 12px;font-size:18px}.meta{display:flex;justify-content:space-between;gap:10px;color:#a9bac6}.price{margin-top:14px;color:#15d9f4;font-weight:800}.empty{grid-column:1/-1;padding:70px 20px;text-align:center;color:#9db0bf;border:1px dashed #304553;border-radius:18px}
    </style>
</head>
<body>
<header class="hero"><div class="wrap brand">
    @if($shop->logo)<img src="{{ asset($shop->logo) }}" alt="شعار {{ $shop->name }}">@endif
    <div><h1>{{ $shop->name }}</h1><p>{{ $shop->description ?: 'عقارات للبيع والإيجار' }}</p></div>
</div></header>
<main class="wrap properties">
    @forelse($properties as $property)
        <a class="card" href="{{ route('real-estate.property', [$shop, $property]) }}">
            @if($property->images->first())<img src="{{ asset($property->images->first()->path) }}" alt="{{ $property->images->first()->alt_text ?: $property->title }}">@else<div class="placeholder">لا توجد صورة</div>@endif
            <div class="card-body"><h2>{{ $property->title }}</h2><div class="meta"><span>{{ $property->city }}</span><span>{{ $property->area ? $property->area.' م²' : '' }}</span></div><div class="price">{{ number_format((float)$property->price) }} {{ $property->currency }}</div></div>
        </a>
    @empty
        <div class="empty">سيتم عرض عقارات الشركة المنشورة هنا.</div>
    @endforelse
</main>
{{ $properties->links() }}
</body>
</html>
