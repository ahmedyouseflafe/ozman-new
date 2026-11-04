<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#faf9f6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="same-origin">
    <script>{!! file_get_contents(public_path('promoter-theme.js')) !!}</script>
    <title>{{ $__env->yieldContent('title', 'مروّجين أوزمان') }}</title>
    <meta name="description" content="{{ $__env->yieldContent('description', 'اكتشف المحلات من خلال ستوريات وفيديوهات مروّجين أوزمان.') }}">
    <style>{!! file_get_contents(public_path('promoter-social.css')) !!}</style>
    <style>{!! file_get_contents(public_path('community.css')) !!}</style>
</head>
<body>
<header class="social-header"><div class="social-wrap header-row">
    <a class="social-brand" href="{{ route('promoters.index') }}"><span class="brand-mark">O<span>·</span></span><span>Ozman <b>Creators</b><small>حكايات من محلاتنا</small></span></a>
    {{-- One header row: discovery, the visitor's own account, then role-specific links. --}}
    <nav aria-label="تنقل المروّجين">
        <a href="{{ route('promoters.index') }}">اكتشف المروّجين</a><a href="{{ route('home') }}">أوزمان ↗</a>
        @auth
            <a class="button outline small account-button" href="{{ route('community.account') }}" title="{{ __('حسابي · المتابعات والإشعارات') }}">@if(auth()->user()->isCustomer())@include('promoters.customer-avatar', ['user' => auth()->user(), 'class' => 'is-small'])@endif<span>{{ __('حسابي') }} 🔔</span>@if(auth()->user()->earnsPoints())<span class="account-points" title="{{ __('رصيد النقاط') }}">⭐ {{ number_format(app(\App\Services\LoyaltyPoints::class)->balance(auth()->user())) }}</span>@endif</a>
            @if(auth()->user()->isPromoter())<a class="button small" href="{{ route('promoter.dashboard') }}">{{ __('لوحتي') }}</a>@endif
            @if(auth()->user()->isSuperAdmin())<a class="button small" href="{{ route('promoter.accounts') }}">{{ __('إدارة المروّجين') }}</a>@endif
        @else
            <a class="button small" href="{{ route('community.login') }}">{{ __('دخول / حساب جديد') }}</a>
            <a class="text-link creator-login" href="{{ route('community.login', ['return' => '/promoter']) }}">{{ __('دخول المروّج') }}</a>
        @endauth
        <button type="button" class="theme-toggle" id="promoterThemeToggle" aria-label="الوضع الداكن" aria-pressed="false"><span aria-hidden="true">☾</span><span data-theme-label>Dark</span></button>
        @if(auth()->user()?->role === 'customer')<form method="post" action="{{ route('logout') }}" class="header-logout">@csrf<button type="submit" class="text-link">{{ __('خروج') }}</button></form>@endif
    </nav>
</div></header>
<main class="social-wrap">
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert"><strong>راجع البيانات التالية:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<footer class="social-wrap social-footer"><span>اكتشافات قريبة منك.</span><a href="{{ route('home') }}">جزء من أوزمان ↗</a></footer>
@yield('scripts')
</body></html>
