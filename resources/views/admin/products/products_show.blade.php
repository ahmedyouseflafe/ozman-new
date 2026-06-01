<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $product->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --yellow:#ffd60a; --danger:#ff3b30; --border:rgba(255,255,255,.1); --text:#fff; --muted:rgba(255,255,255,.64); --dim:rgba(255,255,255,.42); }
        html,body { min-height:100%; background:radial-gradient(circle at 15% 14%, rgba(112,0,255,.14), transparent 29%), radial-gradient(circle at 78% 8%, rgba(0,229,255,.14), transparent 34%), #050505; color:var(--text); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; direction:rtl; }
        .main { min-height:100vh; margin-right:245px; }
        .content { padding:28px 34px 46px; max-width:1100px; margin:0 auto; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:22px; }
        h1 { font-size:32px; font-weight:900; color:var(--primary); text-shadow:0 0 18px rgba(0,229,255,.42); }
        .page-head p { color:var(--muted); font-size:14px; margin-top:6px; font-weight:700; }
        .panel { border:1px solid var(--border); background:linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025)); backdrop-filter:blur(16px); border-radius:26px; overflow:hidden; box-shadow:0 18px 48px rgba(0,0,0,.34); padding:25px; }
        .hero { display:grid; grid-template-columns:180px 1fr; gap:24px; align-items:center; margin-bottom:24px; }
        .image { width:180px; height:180px; border-radius:26px; border:1px solid var(--border); background:#000; color:var(--primary); display:grid; place-items:center; font-size:54px; overflow:hidden; }
        .image img { width:100%; height:100%; object-fit:cover; }
        .badges { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
        .tag { display:inline-flex; align-items:center; justify-content:center; min-height:30px; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:900; border:1px solid currentColor; }
        .tag-g { color:var(--green); background:rgba(37,211,102,.1); }
        .tag-r { color:var(--danger); background:rgba(255,59,48,.1); }
        .tag-y { color:var(--yellow); background:rgba(255,214,10,.1); }
        .tag-c { color:var(--primary); background:rgba(0,229,255,.09); }
        .grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
        .item { border:1px solid var(--border); border-radius:18px; background:rgba(0,0,0,.2); padding:16px; }
        .label { color:var(--dim); font-size:12px; font-weight:800; margin-bottom:6px; }
        .value { font-size:15px; font-weight:900; overflow-wrap:anywhere; }
        .description { border:1px solid var(--border); border-radius:18px; background:rgba(0,0,0,.2); padding:16px; color:var(--muted); line-height:1.8; margin-bottom:20px; }
        .gallery { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:22px; }
        .gallery img { width:110px; height:110px; object-fit:cover; border-radius:18px; border:1px solid var(--border); }
        .campaigns { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-bottom:22px; }
        .campaign { border:1px solid var(--border); border-radius:18px; background:rgba(0,0,0,.2); padding:14px; }
        .campaign h3 { color:var(--primary); font-size:14px; font-weight:900; margin-bottom:10px; }
        .campaign img, .campaign video { width:100%; height:180px; object-fit:cover; border-radius:16px; border:1px solid var(--border); background:#000; }
        .actions { display:flex; gap:10px; }
        .btn { border:1px solid var(--border); min-height:44px; padding:0 18px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:8px; color:#fff; background:rgba(255,255,255,.055); font-family:inherit; font-size:13px; font-weight:900; text-decoration:none; cursor:pointer; }
        .btn-primary { border:0; color:#001014; background:linear-gradient(135deg, var(--primary), var(--accent)); box-shadow:0 0 22px rgba(0,229,255,.34); }
        @media(max-width:900px) { .main{margin-right:0;} .content{padding:20px 16px 34px;} .hero,.grid,.campaigns{grid-template-columns:1fr;} .page-head,.actions{flex-direction:column;align-items:stretch;} .btn{width:100%;} }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض المنتج'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $product->name }}</h1>
                        <p>تفاصيل المنتج والسعر والمخزون والوسائط.</p>
                    </div>
                    <a href="{{ route('products') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للمنتجات</a>
                </header>

                <section class="panel">
                    <div class="hero">
                        <div class="image">
                            @if($product->main_image)
                                <img src="{{ asset($product->main_image) }}" alt="{{ $product->name }}">
                            @else
                                <i class="ti ti-package"></i>
                            @endif
                        </div>
                        <div>
                            <h1>{{ $product->name }}</h1>
                            <div class="badges">
                                <span class="tag {{ $product->is_active ? 'tag-g' : 'tag-r' }}">{{ $product->is_active ? 'نشط' : 'غير نشط' }}</span>
                                <span class="tag {{ $product->is_featured ? 'tag-y' : 'tag-r' }}">{{ $product->is_featured ? 'مميز' : 'غير مميز' }}</span>
                                <span class="tag {{ $product->quantity > 0 ? 'tag-c' : 'tag-r' }}">{{ $product->quantity > 0 ? 'متوفر' : 'نفد المخزون' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid">
                        <div class="item"><div class="label">المتجر</div><div class="value">{{ $product->shop?->name ?? '-' }}</div></div>
                        <div class="item"><div class="label">الفئة</div><div class="value">{{ $product->category?->name ?? '-' }}</div></div>
                        <div class="item"><div class="label">الرابط المختصر</div><div class="value">{{ $product->slug }}</div></div>
                        <div class="item"><div class="label">السعر</div><div class="value">{{ $product->price }}₪</div></div>
                        <div class="item"><div class="label">سعر الخصم</div><div class="value">{{ $product->discount_price ?? '-' }}</div></div>
                        <div class="item"><div class="label">الكمية</div><div class="value">{{ $product->quantity }}</div></div>
                        <div class="item"><div class="label">SKU</div><div class="value">{{ $product->sku ?? '-' }}</div></div>
                        <div class="item"><div class="label">Barcode</div><div class="value">{{ $product->barcode ?? '-' }}</div></div>
                        <div class="item"><div class="label">التقييم</div><div class="value">{{ $product->rating }}</div></div>
                    </div>

                    @if($product->description)
                        <div class="description">{{ $product->description }}</div>
                    @endif

                    @if($product->images->isNotEmpty())
                        <div class="gallery">
                            @foreach($product->images as $image)
                                <img src="{{ asset($image->image) }}" alt="{{ $product->name }}">
                            @endforeach
                        </div>
                    @endif

                    @if($product->campaigns->isNotEmpty())
                        <div class="campaigns">
                            @foreach($product->campaigns as $campaign)
                                <div class="campaign">
                                    <h3>{{ $campaign->title }}</h3>
                                    @if($campaign->type === 'image')
                                        <img src="{{ asset($campaign->media) }}" alt="{{ $campaign->title }}">
                                    @else
                                        <video src="{{ asset($campaign->media) }}" controls></video>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="actions">
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل المنتج</a>
                        <a href="{{ route('products') }}" class="btn">رجوع</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>
