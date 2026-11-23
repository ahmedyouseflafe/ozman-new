<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('ربط الحساب') }}</title>
    @viewStyle('front.social_connected')
</head>
<body>
    <div class="card">
        @if ($failed)
            <div class="icon">⚠️</div>
            <h1>{{ __('الربط ما كمل') }}</h1>
            <p>{{ $error }}</p>
            <p>{{ __('ارجع للتطبيق وجرّب مرة ثانية.') }}</p>
        @else
            <div class="icon">🎉</div>
            <h1>{{ __('تم ربط الحساب') }}</h1>
            <p>{{ __('سكّر هالصفحة وارجع لتطبيق أوزمان، الحساب رح يظهر عندك.') }}</p>
        @endif
    </div>
</body>
</html>
