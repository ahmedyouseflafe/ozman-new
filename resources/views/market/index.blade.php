@extends('promoters.layout')
@section('title', __('المتجر الحر | أوزمان'))
@section('description', __('منتجات جديدة ومستعملة من ناس أوزمان. اطلب مباشرة من الموقع.'))
@section('content')
<style>{!! file_get_contents(public_path('market.css')) !!}</style>
<section class="market-hero">
    <span class="eyebrow">OZMAN FREE MARKET</span>
    <h1>{{ __('المتجر الحر') }}</h1>
    <p>{{ __('أي حد عنده حساب بيقدر يبيع هون. منتجات جديدة ومستعملة من ناس قريبين منك، والطلب من الموقع مباشرة.') }}</p>
</section>
@include('market.nav')
<form class="market-filters" method="get" action="{{ route('market.index') }}" role="search">
    <label class="market-search"><span class="visually-hidden">{{ __('بحث') }}</span><input type="search" name="q" value="{{ $filters['q'] }}" maxlength="80" placeholder="{{ __('شو بتدوّر؟') }}"></label>
    <label><span class="visually-hidden">{{ __('القسم') }}</span><select name="category"><option value="">{{ __('كل الأقسام') }}</option>@foreach(config('market.categories') as $key => $label)<option value="{{ $key }}" @selected($filters['category'] === $key)>{{ __($label) }}</option>@endforeach</select></label>
    <label><span class="visually-hidden">{{ __('الحالة') }}</span><select name="condition"><option value="">{{ __('جديد ومستعمل') }}</option>@foreach(config('market.conditions') as $key => $label)<option value="{{ $key }}" @selected($filters['condition'] === $key)>{{ __($label) }}</option>@endforeach</select></label>
    <label><span class="visually-hidden">{{ __('المدينة') }}</span><input name="city" value="{{ $filters['city'] }}" list="marketCities" maxlength="60" placeholder="{{ __('المدينة') }}"></label>
    <label><span class="visually-hidden">{{ __('الترتيب') }}</span><select name="sort"><option value="latest">{{ __('الأحدث') }}</option><option value="price_asc" @selected($filters['sort'] === 'price_asc')>{{ __('الأرخص') }}</option><option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('الأغلى') }}</option></select></label>
    <button class="button" type="submit">{{ __('بحث') }}</button>
    <datalist id="marketCities">@foreach(config('market.cities') as $city)<option value="{{ $city }}">@endforeach</datalist>
</form>
<div class="market-chips">@foreach(config('market.categories') as $key => $label)<a href="{{ route('market.index', array_filter([...$filters, 'category' => $filters['category'] === $key ? null : $key, 'sort' => $filters['sort'] === 'latest' ? null : $filters['sort']])) }}" @class(['is-current' => $filters['category'] === $key])>{{ __($label) }}</a>@endforeach</div>
<div class="market-grid">
    @forelse($listings as $listing)@include('market.card')
    @empty<div class="empty-state"><span class="empty-icon">🛍</span><h3>{{ __('ما في منتجات هون لهلأ') }}</h3><p>{{ __('كون أول واحد بيعرض منتج بالمتجر الحر.') }}</p><a class="button" href="{{ auth()->check() ? route('market.create') : route('community.login', ['return' => '/free-market/new']) }}">{{ __('+ بيع منتج') }}</a></div>@endforelse
</div>
@include('promoters.pagination', ['items' => $listings])
@endsection
