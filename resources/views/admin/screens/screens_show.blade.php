<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>{{ $screen->title }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('admin.screens.styles')
</head>
<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض الشاشة'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $screen->title }}</h1>
                        <p>تفاصيل محتوى الشاشة الرئيسية.</p>
                    </div>
                    <a href="{{ route('screens') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للشاشات</a>
                </header>

                <section class="panel" style="padding:25px">
                    @if($screen->type === 'image')
                        <img src="{{ asset($screen->media) }}" class="preview" alt="{{ $screen->title }}">
                    @elseif($screen->type === 'video')
                        <video src="{{ asset($screen->media) }}" class="preview" controls></video>
                    @else
                        <div class="detail-box">
                            <span class="label">رابط يوتيوب</span>
                            <a href="{{ $screen->media }}" target="_blank" class="value">{{ $screen->media }}</a>
                        </div>
                    @endif

                    <div class="detail-grid">
                        <div class="detail-box"><span class="label">النوع</span><span class="value">{{ ['image' => 'صورة', 'video' => 'فيديو', 'youtube' => 'يوتيوب'][$screen->type] ?? $screen->type }}</span></div>
                        <div class="detail-box"><span class="label">مكان العرض</span><span class="value">{{ ['top' => 'الشاشة العلوية', 'bottom' => 'الشاشة السفلية'][$screen->placement ?? 'top'] ?? 'الشاشة العلوية' }}</span></div>
                        <div class="detail-box"><span class="label">المدة</span><span class="value">{{ $screen->duration }} ثانية</span></div>
                        <div class="detail-box"><span class="label">الحالة</span><span class="value">{{ $screen->is_active ? 'نشط' : 'معطل' }}</span></div>
                        <div class="detail-box"><span class="label">تاريخ الإضافة</span><span class="value">{{ optional($screen->created_at)->format('Y-m-d H:i') }}</span></div>
                    </div>

                    <div class="actions">
                        <a href="{{ route('screens.edit', $screen) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل الشاشة</a>
                        <a href="{{ route('screens') }}" class="btn">رجوع</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
