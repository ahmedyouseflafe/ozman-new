<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>دخول مندوب التوصيل | Ozman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body { min-height:100vh; margin:0; display:grid; place-items:center; padding:22px; background:#03080a; color:#fff; font-family:Arial,sans-serif; }
        .card { width:min(470px,100%); padding:30px; border:1px solid #087786; border-radius:28px; background:#07151b; box-shadow:0 25px 90px #000; }
        .icon { width:76px; height:76px; margin:0 auto 18px; display:grid; place-items:center; border:1px solid #08def4; border-radius:24px; color:#08def4; font-size:37px; }
        h1 { text-align:center; margin:0; font-size:28px; }
        p { text-align:center; color:#98a8b3; line-height:1.8; margin:8px 0 25px; }
        .field { display:grid; gap:8px; margin-bottom:16px; font-weight:800; }
        .field input { width:100%; min-height:52px; border:1px solid #34434a; border-radius:15px; background:#02070a; color:#fff; padding:0 15px; font:inherit; outline:0; }
        .field input:focus { border-color:#08def4; box-shadow:0 0 0 3px rgba(8,222,244,.1); }
        button,.back { width:100%; min-height:52px; border:0; border-radius:15px; background:#08def4; color:#001014; font-size:16px; font-weight:900; cursor:pointer; }
        .back { display:grid; place-items:center; margin-top:13px; background:transparent; border:1px solid #087786; color:#08def4; text-decoration:none; }
        .errors { padding:13px; margin-bottom:17px; border:1px solid #873945; border-radius:14px; background:#2a1117; color:#ffabb5; }
    </style>
</head>
<body>
<main class="card">
    <div class="icon"><i class="ti ti-motorbike"></i></div>
    <h1>دخول مندوب التوصيل</h1>
    <p>استخدم البريد وكلمة المرور اللذين أنشأهما لك المطعم لتستلم طلبات التوصيل وتحدّث حالتها.</p>
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('driver.login.store') }}">
        @csrf
        <label class="field"><span>البريد الإلكتروني</span><input type="email" name="email" value="{{ old('email') }}" dir="ltr" autocomplete="username" required autofocus></label>
        <label class="field"><span>كلمة المرور</span><input type="password" name="password" dir="ltr" autocomplete="current-password" required></label>
        <button type="submit"><i class="ti ti-login"></i> الدخول إلى طلبات التوصيل</button>
    </form>
    <a class="back" href="{{ route('merchant.login') }}">العودة إلى دخول صاحب المتجر</a>
</main>
</body>
</html>
