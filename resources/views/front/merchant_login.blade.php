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
    <style>
        .merchant-login-body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #020707;
            color: #fff;
            font-family: Cairo, sans-serif
        }

        .merchant-login-card {
            width: min(100%, 480px);
            padding: 30px;
            border: 1px solid rgba(0, 229, 255, .35);
            border-radius: 26px;
            background: linear-gradient(145deg, rgba(0, 229, 255, .09), rgba(14, 9, 28, .94));
            box-shadow: 0 0 40px rgba(0, 229, 255, .14)
        }

        .merchant-login-head {
            text-align: center;
            margin-bottom: 25px
        }

        .merchant-login-icon {
            width: 72px;
            height: 72px;
            display: grid;
            place-items: center;
            margin: 0 auto 15px;
            border: 1px solid #00e5ff;
            border-radius: 50%;
            color: #00e5ff;
            font-size: 28px;
            box-shadow: 0 0 22px rgba(0, 229, 255, .3)
        }

        .merchant-login-head h1 {
            margin: 0 0 8px;
            font-size: 1.6rem
        }

        .merchant-login-head p {
            margin: 0;
            color: rgba(255, 255, 255, .64);
            line-height: 1.8;
            font-size: .9rem
        }

        .merchant-login-field {
            display: grid;
            gap: 8px;
            margin-bottom: 17px;
            font-weight: 800
        }

        .merchant-login-field input {
            width: 100%;
            box-sizing: border-box;
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 14px;
            background: rgba(0, 0, 0, .46);
            color: #fff;
            font: inherit;
            outline: none
        }

        .merchant-login-field input:focus {
            border-color: #00e5ff;
            box-shadow: 0 0 0 3px rgba(0, 229, 255, .1)
        }

        .merchant-login-remember {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 4px 0 20px;
            color: rgba(255, 255, 255, .78)
        }

        .merchant-login-submit {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 14px;
            background: #00e5ff;
            color: #001014;
            font: 900 1rem Cairo, sans-serif;
            cursor: pointer;
            box-shadow: 0 0 20px rgba(0, 229, 255, .3)
        }

        .merchant-login-back {
            display: block;
            margin-top: 18px;
            text-align: center;
            color: #00e5ff;
            text-decoration: none;
            font-weight: 800
        }

        .merchant-register-link {
            display: block;
            margin-top: 14px;
            padding: 13px;
            border: 1px solid rgba(0, 229, 255, .38);
            border-radius: 14px;
            text-align: center;
            color: #00e5ff;
            text-decoration: none;
            font-weight: 900
        }

        .merchant-login-errors {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid rgba(255, 70, 70, .45);
            border-radius: 12px;
            background: rgba(255, 50, 50, .1);
            color: #ff9b9b
        }
    </style>
</head>

<body class="merchant-login-body">
    <main class="merchant-login-card">
        <div class="merchant-login-head">
            <div class="merchant-login-icon"><i class="fas fa-store"></i></div>
            <h1>لوحة تحكم متجرك</h1>
            <p>سجّل دخولك لإدارة متجرك، إضافة المنتجات والعروض، نشر الستوريات ومتابعة الطلبات من مكان واحد.</p>
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
        <a class="merchant-login-back" href="{{ route('home') }}">العودة إلى الموقع</a>
    </main>
</body>

</html>
