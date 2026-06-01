<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>إضافة شاشة رئيسية - Ozman</title>
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
            @include('admin.includes.header', ['title' => 'إضافة شاشة رئيسية'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>إضافة شاشة رئيسية</h1>
                        <p>أضف صورة أو فيديو أو رابط يوتيوب للشاشة الرئيسية العامة.</p>
                    </div>
                    <a href="{{ route('screens') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للشاشات</a>
                </header>
                @include('admin.screens.validation')
                <form class="form-shell" action="{{ route('screens.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('admin.screens._form')
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i>حفظ الشاشة</button>
                        <a href="{{ route('screens') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
