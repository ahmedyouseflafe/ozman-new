{{-- Floating "share this page" button for storefronts. Usage: @include('front.partials.page_share', ['title' => $shop->name]) --}}
@php($pageShareLabel = match (app()->getLocale()) { 'he' => 'שיתוף', 'en' => 'Share', default => 'مشاركة' })
<button type="button" class="oz-page-share" data-share-url="{{ $url ?? url()->current() }}" data-share-title="{{ $title ?? config('app.name', 'Ozman') }}"
    aria-label="{{ $pageShareLabel }}" title="{{ $pageShareLabel }}">
    <span class="oz-page-share-icon"><svg class="oz-share-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.2 3.6a.9.9 0 0 1 1.5-.66l7.4 6.9a.9.9 0 0 1 0 1.32l-7.4 6.9a.9.9 0 0 1-1.5-.66v-3.5c-4.7 0-8 1.5-10.4 5.1-.3.45-1 .2-.95-.35.75-5.9 4.3-10.5 11.35-11.4z"/></svg></span>
    <span class="oz-share-label">{{ $pageShareLabel }}</span>
</button>
{{-- The shop's star rating, above the share button, once it has reviews. --}}
@if (isset($shop) && $shop instanceof \App\Models\Shop && ($shopRating = \App\Models\ShopReview::summaryFor($shop->id))['count'] > 0)
    <a class="oz-shop-rating" href="{{ route('shop.reviews', $shop) }}" aria-label="{{ __('تقييمات :shop', ['shop' => $shop->name]) }}">
        <span class="oz-shop-rating-star">★</span><b>{{ number_format($shopRating['average'], 1) }}</b><small>{{ $shopRating['count'] }}</small>
    </a>
    @once
        <style>
            .oz-shop-rating{position:fixed;z-index:9990;inset-inline-start:10px;bottom:calc(250px + env(safe-area-inset-bottom,0px));display:flex;flex-direction:column;align-items:center;justify-content:center;width:52px;height:52px;border-radius:50%;background:rgba(12,16,28,.88);border:1px solid rgba(255,197,66,.55);color:#fff;text-decoration:none;font-family:inherit;line-height:1;box-shadow:0 6px 18px rgba(0,0,0,.35);-webkit-tap-highlight-color:transparent}
            .oz-shop-rating b{font-size:14px;margin-top:1px}.oz-shop-rating small{font-size:9px;color:#c9d2e8;margin-top:2px}
            .oz-shop-rating-star{color:#ffc542;font-size:13px}
            .oz-shop-rating:focus-visible{outline:2px solid #ffc542;outline-offset:3px}
        </style>
    @endonce
@endif
@once
    @include('front.partials.product_like_assets')
    @include('front.partials.shopping_assistant')
@endonce
