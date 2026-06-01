<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>{{ $distributor->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('admin.distributors.styles')
</head>
<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض الموزع'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $distributor->name }}</h1>
                        <p>تفاصيل الموزع ومعلومات التواصل والموقع.</p>
                    </div>
                    <a href="{{ route('distributors') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للموزعين</a>
                </header>

                <section class="panel" style="padding:25px">
                    @if($distributor->image)
                        <img src="{{ asset($distributor->image) }}" class="avatar" alt="{{ $distributor->name }}">
                    @else
                        <div class="avatar"><i class="ti ti-truck-delivery"></i></div>
                    @endif

                    <span class="tag {{ $distributor->is_active ? 'tag-g' : 'tag-r' }}">{{ $distributor->is_active ? 'نشط' : 'غير نشط' }}</span>

                    <div class="detail-grid" style="margin-top:18px">
                        <div class="detail-box"><span class="label">المتجر</span><span class="value">{{ $distributor->shop?->name ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">الهاتف</span><span class="value" dir="ltr">{{ $distributor->phone ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">واتساب</span><span class="value" dir="ltr">{{ $distributor->whatsapp ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">البريد</span><span class="value">{{ $distributor->email ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">العنوان</span><span class="value">{{ $distributor->address ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">الإحداثيات</span><span class="value">{{ $distributor->latitude ?? '-' }}, {{ $distributor->longitude ?? '-' }}</span></div>
                    </div>

                    <div class="actions">
                        <a href="{{ route('distributors.edit', $distributor) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل الموزع</a>
                        <a href="{{ route('distributors') }}" class="btn">رجوع</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
