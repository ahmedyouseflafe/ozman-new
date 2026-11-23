<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title') · أوزمان</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    @viewStyle('front.policies._layout')
</head>
<body>
<main>
    <div class="card">
        @yield('content')
    </div>
    <p class="muted" style="text-align:center;margin-top:18px"><a href="{{ url('/') }}">ozman.online</a> · <a href="{{ route('policies.privacy') }}">سياسة الخصوصية</a> · <a href="{{ route('policies.delete-account') }}">حذف الحساب</a></p>
</main>
</body>
</html>
