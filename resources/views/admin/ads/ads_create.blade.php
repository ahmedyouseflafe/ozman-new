<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إضافة إعلان جديد - Ozman</title>
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
            @include('admin.includes.header', ['title' => 'إضافة إعلان جديد'])

            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>إضافة إعلان جديد</h1>
                        <p>أنشئ إعلان صورة أو فيديو أو يوتيوب واربطه بمتجر عند الحاجة.</p>
                    </div>
                    <a href="{{ route('ads') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للإعلانات</a>
                </header>

                @include('admin.ads.validation')

                <form class="form-shell" action="{{ route('ads.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('admin.ads._form')
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i>حفظ الإعلان</button>
                        <a href="{{ route('ads') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>
