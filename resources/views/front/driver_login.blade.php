<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('دخول مندوب التوصيل | Ozman') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.driver_login')
</head>
<body>
<main class="card">
    <div class="icon"><i class="ti ti-motorbike"></i></div>
    <h1>{{ __('دخول مندوب التوصيل') }}</h1>
    <p>{{ __('استخدم البريد وكلمة المرور اللذين أنشأهما لك المطعم لتستلم طلبات التوصيل وتحدّث حالتها.') }}</p>
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('driver.login.store') }}">
        @csrf
        <label class="field"><span>{{ __('البريد الإلكتروني') }}</span><input type="email" name="email" value="{{ old('email') }}" dir="ltr" autocomplete="username" required autofocus></label>
        <label class="field"><span>{{ __('كلمة المرور') }}</span><input type="password" name="password" dir="ltr" autocomplete="current-password" required></label>
        <button type="submit"><i class="ti ti-login"></i> {{ __('الدخول إلى طلبات التوصيل') }}</button>
    </form>
    <a class="back" href="{{ route('merchant.login') }}">{{ __('العودة إلى دخول صاحب المتجر') }}</a>
</main>
</body>
</html>
