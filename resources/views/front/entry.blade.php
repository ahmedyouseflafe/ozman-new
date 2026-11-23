<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he'], true) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ozman - اختر نوع الدخول</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;800;900&display=swap" rel="stylesheet">
    @viewStyle('front.entry')
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
