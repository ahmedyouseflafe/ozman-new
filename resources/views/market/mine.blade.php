@extends('promoters.layout')
@section('title', __('منتجاتي · المتجر الحر'))
@section('content')
<style>{!! file_get_contents(public_path('market.css')) !!}</style>
@include('market.nav')

<div class="market-rows">
@forelse($listings as $listing)
<article class="market-row">
    <a href="{{ $listing->url() }}" class="market-row-img">@if($cover = $listing->coverUrl())<img src="{{ $cover }}" alt="" loading="lazy">@else🛍@endif</a>
    <div class="market-row-body"><h3><a href="{{ $listing->url() }}">{{ $listing->title }}</a></h3><p><b>{{ $listing->formattedPrice() }}</b> · {{ __('الكمية :q', ['q' => $listing->quantity]) }} · 👁 {{ number_format($listing->views) }}</p><span class="market-status is-{{ $listing->status }}">{{ $listing->statusLabel() }}</span>@if($listing->open_orders_count)<a class="market-status is-alert" href="{{ route('market.sales') }}">{{ __(':count طلب جديد', ['count' => $listing->open_orders_count]) }}</a>@endif</div>
    <div class="market-row-actions"><a class="button small outline" href="{{ route('market.edit', $listing) }}">{{ __('تعديل') }}</a>@if($listing->status !== 'removed')<form method="post" action="{{ route('market.toggle', $listing) }}">@csrf @method('PATCH')<button class="text-link" type="submit">{{ $listing->status === 'active' ? __('إخفاء') : __('إظهار') }}</button></form>@endif</div>
</article>
@empty<div class="empty-state"><span class="empty-icon">🛍</span><h3>{{ __('لسه ما عرضت ولا منتج') }}</h3><p>{{ __('صوّر المنتج، حط السعر، وبيظهر بالمتجر فوراً.') }}</p><a class="button" href="{{ route('market.create') }}">{{ __('+ بيع منتج') }}</a></div>@endforelse
</div>
@include('promoters.pagination', ['items' => $listings])
@endsection
