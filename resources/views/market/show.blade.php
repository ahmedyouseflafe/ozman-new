@extends('promoters.layout')
@section('title', $listing->title.' · '.__('المتجر الحر'))
@section('description', \Illuminate\Support\Str::limit((string) $listing->description, 150) ?: $listing->title)
@section('content')
<style>{!! file_get_contents(public_path('market.css')) !!}</style>
@include('market.nav')
@php($seller = $listing->seller)
@unless($listing->isVisible())<div class="notice error" role="status">{{ __('هذا المنتج مش ظاهر بالمتجر حالياً:') }} {{ $listing->quantity < 1 ? __('خلصت الكمية') : $listing->statusLabel() }}.</div>@endunless
<div class="market-detail">
    <section class="market-gallery" aria-label="{{ __('صور المنتج') }}">
        <div class="market-gallery-main">@if($listing->images->isNotEmpty())<img id="marketMainImage" src="{{ route('market.image', $listing->images->first()) }}" alt="{{ $listing->title }}">@else<span class="market-noimg" aria-hidden="true">🛍</span>@endif</div>
        @if($listing->images->count() > 1)<div class="market-thumbs">@foreach($listing->images as $image)<button type="button" data-src="{{ route('market.image', $image) }}" aria-label="{{ __('صورة :n', ['n' => $loop->iteration]) }}" @class(['is-current' => $loop->first])><img src="{{ route('market.image', $image) }}" alt="" loading="lazy"></button>@endforeach</div>@endif
    </section>
    <section class="market-info">
        <div class="market-tags"><span>{{ $listing->conditionLabel() }}</span><span>{{ $listing->categoryLabel() }}</span><span>📍 {{ $listing->city }}</span></div>
        <h1>{{ $listing->title }}</h1>
        <strong class="market-price is-large">{{ $listing->formattedPrice() }}</strong>
        <p class="market-meta">{{ __('المتوفر: :count', ['count' => $listing->quantity]) }} · {{ __('نُشر :time', ['time' => $listing->created_at->diffForHumans()]) }} · 👁 {{ number_format($listing->views) }}</p>
        @if($listing->description)<div class="market-description">{{ $listing->description }}</div>@endif
        <div class="market-seller">@include('promoters.customer-avatar', ['user' => $seller])<div><strong>{{ \Illuminate\Support\Str::before(trim($seller->name).' ', ' ') }}</strong>@if($seller->username)<span class="handle" dir="ltr">{{ '@'.$seller->username }}</span>@endif<small>{{ __('عضو من :date', ['date' => $seller->created_at?->translatedFormat('F Y')]) }}</small></div></div>

        @if($canManage)
            <div class="market-owner-actions">
                <a class="button" href="{{ route('market.edit', $listing) }}">{{ __('تعديل المنتج') }}</a>
                @if($listing->status !== 'removed' && auth()->id() === $listing->user_id)<form method="post" action="{{ route('market.toggle', $listing) }}">@csrf @method('PATCH')<button class="button outline" type="submit">{{ $listing->status === 'active' ? __('إخفاء') : __('إظهار') }}</button></form>@endif
                <form method="post" action="{{ route('market.destroy', $listing) }}" onsubmit="return confirm('{{ __('حذف المنتج نهائياً؟') }}')">@csrf @method('DELETE')<button class="text-link danger" type="submit">{{ __('حذف') }}</button></form>
            </div>
        @elseif($listing->isVisible())
            @auth
                @if(auth()->user()->earnsPoints())
                <form class="panel market-order stack" method="post" action="{{ route('market.order', $listing) }}">@csrf
                    <h2>{{ __('اطلب المنتج') }}</h2>
                    <div class="market-order-row"><label>{{ __('الكمية') }}<input type="number" name="quantity" min="1" max="{{ $listing->quantity }}" value="{{ old('quantity', 1) }}" required data-qty data-price="{{ $listing->price }}"></label><p class="market-total">{{ __('المجموع') }}: <b data-total>{{ $listing->formattedPrice() }}</b></p></div>
                    <label>{{ __('الاسم') }}<input name="buyer_name" maxlength="100" value="{{ old('buyer_name', auth()->user()->name) }}" required autocomplete="name"></label>
                    <label>{{ __('رقم الجوال') }}<input name="buyer_phone" type="tel" dir="ltr" maxlength="30" value="{{ old('buyer_phone', auth()->user()->phone) }}" required autocomplete="tel"></label>
                    <label>{{ __('المدينة') }}<input name="city" maxlength="60" list="marketCities" value="{{ old('city') }}" required></label>
                    <label>{{ __('العنوان') }}<input name="address" maxlength="255" value="{{ old('address') }}" required autocomplete="street-address" placeholder="{{ __('الحي، الشارع، علامة مميزة') }}"></label>
                    <label>{{ __('ملاحظة للبائع (اختياري)') }}<textarea name="note" rows="2" maxlength="500">{{ old('note') }}</textarea></label>
                    <button class="button" type="submit">{{ __('أرسل الطلب للبائع') }}</button>
                    <small>{{ __('الدفع والتسليم بتتفقوا عليهم مع البائع بعد ما يقبل الطلب.') }}</small>
                    <datalist id="marketCities">@foreach(config('market.cities') as $city)<option value="{{ $city }}">@endforeach</datalist>
                </form>
                @else
                <p class="quiet-empty">{{ __('الطلب من المتجر الحر لحسابات العملاء والمروّجين.') }}</p>
                @endif
            @else
                <div class="panel market-order"><h2>{{ __('بدك هالمنتج؟') }}</h2><p>{{ __('سجّل دخول أو اعمل حساب بثواني عشان تطلب.') }}</p><a class="button" href="{{ route('community.login', ['return' => parse_url($listing->url(), PHP_URL_PATH)]) }}">{{ __('دخول / حساب جديد') }}</a></div>
            @endauth
        @endif
    </section>
</div>
@if($more->isNotEmpty())<section><div class="section-heading"><h2>{{ __('منتجات ثانية من نفس البائع') }}</h2></div><div class="market-grid">@foreach($more as $listing)@include('market.card')@endforeach</div></section>@endif
@endsection
@section('scripts')
<script>
document.querySelectorAll('.market-thumbs button').forEach(b=>b.addEventListener('click',()=>{const main=document.getElementById('marketMainImage');if(main)main.src=b.dataset.src;document.querySelectorAll('.market-thumbs button').forEach(x=>x.classList.toggle('is-current',x===b));}));
document.querySelectorAll('[data-qty]').forEach(input=>{const out=input.form.querySelector('[data-total]');const price=Number(input.dataset.price);const fmt=v=>(Math.round(v*100)/100).toString()+' ₪';input.addEventListener('input',()=>{const q=Math.max(1,parseInt(input.value||'1',10));out.textContent=fmt(price*q);});});
</script>
@endsection
