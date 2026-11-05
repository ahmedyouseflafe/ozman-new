@extends('promoters.layout')
@section('title', __('طلباتي · المتجر الحر'))
@section('content')
<style>{!! file_get_contents(public_path('market.css')) !!}</style>
@include('market.nav')
<div class="section-heading"><div><span class="eyebrow">{{ __('المتجر الحر') }}</span><h1>{{ __('طلباتي') }}</h1></div></div>
@error('order')<div class="notice error" role="alert">{{ $message }}</div>@enderror
<div class="market-rows">
@forelse($orders as $order)
<article class="market-row market-order-card is-{{ $order->status }}">
    <a href="{{ $order->listing?->url() ?? route('market.index') }}" class="market-row-img">@if($order->listing && ($cover = $order->listing->coverUrl()))<img src="{{ $cover }}" alt="" loading="lazy">@else🛍@endif</a>
    <div class="market-row-body">
        <p class="market-order-head"><bdi dir="ltr">{{ $order->order_number }}</bdi> · {{ $order->created_at->diffForHumans() }} <span class="market-status is-{{ $order->status }}">{{ $order->statusLabel() }}</span></p>
        <h3>{{ $order->listing_title }}</h3>
        <p>{{ __('الكمية :q', ['q' => $order->quantity]) }} · <b>{{ $order->formattedTotal() }}</b></p>
        <p class="market-meta">{{ __('البائع:') }} {{ \Illuminate\Support\Str::before(trim((string) $order->seller?->name).' ', ' ') }}@if($order->seller?->username) <span class="handle" dir="ltr">{{ '@'.$order->seller->username }}</span>@endif
        @if(in_array($order->status, ['accepted', 'completed'], true) && $order->seller?->phone) · <a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $order->seller->phone) }}">{{ $order->seller->phone }}</a>@endif</p>
    </div>
    <div class="market-row-actions">@if($order->status === 'pending')<form method="post" action="{{ route('market.orders.cancel', $order) }}" onsubmit="return confirm('{{ __('إلغاء الطلب؟') }}')">@csrf @method('PATCH')<button class="text-link danger" type="submit">{{ __('إلغاء الطلب') }}</button></form>@endif</div>
</article>
@empty<div class="empty-state"><span class="empty-icon">🧺</span><h3>{{ __('ما طلبت إشي لهلأ') }}</h3><a class="button" href="{{ route('market.index') }}">{{ __('تصفّح المتجر الحر') }}</a></div>@endforelse
</div>
@include('promoters.pagination', ['items' => $orders])
@endsection
