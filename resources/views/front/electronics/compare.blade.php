<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    @include('front.partials.seo', [
        'title' => 'مقارنة الأجهزة | '.$shop->name,
        'description' => 'قارن مواصفات وأسعار الأجهزة في '.$shop->name.'.',
        'canonical' => route('electronics.store', $shop),
        'robots' => 'noindex, follow',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    @viewStyle('front.electronics.compare')
</head>
<body>
@php
    $mediaUrl = fn($path) => filled($path) ? (\Illuminate\Support\Str::startsWith($path,['http://','https://','storage/']) ? asset($path) : asset('storage/'.$path)) : asset('images/logo.svg');
    $rows = [
        ['price','السعر'],['brand','الماركة'],['model','الموديل'],['condition','الحالة'],['network','الشبكة'],['screen_size','الشاشة'],['processor','المعالج'],['storage','سعات التخزين'],['ram','الرام'],['colors','الألوان'],['battery','البطارية'],['battery_health','صحة البطارية'],['cameras','الكاميرات'],['warranty_months','الضمان'],
    ];
    $values = fn($product,$key) => match($key) {
        'price' => number_format((float)($product->variants->where('quantity','>',0)->min(fn($v)=>(float)($v->price??$product->discount_price??$product->price)) ?? $product->discount_price ?? $product->price),2).' ₪',
        'storage' => $product->variants->pluck('storage')->filter()->unique()->implode('، '),
        'ram' => $product->variants->pluck('ram')->filter()->unique()->implode('، '),
        'colors' => $product->variants->pluck('color_name')->filter()->unique()->implode('، '),
        'battery_health' => filled(data_get($product->catalog_attributes,$key)) ? data_get($product->catalog_attributes,$key).'%' : '—',
        'warranty_months' => filled(data_get($product->catalog_attributes,$key)) ? data_get($product->catalog_attributes,$key).' شهر' : '—',
        default => data_get($product->catalog_attributes,$key) ?: '—',
    };
@endphp
<main class="wrap">
    <header class="top"><div><h1>مقارنة الأجهزة</h1><span style="color:var(--cyan)">{{ $shop->name }}</span></div><a class="back" href="{{ route('electronics.store',$shop) }}">العودة للأجهزة ←</a></header>
    <p class="hint">اسحب الجدول يمينًا ويسارًا على الهاتف لمشاهدة جميع الأجهزة.</p><div class="legend"><i></i> الخط السماوي يعني أن المواصفات مختلفة.</div>
    <div class="table-wrap"><table class="compare"><thead><tr><th>الجهاز</th>@foreach($products as $product)<th class="device"><img src="{{ $mediaUrl($product->main_image) }}" alt=""><b>{{ $product->localized('name') }}</b><a href="{{ route('electronics.product',[$shop,$product]) }}">عرض التفاصيل</a><form method="POST" action="{{ route('electronics.compare.toggle',[$shop,$product]) }}">@csrf<button class="remove">إزالة</button></form></th>@endforeach</tr></thead><tbody>@foreach($rows as [$key,$label])@php $rowValues=$products->map(fn($product)=>$values($product,$key));$different=$rowValues->map(fn($value)=>mb_strtolower(trim((string)$value)))->unique()->count()>1; @endphp<tr @class(['different'=>$different])><td>{{ $label }}</td>@foreach($rowValues as $value)<td @class(['price'=>$key==='price'])>{{ is_array($value)?implode('، ',$value):$value }}</td>@endforeach</tr>@endforeach</tbody></table></div>
</main>
</body></html>
