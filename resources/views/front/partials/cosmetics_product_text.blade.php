@if($shop->slug === 'elegance-perfumes')
    <h3 class="product-name"><bdi>{{ $product->localized('name') }}</bdi>@if(filled(data_get($attrs, 'volume'))) <bdi class="product-volume">{{ data_get($attrs, 'volume') }}</bdi>@endif</h3>
    <div class="product-details">{{ $product->localized('description') ?: __('منتج عناية مختار بعناية.') }}</div>
    @if($shades = collect(data_get($attrs, 'shades', []))->filter()->implode(' · '))
        <div class="product-details">{{ $shades }}</div>
    @endif
@else
    <h3 class="product-name">{{ $product->localized('name') }}</h3>
    <div class="product-details">{{ collect([data_get($attrs, 'volume'), collect(data_get($attrs, 'shades', []))->filter()->implode(' · ')])->filter()->implode(' — ') ?: ($product->localized('description') ?: __('منتج عناية مختار بعناية.')) }}</div>
@endif
