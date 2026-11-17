<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('ربط الحساب') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0b0f1a; color: #eef2ff; font-family: system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; }
        .card { max-width: 360px; margin: 16px; padding: 28px 22px; text-align: center; border-radius: 22px; background: #141a2b; border: 1px solid #2a3350; }
        .icon { font-size: 52px; }
        h1 { font-size: 20px; margin: 12px 0 8px; }
        p { color: #9aa6c4; line-height: 1.7; margin: 0; }
    </style>
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
