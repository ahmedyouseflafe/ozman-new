<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>تعديل الوكيل - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @include('admin.agent.styles')
</head>
<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'تعديل الوكيل'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>تعديل الوكيل</h1>
                        <p>حدّث بيانات الوكيل ومعلومات التواصل.</p>
                    </div>
                    <a href="{{ route('agents') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للوكلاء</a>
                </header>
                @include('admin.agent.validation')
                <form class="form-shell" action="{{ route('agents.update', $agent) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.agent._form', ['agent' => $agent])
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i>حفظ التعديلات</button>
                        <a href="{{ route('agents') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
