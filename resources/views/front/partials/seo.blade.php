@php
    $seoTitle = trim($title ?? config('app.name', 'Ozman'));
    $seoDescription = trim(strip_tags($description ?? 'اكتشف المتاجر والمنتجات والمطاعم المحلية عبر منصة Ozman.'));
    $seoDescription = \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $seoDescription), 160, '');
    $seoCanonical = $canonical ?? url()->current();
    $seoImage = $image ?? asset('images/logo.svg');
    $seoType = $type ?? 'website';
    $seoRobots = $robots ?? 'index, follow, max-image-preview:large';
    $seoLocale = ['ar' => 'ar_AR', 'he' => 'he_IL', 'en' => 'en_US'][app()->getLocale()] ?? 'ar_AR';
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta property="og:site_name" content="Ozman">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:locale" content="{{ $seoLocale }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:secure_url" content="{{ $seoImage }}">
<meta property="og:image:alt" content="{{ $imageAlt ?? $seoTitle }}">
@if(!empty($updatedTime))<meta property="og:updated_time" content="{{ $updatedTime }}">@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
@if(!empty($schema))
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
