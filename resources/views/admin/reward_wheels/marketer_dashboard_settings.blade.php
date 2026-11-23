<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إعداد عجلة المسوقة - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.reward_wheels.marketer_dashboard_settings')
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
