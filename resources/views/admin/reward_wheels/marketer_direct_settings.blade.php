<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>إعداد العجلة المباشرة - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.reward_wheels.marketer_direct_settings')
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
                                <strong>{{ $activeQuota }}</strong>
                                <p>الأرقام أوزان نسبية؛ كلما زاد الرقم زادت فرصة ظهور الجائزة.</p>
                            </section>
                        </aside>

                        <section class="panel">
                            <h2 class="panel-title">
                                <span><i class="ti ti-disc" aria-hidden="true"></i> الجوائز</span>
                                <button type="button" class="btn secondary small" id="addSegment"><i class="ti ti-plus"></i> إضافة جائزة</button>
                            </h2>
                            <div class="rows" id="segmentsRows">
                                @foreach($segmentsDraft as $index => $segment)
                                    <div class="row-card segment-row" data-segment-id="{{ data_get($segment, 'id') }}">
                                        <input type="hidden" name="segments[{{ $index }}][id]" value="{{ data_get($segment, 'id') }}">
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
                                                <label>عدد فرص الظهور</label>
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
        const wheelForm = segmentsRows?.closest('form');
        const draftKey = 'ozman:marketer-direct-wheel-draft';
        const segmentDeleteUrl = @json(route('reward-wheels.marketer.direct.segments.destroy', ['segment' => '__SEGMENT__']));
        const csrfToken = @json(csrf_token());
        const clearDraft = @json(session('status') !== null);
        let restoringDraft = false;

        if (clearDraft) localStorage.removeItem(draftKey);

        function reindexSegments() {
            Array.from(segmentsRows?.children || []).forEach((row, index) => {
                row.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/^segments\[\d+\]/, `segments[${index}]`);
                });

                const badge = row.querySelector('.segment-index b');
                if (badge) badge.textContent = index + 1;
            });
        }

        function bindRemove(scope) {
            scope.querySelectorAll('.remove-row').forEach((button) => {
                button.onclick = async () => {
                    if (segmentsRows.children.length <= 1) {
                        alert('يجب أن تبقى جائزة واحدة على الأقل.');
                        return;
                    }

                    const row = button.closest('.row-card');
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
                            if (!response.ok) throw new Error(result.message || 'تعذر حذف الجائزة.');
                        } catch (error) {
                            button.disabled = false;
                            alert(error.message || 'تعذر حذف الجائزة.');
                            return;
                        }
                    }

                    row?.remove();
                    reindexSegments();
                    saveDraft();
                };
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
                        <div class="field"><label>عدد فرص الظهور</label><input type="number" min="0" max="20" name="segments[${index}][win_quota]" value="1"></div>
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
            reindexSegments();
            saveDraft();
        }

        function segmentDraft(row) {
            const data = {};
            row.querySelectorAll('[name]').forEach((field) => {
                if (field.type === 'file') return;
                const key = field.name.match(/\[([^\]]+)\]$/)?.[1];
                if (!key) return;
                data[key] = field.type === 'checkbox' ? field.checked : field.value;
            });
            return data;
        }

        function saveDraft() {
            if (restoringDraft || !wheelForm) return;
            localStorage.setItem(draftKey, JSON.stringify({
                title: wheelForm.querySelector('[name="title"]')?.value || '',
                is_active: wheelForm.querySelector('[name="is_active"]')?.checked || false,
                segments: Array.from(segmentsRows.children).map(segmentDraft),
            }));
        }

        function restoreDraft() {
            if (clearDraft) return;

            let draft = null;
            try {
                draft = JSON.parse(localStorage.getItem(draftKey) || 'null');
            } catch (_) {
                localStorage.removeItem(draftKey);
            }
            if (!draft || !Array.isArray(draft.segments) || !draft.segments.length) return;

            restoringDraft = true;
            while (segmentsRows.children.length < draft.segments.length) addSegmentRow();
            while (segmentsRows.children.length > draft.segments.length) segmentsRows.lastElementChild?.remove();

            Array.from(segmentsRows.children).forEach((row, index) => {
                const segment = draft.segments[index] || {};
                row.querySelectorAll('[name]').forEach((field) => {
                    if (field.type === 'file') return;
                    const key = field.name.match(/\[([^\]]+)\]$/)?.[1];
                    if (!key || !(key in segment)) return;
                    if (field.type === 'checkbox') field.checked = Boolean(segment[key]);
                    else field.value = segment[key] ?? '';
                });
            });

            const title = wheelForm.querySelector('[name="title"]');
            const active = wheelForm.querySelector('[name="is_active"]');
            if (title) title.value = draft.title || title.value;
            if (active) active.checked = Boolean(draft.is_active);
            reindexSegments();
            bindRemove(segmentsRows);
            bindGiftUploads(segmentsRows);
            restoringDraft = false;
        }

        document.getElementById('addSegment')?.addEventListener('click', addSegmentRow);
        document.getElementById('addSegmentBottom')?.addEventListener('click', addSegmentRow);

        segmentsRows?.addEventListener('click', () => {
            reindexSegments();
        });

        document.querySelector('form')?.addEventListener('submit', reindexSegments);
        wheelForm?.addEventListener('input', saveDraft);
        wheelForm?.addEventListener('change', saveDraft);

        bindRemove(document);
        bindGiftUploads(document);
        restoreDraft();
    </script>
</body>
</html>
