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
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15V3.5M7.5 8 12 3.5 16.5 8M5 12.5v6A2.5 2.5 0 0 0 7.5 21h9a2.5 2.5 0 0 0 2.5-2.5v-6"/></svg>
    </button>
</span>
@once
    @include('front.partials.product_like_assets')
@endonce
