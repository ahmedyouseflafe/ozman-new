@php
    $name = $product->localized('name');
    $attrs = $product->catalog_attributes ?? [];
    $price = (float) ($product->discount_price ?: $product->price);
    $image = $mediaUrl($product->main_image);
    $meta = collect([
        data_get($attrs, 'dimensions'),
        data_get($attrs, 'material'),
        data_get($attrs, 'brand'),
    ])->filter()->implode(' · ');
@endphp
<article class="product">
    <button class="product-picture" type="button" data-image="{{ $image }}" aria-label="تكبير صورة {{ $name }}"><img src="{{ $image }}" alt="{{ $name }}" loading="lazy">@if($product->is_featured)<span class="product-tag">اختيار البيت</span>@endif</button>
    <div class="product-body"><div class="product-brand">{{ data_get($attrs, 'brand') ?: 'تفاصيل تصنع الفرق' }}</div><h4 class="product-name">{{ $name }}</h4><p class="product-meta">{{ $meta ?: ($product->localized('description') ?: 'قطعة مختارة لتكمل بيتك.') }}</p><div class="product-bottom"><strong class="product-price">{{ number_format($price, 2) }} ₪</strong><button class="quick-add" type="button" data-add data-id="{{ $product->id }}" data-name="{{ $name }}" data-price="{{ $price }}"><i class="ti ti-plus"></i> أضف</button></div></div>
</article>
