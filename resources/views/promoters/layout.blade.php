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
    <style>{!! file_get_contents(public_path('community-shell.css')) !!}</style>
</head>
<body>
@php
    $me = auth()->user();
    $inMarket = request()->routeIs('market.*');
    $isCreator = $me?->isPromoter() && $me->promoterProfile && ! $me->promoterProfile->isPending();
    $pendingSales = $me?->earnsPoints() ? \App\Models\MarketOrder::where('seller_id', $me->id)->where('status', 'pending')->count() : 0;
    $pendingPromoters = $me?->isSuperAdmin() ? \App\Models\PromoterProfile::pending()->count() : 0;
    $alerts = $pendingSales + $pendingPromoters;
    $loginUrl = route('community.login', ['return' => '/'.ltrim(request()->path(), '/')]);
@endphp
{{-- One app for the community: Creators and the Free Market, a single-row header and a bottom bar on phones. --}}
<header class="app-header"><div class="social-wrap app-header-row">
    <a class="social-brand app-brand" href="{{ route('promoters.index') }}"><span class="brand-mark">O<span>·</span></span><span>Ozman<small>{{ $inMarket ? __('المتجر الحر') : __('المروّجين') }}</small></span></a>
    <div class="app-tabs" role="navigation" aria-label="{{ __('أقسام المجتمع') }}">
        <a href="{{ route('promoters.index') }}" @class(['is-current' => ! $inMarket]) @if(! $inMarket) aria-current="page" @endif>★ {{ __('المروّجين') }}</a>
        <a href="{{ route('market.index') }}" @class(['is-current' => $inMarket]) @if($inMarket) aria-current="page" @endif>🛍 {{ __('المتجر الحر') }}</a>
    </div>
    <div class="app-actions">
        <button type="button" class="theme-toggle" id="promoterThemeToggle" aria-label="الوضع الداكن" aria-pressed="false"><span aria-hidden="true">☾</span><span data-theme-label class="visually-hidden">Dark</span></button>
        @auth
            <details class="account-menu">
                <summary class="account-button" title="{{ __('حسابي') }}">@if($me->earnsPoints())@include('promoters.customer-avatar', ['user' => $me, 'class' => 'is-small'])@else<span class="customer-avatar is-small" aria-hidden="true">{{ $me->avatarInitial() }}</span>@endif<span class="account-name">{{ __('حسابي') }}</span>@if($me->earnsPoints())<span class="account-points" title="{{ __('رصيد النقاط') }}">⭐ {{ number_format(app(\App\Services\LoyaltyPoints::class)->balance($me)) }}</span>@endif @if($alerts)<span class="pending-count" title="{{ __('بانتظارك') }}">{{ $alerts }}</span>@endif</summary>
                <div class="account-menu-panel">
                    <p class="account-menu-head"><b>{{ $me->name }}</b>@if($me->username)<span dir="ltr">{{ '@'.$me->username }}</span>@endif</p>
                    @if($me->earnsPoints())<a href="{{ route('community.account') }}">👤 {{ __('حسابي ونقاطي') }}</a>@endif
                    @if($me->isPromoter())@if($isCreator)<a href="{{ route('promoter.dashboard') }}">🎬 {{ __('لوحتي كمروّج') }}</a>@else<span class="account-menu-note">⏳ {{ __('طلبك كمروّج قيد المراجعة') }}</span>@endif @endif
                    @if($me->earnsPoints())
                        <hr><span class="account-menu-label">{{ __('المتجر الحر') }}</span>
                        <a href="{{ route('market.create') }}">＋ {{ __('بيع منتج') }}</a>
                        <a href="{{ route('market.mine') }}">🏷 {{ __('منتجاتي') }}</a>
                        <a href="{{ route('market.sales') }}">📥 {{ __('طلبات وصلتني') }}@if($pendingSales) <span class="pending-count">{{ $pendingSales }}</span>@endif</a>
                        <a href="{{ route('market.purchases') }}">📦 {{ __('طلباتي') }}</a>
                    @endif
                    @if($me->isSuperAdmin())
                        <hr><span class="account-menu-label">{{ __('الإدارة') }}</span>
                        <a href="{{ route('promoter.accounts') }}">{{ __('إدارة المروّجين') }}@if($pendingPromoters) <span class="pending-count" title="{{ __('طلبات بانتظار الموافقة') }}">{{ $pendingPromoters }}</span>@endif</a>
                        <a href="{{ route('market.admin') }}">{{ __('إدارة المتجر') }}</a>
                        <a href="{{ route('dashboard') }}">{{ __('لوحة التحكم') }}</a>
                    @endif
                    <hr><a href="{{ route('home') }}">{{ __('أوزمان ↗') }}</a>
                    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">{{ __('تسجيل الخروج') }}</button></form>
                </div>
            </details>
        @else
            <a class="button small" href="{{ route('community.login') }}">{{ __('دخول / حساب جديد') }}</a>
        @endauth
    </div>
</div></header>
<main class="social-wrap">
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert"><strong>راجع البيانات التالية:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<footer class="social-wrap social-footer"><span>اكتشافات قريبة منك.</span><a href="{{ route('home') }}">جزء من أوزمان ↗</a></footer>
<div class="app-bottom-bar" role="navigation" aria-label="{{ __('التنقل') }}">
    <a href="{{ route('promoters.index') }}" @class(['is-current' => request()->routeIs('promoters.*', 'promoter.manage')])><span aria-hidden="true">★</span>{{ __('المروّجين') }}</a>
    <a href="{{ route('market.index') }}" @class(['is-current' => request()->routeIs('market.index', 'market.show')])><span aria-hidden="true">🛍</span>{{ __('المتجر') }}</a>
    @if($me && ! $me->earnsPoints())
        {{-- Admins and staff: management shortcuts instead of buying and selling. --}}
        <a class="app-bottom-add" href="{{ route('dashboard') }}" aria-label="{{ __('لوحة التحكم') }}"><span aria-hidden="true">⚙</span></a>
        @if($me->isSuperAdmin())
            <a href="{{ route('market.admin') }}" @class(['is-current' => request()->routeIs('market.admin')])><span aria-hidden="true">🏪</span>{{ __('إدارة المتجر') }}</a>
            <a href="{{ route('promoter.accounts') }}" @class(['is-current' => request()->routeIs('promoter.accounts', 'promoter.gifts.*')])><span aria-hidden="true">👥</span>{{ __('المروّجين') }}@if($pendingPromoters)<i class="pending-dot"></i>@endif</a>
        @else
            <span></span><span></span>
        @endif
    @else
        <a class="app-bottom-add" href="{{ $isCreator && ! $inMarket ? route('promoter.dashboard').'#storyComposer' : ($me ? route('market.create') : route('community.login', ['return' => '/free-market/new'])) }}" aria-label="{{ $isCreator && ! $inMarket ? __('انشر حكاية') : __('بيع منتج') }}"><span aria-hidden="true">＋</span></a>
        <a href="{{ $me ? route('market.purchases') : $loginUrl }}" @class(['is-current' => request()->routeIs('market.purchases', 'market.sales', 'market.mine')])><span aria-hidden="true">📦</span>{{ __('طلباتي') }}@if($pendingSales)<i class="pending-dot" aria-label="{{ __('طلبات جديدة') }}"></i>@endif</a>
        <a href="{{ $me ? route('community.account') : route('community.login') }}" @class(['is-current' => request()->routeIs('community.*')])><span aria-hidden="true">👤</span>{{ $me ? __('حسابي') : __('دخول') }}</a>
    @endif
</div>
@yield('scripts')
</body></html>
