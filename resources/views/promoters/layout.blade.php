<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#faf9f6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="same-origin">
    <script>{!! file_get_contents(public_path('promoter-theme.js')) !!}</script>
    <title>{{ $__env->yieldContent('title', 'مروّجات أوزمان') }}</title>
    <meta name="description" content="{{ $__env->yieldContent('description', 'اكتشف المحلات من خلال ستوريات وفيديوهات مروّجات أوزمان.') }}">
    <style>{!! file_get_contents(public_path('promoter-social.css')) !!}</style>
    <style>{!! file_get_contents(public_path('community.css')) !!}</style>
</head>
<body>
<header class="social-header"><div class="social-wrap header-row">
    <a class="social-brand" href="{{ route('promoters.index') }}"><span class="brand-mark">O<span>·</span></span><span>Ozman <b>Creators</b><small>حكايات من محلاتنا</small></span></a>
    <nav aria-label="تنقل المروّجات"><a href="{{ route('promoters.index') }}">اكتشف المروّجات</a><a href="{{ route('home') }}">أوزمان ↗</a><a class="button small" href="{{ route('promoter.dashboard') }}">{{ auth()->user()?->isPromoter() ? 'لوحتي' : 'دخول المروّجة' }}</a><button type="button" class="theme-toggle" id="promoterThemeToggle" aria-label="الوضع الداكن" aria-pressed="false"><span aria-hidden="true">☾</span><span data-theme-label>Dark</span></button></nav>
    @if(auth()->user()?->role === 'customer')<form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="text-link">تسجيل الخروج</button></form>@endif
</div></header>
<main class="social-wrap">
    <div class="profile-actions" style="margin-top:16px">
    @auth
        <a class="button outline small" href="{{ route('community.account') }}">حسابي · المتابعات والإشعارات 🔔</a>
        @if(auth()->user()->isSuperAdmin())<a class="button small" href="{{ route('promoter.accounts') }}">إضافة وإدارة المروّجين</a>@endif
    @else
        <a class="button small" href="{{ route('community.login') }}">دخول / إنشاء حساب عميل</a>
    @endauth
    </div>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert"><strong>راجع البيانات التالية:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<footer class="social-wrap social-footer"><span>اكتشافات قريبة منك.</span><a href="{{ route('home') }}">جزء من أوزمان ↗</a></footer>
@yield('scripts')
</body></html>
