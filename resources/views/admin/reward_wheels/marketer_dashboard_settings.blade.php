<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إعداد عجلة المسوقة - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --yellow:#ffd60a; --danger:#ff3b30; --bg:#050505; --border:rgba(255,255,255,.1); --text:#fff; --muted:rgba(255,255,255,.66); }
        body { min-height:100vh; background:linear-gradient(180deg,#030303,#08020f); color:var(--text); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; direction:rtl; }
        .main { min-height:100vh; margin-right:245px; }
        .content { padding:28px 34px 46px; }
        .page-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-end; margin-bottom:22px; }
        .kicker { color:var(--primary); font-size:13px; font-weight:900; margin-bottom:6px; }
        h1 { color:var(--primary); font-size:34px; line-height:1.15; font-weight:900; text-shadow:0 0 18px rgba(0,229,255,.42); }
        p { color:var(--muted); font-weight:700; margin-top:8px; }
        .grid { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(340px,.9fr); gap:18px; align-items:start; }
        .panel { border:1px solid var(--border); background:linear-gradient(145deg,rgba(255,255,255,.07),rgba(255,255,255,.025)); border-radius:24px; padding:22px; box-shadow:0 18px 48px rgba(0,0,0,.34); }
        .panel + .panel { margin-top:18px; }
        .panel-title { display:flex; align-items:center; gap:9px; color:#fff; font-size:19px; font-weight:900; margin-bottom:16px; }
        .panel-title i { color:var(--primary); }
        .field { margin-bottom:14px; }
        label { display:block; color:var(--muted); font-size:13px; font-weight:900; margin-bottom:7px; }
        input, select { width:100%; min-height:44px; border:1px solid var(--border); background:rgba(255,255,255,.06); border-radius:14px; color:#fff; padding:0 13px; font-family:inherit; font-weight:800; outline:none; }
        select option { color:#111; }
        input[type="color"] { padding:4px; cursor:pointer; }
        input[type="checkbox"] { width:18px; min-height:18px; accent-color:var(--primary); }
        .check { display:flex; align-items:center; gap:10px; color:#fff; font-weight:900; }
        .rows { display:grid; gap:12px; }
        .row-card { border:1px solid var(--border); background:rgba(0,0,0,.22); border-radius:18px; padding:14px; }
        .row-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .option-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin-top:2px; }
        .gift-upload { margin-top:12px; }
        .gift-upload.is-hidden { display:none; }
        .gift-preview { width:74px; height:74px; border-radius:16px; object-fit:cover; border:1px solid var(--border); margin-top:8px; background:rgba(255,255,255,.06); }
        .actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
        .btn { border:0; min-height:44px; border-radius:999px; padding:0 18px; display:inline-flex; align-items:center; gap:8px; color:#001014; background:linear-gradient(135deg,var(--primary),var(--accent)); font-family:inherit; font-weight:900; cursor:pointer; text-decoration:none; }
        .btn.secondary { color:#fff; background:rgba(255,255,255,.08); border:1px solid var(--border); }
        .btn.danger { color:#fff; background:rgba(255,59,48,.14); border:1px solid rgba(255,59,48,.38); }
        .notice { border:1px solid rgba(37,211,102,.28); color:var(--green); background:rgba(37,211,102,.1); border-radius:16px; padding:12px 14px; font-weight:900; margin-bottom:18px; }
        .errors { border:1px solid rgba(255,59,48,.32); color:#ffb8b3; background:rgba(255,59,48,.1); border-radius:16px; padding:12px 18px; margin-bottom:18px; font-weight:800; }
        .preview-wheel { width:min(100%,360px); aspect-ratio:1; margin:8px auto 0; border-radius:50%; border:10px solid #050505; box-shadow:0 0 32px rgba(0,229,255,.22); background:conic-gradient(#00e5ff 0 25%,#7000ff 0 50%,#25d366 0 75%,#ffd60a 0 100%); }
        @media(max-width:1000px){ .main{margin-right:0}.grid{grid-template-columns:1fr}.content{padding:22px 16px 36px}.page-head{flex-direction:column;align-items:stretch} }
        @media(max-width:640px){ .row-grid{grid-template-columns:1fr} h1{font-size:28px} }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'إعداد عجلة المسوقة'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <div class="kicker">الداش بورد</div>
                        <h1>عجلة المسوقة</h1>
                        <p>هذه العجلة داخل لوحة التحكم فقط، وتفتح للمسوّقة بعد إجابة الأسئلة النشطة.</p>
                    </div>
                    <a class="btn secondary" href="{{ route('reward-wheels.marketer.play') }}">
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

                <form method="POST" action="{{ route('reward-wheels.marketer.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="grid">
                        <section>
                            <div class="panel">
                                <h2 class="panel-title"><i class="ti ti-settings" aria-hidden="true"></i> الإعدادات</h2>
                                <div class="field">
                                    <label for="title">عنوان العجلة</label>
                                    <input id="title" name="title" value="{{ old('title', $wheel->title) }}" required>
                                </div>
                                <label class="check">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $wheel->is_active))>
                                    العجلة فعالة
                                </label>
                            </div>

                            <div class="panel">
                                <h2 class="panel-title"><i class="ti ti-help-circle" aria-hidden="true"></i> أسئلة فتح العجلة</h2>
                                <div class="rows" id="questionsRows">
                                    @foreach(old('questions', $wheel->questions->toArray()) as $index => $question)
                                        @php
                                            $questionOptions = collect(data_get($question, 'options', []))->filter()->values();

                                            if ($questionOptions->isEmpty() && data_get($question, 'answer')) {
                                                $questionOptions = collect([data_get($question, 'answer')]);
                                            }

                                            $questionOptions = $questionOptions->pad(4, '')->take(4)->values();
                                        @endphp
                                        <div class="row-card question-row">
                                            <div class="row-grid">
                                                <div class="field">
                                                    <label>السؤال</label>
                                                    <input name="questions[{{ $index }}][question]" value="{{ data_get($question, 'question') }}" required>
                                                </div>
                                                <div class="field">
                                                    <label>الإجابة الصحيحة</label>
                                                    <select name="questions[{{ $index }}][answer]" class="answer-select">
                                                        @foreach($questionOptions as $option)
                                                            @if($option !== '')
                                                                <option value="{{ $option }}" @selected(data_get($question, 'answer') === $option)>{{ $option }}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="field">
                                                <label>خيارات الإجابة</label>
                                                <div class="option-grid">
                                                    @foreach($questionOptions as $optionIndex => $option)
                                                        <input class="question-option-input" name="questions[{{ $index }}][options][{{ $optionIndex }}]" value="{{ $option }}" placeholder="خيار {{ $optionIndex + 1 }}">
                                                    @endforeach
                                                </div>
                                            </div>
                                            <label class="check">
                                                <input type="checkbox" name="questions[{{ $index }}][is_active]" value="1" @checked(data_get($question, 'is_active', true))>
                                                سؤال فعال
                                            </label>
                                            <div class="actions"><button type="button" class="btn danger remove-row"><i class="ti ti-trash"></i> حذف</button></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="actions"><button type="button" class="btn secondary" id="addQuestion"><i class="ti ti-plus"></i> إضافة سؤال</button></div>
                            </div>
                        </section>

                        <aside>
                            <div class="panel">
                                <h2 class="panel-title"><i class="ti ti-disc" aria-hidden="true"></i> شرائح الجوائز</h2>
                                <div class="rows" id="segmentsRows">
                                    @foreach(old('segments', $wheel->segments->toArray()) as $index => $segment)
                                        <div class="row-card segment-row" data-segment-id="{{ data_get($segment, 'id') }}">
                                            <input type="hidden" name="segments[{{ $index }}][id]" value="{{ data_get($segment, 'id') }}">
                                            <div class="row-grid">
                                                <div class="field">
                                                    <label>اسم الشريحة</label>
                                                    <input name="segments[{{ $index }}][label]" value="{{ data_get($segment, 'label') }}" required>
                                                </div>
                                                <div class="field">
                                                    <label>نوع الجائزة</label>
                                                    <select class="discount-type-select" name="segments[{{ $index }}][discount_type]" required>
                                                        @foreach(['percent' => 'نسبة خصم', 'amount' => 'مبلغ ثابت', 'free_shipping' => 'توصيل مجاني', 'gift' => 'هدية'] as $type => $label)
                                                            <option value="{{ $type }}" @selected(data_get($segment, 'discount_type') === $type)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="field">
                                                    <label>القيمة</label>
                                                    <input type="number" min="0" name="segments[{{ $index }}][discount_value]" value="{{ data_get($segment, 'discount_value') }}">
                                                </div>
                                                <div class="field">
                                                    <label>عدد فرص الظهور</label>
                                                    <input type="number" min="0" max="20" name="segments[{{ $index }}][win_quota]" value="{{ data_get($segment, 'win_quota', 1) }}">
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
                                            <label class="check">
                                                <input type="checkbox" name="segments[{{ $index }}][is_active]" value="1" @checked(data_get($segment, 'is_active', true))>
                                                شريحة فعالة
                                            </label>
                                            <div class="actions"><button type="button" class="btn danger remove-row"><i class="ti ti-trash"></i> حذف</button></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="actions">
                                    <button type="button" class="btn secondary" id="addSegment"><i class="ti ti-plus"></i> إضافة شريحة</button>
                                    <button type="submit" class="btn"><i class="ti ti-device-floppy"></i> حفظ</button>
                                </div>
                                <div class="preview-wheel" aria-hidden="true"></div>
                            </div>
                        </aside>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const questionsRows = document.getElementById('questionsRows');
        const segmentsRows = document.getElementById('segmentsRows');
        const segmentDeleteUrl = @json(route('reward-wheels.marketer.segments.destroy', ['segment' => '__SEGMENT__']));
        const csrfToken = @json(csrf_token());

        function reindexRows(container, prefix) {
            Array.from(container?.children || []).forEach((row, index) => {
                row.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(new RegExp(`^${prefix}\\[\\d+\\]`), `${prefix}[${index}]`);
                });
            });
        }

        function bindRemove(scope) {
            scope.querySelectorAll('.remove-row').forEach((button) => {
                button.onclick = async () => {
                    const row = button.closest('.row-card');
                    const container = row?.parentElement;

                    if (container === segmentsRows) {
                        if (segmentsRows.children.length <= 1) {
                            alert('يجب أن تبقى شريحة واحدة على الأقل.');
                            return;
                        }

                        const segmentId = row?.dataset.segmentId;
                        if (segmentId) {
                            button.disabled = true;
                            try {
                                const response = await fetch(segmentDeleteUrl.replace('__SEGMENT__', segmentId), {
                                    method: 'DELETE',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                });
                                const result = await response.json().catch(() => ({}));
                                if (!response.ok) throw new Error(result.message || 'تعذر حذف الشريحة.');
                            } catch (error) {
                                button.disabled = false;
                                alert(error.message || 'تعذر حذف الشريحة.');
                                return;
                            }
                        }
                    }

                    row?.remove();

                    if (container === questionsRows) reindexRows(questionsRows, 'questions');
                    if (container === segmentsRows) reindexRows(segmentsRows, 'segments');
                };
            });
        }

        function syncAnswerSelect(row) {
            const select = row.querySelector('.answer-select');
            if (!select) return;

            const previousValue = select.value;
            const options = Array.from(row.querySelectorAll('.question-option-input'))
                .map((input) => input.value.trim())
                .filter(Boolean);

            select.innerHTML = '';
            options.forEach((option) => {
                const optionElement = document.createElement('option');
                optionElement.value = option;
                optionElement.textContent = option;
                select.appendChild(optionElement);
            });

            if (options.includes(previousValue)) {
                select.value = previousValue;
            }
        }

        function bindQuestionOptions(scope) {
            scope.querySelectorAll('.question-row').forEach((row) => {
                row.querySelectorAll('.question-option-input').forEach((input) => {
                    input.oninput = () => syncAnswerSelect(row);
                });
                syncAnswerSelect(row);
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

        document.getElementById('addQuestion')?.addEventListener('click', () => {
            const index = questionsRows.children.length;
            questionsRows.insertAdjacentHTML('beforeend', `
                <div class="row-card question-row">
                    <div class="row-grid">
                        <div class="field"><label>السؤال</label><input name="questions[${index}][question]" required></div>
                        <div class="field"><label>الإجابة الصحيحة</label><select name="questions[${index}][answer]" class="answer-select"></select></div>
                    </div>
                    <div class="field">
                        <label>خيارات الإجابة</label>
                        <div class="option-grid">
                            <input class="question-option-input" name="questions[${index}][options][0]" placeholder="خيار 1">
                            <input class="question-option-input" name="questions[${index}][options][1]" placeholder="خيار 2">
                            <input class="question-option-input" name="questions[${index}][options][2]" placeholder="خيار 3">
                            <input class="question-option-input" name="questions[${index}][options][3]" placeholder="خيار 4">
                        </div>
                    </div>
                    <label class="check"><input type="checkbox" name="questions[${index}][is_active]" value="1" checked> سؤال فعال</label>
                    <div class="actions"><button type="button" class="btn danger remove-row"><i class="ti ti-trash"></i> حذف</button></div>
                </div>`);
            bindRemove(questionsRows);
            bindQuestionOptions(questionsRows);
            reindexRows(questionsRows, 'questions');
        });

        document.getElementById('addSegment')?.addEventListener('click', () => {
            const index = segmentsRows.children.length;
            segmentsRows.insertAdjacentHTML('beforeend', `
                <div class="row-card segment-row">
                    <div class="row-grid">
                        <div class="field"><label>اسم الشريحة</label><input name="segments[${index}][label]" required></div>
                        <div class="field"><label>نوع الجائزة</label><select class="discount-type-select" name="segments[${index}][discount_type]" required><option value="percent">نسبة خصم</option><option value="amount">مبلغ ثابت</option><option value="free_shipping">توصيل مجاني</option><option value="gift">هدية</option></select></div>
                        <div class="field"><label>القيمة</label><input type="number" min="0" name="segments[${index}][discount_value]"></div>
                        <div class="field"><label>عدد فرص الظهور</label><input type="number" min="0" max="20" name="segments[${index}][win_quota]" value="1"></div>
                        <div class="field"><label>اللون</label><input type="color" name="segments[${index}][color]" value="#00e5ff" required></div>
                    </div>
                    <div class="field gift-upload"><label>صورة الهدية</label><input type="file" name="segments[${index}][gift_image]" accept="image/*"></div>
                    <label class="check"><input type="checkbox" name="segments[${index}][is_active]" value="1" checked> شريحة فعالة</label>
                    <div class="actions"><button type="button" class="btn danger remove-row"><i class="ti ti-trash"></i> حذف</button></div>
                </div>`);
            bindRemove(segmentsRows);
            bindGiftUploads(segmentsRows);
            reindexRows(segmentsRows, 'segments');
        });

        document.querySelector('form')?.addEventListener('submit', () => {
            reindexRows(questionsRows, 'questions');
            reindexRows(segmentsRows, 'segments');
        });

        bindRemove(document);
        bindQuestionOptions(document);
        bindGiftUploads(document);
    </script>
</body>
</html>
