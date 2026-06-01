<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>تعديل الموزع - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @include('admin.distributors.styles')
</head>
<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'تعديل الموزع'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>تعديل الموزع</h1>
                        <p>حدث بيانات الموزع ومعلومات التواصل والموقع.</p>
                    </div>
                    <a href="{{ route('distributors') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للموزعين</a>
                </header>
                @include('admin.distributors.validation')
                <form class="form-shell" action="{{ route('distributors.update', $distributor) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.distributors._form', ['distributor' => $distributor])
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i>حفظ التعديلات</button>
                        <a href="{{ route('distributors') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
