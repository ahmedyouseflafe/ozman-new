<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إضافة فئة جديدة - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --danger: #ff3b30;
            --border: rgba(255,255,255,.1);
            --text: #fff;
            --muted: rgba(255,255,255,.64);
            --dim: rgba(255,255,255,.42);
        }
        html, body {
            min-height: 100%;
            background: radial-gradient(circle at 15% 14%, rgba(112,0,255,.14), transparent 29%),
                radial-gradient(circle at 78% 8%, rgba(0,229,255,.14), transparent 34%), #050505;
            color: var(--text);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
        }
        .main { min-height: 100vh; margin-right: 245px; }
        .content { padding: 28px 34px 46px; max-width: 980px; margin: 0 auto; }
        .page-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; margin-bottom: 22px; }
        h1 { font-size: 32px; font-weight: 900; color: var(--primary); text-shadow: 0 0 18px rgba(0,229,255,.42); }
        .page-head p { color: var(--muted); font-size: 14px; margin-top: 6px; font-weight: 700; }
        .form-shell {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025));
            backdrop-filter: blur(16px);
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 18px 48px rgba(0,0,0,.34);
        }
        .form-section { padding: 25px; }
        .section-head { display: flex; align-items: center; gap: 13px; margin-bottom: 18px; }
        .section-icon, .card-icon {
            width: 46px; height: 46px; border-radius: 16px; background: #000; border: 1px solid var(--primary);
            color: var(--primary); display: grid; place-items: center; font-size: 21px; box-shadow: 0 0 18px rgba(0,229,255,.28);
        }
        .section-head h2 { font-size: 18px; font-weight: 900; }
        .section-head p { color: var(--dim); font-size: 12px; font-weight: 700; margin-top: 4px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 15px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label { display: flex; align-items: center; gap: 7px; color: rgba(255,255,255,.72); font-size: 12px; font-weight: 900; margin-bottom: 8px; }
        .form-label i { color: var(--primary); font-size: 16px; }
        input, select {
            width: 100%; border: 1px solid var(--border); background: rgba(255,255,255,.055); border-radius: 16px;
            color: #fff; padding: 12px 14px; outline: none; font-family: inherit; font-size: 13px; font-weight: 700;
        }
        select option { color: #111; }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 18px rgba(0,229,255,.22); }
        .upload-box {
            position: relative; display: flex; align-items: center; gap: 14px; min-height: 94px; padding: 16px;
            border: 1px dashed rgba(0,229,255,.35); border-radius: 20px; background: rgba(0,0,0,.22); cursor: pointer;
        }
        .upload-box input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .switch-card { display: flex; align-items: center; justify-content: space-between; gap: 16px; border: 1px solid var(--border); border-radius: 22px; background: rgba(0,0,0,.22); padding: 16px; }
        .card-copy { display: flex; align-items: center; gap: 13px; }
        .card-title { display: block; font-size: 14px; font-weight: 900; }
        .card-sub { display: block; color: var(--dim); font-size: 11px; font-weight: 700; margin-top: 4px; }
        .switch { position: relative; width: 58px; height: 32px; flex-shrink: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; inset: 0; cursor: pointer; background: rgba(255,255,255,.08); border: 1px solid var(--border); border-radius: 999px; transition: all .3s ease; }
        .slider::before { content: ''; position: absolute; width: 24px; height: 24px; right: 4px; top: 3px; border-radius: 50%; background: rgba(255,255,255,.72); transition: all .3s ease; }
        .switch input:checked + .slider { background: rgba(37,211,102,.22); border-color: var(--green); }
        .switch input:checked + .slider::before { transform: translateX(-26px); background: var(--green); }
        .form-actions { display: flex; gap: 12px; padding: 20px 25px; border-top: 1px solid rgba(255,255,255,.08); background: rgba(0,0,0,.22); }
        .btn {
            border: 1px solid var(--border); min-height: 44px; padding: 0 18px; border-radius: 999px; display: inline-flex;
            align-items: center; justify-content: center; gap: 8px; color: #fff; background: rgba(255,255,255,.055);
            font-family: inherit; font-size: 13px; font-weight: 900; text-decoration: none; cursor: pointer;
        }
        .btn-primary { border: 0; color: #001014; background: linear-gradient(135deg, var(--primary), var(--accent)); box-shadow: 0 0 22px rgba(0,229,255,.34); }
        .alert { margin-bottom: 18px; padding: 16px; border: 1px solid rgba(255,59,48,.35); background: rgba(255,59,48,.08); border-radius: 18px; }
        .alert ul { margin: 8px 20px 0; color: rgba(255,255,255,.78); font-size: 13px; }
        @media(max-width: 900px) { .main { margin-right: 0; } .content { padding: 20px 16px 34px; } .form-grid { grid-template-columns: 1fr; } .page-head, .form-actions, .switch-card { flex-direction: column; align-items: stretch; } .btn { width: 100%; } }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'إضافة فئة جديدة'])

            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>إضافة فئة جديدة</h1>
                        <p>اربط الفئة بمتجر، وحدد اسمها وصورتها وحالة ظهورها.</p>
                    </div>
                    <a href="{{ route('categories') }}" class="btn">
                        <i class="ti ti-arrow-right" aria-hidden="true"></i>
                        رجوع للفئات
                    </a>
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

                <form class="form-shell" action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-category-plus" aria-hidden="true"></i></div>
                            <div>
                                <h2>بيانات الفئة</h2>
                                <p>الحقول الأساسية الخاصة بتصنيف المنتجات.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="shop_id"><i class="ti ti-building-store" aria-hidden="true"></i>المتجر</label>
                                <select id="shop_id" name="shop_id" required>
                                    <option value="">اختر المتجر</option>
                                    @foreach($shops as $shop)
                                        <option value="{{ $shop->id }}" data-catalog-type="{{ $shop->catalog_type ?: 'general' }}" @selected(old('shop_id', $selectedShopId) == $shop->id)>{{ $shop->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @include('admin.categories._catalog_guidance')

                            <div class="form-group">
                                <label class="form-label" for="name"><i class="ti ti-tag" aria-hidden="true"></i>اسم الفئة</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" data-auto-translate-source placeholder="مثال: مشروبات" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name_en">اسم الفئة بالإنجليزي</label>
                                <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" dir="ltr">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name_he">اسم الفئة بالعبري</label>
                                <input type="text" id="name_he" name="name_he" value="{{ old('name_he') }}">
                            </div>

                            <div class="form-group full">
                                <label class="form-label" for="slug"><i class="ti ti-link" aria-hidden="true"></i>الرابط المختصر</label>
                                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="اتركه فارغاً ليتم توليده تلقائياً">
                            </div>

                            <div class="form-group full">
                                <label class="upload-box">
                                    <input type="file" name="image" accept="image/*">
                                    <span class="card-icon"><i class="ti ti-photo-up" aria-hidden="true"></i></span>
                                    <span>
                                        <span class="card-title">صورة الفئة</span>
                                        <span class="card-sub">PNG أو JPG، اختياري</span>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group full">
                                <div class="switch-card">
                                    <div class="card-copy">
                                        <span class="card-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span>
                                        <span>
                                            <span class="card-title">تفعيل الفئة</span>
                                            <span class="card-sub">الفئة النشطة تظهر في قوائم التصنيفات.</span>
                                        </span>
                                    </div>
                                    <label class="switch" for="is_active">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i>
                            حفظ الفئة
                        </button>
                        <a href="{{ route('categories') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    @include('admin.includes.auto_translate')
</body>

</html>
