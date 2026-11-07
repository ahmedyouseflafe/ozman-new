{{-- Floating "share this page" button for storefronts. Usage: @include('front.partials.page_share', ['title' => $shop->name]) --}}
@php($pageShareLabel = match (app()->getLocale()) { 'he' => 'שיתוף העמוד', 'en' => 'Share this page', default => 'شارك الصفحة' })
<button type="button" class="oz-page-share" data-share-url="{{ $url ?? url()->current() }}" data-share-title="{{ $title ?? config('app.name', 'Ozman') }}"
    aria-label="{{ $pageShareLabel }}" title="{{ $pageShareLabel }}">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15V3.5M7.5 8 12 3.5 16.5 8M5 12.5v6A2.5 2.5 0 0 0 7.5 21h9a2.5 2.5 0 0 0 2.5-2.5v-6"/></svg>
    <span>{{ $pageShareLabel }}</span>
</button>
@once
    @include('front.partials.product_like_assets')
@endonce
