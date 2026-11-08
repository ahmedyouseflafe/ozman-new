{{-- Heart + share buttons for a product card.
     Usage: @include('front.partials.product_like', ['product' => $product]) — optional 'variant' => 'inline' (next to the name) and 'side' => 'right'|'left'. --}}
@php($actionLabels = match (app()->getLocale()) { 'he' => ['אהבתי', 'שיתוף'], 'en' => ['Like', 'Share'], default => ['أعجبني', 'مشاركة'] })
<span class="oz-actions{{ ($variant ?? null) === 'inline' ? ' oz-actions--inline' : '' }}" data-oz-product="{{ $product->id }}" @isset($side) data-side="{{ $side }}" @endisset>
    <button type="button" class="oz-like" data-like-product="{{ $product->id }}" data-like-count="{{ (int) ($product->likes_count ?? 0) }}"
        aria-pressed="false" aria-label="{{ $actionLabels[0] }}" title="{{ $actionLabels[0] }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.6s-7.6-4.6-9.4-9.3C1.4 8 3.6 4.6 7.1 4.6c2 0 3.6 1.1 4.9 2.8 1.3-1.7 2.9-2.8 4.9-2.8 3.5 0 5.7 3.4 4.5 6.7-1.8 4.7-9.4 9.3-9.4 9.3z"/></svg>
        <span class="oz-like-count"></span>
    </button>
    <button type="button" class="oz-share" data-share-url="{{ route('share.product', $product) }}" data-share-title="{{ $product->localized('name') }}"
        aria-label="{{ $actionLabels[1] }}" title="{{ $actionLabels[1] }}">
        <svg class="oz-share-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.2 3.6a.9.9 0 0 1 1.5-.66l7.4 6.9a.9.9 0 0 1 0 1.32l-7.4 6.9a.9.9 0 0 1-1.5-.66v-3.5c-4.7 0-8 1.5-10.4 5.1-.3.45-1 .2-.95-.35.75-5.9 4.3-10.5 11.35-11.4z"/></svg>
        <span class="oz-share-label">{{ $actionLabels[1] }}</span>
    </button>
</span>
@once
    @include('front.partials.product_like_assets')
@endonce
