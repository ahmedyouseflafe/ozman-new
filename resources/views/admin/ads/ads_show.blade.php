<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $ad->title }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('admin.ads.styles')
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض الإعلان'])

            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $ad->title }}</h1>
                        <p>{{ $ad->description ?: 'لا يوجد وصف لهذا الإعلان.' }}</p>
                    </div>
                    <a href="{{ route('ads') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للإعلانات</a>
                </header>

                <section class="panel" style="padding:25px">
                    @if($ad->type === 'image')
                        <img src="{{ asset($ad->media) }}" class="preview" alt="{{ $ad->title }}">
                    @elseif($ad->type === 'video')
                        <video src="{{ asset($ad->media) }}" class="preview" controls></video>
                    @else
                        <div class="detail-box">
                            <span class="label">رابط يوتيوب</span>
                            <a href="{{ $ad->media }}" target="_blank" class="value">{{ $ad->media }}</a>
                        </div>
                    @endif

                    <div class="detail-grid">
                        <div class="detail-box">
                            <span class="label">المتجر</span>
                            <span class="value">{{ $ad->shop?->name ?? 'عام' }}</span>
                        </div>
                        <div class="detail-box">
                            <span class="label">النوع</span>
                            <span class="tag tag-c">{{ ['image' => 'صورة', 'video' => 'فيديو', 'youtube' => 'يوتيوب'][$ad->type] ?? $ad->type }}</span>
                        </div>
                        <div class="detail-box">
                            <span class="label">المدة</span>
                            <span class="value">{{ $ad->duration }} ثانية</span>
                        </div>
                        <div class="detail-box">
                            <span class="label">الترتيب</span>
                            <span class="value">{{ $ad->sort_order }}</span>
                        </div>
                        <div class="detail-box">
                            <span class="label">الحالة</span>
                            <span class="tag {{ $ad->is_active ? 'tag-g' : 'tag-r' }}">{{ $ad->is_active ? 'نشط' : 'غير نشط' }}</span>
                        </div>
                        <div class="detail-box">
                            <span class="label">تاريخ الإضافة</span>
                            <span class="value">{{ optional($ad->created_at)->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>

                    <div class="actions">
                        <a href="{{ route('ads.edit', $ad) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل الإعلان</a>
                        <a href="{{ route('ads') }}" class="btn">رجوع</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>
