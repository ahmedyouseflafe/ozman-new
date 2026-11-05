@extends('promoters.layout')
@section('title', __('إدارة المتجر الحر'))
@section('content')
<style>{!! file_get_contents(public_path('market.css')) !!}</style>
@include('market.nav')
<div class="section-heading page-heading"><div><span class="eyebrow">{{ __('إدارة أوزمان') }}</span><h1>{{ __('المتجر الحر') }}</h1><p>{{ __('المنتجات بتنزل فوراً. من هون بتقدر توقف أو تحذف أي منتج مخالف.') }}</p></div><a class="button outline" href="{{ route('dashboard') }}">{{ __('العودة للوحة التحكم') }}</a></div>
<div class="reach-stats market-stats"><div><strong>{{ number_format($stats['active']) }}</strong><span>{{ __('منتج معروض') }}</span></div><div><strong>{{ number_format($stats['sellers']) }}</strong><span>{{ __('بائع') }}</span></div><div><strong>{{ number_format($stats['open_orders']) }}</strong><span>{{ __('طلب مفتوح') }}</span></div><div><strong>{{ number_format($stats['completed']) }}</strong><span>{{ __('طلب تم تسليمه') }}</span></div></div>
<form class="market-filters" method="get" action="{{ route('market.admin') }}"><label class="market-search"><span class="visually-hidden">{{ __('بحث') }}</span><input type="search" name="q" value="{{ $q }}" placeholder="{{ __('اسم المنتج، البائع، الإيميل…') }}"></label><label><span class="visually-hidden">{{ __('الحالة') }}</span><select name="status"><option value="">{{ __('كل الحالات') }}</option>@foreach(['active' => 'معروض', 'hidden' => 'مخفي', 'sold' => 'خلص', 'removed' => 'موقوف'] as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ __($label) }}</option>@endforeach</select></label><button class="button" type="submit">{{ __('بحث') }}</button></form>
<div class="market-rows">
@forelse($listings as $listing)
<article class="market-row">
    <a href="{{ $listing->url() }}" class="market-row-img">@if($cover = $listing->coverUrl())<img src="{{ $cover }}" alt="" loading="lazy">@else🛍@endif</a>
    <div class="market-row-body"><h3><a href="{{ $listing->url() }}">{{ $listing->title }}</a></h3><p><b>{{ $listing->formattedPrice() }}</b> · {{ $listing->categoryLabel() }} · {{ $listing->city }} · {{ $listing->created_at->diffForHumans() }}</p><p class="market-meta">{{ $listing->seller->name }} <span dir="ltr">{{ '@'.$listing->seller->username }} · {{ $listing->seller->email }}</span>@if($listing->seller->phone) · <span dir="ltr">{{ $listing->seller->phone }}</span>@endif · {{ __(':count طلب', ['count' => $listing->orders_count]) }}</p><span class="market-status is-{{ $listing->status }}">{{ $listing->statusLabel() }}</span></div>
    <div class="market-row-actions">
        <form method="post" action="{{ route('market.admin.moderate', $listing) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="{{ $listing->status === 'removed' ? 'restore' : 'remove' }}"><button class="button small outline" type="submit">{{ $listing->status === 'removed' ? __('إرجاع') : __('إيقاف') }}</button></form>
        <form method="post" action="{{ route('market.admin.destroy', $listing) }}" onsubmit="return confirm('{{ __('حذف المنتج نهائياً؟') }}')">@csrf @method('DELETE')<button class="text-link danger" type="submit">{{ __('حذف') }}</button></form>
    </div>
</article>
@empty<p class="quiet-empty">{{ __('ما في منتجات.') }}</p>@endforelse
</div>
@include('promoters.pagination', ['items' => $listings])
@endsection
