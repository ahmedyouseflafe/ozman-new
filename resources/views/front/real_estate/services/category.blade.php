@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $logo = $shop->logo ? asset($shop->logo) : asset('ozman-favicon.png');
    $labels = [
        'ar' => ['back' => 'جميع الأقسام', 'title' => 'الخدمات المتاحة', 'open' => 'خصّص طلبك', 'hint' => 'اختر الخدمة ثم حدّد المقاسات والمواد والتجهيزات المناسبة لك.', 'empty' => 'لا توجد خدمات متاحة في هذا القسم حاليًا.'],
        'he' => ['back' => 'כל הקטגוריות', 'title' => 'שירותים זמינים', 'open' => 'התאמת הבקשה', 'hint' => 'בחרו שירות ולאחר מכן התאימו מידות, חומרים ואבזור.', 'empty' => 'אין כרגע שירותים זמינים בקטגוריה זו.'],
        'en' => ['back' => 'All categories', 'title' => 'Available services', 'open' => 'Configure request', 'hint' => 'Choose a service, then configure its sizes, materials and fittings.', 'empty' => 'There are no services in this category yet.'],
    ][$locale] ?? ['back' => 'جميع الأقسام', 'title' => 'الخدمات المتاحة', 'open' => 'خصّص طلبك', 'hint' => 'اختر الخدمة وحدد مواصفاتها.', 'empty' => 'لا توجد خدمات متاحة.'];
@endphp
<!doctype html><html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
@include('front.partials.seo', ['title' => $category->localized('name').' | '.$shop->name, 'description' => $category->localized('description'), 'canonical' => $category->publicUrl(), 'image' => $category->imageUrl() ?: $logo])
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
@viewStyle('front.real_estate.services.category')</head><body>
<header class="header"><nav class="topbar"><a class="brand" href="{{ route('real-estate.company', $shop) }}"><img src="{{ $logo }}" alt="{{ $shop->name }}"><span><b>{{ $shop->name }}</b><small>{{ $category->localized('name') }}</small></span></a>@include('front.partials.public_language_switcher')</nav></header>
<main class="shell"><a class="back" href="{{ route('real-estate.company', $shop) }}">{{ $labels['back'] }}</a><section class="hero"><img src="{{ $category->imageUrl() ?: $logo }}" alt="{{ $category->localized('name') }}"><div class="hero-copy"><span class="hero-label">{{ $shop->name }}</span><h1>{{ $category->localized('name') }}</h1><p>{{ $category->localized('description') }}</p></div></section>
<div class="section-head"><h2>{{ $labels['title'] }}</h2><p>{{ $labels['hint'] }}</p></div><section class="grid">
@forelse($category->services as $service)<a class="service" href="{{ $service->publicUrl() }}"><span class="service-media"><img src="{{ $service->imageUrl() ?: $category->imageUrl() ?: $logo }}" alt="{{ $service->localized('name') }}" loading="lazy"></span><span class="service-copy"><h3>{{ $service->localized('name') }}</h3><p>{{ $service->localized('short_description') }}</p><span class="service-cta">{{ $labels['open'] }}</span></span></a>@empty<div class="empty">{{ $labels['empty'] }}</div>@endforelse
</section></main></body></html>
