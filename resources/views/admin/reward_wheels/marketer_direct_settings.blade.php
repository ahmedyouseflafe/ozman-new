<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إعداد العجلة المباشرة - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --yellow:#ffd60a; --danger:#ff3b30; --border:rgba(255,255,255,.1); --soft-border:rgba(0,229,255,.18); --text:#fff; --muted:rgba(255,255,255,.66); --panel:rgba(255,255,255,.055); }
        body { min-height:100vh; background:radial-gradient(circle at 18% 20%,rgba(112,0,255,.12),transparent 32%),radial-gradient(circle at 84% 8%,rgba(0,229,255,.12),transparent 30%),linear-gradient(180deg,#030303,#08020f); color:var(--text); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; direction:rtl; }
        .main { min-height:100vh; margin-right:245px; }
        .content { width:min(1180px,calc(100% - 68px)); margin:0 auto; padding:32px 0 54px; }
        .page-head { display:flex; justify-content:space-between; gap:18px; align-items:center; margin-bottom:22px; padding:24px; border:1px solid var(--soft-border); border-radius:28px; background:linear-gradient(135deg,rgba(0,229,255,.08),rgba(112,0,255,.08) 42%,rgba(255,255,255,.035)); box-shadow:0 22px 70px rgba(0,0,0,.34); }
        .kicker { color:var(--primary); font-size:13px; font-weight:900; margin-bottom:6px; }
        h1 { color:var(--primary); font-size:38px; line-height:1.15; font-weight:900; text-shadow:0 0 18px rgba(0,229,255,.42); }
        p { color:var(--muted); font-weight:800; margin-top:8px; }
        .direct-layout { display:grid; grid-template-columns:340px minmax(0,1fr); gap:18px; align-items:start; }
        .direct-side { position:sticky; top:108px; display:grid; gap:18px; }
        .panel { border:1px solid var(--border); background:linear-gradient(145deg,rgba(255,255,255,.07),rgba(255,255,255,.025)); border-radius:24px; padding:22px; box-shadow:0 18px 48px rgba(0,0,0,.34); }
        .panel-title { display:flex; align-items:center; justify-content:space-between; gap:12px; color:#fff; font-size:19px; font-weight:900; margin-bottom:16px; }
        .panel-title span { display:inline-flex; align-items:center; gap:9px; }
        .panel-title i { color:var(--primary); }
        .field { margin-bottom:14px; min-width:0; }
        label { display:block; color:var(--muted); font-size:13px; font-weight:900; margin-bottom:7px; }
        input, select { width:100%; min-height:46px; border:1px solid var(--border); background:rgba(255,255,255,.065); border-radius:14px; color:#fff; padding:0 13px; font-family:inherit; font-weight:900; outline:none; transition:border-color .2s ease, box-shadow .2s ease, background .2s ease; }
        input:focus, select:focus { border-color:rgba(0,229,255,.62); box-shadow:0 0 0 3px rgba(0,229,255,.1); background:rgba(255,255,255,.09); }
        select option { color:#111; }
        input[type="color"] { padding:4px; cursor:pointer; height:46px; }
        input[type="checkbox"] { width:18px; min-height:18px; accent-color:var(--primary); }
        input[type="file"] { padding:0; display:flex; align-items:center; color:var(--muted); overflow:hidden; }
        input[type="file"]::file-selector-button { min-height:44px; margin-inline-start:12px; border:0; border-inline-end:1px solid var(--soft-border); padding:0 16px; background:rgba(0,229,255,.13); color:var(--primary); font-family:inherit; font-weight:900; cursor:pointer; }
        .check { display:flex; align-items:center; gap:10px; color:#fff; font-weight:900; margin:0; }
        .switch-row { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px; border:1px solid var(--border); border-radius:18px; background:rgba(0,0,0,.22); }
        .switch-row small { display:block; color:var(--muted); font-size:11px; margin-top:4px; }
        .rows { display:grid; gap:14px; }
        .row-card { border:1px solid rgba(255,255,255,.09); background:linear-gradient(145deg,rgba(0,0,0,.42),rgba(255,255,255,.035)); border-radius:22px; padding:16px; box-shadow:inset 0 1px 0 rgba(255,255,255,.04); }
        .segment-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid rgba(255,255,255,.08); }
        .segment-index { display:inline-flex; align-items:center; gap:8px; color:var(--primary); font-size:13px; font-weight:900; }
        .segment-index b { width:28px; height:28px; border-radius:50%; display:grid; place-items:center; background:rgba(0,229,255,.12); border:1px solid var(--soft-border); }
        .row-grid { display:grid; grid-template-columns:1.15fr .9fr .65fr; gap:12px; align-items:end; }
        .row-grid.secondary { grid-template-columns:.9fr 1fr; }
        .gift-upload { margin-top:12px; padding:14px; border:1px dashed rgba(0,229,255,.24); border-radius:18px; background:rgba(0,229,255,.035); }
        .gift-upload.is-hidden { display:none; }
        .gift-preview { width:74px; height:74px; border-radius:16px; object-fit:cover; border:1px solid var(--border); margin-top:10px; background:rgba(255,255,255,.06); }
        .actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
        .form-actions { position:sticky; bottom:14px; z-index:5; display:flex; justify-content:flex-end; gap:10px; padding:14px; margin-top:18px; border:1px solid rgba(255,255,255,.08); border-radius:22px; background:rgba(5,5,8,.8); backdrop-filter:blur(16px); box-shadow:0 -12px 35px rgba(0,0,0,.28); }
        .btn { border:0; min-height:46px; border-radius:999px; padding:0 20px; display:inline-flex; align-items:center; justify-content:center; gap:8px; color:#001014; background:linear-gradient(135deg,var(--primary),var(--accent)); font-family:inherit; font-weight:900; cursor:pointer; text-decoration:none; }
        .btn.secondary { color:#fff; background:rgba(255,255,255,.08); border:1px solid var(--border); }
        .btn.danger { color:#fff; background:rgba(255,59,48,.14); border:1px solid rgba(255,59,48,.38); }
        .btn.small { min-height:38px; padding:0 14px; font-size:12px; }
        .notice { border:1px solid rgba(37,211,102,.28); color:var(--green); background:rgba(37,211,102,.1); border-radius:16px; padding:12px 14px; font-weight:900; margin-bottom:18px; }
        .errors { border:1px solid rgba(255,59,48,.32); color:#ffb8b3; background:rgba(255,59,48,.1); border-radius:16px; padding:12px 18px; margin-bottom:18px; font-weight:800; }
        .stat-card { display:grid; gap:8px; padding:16px; border-radius:20px; background:linear-gradient(145deg,rgba(0,229,255,.1),rgba(112,0,255,.08)); border:1px solid var(--soft-border); }
        .stat-card span { color:var(--muted); font-weight:900; font-size:12px; }
        .stat-card strong { color:var(--primary); font-size:30px; line-height:1; text-shadow:0 0 14px rgba(0,229,255,.42); }
        @media(max-width:1100px){ .direct-layout{grid-template-columns:1fr}.direct-side{position:static;grid-template-columns:repeat(2,minmax(0,1fr))} }
        @media(max-width:900px){ .main{margin-right:0}.content{width:calc(100% - 28px);padding:22px 0 94px}.page-head{flex-direction:column;align-items:stretch} }
        @media(max-width:700px){ .direct-side,.row-grid,.row-grid.secondary{grid-template-columns:1fr}.form-actions{justify-content:stretch}.form-actions .btn{flex:1} h1{font-size:30px} }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'إعداد العجلة المباشرة'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <div class="kicker">المسوّقة</div>
                        <h1>العجلة المباشرة</h1>
                        <p>عجلة بدون أسئلة. اضبطي الجوائز ومجموع ظهور الجوائز الفعالة يجب أن يساوي 20.</p>
                    </div>
                    <a class="btn secondary" href="{{ route('reward-wheels.marketer.direct.play') }}">
                        <i class="ti ti-player-play" aria-hidden="true"></i>
                        معاينة العجلة
                    </a>
                </header>

                @if(session('status'))
                    <div class="notice">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="errors">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('reward-wheels.marketer.direct.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @php
                        $segmentsDraft = old('segments', $wheel->segments->toArray());
                        $activeQuota = collect($segmentsDraft)
                            ->filter(fn($segment) => data_get($segment, 'is_active', true))
                            ->sum(fn($segment) => (int) data_get($segment, 'win_quota', 0));
                    @endphp

                    <div class="direct-layout">
                        <aside class="direct-side">
                            <section class="panel">
                                <h2 class="panel-title"><span><i class="ti ti-settings" aria-hidden="true"></i> الإعدادات</span></h2>
                                <div class="field">
                                    <label for="title">عنوان العجلة</label>
                                    <input id="title" name="title" value="{{ old('title', $wheel->title) }}" required>
                                </div>
                                <div class="switch-row">
                                    <span>
                                        <strong>العجلة فعالة</strong>
                                        <small>إظهار العجلة للمستخدمين المسموح لهم بالتشغيل</small>
                                    </span>
                                    <label class="check">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $wheel->is_active))>
                                    </label>
                                </div>
                            </section>

                            <section class="stat-card">
                                <span>مجموع فرص الجوائز الفعالة</span>
                                <strong>{{ $activeQuota }}/20</strong>
                                <p>لازم مجموع الظهور للجوائز الفعالة يساوي 20.</p>
                            </section>
                        </aside>

                        <section class="panel">
                            <h2 class="panel-title">
                                <span><i class="ti ti-disc" aria-hidden="true"></i> الجوائز</span>
                                <button type="button" class="btn secondary small" id="addSegment"><i class="ti ti-plus"></i> إضافة جائزة</button>
                            </h2>
                            <div class="rows" id="segmentsRows">
                                @foreach($segmentsDraft as $index => $segment)
                                    <div class="row-card segment-row">
                                        <div class="segment-head">
                                            <span class="segment-index"><b>{{ $index + 1 }}</b> جائزة</span>
                                            <label class="check">
                                                <input type="checkbox" name="segments[{{ $index }}][is_active]" value="1" @checked(data_get($segment, 'is_active', true))>
                                                فعالة
                                            </label>
                                        </div>

                                        <div class="row-grid">
                                            <div class="field">
                                                <label>اسم الجائزة</label>
                                                <input name="segments[{{ $index }}][label]" value="{{ data_get($segment, 'label') }}" required>
                                            </div>
                                            <div class="field">
                                                <label>النوع</label>
                                                <select class="discount-type-select" name="segments[{{ $index }}][discount_type]" required>
                                                    @foreach(['percent' => 'نسبة خصم', 'amount' => 'مبلغ ثابت', 'free_shipping' => 'توصيل مجاني', 'gift' => 'هدية'] as $type => $label)
                                                        <option value="{{ $type }}" @selected(data_get($segment, 'discount_type') === $type)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label>ظهور من 20</label>
                                                <input type="number" min="0" max="20" name="segments[{{ $index }}][win_quota]" value="{{ data_get($segment, 'win_quota', 1) }}">
                                            </div>
                                        </div>

                                        <div class="row-grid secondary">
                                            <div class="field">
                                                <label>القيمة</label>
                                                <input type="number" min="0" name="segments[{{ $index }}][discount_value]" value="{{ data_get($segment, 'discount_value') }}">
                                            </div>
                                            <div class="field">
                                                <label>اللون</label>
                                                <input type="color" name="segments[{{ $index }}][color]" value="{{ data_get($segment, 'color', '#00e5ff') }}" required>
                                            </div>
                                        </div>

                                        <input type="hidden" name="segments[{{ $index }}][existing_gift_image]" value="{{ data_get($segment, 'gift_image') }}">
                                        <div class="field gift-upload">
                                            <label>صورة الهدية</label>
                                            <input type="file" name="segments[{{ $index }}][gift_image]" accept="image/*">
                                            @if(data_get($segment, 'gift_image'))
                                                <img class="gift-preview" src="{{ asset(data_get($segment, 'gift_image')) }}" alt="صورة الهدية الحالية">
                                            @endif
                                        </div>

                                        <div class="actions"><button type="button" class="btn danger small remove-row"><i class="ti ti-trash"></i> حذف</button></div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn secondary" id="addSegmentBottom"><i class="ti ti-plus"></i> إضافة جائزة</button>
                                <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> حفظ</button>
                            </div>
                        </section>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const segmentsRows = document.getElementById('segmentsRows');

        function bindRemove(scope) {
            scope.querySelectorAll('.remove-row').forEach((button) => {
                button.onclick = () => button.closest('.row-card')?.remove();
            });
        }

        function bindGiftUploads(scope) {
            scope.querySelectorAll('.segment-row').forEach((row) => {
                const select = row.querySelector('.discount-type-select');
                const giftUpload = row.querySelector('.gift-upload');
                if (!select || !giftUpload) return;

                const toggle = () => giftUpload.classList.toggle('is-hidden', select.value !== 'gift');
                select.onchange = toggle;
                toggle();
            });
        }

        function addSegmentRow() {
            const index = segmentsRows.children.length;
            segmentsRows.insertAdjacentHTML('beforeend', `
                <div class="row-card segment-row">
                    <div class="segment-head">
                        <span class="segment-index"><b>${index + 1}</b> جائزة</span>
                        <label class="check"><input type="checkbox" name="segments[${index}][is_active]" value="1" checked> فعالة</label>
                    </div>
                    <div class="row-grid">
                        <div class="field"><label>اسم الجائزة</label><input name="segments[${index}][label]" required></div>
                        <div class="field"><label>النوع</label><select class="discount-type-select" name="segments[${index}][discount_type]" required><option value="percent">نسبة خصم</option><option value="amount">مبلغ ثابت</option><option value="free_shipping">توصيل مجاني</option><option value="gift">هدية</option></select></div>
                        <div class="field"><label>ظهور من 20</label><input type="number" min="0" max="20" name="segments[${index}][win_quota]" value="1"></div>
                    </div>
                    <div class="row-grid secondary">
                        <div class="field"><label>القيمة</label><input type="number" min="0" name="segments[${index}][discount_value]"></div>
                        <div class="field"><label>اللون</label><input type="color" name="segments[${index}][color]" value="#00e5ff" required></div>
                    </div>
                    <div class="field gift-upload"><label>صورة الهدية</label><input type="file" name="segments[${index}][gift_image]" accept="image/*"></div>
                    <div class="actions"><button type="button" class="btn danger small remove-row"><i class="ti ti-trash"></i> حذف</button></div>
                </div>`);
            bindRemove(segmentsRows);
            bindGiftUploads(segmentsRows);
        }

        document.getElementById('addSegment')?.addEventListener('click', addSegmentRow);
        document.getElementById('addSegmentBottom')?.addEventListener('click', addSegmentRow);

        segmentsRows?.addEventListener('click', () => {
            segmentsRows.querySelectorAll('.segment-index b').forEach((badge, index) => {
                badge.textContent = index + 1;
            });
        });

        bindRemove(document);
        bindGiftUploads(document);
    </script>
</body>
</html>
