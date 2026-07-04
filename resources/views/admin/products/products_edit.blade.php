<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>تعديل المنتج - Ozman</title>
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
        .content { padding:28px 34px 46px; max-width:1100px; margin:0 auto; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:22px; }
        h1 { font-size:32px; font-weight:900; color:var(--primary); text-shadow:0 0 18px rgba(0,229,255,.42); }
        .page-head p { color:var(--muted); font-size:14px; margin-top:6px; font-weight:700; }
        .form-shell { border:1px solid var(--border); background:linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025)); backdrop-filter:blur(16px); border-radius:26px; overflow:hidden; box-shadow:0 18px 48px rgba(0,0,0,.34); }
        .form-section { padding:25px; border-bottom:1px solid rgba(255,255,255,.08); }
        .form-section:last-of-type { border-bottom:0; }
        .section-head { display:flex; align-items:center; gap:13px; margin-bottom:18px; }
        .section-icon, .card-icon { width:46px; height:46px; border-radius:16px; background:#000; border:1px solid var(--primary); color:var(--primary); display:grid; place-items:center; font-size:21px; box-shadow:0 0 18px rgba(0,229,255,.28); flex-shrink:0; }
        .section-head h2 { font-size:18px; font-weight:900; }
        .section-head p { color:var(--dim); font-size:12px; font-weight:700; margin-top:4px; }
        .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:15px; }
        .form-group.full { grid-column:1 / -1; }
        .form-label { display:flex; align-items:center; gap:7px; color:rgba(255,255,255,.72); font-size:12px; font-weight:900; margin-bottom:8px; }
        input, textarea, select { width:100%; border:1px solid var(--border); background:rgba(255,255,255,.055); border-radius:16px; color:#fff; padding:12px 14px; outline:none; font-family:inherit; font-size:13px; font-weight:700; }
        textarea { min-height:120px; resize:vertical; line-height:1.7; }
        select option { color:#111; }
        input:focus, textarea:focus, select:focus { border-color:var(--primary); box-shadow:0 0 18px rgba(0,229,255,.22); }
        input[type="number"], input[dir="ltr"] { direction:ltr; text-align:left; }
        .upload-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:15px; }
        .upload-box { position:relative; display:flex; align-items:center; gap:14px; min-height:94px; padding:16px; border:1px dashed rgba(0,229,255,.35); border-radius:20px; background:rgba(0,0,0,.22); cursor:pointer; }
        .upload-box input { position:absolute; inset:0; opacity:0; cursor:pointer; }
        .campaign-list { display:grid; gap:14px; }
        .campaign-card { border:1px solid var(--border); border-radius:22px; background:rgba(0,0,0,.22); padding:16px; }
        .campaign-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .campaign-title { display:flex; align-items:center; gap:10px; color:var(--primary); font-size:13px; font-weight:900; }
        .campaign-remove { min-height:36px; padding:0 12px; border-radius:999px; border:1px solid rgba(255,59,48,.38); color:#ff8a84; background:rgba(255,59,48,.08); font-family:inherit; font-weight:900; cursor:pointer; }
        .campaign-media { margin-top:12px; color:var(--dim); font-size:12px; font-weight:800; }
        .campaign-media img, .campaign-media video { display:block; width:140px; max-width:100%; height:90px; object-fit:cover; border:1px solid var(--border); border-radius:14px; margin-top:8px; }
        .card-title { display:block; font-size:14px; font-weight:900; }
        .card-sub { display:block; color:var(--dim); font-size:11px; font-weight:700; margin-top:4px; }
        .preview-grid { display:flex; flex-wrap:wrap; gap:10px; margin-top:12px; }
        .preview-item { position:relative; width:82px; display:grid; gap:6px; justify-items:center; }
        .preview-item img, .preview-item video { width:74px; height:74px; border-radius:16px; object-fit:cover; border:1px solid var(--border); background:#08080c; }
        .media-delete { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:30px; padding:0 9px; border-radius:999px; border:1px solid rgba(255,59,48,.42); background:rgba(255,59,48,.1); color:#ff8a84; font-size:11px; font-weight:900; cursor:pointer; }
        .media-delete input { width:14px; height:14px; accent-color:var(--danger); }
        .media-delete:has(input:checked) { background:rgba(255,59,48,.2); border-color:var(--danger); color:#fff; }
        .switch-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:15px; }
        .switch-card { display:flex; align-items:center; justify-content:space-between; gap:16px; border:1px solid var(--border); border-radius:22px; background:rgba(0,0,0,.22); padding:16px; }
        .card-copy { display:flex; align-items:center; gap:13px; }
        .switch { position:relative; width:58px; height:32px; flex-shrink:0; }
        .switch input { opacity:0; width:0; height:0; }
        .slider { position:absolute; inset:0; cursor:pointer; background:rgba(255,255,255,.08); border:1px solid var(--border); border-radius:999px; transition:all .3s ease; }
        .slider::before { content:''; position:absolute; width:24px; height:24px; right:4px; top:3px; border-radius:50%; background:rgba(255,255,255,.72); transition:all .3s ease; }
        .switch input:checked + .slider { background:rgba(37,211,102,.22); border-color:var(--green); }
        .switch input:checked + .slider::before { transform:translateX(-26px); background:var(--green); }
        .form-actions { display:flex; gap:12px; padding:20px 25px; border-top:1px solid rgba(255,255,255,.08); background:rgba(0,0,0,.22); }
        .btn { border:1px solid var(--border); min-height:44px; padding:0 18px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:8px; color:#fff; background:rgba(255,255,255,.055); font-family:inherit; font-size:13px; font-weight:900; text-decoration:none; cursor:pointer; }
        .btn-primary { border:0; color:#001014; background:linear-gradient(135deg, var(--primary), var(--accent)); box-shadow:0 0 22px rgba(0,229,255,.34); }
        .alert { margin-bottom:18px; padding:16px; border:1px solid rgba(255,59,48,.35); background:rgba(255,59,48,.08); border-radius:18px; }
        .alert ul { margin:8px 20px 0; color:rgba(255,255,255,.78); font-size:13px; }
        @media(max-width:900px) { .main{margin-right:0;} .content{padding:20px 16px 34px;} .form-grid,.upload-grid,.switch-grid{grid-template-columns:1fr;} .page-head,.form-actions,.switch-card,.campaign-head{flex-direction:column;align-items:stretch;} .btn{width:100%;} }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'تعديل المنتج'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>تعديل المنتج</h1>
                        <p>حدّث بيانات المنتج والسعر والصور وحالة الظهور.</p>
                    </div>
                    <a href="{{ route('products') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للمنتجات</a>
                </header>

                @if($errors->any())
                    <div class="alert">
                        <strong>راجع الحقول التالية:</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-shell" action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <section class="form-section">
                        <div class="section-head"><div class="section-icon"><i class="ti ti-package"></i></div><div><h2>بيانات المنتج</h2><p>المتجر والفئة والاسم والوصف.</p></div></div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="shop_id">المتجر</label>
                                <select id="shop_id" name="shop_id" required>
                                    <option value="">اختر المتجر</option>
                                    @foreach($shops as $shop)
                                        <option value="{{ $shop->id }}" @selected(old('shop_id', $product->shop_id) == $shop->id)>{{ $shop->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="category_id">الفئة</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">اختر الفئة</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" data-shop-id="{{ $category->shop_id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }} - {{ $category->shop?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="agent_id">الوكيل</label>
                                <select id="agent_id" name="agent_id">
                                    <option value="">منتج رئيسي للمتجر</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" data-shop-id="{{ $agent->shop_id }}" @selected(old('agent_id', $product->agent_id) == $agent->id)>{{ $agent->name }} - {{ $agent->shop?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group"><label class="form-label" for="name">اسم المنتج</label><input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" data-auto-translate-source required></div>
                            <div class="form-group"><label class="form-label" for="name_en">اسم المنتج بالإنجليزي</label><input type="text" id="name_en" name="name_en" value="{{ old('name_en', data_get($product->name_translations, 'en')) }}" dir="ltr"></div>
                            <div class="form-group"><label class="form-label" for="name_he">اسم المنتج بالعبري</label><input type="text" id="name_he" name="name_he" value="{{ old('name_he', data_get($product->name_translations, 'he')) }}"></div>
                            <div class="form-group"><label class="form-label" for="slug">الرابط المختصر</label><input type="text" id="slug" name="slug" value="{{ old('slug', $product->slug) }}"></div>
                            <div class="form-group full"><label class="form-label" for="description">الوصف</label><textarea id="description" name="description" data-auto-translate-source>{{ old('description', $product->description) }}</textarea></div>
                            <div class="form-group full"><label class="form-label" for="description_en">الوصف بالإنجليزي</label><textarea id="description_en" name="description_en" dir="ltr">{{ old('description_en', data_get($product->description_translations, 'en')) }}</textarea></div>
                            <div class="form-group full"><label class="form-label" for="description_he">الوصف بالعبري</label><textarea id="description_he" name="description_he">{{ old('description_he', data_get($product->description_translations, 'he')) }}</textarea></div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head"><div class="section-icon"><i class="ti ti-coin"></i></div><div><h2>السعر والمخزون</h2><p>الأسعار، الكمية، SKU، الباركود، والتقييم.</p></div></div>
                        <div class="form-grid">
                            <div class="form-group"><label class="form-label" for="price">السعر</label><input type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', $product->price) }}" required></div>
                            <div class="form-group"><label class="form-label" for="discount_price">سعر الخصم</label><input type="number" step="0.01" min="0" id="discount_price" name="discount_price" value="{{ old('discount_price', $product->discount_price) }}"></div>
                            <div class="form-group"><label class="form-label" for="merchant_price">سعر التاجر</label><input type="number" step="0.01" min="0" id="merchant_price" name="merchant_price" value="{{ old('merchant_price', $product->merchant_price) }}"></div>
                            <div class="form-group"><label class="form-label" for="package_price">سعر العبوة</label><input type="number" step="0.01" min="0" id="package_price" name="package_price" value="{{ old('package_price', $product->package_price) }}"></div>
                            <div class="form-group"><label class="form-label" for="pallet_price">سعر المشطاح</label><input type="number" step="0.01" min="0" id="pallet_price" name="pallet_price" value="{{ old('pallet_price', $product->pallet_price) }}"></div>
                            <div class="form-group"><label class="form-label" for="carton_price">سعر الكرتونة</label><input type="number" step="0.01" min="0" id="carton_price" name="carton_price" value="{{ old('carton_price', $product->carton_price) }}"></div>
                            <div class="form-group"><label class="form-label" for="quantity">الكمية</label><input type="number" min="0" id="quantity" name="quantity" value="{{ old('quantity', $product->quantity) }}"></div>
                            <div class="form-group"><label class="form-label" for="rating">التقييم</label><input type="number" step="0.1" min="0" max="5" id="rating" name="rating" value="{{ old('rating', $product->rating) }}"></div>
                            <div class="form-group"><label class="form-label" for="sku">SKU</label><input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" dir="ltr"></div>
                            <div class="form-group"><label class="form-label" for="barcode">Barcode</label><input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}" dir="ltr"></div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head"><div class="section-icon"><i class="ti ti-photo-up"></i></div><div><h2>الصور والفيديو</h2><p>اترك الملفات فارغة للاحتفاظ بالملفات الحالية.</p></div></div>
                        <div class="upload-grid">
                            <label class="upload-box"><input type="file" name="main_image" accept="image/*,.gif"><span class="card-icon"><i class="ti ti-photo"></i></span><span><span class="card-title">تغيير الصورة الرئيسية</span><span class="card-sub">PNG أو JPG أو GIF</span></span></label>
                            <label class="upload-box"><input type="file" name="video" accept="video/*"><span class="card-icon"><i class="ti ti-video"></i></span><span><span class="card-title">تغيير الفيديو</span><span class="card-sub">MP4 أو WebM</span></span></label>
                            <label class="upload-box"><input type="file" name="images[]" accept="image/*,.gif" multiple><span class="card-icon"><i class="ti ti-library-photo"></i></span><span><span class="card-title">إضافة صور</span><span class="card-sub">تضاف للمعرض الحالي وتدعم GIF</span></span></label>
                        </div>
                        <div class="preview-grid">
                            @if($product->main_image)
                                <div class="preview-item">
                                    <img src="{{ asset($product->main_image) }}" alt="{{ $product->name }}">
                                    <label class="media-delete">
                                        <input type="checkbox" name="delete_main_image" value="1">
                                        حذف الرئيسية
                                    </label>
                                </div>
                            @endif
                            @if($product->video)
                                <div class="preview-item">
                                    <video src="{{ asset($product->video) }}" muted></video>
                                    <label class="media-delete">
                                        <input type="checkbox" name="delete_video" value="1">
                                        حذف الفيديو
                                    </label>
                                </div>
                            @endif
                            @foreach($product->images as $image)
                                <div class="preview-item">
                                    <img src="{{ asset($image->image) }}" alt="{{ $product->name }}">
                                    <label class="media-delete">
                                        <input type="checkbox" name="delete_image_ids[]" value="{{ $image->id }}">
                                        حذف
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-speakerphone"></i></div>
                            <div>
                                <h2>حملات المنتج</h2>
                                <p>احذف حملات قديمة أو أضف عرض حملة فعلي مثل: 3 عبوات بسعر 10، مع صورة أو فيديو اختياري.</p>
                            </div>
                        </div>

                        @if($product->campaigns->isNotEmpty())
                            <div class="campaign-list" style="margin-bottom:16px">
                                @foreach($product->campaigns as $campaign)
                                    <div class="campaign-card">
                                        <div class="campaign-head">
                                            <div class="campaign-title"><i class="ti ti-ad"></i>{{ $campaign->title ?: 'حملة المنتج' }}</div>
                                            <label class="campaign-remove">
                                                <input type="checkbox" name="delete_campaign_ids[]" value="{{ $campaign->id }}" style="width:auto;margin-left:6px">
                                                حذف الحملة
                                            </label>
                                        </div>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label class="form-label">عنوان الحملة</label>
                                                <input type="text" name="existing_campaigns[{{ $campaign->id }}][title]" value="{{ old("existing_campaigns.{$campaign->id}.title", $campaign->title) }}" data-auto-translate-source placeholder="مثال: عرض الصيف">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">عنوان الحملة بالإنجليزي</label>
                                                <input type="text" name="existing_campaigns[{{ $campaign->id }}][title_en]" value="{{ old("existing_campaigns.{$campaign->id}.title_en", data_get($campaign->title_translations, 'en')) }}" dir="ltr">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">عنوان الحملة بالعبري</label>
                                                <input type="text" name="existing_campaigns[{{ $campaign->id }}][title_he]" value="{{ old("existing_campaigns.{$campaign->id}.title_he", data_get($campaign->title_translations, 'he')) }}">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">نوع الحملة</label>
                                                <select name="existing_campaigns[{{ $campaign->id }}][type]">
                                                    <option value="image" @selected(old("existing_campaigns.{$campaign->id}.type", $campaign->type) === 'image')>صورة</option>
                                                    <option value="video" @selected(old("existing_campaigns.{$campaign->id}.type", $campaign->type) === 'video')>فيديو</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">نوع العرض</label>
                                                <select name="existing_campaigns[{{ $campaign->id }}][offer_type]">
                                                    <option value="bundle_price" @selected(old("existing_campaigns.{$campaign->id}.offer_type", $campaign->offer_type ?: 'bundle_price') === 'bundle_price')>عدد بسعر محدد</option>
                                                    <option value="custom" @selected(old("existing_campaigns.{$campaign->id}.offer_type", $campaign->offer_type) === 'custom')>عرض مخصص</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">تطبيق الحملة على</label>
                                                <select name="existing_campaigns[{{ $campaign->id }}][unit_key]">
                                                    <option value="" @selected(old("existing_campaigns.{$campaign->id}.unit_key", $campaign->unit_key) === null || old("existing_campaigns.{$campaign->id}.unit_key", $campaign->unit_key) === '')>السعر الأساسي</option>
                                                    <option value="package" @selected(old("existing_campaigns.{$campaign->id}.unit_key", $campaign->unit_key) === 'package')>العبوة</option>
                                                    <option value="pallet" @selected(old("existing_campaigns.{$campaign->id}.unit_key", $campaign->unit_key) === 'pallet')>المشطاح</option>
                                                    <option value="carton" @selected(old("existing_campaigns.{$campaign->id}.unit_key", $campaign->unit_key) === 'carton')>الكرتونة</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">عدد القطع في العرض</label>
                                                <input type="number" min="1" name="existing_campaigns[{{ $campaign->id }}][offer_quantity]" value="{{ old("existing_campaigns.{$campaign->id}.offer_quantity", $campaign->offer_quantity) }}" placeholder="مثال: 3">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">سعر العرض</label>
                                                <input type="number" min="0" step="0.01" name="existing_campaigns[{{ $campaign->id }}][offer_price]" value="{{ old("existing_campaigns.{$campaign->id}.offer_price", $campaign->offer_price) }}" placeholder="مثال: 10">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">بداية العرض</label>
                                                <input type="date" name="existing_campaigns[{{ $campaign->id }}][starts_at]" value="{{ old("existing_campaigns.{$campaign->id}.starts_at", $campaign->starts_at?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">نهاية العرض</label>
                                                <input type="date" name="existing_campaigns[{{ $campaign->id }}][ends_at]" value="{{ old("existing_campaigns.{$campaign->id}.ends_at", $campaign->ends_at?->format('Y-m-d')) }}">
                                            </div>
                                            <div class="form-group full">
                                                <label class="form-label">وصف العرض</label>
                                                <textarea name="existing_campaigns[{{ $campaign->id }}][offer_note]" data-auto-translate-source placeholder="مثال: اشتري 3 عبوات بسعر 10 بدل 12">{{ old("existing_campaigns.{$campaign->id}.offer_note", $campaign->offer_note) }}</textarea>
                                            </div>
                                            <div class="form-group full">
                                                <label class="form-label">وصف العرض بالإنجليزي</label>
                                                <textarea name="existing_campaigns[{{ $campaign->id }}][offer_note_en]" dir="ltr">{{ old("existing_campaigns.{$campaign->id}.offer_note_en", data_get($campaign->offer_note_translations, 'en')) }}</textarea>
                                            </div>
                                            <div class="form-group full">
                                                <label class="form-label">وصف العرض بالعبري</label>
                                                <textarea name="existing_campaigns[{{ $campaign->id }}][offer_note_he]">{{ old("existing_campaigns.{$campaign->id}.offer_note_he", data_get($campaign->offer_note_translations, 'he')) }}</textarea>
                                            </div>
                                            <div class="form-group full">
                                                <label class="upload-box">
                                                    <input type="file" name="existing_campaigns[{{ $campaign->id }}][media]" accept="image/*,video/*">
                                                    <span class="card-icon"><i class="ti ti-upload"></i></span>
                                                    <span><span class="card-title">تغيير ملف الحملة</span><span class="card-sub">اتركه فارغًا للاحتفاظ بالملف الحالي</span></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="campaign-media">
                                            @if($campaign->offer_quantity || $campaign->offer_price || $campaign->offer_note)
                                                <div>
                                                    العرض:
                                                    @if($campaign->offer_quantity && $campaign->offer_price)
                                                        {{ $campaign->offer_quantity }} بسعر {{ number_format((float) $campaign->offer_price, 2) }}
                                                    @endif
                                                    @if($campaign->offer_note)
                                                        - {{ $campaign->offer_note }}
                                                    @endif
                                                </div>
                                            @endif
                                            @if($campaign->starts_at || $campaign->ends_at)
                                                <div>الفترة: {{ $campaign->starts_at?->format('Y-m-d') ?? 'بدون بداية' }} - {{ $campaign->ends_at?->format('Y-m-d') ?? 'بدون نهاية' }}</div>
                                            @endif
                                            @if($campaign->media)
                                            <div>النوع: {{ $campaign->type === 'image' ? 'صورة' : 'فيديو' }}</div>
                                            @if($campaign->type === 'image')
                                                <img src="{{ asset($campaign->media) }}" alt="{{ $campaign->title }}">
                                            @else
                                                <video src="{{ asset($campaign->media) }}" muted controls></video>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="campaign-list" id="campaignList">
                            @php
                                $oldCampaigns = old('campaigns', [['title' => '', 'type' => 'image', 'offer_type' => 'bundle_price']]);
                            @endphp

                            @foreach($oldCampaigns as $index => $campaign)
                                <div class="campaign-card" data-campaign-card>
                                    <div class="campaign-head">
                                        <div class="campaign-title"><i class="ti ti-ad"></i>حملة جديدة رقم <span data-campaign-number>{{ $loop->iteration }}</span></div>
                                        <button type="button" class="campaign-remove" data-remove-campaign>حذف</button>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">عنوان الحملة</label>
                                            <input type="text" name="campaigns[{{ $index }}][title]" value="{{ data_get($campaign, 'title') }}" data-auto-translate-source placeholder="مثال: عرض الصيف">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">عنوان الحملة بالإنجليزي</label>
                                            <input type="text" name="campaigns[{{ $index }}][title_en]" value="{{ data_get($campaign, 'title_en') }}" dir="ltr">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">عنوان الحملة بالعبري</label>
                                            <input type="text" name="campaigns[{{ $index }}][title_he]" value="{{ data_get($campaign, 'title_he') }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">نوع الحملة</label>
                                            <select name="campaigns[{{ $index }}][type]">
                                                <option value="image" @selected(data_get($campaign, 'type', 'image') === 'image')>صورة</option>
                                                <option value="video" @selected(data_get($campaign, 'type') === 'video')>فيديو</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">نوع العرض</label>
                                            <select name="campaigns[{{ $index }}][offer_type]">
                                                <option value="bundle_price" @selected(data_get($campaign, 'offer_type', 'bundle_price') === 'bundle_price')>عدد بسعر محدد</option>
                                                <option value="custom" @selected(data_get($campaign, 'offer_type') === 'custom')>عرض مخصص</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">تطبيق الحملة على</label>
                                            <select name="campaigns[{{ $index }}][unit_key]">
                                                <option value="" @selected(data_get($campaign, 'unit_key') === null || data_get($campaign, 'unit_key') === '')>السعر الأساسي</option>
                                                <option value="package" @selected(data_get($campaign, 'unit_key') === 'package')>العبوة</option>
                                                <option value="pallet" @selected(data_get($campaign, 'unit_key') === 'pallet')>المشطاح</option>
                                                <option value="carton" @selected(data_get($campaign, 'unit_key') === 'carton')>الكرتونة</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">عدد القطع في العرض</label>
                                            <input type="number" min="1" name="campaigns[{{ $index }}][offer_quantity]" value="{{ data_get($campaign, 'offer_quantity') }}" placeholder="مثال: 3">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">سعر العرض</label>
                                            <input type="number" min="0" step="0.01" name="campaigns[{{ $index }}][offer_price]" value="{{ data_get($campaign, 'offer_price') }}" placeholder="مثال: 10">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">بداية العرض</label>
                                            <input type="date" name="campaigns[{{ $index }}][starts_at]" value="{{ data_get($campaign, 'starts_at') }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">نهاية العرض</label>
                                            <input type="date" name="campaigns[{{ $index }}][ends_at]" value="{{ data_get($campaign, 'ends_at') }}">
                                        </div>
                                        <div class="form-group full">
                                            <label class="form-label">وصف العرض</label>
                                            <textarea name="campaigns[{{ $index }}][offer_note]" data-auto-translate-source placeholder="مثال: اشتري 3 عبوات بسعر 10 بدل 12">{{ data_get($campaign, 'offer_note') }}</textarea>
                                        </div>
                                        <div class="form-group full">
                                            <label class="form-label">وصف العرض بالإنجليزي</label>
                                            <textarea name="campaigns[{{ $index }}][offer_note_en]" dir="ltr">{{ data_get($campaign, 'offer_note_en') }}</textarea>
                                        </div>
                                        <div class="form-group full">
                                            <label class="form-label">وصف العرض بالعبري</label>
                                            <textarea name="campaigns[{{ $index }}][offer_note_he]">{{ data_get($campaign, 'offer_note_he') }}</textarea>
                                        </div>
                                        <div class="form-group full">
                                            <label class="upload-box">
                                                <input type="file" name="campaigns[{{ $index }}][media]" accept="image/*,video/*">
                                                <span class="card-icon"><i class="ti ti-upload"></i></span>
                                                <span><span class="card-title">ملف الحملة</span><span class="card-sub">اختياري: اختر صورة أو فيديو للحملة</span></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top:15px">
                            <button type="button" class="btn" id="addCampaign"><i class="ti ti-plus"></i>إضافة حملة</button>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="switch-grid">
                            <div class="switch-card">
                                <div class="card-copy"><span class="card-icon"><i class="ti ti-star"></i></span><span><span class="card-title">منتج مميز</span><span class="card-sub">يظهر ضمن المنتجات المميزة.</span></span></div>
                                <label class="switch" for="is_featured"><input type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))><span class="slider"></span></label>
                            </div>
                            <div class="switch-card">
                                <div class="card-copy"><span class="card-icon"><i class="ti ti-circle-check"></i></span><span><span class="card-title">تفعيل المنتج</span><span class="card-sub">المنتج النشط يظهر للعرض.</span></span></div>
                                <label class="switch" for="is_active"><input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active))><span class="slider"></span></label>
                            </div>
                        </div>
                    </section>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i>حفظ التعديلات</button>
                        <a href="{{ route('products') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const shopSelect = document.getElementById('shop_id');
        const categorySelect = document.getElementById('category_id');
        const agentSelect = document.getElementById('agent_id');
        function filterCategories() {
            const shopId = shopSelect.value;
            [...categorySelect.options].forEach((option) => {
                option.hidden = option.value && shopId && option.dataset.shopId !== shopId;
            });

            [...agentSelect.options].forEach((option) => {
                option.hidden = option.value && shopId && option.dataset.shopId !== shopId;
            });

            if (agentSelect.selectedOptions[0]?.hidden) {
                agentSelect.value = '';
            }
        }
        shopSelect.addEventListener('change', filterCategories);
        filterCategories();

        const campaignList = document.getElementById('campaignList');
        const addCampaign = document.getElementById('addCampaign');
        let campaignIndex = campaignList.querySelectorAll('[data-campaign-card]').length;

        function refreshCampaignNumbers() {
            campaignList.querySelectorAll('[data-campaign-card]').forEach((card, index) => {
                card.querySelector('[data-campaign-number]').textContent = index + 1;
                card.querySelector('[data-remove-campaign]').hidden = campaignList.children.length === 1;
            });
        }

        function campaignTemplate(index) {
            return `
                <div class="campaign-card" data-campaign-card>
                    <div class="campaign-head">
                        <div class="campaign-title"><i class="ti ti-ad"></i>حملة جديدة رقم <span data-campaign-number>${index + 1}</span></div>
                        <button type="button" class="campaign-remove" data-remove-campaign>حذف</button>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">عنوان الحملة</label>
                            <input type="text" name="campaigns[${index}][title]" data-auto-translate-source placeholder="مثال: عرض الصيف">
                        </div>
                        <div class="form-group">
                            <label class="form-label">عنوان الحملة بالإنجليزي</label>
                            <input type="text" name="campaigns[${index}][title_en]" dir="ltr">
                        </div>
                        <div class="form-group">
                            <label class="form-label">عنوان الحملة بالعبري</label>
                            <input type="text" name="campaigns[${index}][title_he]">
                        </div>
                        <div class="form-group">
                            <label class="form-label">نوع الحملة</label>
                            <select name="campaigns[${index}][type]">
                                <option value="image">صورة</option>
                                <option value="video">فيديو</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">نوع العرض</label>
                            <select name="campaigns[${index}][offer_type]">
                                <option value="bundle_price">عدد بسعر محدد</option>
                                <option value="custom">عرض مخصص</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">تطبيق الحملة على</label>
                            <select name="campaigns[${index}][unit_key]">
                                <option value="">السعر الأساسي</option>
                                <option value="package">العبوة</option>
                                <option value="pallet">المشطاح</option>
                                <option value="carton">الكرتونة</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">عدد القطع في العرض</label>
                            <input type="number" min="1" name="campaigns[${index}][offer_quantity]" placeholder="مثال: 3">
                        </div>
                        <div class="form-group">
                            <label class="form-label">سعر العرض</label>
                            <input type="number" min="0" step="0.01" name="campaigns[${index}][offer_price]" placeholder="مثال: 10">
                        </div>
                        <div class="form-group">
                            <label class="form-label">بداية العرض</label>
                            <input type="date" name="campaigns[${index}][starts_at]">
                        </div>
                        <div class="form-group">
                            <label class="form-label">نهاية العرض</label>
                            <input type="date" name="campaigns[${index}][ends_at]">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">وصف العرض</label>
                            <textarea name="campaigns[${index}][offer_note]" data-auto-translate-source placeholder="مثال: اشتري 3 عبوات بسعر 10 بدل 12"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">وصف العرض بالإنجليزي</label>
                            <textarea name="campaigns[${index}][offer_note_en]" dir="ltr"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">وصف العرض بالعبري</label>
                            <textarea name="campaigns[${index}][offer_note_he]"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="upload-box">
                                <input type="file" name="campaigns[${index}][media]" accept="image/*,video/*">
                                <span class="card-icon"><i class="ti ti-upload"></i></span>
                                <span><span class="card-title">ملف الحملة</span><span class="card-sub">اختياري: اختر صورة أو فيديو للحملة</span></span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
        }

        addCampaign.addEventListener('click', () => {
            campaignList.insertAdjacentHTML('beforeend', campaignTemplate(campaignIndex));
            campaignIndex++;
            refreshCampaignNumbers();
        });

        campaignList.addEventListener('click', (event) => {
            if (!event.target.matches('[data-remove-campaign]') || campaignList.children.length <= 1) {
                return;
            }

            event.target.closest('[data-campaign-card]').remove();
            refreshCampaignNumbers();
        });

        refreshCampaignNumbers();
    </script>
    @include('admin.includes.auto_translate')
</body>

</html>
