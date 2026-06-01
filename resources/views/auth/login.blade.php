<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>تسجيل الدخول - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 20% 20%, rgba(0, 229, 255, .16), transparent 30%),
                radial-gradient(circle at 80% 10%, rgba(112, 0, 255, .18), transparent 34%),
                linear-gradient(180deg, #030303 0%, #07020d 100%);
            color: #fff;
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        }
        .login-card {
            width: min(440px, 100%);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 26px;
            padding: 30px;
            background: rgba(0, 0, 0, .62);
            box-shadow: 0 24px 80px rgba(0, 0, 0, .48), 0 0 34px rgba(0, 229, 255, .08);
            backdrop-filter: blur(18px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 26px;
        }
        .brand-mark {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            border: 2px solid #00e5ff;
            color: #00e5ff;
            font-weight: 900;
            font-size: 22px;
            box-shadow: 0 0 24px rgba(0, 229, 255, .45);
        }
        h1 {
            color: #00e5ff;
            font-size: 24px;
            font-weight: 900;
            line-height: 1.2;
        }
        .subtitle {
            color: rgba(255, 255, 255, .55);
            font-size: 13px;
            font-weight: 700;
            margin-top: 3px;
        }
        .field { margin-bottom: 15px; }
        label {
            display: block;
            color: rgba(255, 255, 255, .75);
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 8px;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #00e5ff;
            font-size: 17px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            height: 48px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 16px;
            background: rgba(255, 255, 255, .06);
            color: #fff;
            padding: 0 14px 0 42px;
            outline: none;
            direction: ltr;
            text-align: left;
            font-family: inherit;
            font-weight: 700;
        }
        input:focus {
            border-color: #00e5ff;
            box-shadow: 0 0 18px rgba(0, 229, 255, .24);
        }
        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 4px 0 20px;
            color: rgba(255, 255, 255, .65);
            font-size: 12px;
            font-weight: 800;
        }
        .check {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        button {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #00e5ff, #7000ff);
            color: #001014;
            font-family: inherit;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 0 26px rgba(0, 229, 255, .36);
        }
        .error {
            border: 1px solid rgba(255, 59, 48, .35);
            background: rgba(255, 59, 48, .09);
            color: #fff;
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <main class="login-card">
        <div class="brand">
            <div class="brand-mark">O</div>
            <div>
                <h1>تسجيل الدخول</h1>
                <div class="subtitle">ادخل للوحة تحكم Ozman حسب صلاحيات حسابك</div>
            </div>
        </div>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <div class="input-wrap">
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                    <i class="ti ti-mail" aria-hidden="true"></i>
                </div>
            </div>

            <div class="field">
                <label for="password">كلمة المرور</label>
                <div class="input-wrap">
                    <input id="password" type="password" name="password" required>
                    <i class="ti ti-lock" aria-hidden="true"></i>
                </div>
            </div>

            <div class="row">
                <label class="check" for="remember">
                    <input id="remember" type="checkbox" name="remember" value="1">
                    تذكرني
                </label>
            </div>

            <button type="submit">دخول</button>
        </form>
    </main>
</body>

</html>
