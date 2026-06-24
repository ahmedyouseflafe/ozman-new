<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he'], true) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ozman - اختر نوع الدخول</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            color: #fff;
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            background:
                radial-gradient(circle at 18% 18%, rgba(112, 0, 255, .18), transparent 32%),
                radial-gradient(circle at 78% 14%, rgba(0, 229, 255, .16), transparent 35%),
                #050505;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .entry {
            width: min(960px, 100%);
            display: grid;
            gap: 26px;
        }
        .brand {
            text-align: center;
            display: grid;
            justify-items: center;
            gap: 12px;
        }
        .brand-mark {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            border: 2px solid #00e5ff;
            display: grid;
            place-items: center;
            color: #00e5ff;
            font-size: 30px;
            font-weight: 900;
            box-shadow: 0 0 34px rgba(0, 229, 255, .44);
            background: rgba(0, 0, 0, .5);
        }
        h1 {
            color: #00e5ff;
            font-size: clamp(30px, 5vw, 58px);
            font-weight: 900;
            line-height: 1.1;
            text-shadow: 0 0 24px rgba(0, 229, 255, .42);
        }
        .brand p {
            color: rgba(255, 255, 255, .68);
            font-size: 16px;
            font-weight: 800;
        }
        .choices {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .choice {
            min-height: 260px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 26px;
            padding: 28px;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(145deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, .025));
            box-shadow: 0 24px 80px rgba(0, 0, 0, .34);
            display: grid;
            align-content: space-between;
            gap: 22px;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .choice:hover {
            transform: translateY(-4px);
            border-color: rgba(0, 229, 255, .55);
            box-shadow: 0 28px 90px rgba(0, 0, 0, .42), 0 0 32px rgba(0, 229, 255, .15);
        }
        .choice-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            background: #000;
            border: 1px solid #00e5ff;
            color: #00e5ff;
            font-size: 28px;
        }
        .choice h2 {
            font-size: 26px;
            font-weight: 900;
            margin-bottom: 8px;
        }
        .choice p {
            color: rgba(255, 255, 255, .62);
            font-size: 14px;
            font-weight: 800;
            line-height: 1.8;
        }
        .choice-action {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #001014;
            background: linear-gradient(135deg, #00e5ff, #7000ff);
            border-radius: 999px;
            min-height: 46px;
            padding: 0 18px;
            font-weight: 900;
            width: fit-content;
        }
        .admin-link {
            justify-self: center;
            color: rgba(255, 255, 255, .62);
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
        }
        .admin-link:hover { color: #00e5ff; }
        @media (max-width: 760px) {
            .choices { grid-template-columns: 1fr; }
            .choice { min-height: 220px; padding: 22px; }
        }
    </style>
</head>

<body>
    <main class="entry">
        <section class="brand">
            <div class="brand-mark">O</div>
            <h1>Ozman</h1>
            <p>{{ __('اختر طريقة الدخول المناسبة لك') }}</p>
        </section>

        <section class="choices" aria-label="{{ __('نوع الدخول') }}">
            <a class="choice" href="{{ route('front.home', ['type' => 'customer']) }}">
                <div>
                    <div class="choice-icon"><i class="fas fa-user"></i></div>
                    <h2>{{ __('أنا عميل') }}</h2>
                    <p>{{ __('سجل بياناتك، تصفح المتاجر والمنتجات، واطلب مباشرة من Ozman.') }}</p>
                </div>
                <span class="choice-action">{{ __('تسجيل كعميل') }} <i class="fas fa-arrow-left"></i></span>
            </a>

            <a class="choice" href="{{ route('front.home', ['type' => 'merchant']) }}">
                <div>
                    <div class="choice-icon"><i class="fas fa-store"></i></div>
                    <h2>{{ __('أنا تاجر') }}</h2>
                    <p>{{ __('سجل بيانات متجرك وموقعه ليتم التواصل معك وتجهيز حسابك داخل النظام.') }}</p>
                </div>
                <span class="choice-action">{{ __('تسجيل كتاجر') }} <i class="fas fa-arrow-left"></i></span>
            </a>
        </section>

        <a class="admin-link" href="{{ route('login') }}">{{ __('لدي حساب إدارة بالفعل') }}</a>
    </main>
</body>

</html>
