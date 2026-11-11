<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title') · أوزمان</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #05070a; --card: #10151a; --line: rgba(150,174,190,.18); --cyan: #08def4; --muted: #93aab7 }
        * { box-sizing: border-box }
        body { margin: 0; background: radial-gradient(circle at 80% 0, rgba(8,222,244,.10), transparent 40%), var(--bg); color: #eef4f8; font: 16px/1.9 Cairo, Arial, sans-serif }
        main { max-width: 760px; margin: 0 auto; padding: 36px 18px 60px }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 22px; padding: 26px }
        .eyebrow { color: var(--cyan); font-weight: 900; font-size: 13px }
        h1 { margin: 4px 0 6px; font-size: clamp(26px, 5vw, 34px); line-height: 1.3 }
        h2 { margin: 26px 0 6px; font-size: 19px; color: var(--cyan) }
        p, li { color: #d5e1e8 }
        .muted { color: var(--muted); font-size: 14px }
        a { color: var(--cyan) }
        .btn { display: inline-block; margin-top: 10px; padding: 12px 20px; border-radius: 16px; background: linear-gradient(90deg, #08def4, #1fa8ee); color: #02161a; font-weight: 900; text-decoration: none }
        ol li { margin-bottom: 6px }
    </style>
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
