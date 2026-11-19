<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('شوفها بغرفتك') }} · {{ $product->localized('name') }}</title>
    <meta name="description" content="{{ __(':product من :shop، بالحجم الحقيقي بغرفتك.', ['product' => $product->localized('name'), 'shop' => $product->shop->name]) }}">
    <style>
        :root { --bg: #0b0f1a; --card: #141a2b; --line: #263049; --text: #eef2ff; --muted: #9aa6c4; --accent: #08def4; }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; background: var(--bg); color: var(--text); font-family: Cairo, system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; }
        .wrap { display: flex; flex-direction: column; min-height: 100%; max-width: 760px; margin: 0 auto; padding: 14px 16px 22px; gap: 12px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .top h1 { margin: 0; font-size: 19px; line-height: 1.4; }
        .top small { display: block; color: var(--muted); font-weight: 600; }
        .back { color: var(--accent); text-decoration: none; font-weight: 800; white-space: nowrap; }
        model-viewer { flex: 1; width: 100%; min-height: 58vh; border-radius: 22px; background: radial-gradient(circle at 50% 35%, #1b2440, #0d1120 70%); border: 1px solid var(--line); --poster-color: transparent; }
        .place { position: absolute; inset-inline: 16px; bottom: 16px; padding: 14px; border: 0; border-radius: 16px; background: var(--accent); color: #02161a; font: inherit; font-size: 16px; font-weight: 900; cursor: pointer; }
        .facts { display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; align-items: center; }
        .price { font-size: 20px; font-weight: 900; }
        .size { color: var(--muted); font-size: 14px; }
        .note { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.7; }
    </style>
</head>
<body>
@php
    $price = (float) ($product->discount_price ?: $product->price);
    $sizeText = $size ? collect(['w' => __('عرض'), 'd' => __('عمق'), 'h' => __('ارتفاع')])
        ->filter(fn ($label, $key) => ! empty($size[$key]))->map(fn ($label, $key) => $label.' '.rtrim(rtrim(number_format($size[$key], 1, '.', ''), '0'), '.'))->implode(' × ').' '.__('سم') : null;
@endphp
<main class="wrap">
    <div class="top">
        <h1>{{ $product->localized('name') }}<small>{{ $product->shop->name }}</small></h1>
        <a class="back" href="{{ $product->shop->publicUrl() }}">{{ __('ارجع للمحل') }}</a>
    </div>
    <model-viewer id="model" src="{{ $model }}" alt="{{ $product->localized('name') }}" ar ar-modes="webxr scene-viewer quick-look"
        ar-placement="floor" ar-scale="fixed" camera-controls touch-action="pan-y" shadow-intensity="1" exposure="1" interaction-prompt="auto">
        <button slot="ar-button" class="place" type="button">{{ __('📷 حطها بغرفتك') }}</button>
    </model-viewer>
    <div class="facts">
        <span class="price">{{ number_format($price, 2) }} ₪</span>
        @if ($sizeText)<span class="size">{{ $sizeText }}</span>@endif
    </div>
    <p class="note">{{ __('لفّ القطعة بإصبعك. على الجوال اكبس «حطها بغرفتك»، وجّه الكاميرا على الأرض، وبتطلع القطعة بحجمها الحقيقي.') }}</p>
</main>
<script type="module" src="https://unpkg.com/@google/model-viewer@4.4.0/dist/model-viewer.min.js"></script>
<script>
// Models made from a photo have no real size: scale them so the widest side matches the size the shop entered.
(() => {
    const size = @json($size);
    const viewer = document.getElementById('model');
    if (!size || !(size.w || size.d || size.h)) return;
    viewer.addEventListener('load', () => {
        const box = viewer.getDimensions(); // metres
        const wanted = [size.w, size.d, size.h].map((cm) => cm ? cm / 100 : null);
        const actual = [box.x, box.z, box.y];
        const ratios = wanted.map((w, i) => w && actual[i] > 0 ? w / actual[i] : null).filter(Boolean);
        if (!ratios.length) return;
        const scale = ratios.reduce((a, b) => a + b, 0) / ratios.length;
        if (Math.abs(scale - 1) > 0.02) viewer.scale = `${scale} ${scale} ${scale}`;
    }, {once: true});
})();
</script>
</body>
</html>
