{{-- Heart button for a product card. Usage: @include('front.partials.product_like', ['product' => $product]) --}}
@php($likeLabel = match (app()->getLocale()) { 'he' => 'אהבתי', 'en' => 'Like', default => 'أعجبني' })
<button type="button" class="oz-like" data-like-product="{{ $product->id }}" data-like-count="{{ (int) ($product->likes_count ?? 0) }}"
    @isset($variant) data-like-variant="{{ $variant }}" @endisset @isset($side) data-like-side="{{ $side }}" @endisset aria-pressed="false" aria-label="{{ $likeLabel }}" title="{{ $likeLabel }}">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.6s-7.6-4.6-9.4-9.3C1.4 8 3.6 4.6 7.1 4.6c2 0 3.6 1.1 4.9 2.8 1.3-1.7 2.9-2.8 4.9-2.8 3.5 0 5.7 3.4 4.5 6.7-1.8 4.7-9.4 9.3-9.4 9.3z"/></svg>
    <span class="oz-like-count"></span>
</button>
@once
    @include('front.partials.product_like_assets')
@endonce
