<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ __('تسجيل الدخول - Ozman') }}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('auth.login')
</head>

<body>
    <main class="login-card">
        <div class="brand">
            <div class="brand-mark">O</div>
            <div>
                <h1>{{ __('تسجيل الدخول') }}</h1>
                <div class="subtitle">{{ __('ادخل للوحة تحكم Ozman حسب صلاحيات حسابك') }}</div>
            </div>
        </div>

        @if(session('status'))
            <div class="error" style="color:#7dffb0;border-color:rgba(39,221,134,.35);background:rgba(39,221,134,.1)">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="field">
                <label for="email">{{ __('البريد الإلكتروني') }}</label>
                <div class="input-wrap">
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                    <i class="ti ti-mail" aria-hidden="true"></i>
                </div>
            </div>

            <div class="field">
                <label for="password">{{ __('كلمة المرور') }}</label>
                <div class="input-wrap">
                    <input id="password" type="password" name="password" required>
                    <i class="ti ti-lock" aria-hidden="true"></i>
                </div>
            </div>

            <div class="row">
                <label class="check" for="remember">
                    <input id="remember" type="checkbox" name="remember" value="1">
                    {{ __('تذكرني') }}
                </label>
                <a href="{{ route('community.password.request') }}" style="color:inherit;font-weight:700">{{ __('نسيت كلمة المرور؟') }}</a>
            </div>

            <button type="submit">{{ __('دخول') }}</button>
        </form>
    </main>
</body>

</html>
