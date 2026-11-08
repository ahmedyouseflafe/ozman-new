{{-- Floating "share this page" button for storefronts. Usage: @include('front.partials.page_share', ['title' => $shop->name]) --}}
@php($pageShareLabel = match (app()->getLocale()) { 'he' => 'שיתוף', 'en' => 'Share', default => 'مشاركة' })
<button type="button" class="oz-page-share" data-share-url="{{ $url ?? url()->current() }}" data-share-title="{{ $title ?? config('app.name', 'Ozman') }}"
    aria-label="{{ $pageShareLabel }}" title="{{ $pageShareLabel }}">
    <span class="oz-page-share-icon"><svg class="oz-share-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.2 3.6a.9.9 0 0 1 1.5-.66l7.4 6.9a.9.9 0 0 1 0 1.32l-7.4 6.9a.9.9 0 0 1-1.5-.66v-3.5c-4.7 0-8 1.5-10.4 5.1-.3.45-1 .2-.95-.35.75-5.9 4.3-10.5 11.35-11.4z"/></svg></span>
    <span class="oz-share-label">{{ $pageShareLabel }}</span>
</button>
@once
    @include('front.partials.product_like_assets')
@endonce
