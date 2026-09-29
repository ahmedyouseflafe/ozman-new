@php
    $name = $product->localized('name');
    $description = $product->localized('description');
    $price = (float) ($product->discount_price ?: $product->price);
    $oldPrice = $product->discount_price && (float) $product->discount_price < (float) $product->price ? (float) $product->price : null;
    $image = $mediaUrl($product->main_image, 'images/logo.svg');
    $brand = data_get($product->catalog_attributes ?? [], 'brand');
@endphp
<article class="card">
    <button class="picture" type="button" data-image="{{ $image }}" aria-label="تكبير صورة {{ $name }}"><img src="{{ $image }}" alt="{{ $name }}" loading="lazy">@if($product->is_featured)<span class="badge"><i class="ti ti-sparkles"></i> مميز</span>@endif</button>
    <div class="body">
        <div class="small-brand">{{ $brand ?: 'صناعة '.$shop->name }}</div>
        <h4 class="name">{{ $name }}</h4>
        <p class="description">{{ $description ?: 'حلاوة خاصة تُجهز لتكمل لحظتك الحلوة.' }}</p>
        <div class="foot"><div class="prices">@if($oldPrice)<span class="was">{{ number_format($oldPrice, 2) }} ₪</span>@endif<span class="price">{{ number_format($price, 2) }} ₪</span></div><button class="add" type="button" data-add="{{ $product->id }}" data-name="{{ e($name) }}" data-price="{{ $price }}"><i class="ti ti-plus"></i> أضف للسلة</button></div>
    </div>
</article>
