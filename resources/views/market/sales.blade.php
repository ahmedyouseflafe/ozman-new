@extends('promoters.layout')
@section('title', __('طلبات وصلتني · المتجر الحر'))
@section('content')
<style>{!! file_get_contents(public_path('market.css')) !!}</style>
@include('market.nav')
<div class="section-heading"><div><span class="eyebrow">{{ __('المتجر الحر') }}</span><h1>{{ __('طلبات وصلتني') }}</h1><p>{{ __('اقبل الطلب عشان تنحجز الكمية، وبعد التسليم اضغط "تم التسليم".') }}</p></div></div>
@error('order')<div class="notice error" role="alert">{{ $message }}</div>@enderror
<div class="market-rows">
@forelse($orders as $order)
<article class="market-row market-order-card is-{{ $order->status }}">
    <div class="market-row-body">
        <p class="market-order-head"><bdi dir="ltr">{{ $order->order_number }}</bdi> · <time datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->diffForHumans() }}</time> <span class="market-status is-{{ $order->status }}">{{ $order->statusLabel() }}</span></p>
        <h3>@if($order->listing)<a href="{{ $order->listing->url() }}">{{ $order->listing_title }}</a>@else{{ $order->listing_title }}@endif</h3>
        <p>{{ __('الكمية :q × :price = ', ['q' => $order->quantity, 'price' => rtrim(rtrim(number_format((float) $order->unit_price, 2), '0'), '.')]) }}<b>{{ $order->formattedTotal() }}</b></p>
        <dl class="market-buyer"><div><dt>{{ __('الزبون') }}</dt><dd>{{ $order->buyer_name }}@if($order->buyer?->username) <span class="handle" dir="ltr">{{ '@'.$order->buyer->username }}</span>@endif</dd></div><div><dt>{{ __('الجوال') }}</dt><dd><a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $order->buyer_phone) }}">{{ $order->buyer_phone }}</a></dd></div><div><dt>{{ __('العنوان') }}</dt><dd>{{ $order->city }} · {{ $order->address }}</dd></div>@if($order->note)<div><dt>{{ __('ملاحظة') }}</dt><dd>{{ $order->note }}</dd></div>@endif</dl>
    </div>
    <div class="market-row-actions">
        @if($order->status === 'pending')
            <form method="post" action="{{ route('market.orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="accept"><button class="button small" type="submit">{{ __('✓ قبول') }}</button></form>
            <form method="post" action="{{ route('market.orders.update', $order) }}" onsubmit="return confirm('{{ __('رفض الطلب؟') }}')">@csrf @method('PATCH')<input type="hidden" name="action" value="reject"><button class="text-link danger" type="submit">{{ __('رفض') }}</button></form>
        @elseif($order->status === 'accepted')
            <form method="post" action="{{ route('market.orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="complete"><button class="button small" type="submit">{{ __('تم التسليم') }}</button></form>
            <form method="post" action="{{ route('market.orders.update', $order) }}" onsubmit="return confirm('{{ __('إلغاء الطلب؟ الكمية بترجع للمنتج.') }}')">@csrf @method('PATCH')<input type="hidden" name="action" value="cancel"><button class="text-link danger" type="submit">{{ __('إلغاء') }}</button></form>
        @endif
    </div>
</article>
@empty<div class="empty-state"><span class="empty-icon">📦</span><h3>{{ __('ما وصلك طلبات لهلأ') }}</h3><p>{{ __('شارك رابط منتجك مع أصحابك عشان يوصلك أول طلب.') }}</p></div>@endforelse
</div>
@include('promoters.pagination', ['items' => $orders])
@endsection
