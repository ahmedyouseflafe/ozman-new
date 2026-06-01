<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>{{ $agent->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('admin.agent.styles')
</head>
<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض الوكيل'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $agent->name }}</h1>
                        <p>تفاصيل الوكيل ومعلومات التواصل والموقع.</p>
                    </div>
                    <a href="{{ route('agents') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للوكلاء</a>
                </header>

                <section class="panel" style="padding:25px">
                    @if($agent->image)
                        <img src="{{ asset($agent->image) }}" class="avatar" alt="{{ $agent->name }}">
                    @else
                        <div class="avatar"><i class="ti ti-user-star"></i></div>
                    @endif

                    <span class="tag {{ $agent->is_active ? 'tag-g' : 'tag-r' }}">{{ $agent->is_active ? 'نشط' : 'غير نشط' }}</span>

                    <div class="detail-grid" style="margin-top:18px">
                        <div class="detail-box"><span class="label">المتجر</span><span class="value">{{ $agent->shop?->name ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">الهاتف</span><span class="value" dir="ltr">{{ $agent->phone ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">واتساب</span><span class="value" dir="ltr">{{ $agent->whatsapp ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">البريد</span><span class="value">{{ $agent->email ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">العنوان</span><span class="value">{{ $agent->address ?? '-' }}</span></div>
                        <div class="detail-box"><span class="label">الإحداثيات</span><span class="value">{{ $agent->latitude ?? '-' }}, {{ $agent->longitude ?? '-' }}</span></div>
                    </div>

                    <div class="actions">
                        <a href="{{ route('agents.edit', $agent) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل الوكيل</a>
                        <a href="{{ route('agents') }}" class="btn">رجوع</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
