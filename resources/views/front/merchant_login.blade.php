<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>الدخول إلى لوحة تحكم المتجر - Ozman</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    @viewStyle('front.merchant_login')
</head>

<body class="merchant-login-body">
    <main class="merchant-login-card">
        <div class="merchant-login-head">
            <div class="merchant-login-icon"><i class="fas fa-store"></i></div>
            <h1>لوحة تحكم متجرك</h1>
            <p>سجّل دخولك للوصول إلى اللوحة المناسبة لنوع متجرك وإدارة المحتوى والخدمات وطلبات العملاء من مكان واحد.</p>
        </div>

        @if ($errors->any())
            <div class="merchant-login-errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('merchant.login.store') }}">
            @csrf
            <input type="hidden" name="redirect" value="{{ old('redirect', $redirectTo) }}">
            <label class="merchant-login-field">
                <span>البريد الإلكتروني</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" dir="ltr"
                    required autofocus>
            </label>
            <label class="merchant-login-field">
                <span>كلمة المرور</span>
                <input type="password" name="password" autocomplete="current-password" dir="ltr" required>
            </label>
            <div class="merchant-login-remember">
                <i class="fas fa-shield-halved"></i>
                <span>سيتم حفظ تسجيل الدخول لتصل إلى لوحة متجرك بسرعة</span>
            </div>
            <button class="merchant-login-submit" type="submit">
                <i class="fas fa-right-to-bracket"></i>
                الدخول إلى لوحة التحكم
            </button>
        </form>
        @if ($canRegister)
            <a class="merchant-register-link" href="{{ route('merchant.register', ['redirect' => $redirectTo]) }}">
                <i class="fas fa-user-plus"></i>
                ليس لدي حساب — إنشاء متجر جديد
            </a>
        @endif
        <a class="merchant-register-link" href="{{ route('driver.login') }}">
            <i class="fas fa-motorcycle"></i>
            دخول مندوب توصيل المطعم
        </a>
        <a class="merchant-login-back" href="{{ route('home') }}">العودة إلى الموقع</a>
    </main>
</body>

</html>
