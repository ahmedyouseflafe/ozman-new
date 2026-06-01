<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $category->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --danger:#ff3b30; --border:rgba(255,255,255,.1); --text:#fff; --muted:rgba(255,255,255,.64); --dim:rgba(255,255,255,.42); }
        html, body { min-height:100%; background:radial-gradient(circle at 15% 14%, rgba(112,0,255,.14), transparent 29%), radial-gradient(circle at 78% 8%, rgba(0,229,255,.14), transparent 34%), #050505; color:var(--text); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; direction:rtl; }
        .main { min-height:100vh; margin-right:245px; }
        .content { padding:28px 34px 46px; max-width:980px; margin:0 auto; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:22px; }
        h1 { font-size:32px; font-weight:900; color:var(--primary); text-shadow:0 0 18px rgba(0,229,255,.42); }
        .page-head p { color:var(--muted); font-size:14px; margin-top:6px; font-weight:700; }
        .panel { border:1px solid var(--border); background:linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025)); backdrop-filter:blur(16px); border-radius:26px; overflow:hidden; box-shadow:0 18px 48px rgba(0,0,0,.34); padding:25px; }
        .hero { display:grid; grid-template-columns:140px 1fr; gap:22px; align-items:center; margin-bottom:24px; }
        .image { width:140px; height:140px; border-radius:24px; border:1px solid var(--border); background:rgba(0,0,0,.28); display:grid; place-items:center; overflow:hidden; color:var(--primary); font-size:44px; }
        .image img { width:100%; height:100%; object-fit:cover; }
        .badge { display:inline-flex; align-items:center; gap:7px; min-height:32px; border-radius:999px; padding:0 12px; font-size:12px; font-weight:900; background:rgba(37,211,102,.12); color:var(--green); border:1px solid rgba(37,211,102,.35); }
        .badge.off { background:rgba(255,59,48,.12); color:var(--danger); border-color:rgba(255,59,48,.35); }
        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .item { border:1px solid var(--border); border-radius:18px; background:rgba(0,0,0,.2); padding:16px; }
        .label { color:var(--dim); font-size:12px; font-weight:800; margin-bottom:6px; }
        .value { font-size:15px; font-weight:900; overflow-wrap:anywhere; }
        .actions { display:flex; gap:10px; margin-top:22px; }
        .btn { border:1px solid var(--border); min-height:44px; padding:0 18px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:8px; color:#fff; background:rgba(255,255,255,.055); font-family:inherit; font-size:13px; font-weight:900; text-decoration:none; cursor:pointer; }
        .btn-primary { border:0; color:#001014; background:linear-gradient(135deg, var(--primary), var(--accent)); box-shadow:0 0 22px rgba(0,229,255,.34); }
        @media(max-width:900px) { .main{margin-right:0;} .content{padding:20px 16px 34px;} .hero,.grid{grid-template-columns:1fr;} .page-head,.actions{flex-direction:column;align-items:stretch;} .btn{width:100%;} }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض الفئة'])

            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $category->name }}</h1>
                        <p>تفاصيل الفئة وعدد المنتجات المرتبطة بها.</p>
                    </div>
                    <a href="{{ route('categories') }}" class="btn">
                        <i class="ti ti-arrow-right" aria-hidden="true"></i>
                        رجوع للفئات
                    </a>
                </header>

                <section class="panel">
                    <div class="hero">
                        <div class="image">
                            @if($category->image)
                                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                            @else
                                <i class="ti ti-category" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div>
                            <span class="badge {{ $category->is_active ? '' : 'off' }}">
                                <i class="ti {{ $category->is_active ? 'ti-circle-check' : 'ti-circle-x' }}" aria-hidden="true"></i>
                                {{ $category->is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                            <h1 style="margin-top:12px">{{ $category->name }}</h1>
                        </div>
                    </div>

                    <div class="grid">
                        <div class="item">
                            <div class="label">المتجر</div>
                            <div class="value">{{ $category->shop?->name ?? '-' }}</div>
                        </div>
                        <div class="item">
                            <div class="label">الرابط المختصر</div>
                            <div class="value">{{ $category->slug }}</div>
                        </div>
                        <div class="item">
                            <div class="label">عدد المنتجات</div>
                            <div class="value">{{ $category->products_count }} منتج</div>
                        </div>
                        <div class="item">
                            <div class="label">تاريخ الإضافة</div>
                            <div class="value">{{ optional($category->created_at)->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>

                    <div class="actions">
                        <a href="{{ route('products.create', ['shop_id' => $category->shop_id, 'category_id' => $category->id]) }}" class="btn btn-primary">
                            <i class="ti ti-package-plus" aria-hidden="true"></i>
                            إضافة منتج
                        </a>
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary">
                            <i class="ti ti-edit" aria-hidden="true"></i>
                            تعديل الفئة
                        </a>
                        <a href="{{ route('categories') }}" class="btn">رجوع</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>
