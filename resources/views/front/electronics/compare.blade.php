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
    <style>
        :root{--cyan:#21dcff;--bg:#05070c;--panel:#0d1622;--line:#20364a;--muted:#91a8b9}*{box-sizing:border-box}html,body{max-width:100%;margin:0;overflow-x:hidden}body{min-height:100vh;background:radial-gradient(circle at 80% 0,rgba(33,220,255,.1),transparent 30%),var(--bg);color:#fff;font-family:Cairo,Segoe UI,sans-serif}a{color:inherit;text-decoration:none}.wrap{width:min(1180px,calc(100% - 28px));margin:auto}.top{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:28px 0}.top h1{margin:0;font-size:clamp(27px,5vw,48px)}.back{padding:11px 16px;border:1px solid var(--line);border-radius:12px;color:var(--cyan)}.hint{margin:-12px 0 20px;color:var(--muted)}.table-wrap{overflow:auto;padding-bottom:18px;overscroll-behavior-inline:contain}.compare{width:100%;min-width:720px;border-collapse:separate;border-spacing:0 9px;table-layout:fixed}.compare th,.compare td{padding:14px;text-align:center;background:var(--panel)}.compare th:first-child,.compare td:first-child{position:sticky;z-index:2;right:0;width:145px;border-radius:0 13px 13px 0;background:#111d2a;color:var(--muted);text-align:right}.compare th:last-child,.compare td:last-child{border-radius:13px 0 0 13px}.device img{display:block;width:120px;height:120px;margin:auto;object-fit:contain}.device b{display:block;font-size:17px}.device a{display:inline-block;margin-top:8px;color:var(--cyan);font-size:12px}.different td:not(:first-child){box-shadow:inset 0 -3px rgba(33,220,255,.45)}.price{color:var(--cyan);font-weight:900}.remove{display:inline-block;margin-top:7px;padding:5px 9px;border:0;border-radius:8px;background:#25151c;color:#ff91a0;font:inherit;font-size:10px;cursor:pointer}.legend{display:flex;align-items:center;gap:8px;margin:0 0 14px;color:var(--muted);font-size:12px}.legend i{width:22px;height:3px;background:var(--cyan)}
        @media(max-width:600px){.wrap{width:100%;padding-inline:8px}.top{align-items:flex-start;padding:17px 3px}.top h1{font-size:25px}.back{padding:8px 10px;font-size:11px}.hint{font-size:11px}.compare{min-width:620px}.compare th,.compare td{padding:10px 7px;font-size:11px}.compare th:first-child,.compare td:first-child{width:105px}.device img{width:82px;height:82px}.device b{font-size:12px}}
    </style>
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
