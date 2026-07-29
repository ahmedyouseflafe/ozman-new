<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>بطاقات السحب - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --danger:#ff4d68; --yellow:#ffd60a; --bg:#050505; --border:rgba(255,255,255,.1); --text:#fff; --muted:rgba(255,255,255,.64); }
        body { min-height:100vh; background:radial-gradient(circle at 12% 12%, rgba(112,0,255,.16), transparent 32%), radial-gradient(circle at 78% 10%, rgba(0,229,255,.16), transparent 34%), #050505; color:var(--text); font-family:Cairo,Segoe UI,sans-serif; }
        body::before { content:''; position:fixed; inset:0; pointer-events:none; background-image:linear-gradient(rgba(255,255,255,.026) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.026) 1px, transparent 1px); background-size:44px 44px; mask-image:linear-gradient(to bottom, black, transparent 82%); }
        .main { min-height:100vh; margin-right:245px; position:relative; z-index:1; }
        .content { padding:28px 34px 46px; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:22px; }
        .page-kicker { color:var(--primary); font-size:13px; font-weight:900; text-shadow:0 0 12px rgba(0,229,255,.5); }
        h1 { margin-top:6px; color:var(--primary); font-size:36px; line-height:1.1; font-weight:900; text-shadow:0 0 20px rgba(0,229,255,.42); }
        .page-head p { margin-top:8px; color:var(--muted); font-weight:800; }
        .grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; margin-bottom:20px; }
        .card, .panel { border:1px solid var(--border); background:linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025)); backdrop-filter:blur(16px); border-radius:26px; box-shadow:0 18px 48px rgba(0,0,0,.34); }
        .card { min-height:124px; padding:22px; position:relative; overflow:hidden; }
        .card span { color:var(--muted); font-size:13px; font-weight:900; }
        .card strong { display:block; margin-top:16px; color:var(--card-color, var(--primary)); font-size:36px; line-height:1; text-shadow:0 0 18px currentColor; }
        .card i { position:absolute; left:20px; bottom:16px; font-size:46px; color:var(--card-color, var(--primary)); opacity:.18; }
        .panel { padding:22px; margin-bottom:20px; }
        .panel-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding-bottom:16px; border-bottom:1px solid var(--border); margin-bottom:18px; }
        .panel-title { display:flex; align-items:center; gap:10px; color:#fff; font-size:20px; font-weight:900; }
        .panel-title i { color:var(--primary); filter:drop-shadow(0 0 10px rgba(0,229,255,.55)); }
        .form-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; align-items:end; }
        label { display:block; color:rgba(255,255,255,.72); font-size:12px; font-weight:900; margin-bottom:8px; }
        input { width:100%; min-height:46px; border:1px solid var(--border); background:rgba(255,255,255,.055); border-radius:16px; color:#fff; padding:0 15px; outline:none; font-family:inherit; font-size:13px; font-weight:800; }
        select { width:100%; min-height:46px; border:1px solid var(--border); background:rgba(255,255,255,.055); border-radius:16px; color:#fff; padding:0 15px; outline:none; font-family:inherit; font-size:13px; font-weight:800; }
        select option { color:#111; }
        input[type=file] { padding:10px 15px; }
        .file-input { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
        .file-card {
            min-height:46px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            border:1px dashed rgba(0,229,255,.32);
            background:linear-gradient(135deg, rgba(0,229,255,.08), rgba(112,0,255,.08));
            border-radius:16px;
            color:#fff;
            padding:0 14px;
            cursor:pointer;
            font-size:13px;
            font-weight:900;
            transition:all .25s ease;
        }
        .file-card:hover { border-color:var(--primary); box-shadow:0 0 18px rgba(0,229,255,.18); transform:translateY(-1px); }
        .file-card i { color:var(--primary); font-size:18px; filter:drop-shadow(0 0 8px rgba(0,229,255,.45)); }
        .file-card span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        input:focus { border-color:var(--primary); box-shadow:0 0 18px rgba(0,229,255,.22); }
        .check-row { display:flex; align-items:center; gap:9px; min-height:46px; color:#fff; font-weight:900; }
        .check-row input { width:20px; min-height:20px; accent-color:var(--green); }
        .btn { border:0; min-height:46px; padding:0 20px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:8px; font:inherit; font-weight:900; text-decoration:none; cursor:pointer; white-space:nowrap; }
        .btn-primary { color:#001014; background:linear-gradient(135deg, var(--primary), var(--accent)); box-shadow:0 0 22px rgba(0,229,255,.34); }
        .btn-soft { color:#fff; border:1px solid var(--border); background:rgba(255,255,255,.06); }
        .btn-danger { color:#ff91a0; border:1px solid rgba(255,77,104,.34); background:rgba(255,77,104,.08); }
        .btn-danger:disabled { opacity:.45; cursor:not-allowed; }
        .status { margin-bottom:18px; padding:14px 18px; border:1px solid rgba(37,211,102,.35); background:rgba(37,211,102,.1); border-radius:18px; color:#8dffbd; font-weight:900; }
        .errors { margin-bottom:18px; padding:14px 18px; border:1px solid rgba(255,77,104,.35); background:rgba(255,77,104,.1); border-radius:18px; color:#ff9dac; font-weight:900; }
        .filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .table-wrap { width:100%; overflow:auto; border:1px solid var(--border); border-radius:22px; }
        table { width:100%; border-collapse:collapse; min-width:920px; }
        th, td { padding:14px 16px; border-bottom:1px solid rgba(255,255,255,.08); text-align:right; vertical-align:middle; }
        th { color:var(--primary); font-size:12px; font-weight:900; }
        td { color:#fff; font-size:13px; font-weight:800; }
        .tag { display:inline-flex; align-items:center; justify-content:center; min-height:32px; padding:0 12px; border-radius:999px; border:1px solid rgba(0,229,255,.28); color:var(--primary); background:rgba(0,229,255,.08); font-weight:900; }
        .tag.green { color:#2dff83; border-color:rgba(45,255,131,.3); background:rgba(45,255,131,.08); }
        .tag.red { color:#ff6f83; border-color:rgba(255,111,131,.3); background:rgba(255,111,131,.08); }
        .prize { display:flex; align-items:center; gap:10px; }
        .prize img { width:48px; height:48px; border-radius:12px; object-fit:cover; border:1px solid rgba(0,229,255,.28); }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        .select-cell { width:58px; text-align:center; }
        .select-cell input { width:20px; min-height:20px; accent-color:var(--primary); cursor:pointer; }
        .live-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .live-actions .btn { min-height:42px; }
        .edit-box { display:grid; grid-template-columns:95px 1fr 160px 90px auto; gap:8px; align-items:center; }
        .pagination { margin-top:14px; color:#fff; }
        .pagination nav { display:flex; flex-direction:column; gap:10px; align-items:flex-end; color:var(--muted); font-size:12px; font-weight:800; }
        .pagination nav > div:first-child { display:none; }
        .pagination nav > div:last-child { display:flex; flex-direction:column; gap:10px; align-items:flex-end; width:100%; }
        .pagination nav p { color:var(--muted); font-size:12px; margin:0; }
        .pagination nav span[aria-current="page"] span,
        .pagination nav a,
        .pagination nav span[aria-disabled="true"] span {
            min-width:34px;
            height:34px;
            padding:0 10px;
            display:inline-flex !important;
            align-items:center;
            justify-content:center;
            border-radius:999px !important;
            border:1px solid rgba(255,255,255,.1) !important;
            background:rgba(255,255,255,.055) !important;
            color:#fff !important;
            font-size:12px !important;
            font-weight:900 !important;
            line-height:1 !important;
            text-decoration:none;
            box-shadow:none !important;
        }
        .pagination nav span[aria-current="page"] span {
            color:#001014 !important;
            background:linear-gradient(135deg, var(--primary), var(--accent)) !important;
            border-color:transparent !important;
            box-shadow:0 0 16px rgba(0,229,255,.28) !important;
        }
        .pagination nav span[aria-disabled="true"] span { opacity:.42; cursor:not-allowed; }
        .pagination nav a:hover { border-color:rgba(0,229,255,.5) !important; box-shadow:0 0 14px rgba(0,229,255,.18) !important; }
        .pagination nav svg { width:16px !important; height:16px !important; max-width:16px !important; max-height:16px !important; display:block !important; }
        .pagination nav .hidden { display:flex !important; }
        .pagination nav > div:last-child > div:last-child,
        .pagination nav div[role="navigation"],
        .pagination nav div[aria-label="Pagination Navigation"] { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; align-items:center; }
        .live-draw-modal { position:fixed; inset:0; z-index:2000; display:none; align-items:center; justify-content:center; padding:22px; background:rgba(0,0,0,.78); backdrop-filter:blur(10px); }
        .live-draw-modal.show { display:flex; }
        .live-draw-card { width:min(620px, 100%); position:relative; overflow:hidden; border:1px solid rgba(0,229,255,.42); border-radius:34px; padding:34px; text-align:center; background:radial-gradient(circle at 50% 0%, rgba(0,229,255,.2), transparent 38%), linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.025)); box-shadow:0 0 60px rgba(0,229,255,.2), 0 22px 80px rgba(0,0,0,.55); }
        .live-draw-close { position:absolute; top:18px; left:18px; width:42px; height:42px; border-radius:50%; border:1px solid var(--border); color:#fff; background:rgba(255,255,255,.08); cursor:pointer; font-size:20px; }
        .live-draw-kicker { color:var(--primary); font-size:15px; font-weight:900; text-shadow:0 0 18px rgba(0,229,255,.55); }
        .live-draw-title { margin-top:8px; font-size:34px; font-weight:900; color:#fff; }
        .live-draw-number { direction:ltr; margin:26px auto 20px; width:min(390px,100%); min-height:118px; display:flex; align-items:center; justify-content:center; border-radius:28px; border:1px solid rgba(0,229,255,.5); background:rgba(0,0,0,.45); color:var(--primary); font-size:54px; font-weight:900; letter-spacing:8px; text-shadow:0 0 28px rgba(0,229,255,.72); box-shadow:inset 0 0 30px rgba(0,229,255,.12); }
        .live-draw-number.spinning { animation:drawPulse .18s linear infinite; }
        .live-draw-info { display:grid; gap:8px; color:var(--muted); font-weight:900; }
        .live-draw-info strong { color:#fff; }
        .live-draw-error { color:#ff91a0; font-weight:900; margin-top:18px; }
        @keyframes drawPulse { 0%{ transform:scale(.985); opacity:.82; } 100%{ transform:scale(1.015); opacity:1; } }
        @media(max-width:1100px){ .grid,.form-grid{grid-template-columns:1fr;} .edit-box{grid-template-columns:1fr;} }
        @media(max-width:900px){ .main{margin-right:0;} .content{padding:20px 14px 90px;} .page-head{flex-direction:column; align-items:stretch;} h1{font-size:28px;} }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    <main class="main">
        @include('admin.includes.header', ['title' => 'بطاقات السحب'])
        <div class="content">
            <header class="page-head">
                <div>
                    <div class="page-kicker">Ozman Raffle</div>
                    <h1>بطاقات السحب</h1>
                    <p>أدخل الأرقام الرابحة فقط، وأي رقم غير موجود يدخل مرة واحدة في سحب جوائز البثوث المباشرة.</p>
                </div>
            </header>

            @if(session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="errors">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <section class="grid">
                <div class="card"><span>الأرقام الرابحة</span><strong>{{ $totalCards }}</strong><i class="ti ti-ticket"></i></div>
                <div class="card" style="--card-color:var(--green)"><span>بطاقات مستخدمة</span><strong>{{ $usedCards }}</strong><i class="ti ti-gift"></i></div>
                <div class="card" style="--card-color:var(--yellow)"><span>جوائز البثوث المباشرة</span><strong>{{ $liveEntriesCount }}</strong><i class="ti ti-broadcast"></i></div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title"><i class="ti ti-brand-whatsapp"></i> رقم واتساب السحب</div>
                </div>
                <form action="{{ route('raffle-cards.settings') }}" method="POST" class="form-grid" style="grid-template-columns:1fr auto;">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="raffle_whatsapp">رقم الواتساب الذي يتواصل معه الفائز</label>
                        <input id="raffle_whatsapp" name="raffle_whatsapp" value="{{ old('raffle_whatsapp', $raffleWhatsapp) }}" placeholder="+97059xxxxxxx" dir="ltr">
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> حفظ الرقم</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-printer"></i> توليد بطاقات QR للطباعة</div>
                        <p style="color:var(--muted); font-weight:800; margin-top:8px">اختر نطاق الأرقام وسيتم إنشاء صفحة A4 جاهزة للطباعة أو الحفظ PDF، وكل بطاقة تحمل QR خاص بها.</p>
                    </div>
                </div>
                <form action="{{ route('raffle-cards.printable') }}" method="POST" target="_blank" class="form-grid">
                    @csrf
                    <div>
                        <label for="from_number">من رقم</label>
                        <input id="from_number" name="from_number" value="{{ old('from_number', '000001') }}" maxlength="6" pattern="\d{6}" placeholder="000001" dir="ltr" required>
                    </div>
                    <div>
                        <label for="to_number">إلى رقم</label>
                        <input id="to_number" name="to_number" value="{{ old('to_number', '000100') }}" maxlength="6" pattern="\d{6}" placeholder="000100" dir="ltr" required>
                    </div>
                    <div>
                        <label for="cards_per_page">عدد البطاقات في الصفحة</label>
                        <select id="cards_per_page" name="cards_per_page" required>
                            <option value="8" @selected(old('cards_per_page', '8') === '8')>8 بطاقات</option>
                            <option value="10" @selected(old('cards_per_page') === '10')>10 بطاقات</option>
                            <option value="6" @selected(old('cards_per_page') === '6')>6 بطاقات</option>
                        </select>
                    </div>
                    <div>
                        <label for="brand_text">اسم البراند على البطاقة</label>
                        <input id="brand_text" name="brand_text" value="{{ old('brand_text', 'Ozman') }}" placeholder="Ozman">
                    </div>
                    <div style="grid-column:span 2">
                        <label for="social_qr_1_url">QR السوشيال الأول</label>
                        <input id="social_qr_1_url" name="social_qr_1_url" value="{{ old('social_qr_1_url', $defaultSocialQrLinks['first'] ?? '') }}" placeholder="https://instagram.com/..." dir="ltr">
                    </div>
                    <div style="grid-column:span 2">
                        <label for="social_qr_2_url">QR السوشيال الثاني</label>
                        <input id="social_qr_2_url" name="social_qr_2_url" value="{{ old('social_qr_2_url', $defaultSocialQrLinks['second'] ?? '') }}" placeholder="https://tiktok.com/..." dir="ltr">
                    </div>
                    <button class="btn btn-primary" type="submit" style="grid-column:1 / -1">
                        <i class="ti ti-file-type-pdf"></i>
                        توليد صفحة الطباعة / PDF
                    </button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-wand"></i> إضافة بطاقات رابحة عشوائيًا</div>
                        <div class="muted">حدد النطاق وعدد الهدايا، وسيختار النظام أرقامًا غير مستخدمة بشكل عشوائي.</div>
                    </div>
                </div>
                <form action="{{ route('raffle-cards.random-bulk') }}" method="POST" enctype="multipart/form-data" class="form-grid">
                    @csrf
                    <div>
                        <label for="bulk_prize_title">اسم الهدية</label>
                        <input id="bulk_prize_title" name="prize_title" value="{{ old('prize_title') }}" placeholder="مثال: سماعة بلوتوث" required>
                    </div>
                    <div>
                        <label for="bulk_prize_count">عدد الهدايا</label>
                        <input id="bulk_prize_count" name="prize_count" type="number" value="{{ old('prize_count') }}" min="1" max="10000" placeholder="مثال: 20" required>
                    </div>
                    <div>
                        <label for="bulk_from_number">رقم البطاقة من</label>
                        <input id="bulk_from_number" name="from_number" value="{{ old('from_number') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000001" dir="ltr" required>
                    </div>
                    <div>
                        <label for="bulk_to_number">رقم البطاقة إلى</label>
                        <input id="bulk_to_number" name="to_number" value="{{ old('to_number') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="999999" dir="ltr" required>
                    </div>
                    <div>
                        <label for="bulk_prize_image">صورة الهدية</label>
                        <label class="file-card" for="bulk_prize_image">
                            <span data-file-label>اختر صورة الهدية</span>
                            <i class="ti ti-photo-up"></i>
                        </label>
                        <input class="file-input" id="bulk_prize_image" name="prize_image" type="file" accept="image/*" data-file-input>
                    </div>
                    <label class="check-row">
                        <input type="checkbox" name="is_active" value="1" checked>
                        البطاقات نشطة
                    </label>
                    <button class="btn btn-primary" type="submit" style="grid-column:1 / -1">
                        <i class="ti ti-sparkles"></i> إنشاء الأرقام الرابحة عشوائيًا
                    </button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title"><i class="ti ti-plus"></i> إضافة بطاقة رابحة يدويًا</div>
                </div>
                <form action="{{ route('raffle-cards.store') }}" method="POST" enctype="multipart/form-data" class="form-grid">
                    @csrf
                    <div>
                        <label for="card_number">رقم البطاقة</label>
                        <input id="card_number" name="card_number" value="{{ old('card_number') }}" maxlength="6" pattern="\d{6}" placeholder="000000" dir="ltr" required>
                    </div>
                    <div>
                        <label for="prize_title">الجائزة</label>
                        <input id="prize_title" name="prize_title" value="{{ old('prize_title') }}" placeholder="اسم الجائزة" required>
                    </div>
                    <div>
                        <label for="prize_image">صورة الجائزة</label>
                        <label class="file-card" for="prize_image">
                            <span data-file-label>اختر صورة الجائزة</span>
                            <i class="ti ti-photo-up"></i>
                        </label>
                        <input class="file-input" id="prize_image" name="prize_image" type="file" accept="image/*" data-file-input>
                    </div>
                    <label class="check-row">
                        <input type="checkbox" name="is_active" value="1" checked>
                        نشطة
                    </label>
                    <button class="btn btn-primary" type="submit"><i class="ti ti-ticket"></i> إضافة بطاقة</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-file-type-pdf"></i> تحميل الأرقام الرابحة PDF</div>
                        <div class="muted">سيتم تضمين البطاقات الرابحة الموجودة فقط ضمن النطاق المحدد، مع بيانات الجائزة والحالة والفائز.</div>
                    </div>
                </div>
                <form action="{{ route('raffle-cards.export-pdf') }}" method="GET" class="form-grid">
                    <div>
                        <label for="pdf_from_number">رقم البطاقة من</label>
                        <input id="pdf_from_number" name="from_number" value="{{ old('from_number', '000000') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000000" dir="ltr" required>
                    </div>
                    <div>
                        <label for="pdf_to_number">رقم البطاقة إلى</label>
                        <input id="pdf_to_number" name="to_number" value="{{ old('to_number', '999999') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="999999" dir="ltr" required>
                    </div>
                    <button class="btn btn-primary" type="submit" style="grid-column:span 2">
                        <i class="ti ti-download"></i> تحميل ملف PDF
                    </button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <div class="panel-title"><i class="ti ti-list"></i> الأرقام الرابحة</div>
                        <button class="btn btn-danger" type="submit" form="winningCardsBulkDeleteForm" id="deleteSelectedWinningCardsBtn" disabled style="margin-top:10px">
                            <i class="ti ti-trash"></i> حذف المحدد
                        </button>
                    </div>
                    <form class="filters" method="GET">
                        <input name="search" value="{{ $search }}" placeholder="بحث برقم البطاقة أو الجائزة">
                        <select name="status" class="btn btn-soft" onchange="this.form.submit()">
                            <option value="all" @selected($status === 'all')>كل الحالات</option>
                            <option value="available" @selected($status === 'available')>غير مستخدمة</option>
                            <option value="used" @selected($status === 'used')>مستخدمة</option>
                        </select>
                        <button class="btn btn-soft" type="submit"><i class="ti ti-search"></i> بحث</button>
                    </form>
                </div>
                <form id="winningCardsBulkDeleteForm" action="{{ route('raffle-cards.bulk-destroy') }}" method="POST"
                    onsubmit="return confirm('حذف بطاقات الربح المحددة نهائيًا؟')">
                    @csrf
                    @method('DELETE')
                </form>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="select-cell">
                                    <input type="checkbox" id="selectAllWinningCards" title="تحديد الكل">
                                </th>
                                <th>البطاقة</th>
                                <th>الجائزة</th>
                                <th>الحالة</th>
                                <th>الفائز</th>
                                <th>تعديل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cards as $card)
                                <tr>
                                    <td class="select-cell">
                                        <input type="checkbox" name="cards[]" value="{{ $card->id }}" form="winningCardsBulkDeleteForm" data-winning-card-checkbox>
                                    </td>
                                    <td dir="ltr"><span class="tag">{{ $card->card_number }}</span></td>
                                    <td>
                                        <div class="prize">
                                            @if($card->prize_image)
                                                <img src="{{ asset($card->prize_image) }}" alt="{{ $card->prize_title }}">
                                            @endif
                                            <strong>{{ $card->prize_title }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        @if($card->used_at)
                                            <span class="tag red">مستخدمة</span>
                                        @else
                                            <span class="tag green">متاحة</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $card->used_customer_name ?: '-' }}
                                        @if($card->used_customer_whatsapp)
                                            <div dir="ltr" style="color:var(--muted)">{{ $card->used_customer_whatsapp }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('raffle-cards.update', $card) }}" method="POST" enctype="multipart/form-data" class="edit-box">
                                            @csrf
                                            @method('PUT')
                                            <input name="card_number" value="{{ $card->card_number }}" maxlength="6" pattern="\d{6}" dir="ltr" required>
                                            <input name="prize_title" value="{{ $card->prize_title }}" required>
                                            <label class="file-card" for="prize_image_{{ $card->id }}">
                                                <span data-file-label>تغيير الصورة</span>
                                                <i class="ti ti-photo-up"></i>
                                            </label>
                                            <input class="file-input" id="prize_image_{{ $card->id }}" name="prize_image" type="file" accept="image/*" data-file-input>
                                            <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($card->is_active)> نشطة</label>
                                            <div class="actions">
                                                <button class="btn btn-soft" type="submit"><i class="ti ti-device-floppy"></i></button>
                                            </div>
                                        </form>
                                        <form action="{{ route('raffle-cards.destroy', $card) }}" method="POST" onsubmit="return confirm('حذف بطاقة الربح؟')" style="margin-top:8px">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit"><i class="ti ti-trash"></i> حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">لا توجد بطاقات رابحة بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $cards->links() }}</div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title"><i class="ti ti-broadcast"></i> جوائز البثوث المباشرة</div>
                    <div class="live-actions">
                        <button class="btn btn-danger" type="submit" form="liveEntriesBulkDeleteForm" id="deleteSelectedLiveEntriesBtn" disabled>
                            <i class="ti ti-trash"></i> حذف المحدد
                        </button>
                        <button class="btn btn-primary" type="button" id="liveDrawPickBtn"
                            data-url="{{ route('raffle-cards.live-draw.random') }}"
                            data-token="{{ csrf_token() }}">
                            <i class="ti ti-confetti"></i> اختيار فائز عشوائي
                        </button>
                    </div>
                </div>
                <form id="liveEntriesBulkDeleteForm" action="{{ route('raffle-cards.live-draw.bulk-destroy') }}" method="POST"
                    onsubmit="return confirm('حذف الأرقام المحددة من سحب البث المباشر؟')">
                    @csrf
                    @method('DELETE')
                </form>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="select-cell">
                                    <input type="checkbox" id="selectAllLiveEntries" title="تحديد الكل">
                                </th>
                                <th>رقم البطاقة</th>
                                <th>العميل</th>
                                <th>الهاتف/واتساب</th>
                                <th>تاريخ التسجيل</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($liveEntries as $entry)
                                <tr>
                                    <td class="select-cell">
                                        <input type="checkbox" name="entries[]" value="{{ $entry->id }}" form="liveEntriesBulkDeleteForm" data-live-entry-checkbox>
                                    </td>
                                    <td dir="ltr"><span class="tag">{{ $entry->card_number }}</span></td>
                                    <td>{{ $entry->customer_name ?: '-' }}</td>
                                    <td dir="ltr">{{ $entry->customer_whatsapp ?: $entry->customer_phone ?: '-' }}</td>
                                    <td>{{ optional($entry->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <form action="{{ route('raffle-cards.live-draw.destroy', $entry) }}" method="POST" onsubmit="return confirm('حذف هذا الرقم من سحب البث المباشر؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit"><i class="ti ti-trash"></i> حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">لا توجد بطاقات بث مباشر مسجلة بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination">{{ $liveEntries->links() }}</div>
            </section>
        </div>
    </main>
    <div class="live-draw-modal" id="liveDrawModal" aria-hidden="true">
        <div class="live-draw-card">
            <button class="live-draw-close" type="button" id="liveDrawCloseBtn" aria-label="إغلاق">&times;</button>
            <div class="live-draw-kicker">Ozman Live Draw</div>
            <div class="live-draw-title" id="liveDrawTitle">مبروك الفوز</div>
            <div class="live-draw-number" id="liveDrawNumber">000000</div>
            <div class="live-draw-info" id="liveDrawInfo"></div>
            <div class="live-draw-error" id="liveDrawError" hidden></div>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-file-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const label = input.closest('div, form')?.querySelector(`label[for="${input.id}"] [data-file-label]`);
                if (label) {
                    label.textContent = input.files?.[0]?.name || 'اختر صورة الجائزة';
                }
            });
        });

        const liveDrawPickBtn = document.getElementById('liveDrawPickBtn');
        const liveDrawModal = document.getElementById('liveDrawModal');
        const liveDrawCloseBtn = document.getElementById('liveDrawCloseBtn');
        const liveDrawTitle = document.getElementById('liveDrawTitle');
        const liveDrawNumber = document.getElementById('liveDrawNumber');
        const liveDrawInfo = document.getElementById('liveDrawInfo');
        const liveDrawError = document.getElementById('liveDrawError');
        const selectAllLiveEntries = document.getElementById('selectAllLiveEntries');
        const liveEntryCheckboxes = Array.from(document.querySelectorAll('[data-live-entry-checkbox]'));
        const deleteSelectedLiveEntriesBtn = document.getElementById('deleteSelectedLiveEntriesBtn');
        const selectAllWinningCards = document.getElementById('selectAllWinningCards');
        const winningCardCheckboxes = Array.from(document.querySelectorAll('[data-winning-card-checkbox]'));
        const deleteSelectedWinningCardsBtn = document.getElementById('deleteSelectedWinningCardsBtn');

        function syncWinningCardBulkActions() {
            const checkedCount = winningCardCheckboxes.filter((checkbox) => checkbox.checked).length;
            if (deleteSelectedWinningCardsBtn) deleteSelectedWinningCardsBtn.disabled = checkedCount === 0;
            if (selectAllWinningCards) {
                selectAllWinningCards.checked = checkedCount > 0 && checkedCount === winningCardCheckboxes.length;
                selectAllWinningCards.indeterminate = checkedCount > 0 && checkedCount < winningCardCheckboxes.length;
            }
        }

        selectAllWinningCards?.addEventListener('change', () => {
            winningCardCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAllWinningCards.checked;
            });
            syncWinningCardBulkActions();
        });

        winningCardCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncWinningCardBulkActions);
        });

        syncWinningCardBulkActions();

        function syncLiveEntryBulkActions() {
            const checkedCount = liveEntryCheckboxes.filter((checkbox) => checkbox.checked).length;
            if (deleteSelectedLiveEntriesBtn) deleteSelectedLiveEntriesBtn.disabled = checkedCount === 0;
            if (selectAllLiveEntries) {
                selectAllLiveEntries.checked = checkedCount > 0 && checkedCount === liveEntryCheckboxes.length;
                selectAllLiveEntries.indeterminate = checkedCount > 0 && checkedCount < liveEntryCheckboxes.length;
            }
        }

        selectAllLiveEntries?.addEventListener('change', () => {
            liveEntryCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAllLiveEntries.checked;
            });
            syncLiveEntryBulkActions();
        });

        liveEntryCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncLiveEntryBulkActions);
        });

        syncLiveEntryBulkActions();

        function escapeLiveDrawText(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));
        }

        function openLiveDrawModal() {
            liveDrawModal?.classList.add('show');
            liveDrawModal?.setAttribute('aria-hidden', 'false');
        }

        function closeLiveDrawModal() {
            liveDrawModal?.classList.remove('show');
            liveDrawModal?.setAttribute('aria-hidden', 'true');
        }

        function randomSixDigits() {
            return String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
        }

        liveDrawPickBtn?.addEventListener('click', async () => {
            openLiveDrawModal();
            if (liveDrawTitle) liveDrawTitle.textContent = 'جاري اختيار الفائز...';
            if (liveDrawInfo) liveDrawInfo.innerHTML = '';
            if (liveDrawError) {
                liveDrawError.hidden = true;
                liveDrawError.textContent = '';
            }
            liveDrawNumber?.classList.add('spinning');

            const spinTimer = window.setInterval(() => {
                if (liveDrawNumber) liveDrawNumber.textContent = randomSixDigits();
            }, 70);

            liveDrawPickBtn.disabled = true;

            try {
                const response = await fetch(liveDrawPickBtn.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': liveDrawPickBtn.dataset.token || '',
                    },
                    body: JSON.stringify({}),
                });
                const payload = await response.json().catch(() => ({}));

                window.setTimeout(() => {
                    window.clearInterval(spinTimer);
                    liveDrawNumber?.classList.remove('spinning');

                    if (!response.ok || !payload.ok) {
                        if (liveDrawTitle) liveDrawTitle.textContent = 'لم يتم اختيار فائز';
                        if (liveDrawNumber) liveDrawNumber.textContent = '------';
                        if (liveDrawError) {
                            liveDrawError.hidden = false;
                            liveDrawError.textContent = payload.message || 'تعذر اختيار فائز الآن.';
                        }
                        return;
                    }

                    if (liveDrawTitle) liveDrawTitle.textContent = payload.title || 'مبروك الفوز';
                    if (liveDrawNumber) liveDrawNumber.textContent = payload.card_number || '------';
                    if (liveDrawInfo) {
                        liveDrawInfo.innerHTML = `
                            <div>الفائز: <strong>${escapeLiveDrawText(payload.customer_name || '-')}</strong></div>
                            <div dir="ltr">${escapeLiveDrawText(payload.customer_whatsapp || payload.customer_phone || '-')}</div>
                            <div>تاريخ التسجيل: <strong>${escapeLiveDrawText(payload.created_at || '-')}</strong></div>
                        `;
                    }
                }, 1100);
            } catch (error) {
                window.clearInterval(spinTimer);
                liveDrawNumber?.classList.remove('spinning');
                if (liveDrawTitle) liveDrawTitle.textContent = 'تعذر اختيار الفائز';
                if (liveDrawError) {
                    liveDrawError.hidden = false;
                    liveDrawError.textContent = 'راجع الاتصال وحاول مرة أخرى.';
                }
            } finally {
                window.setTimeout(() => {
                    liveDrawPickBtn.disabled = false;
                }, 1200);
            }
        });

        liveDrawCloseBtn?.addEventListener('click', closeLiveDrawModal);
        liveDrawModal?.addEventListener('click', (event) => {
            if (event.target === liveDrawModal) closeLiveDrawModal();
        });
    </script>
</body>

</html>
