<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($price = (float) ($product->discount_price ?: $product->price))
    @include('front.partials.seo', [
        'title' => $product->localized('name').' — '.$shop->name,
        'description' => trim(($product->localized('description') ?: '').' '.($price > 0 ? number_format($price, 2).' ₪' : '')) ?: $shop->name,
        'canonical' => $target,
        'image' => $image,
        'type' => 'product',
        'robots' => 'noindex, follow',
    ])
    <meta http-equiv="refresh" content="0;url={{ $target }}">
    <style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,sans-serif;background:#0f1115;color:#fff;text-align:center}a{display:inline-block;margin-top:14px;padding:12px 22px;border-radius:14px;background:#25d366;color:#fff;font-weight:700;text-decoration:none}img{width:160px;height:160px;object-fit:cover;border-radius:22px}</style>
</head>
<body>
    <main>
        <img src="{{ $image }}" alt="{{ $product->localized('name') }}">
        <h1 style="font-size:20px">{{ $product->localized('name') }}</h1>
        <p style="opacity:.75">{{ $shop->name }}</p>
        <a href="{{ $target }}">{{ app()->getLocale() === 'en' ? 'Open' : (app()->getLocale() === 'he' ? 'פתיחה' : 'افتح') }}</a>
    </main>
    <script>location.replace(@json($target));</script>
</body>
</html>
