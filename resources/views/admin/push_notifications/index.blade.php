<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعارات التطبيق - Ozman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#061012;color:#fff;font-family:Cairo,sans-serif}.shell{min-height:100vh}.main{margin-right:245px;min-height:100vh}.content{padding:32px;max-width:980px;margin:auto}.card{background:rgba(8,25,28,.92);border:1px solid rgba(0,229,255,.2);border-radius:24px;padding:26px;margin-bottom:22px;box-shadow:0 18px 55px rgba(0,0,0,.25)}h1,h2{margin:0 0 8px}.muted{color:#9fb4b7}.stats{display:flex;gap:12px;margin:22px 0}.stat{padding:12px 18px;border-radius:14px;background:rgba(0,229,255,.08);color:#00e5ff;font-weight:800}.notice,.success,.error{padding:14px 16px;border-radius:14px;margin:16px 0}.notice{background:#33280b;color:#ffd86b}.success{background:#0c3929;color:#7fffc5}.error{background:#42181d;color:#ff9da7}label{display:block;font-weight:800;margin:18px 0 7px}input,textarea{width:100%;padding:14px;border-radius:13px;border:1px solid rgba(255,255,255,.16);background:#071416;color:#fff;font:inherit}textarea{min-height:125px;resize:vertical}button{margin-top:22px;padding:14px 25px;border:0;border-radius:14px;background:#00e5ff;color:#001012;font:800 15px Cairo;cursor:pointer}button:disabled{opacity:.45;cursor:not-allowed}.history{display:grid;gap:12px;margin-top:20px}.history-item{padding:15px;border:1px solid rgba(255,255,255,.1);border-radius:15px;background:rgba(255,255,255,.03)}.history-head{display:flex;justify-content:space-between;gap:12px}.badge{font-size:11px;padding:4px 9px;border-radius:999px}.badge.sent{background:#0c3929;color:#7fffc5}.badge.failed{background:#42181d;color:#ff9da7}.history-item p{margin:8px 0;color:#c8d5d7}.history-meta{font-size:11px;color:#82979a}@media(max-width:900px){.main{margin-right:0}.content{padding:20px 14px 110px}}
    </style>
</head>
<body>
<div class="shell">
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => 'إشعارات التطبيق'])
        <div class="content">
            <section class="card">
                <h1>إرسال إشعار للمستخدمين</h1>
                <p class="muted">سيصل الإشعار إلى جميع مستخدمي تطبيق Ozman المشتركين.</p>
                <div class="stats"><div class="stat"><i class="ti ti-device-mobile"></i> أجهزة مسجلة: {{ $devicesCount }}</div></div>

                @if(session('success'))<div class="success">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
                @unless($firebaseConfigured)
                    <div class="notice"><strong>بقيت خطوة حماية أخيرة:</strong> إضافة مفتاح حساب خدمة Firebase إلى الخادم قبل تفعيل زر الإرسال.</div>
                @endunless

                <form method="POST" action="{{ route('push-notifications.send') }}">
                    @csrf
                    <label for="title">عنوان الإشعار</label>
                    <input id="title" name="title" maxlength="100" required value="{{ old('title') }}" placeholder="مثال: عرض جديد من Ozman">
                    <label for="body">نص الإشعار</label>
                    <textarea id="body" name="body" maxlength="500" required placeholder="اكتب الرسالة التي ستظهر على الهاتف">{{ old('body') }}</textarea>
                    <label for="url">الرابط عند الضغط (اختياري)</label>
                    <input id="url" name="url" type="url" value="{{ old('url', 'https://ozman.online/') }}" placeholder="https://ozman.online/">
                    <button type="submit" @disabled(! $firebaseConfigured)><i class="ti ti-send"></i> إرسال الإشعار</button>
                </form>
            </section>
            <section class="card">
                <h2>سجل الإشعارات</h2>
                <p class="muted">آخر 30 إشعارًا تم إرسالها من لوحة الإدارة.</p>
                <div class="history">
                    @forelse($notifications as $notification)
                        <article class="history-item">
                            <div class="history-head">
                                <strong>{{ $notification->title }}</strong>
                                <span class="badge {{ $notification->status }}">{{ $notification->status === 'sent' ? 'تم الإرسال' : 'فشل' }}</span>
                            </div>
                            <p>{{ $notification->body }}</p>
                            <div class="history-meta">
                                {{ $notification->sender?->name ?? 'مدير النظام' }} —
                                {{ ($notification->sent_at ?? $notification->created_at)->format('Y-m-d H:i') }}
                            </div>
                        </article>
                    @empty
                        <div class="muted">لا توجد إشعارات مسجلة بعد.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
