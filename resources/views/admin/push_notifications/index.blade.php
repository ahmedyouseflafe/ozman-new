<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعارات التطبيق - Ozman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.52.0/dist/tabler-icons.min.css">
    @viewStyle('admin.push_notifications.index')
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
