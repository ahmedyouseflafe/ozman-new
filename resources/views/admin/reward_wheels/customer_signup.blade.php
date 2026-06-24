<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>عجلة خصومات العملاء - Ozman</title>
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
            --yellow: #ffd60a;
            --danger: #ff3b30;
            --bg: #050505;
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .64);
            --dim: rgba(255, 255, 255, .42);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 15% 14%, rgba(112, 0, 255, .14), transparent 29%),
                radial-gradient(circle at 78% 8%, rgba(0, 229, 255, .14), transparent 34%),
                linear-gradient(180deg, #030303 0%, #050505 52%, #08020f 100%);
            color: var(--text);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, .026) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .026) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 82%);
        }

        .shell { min-height: 100vh; }
        .main { min-height: 100vh; margin-right: 245px; position: relative; z-index: 1; }
        .content { padding: 28px 34px 46px; max-width: 1500px; margin: 0 auto; }

        .hero-strip {
            min-height: 136px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: linear-gradient(90deg, rgba(0, 229, 255, .08), rgba(255, 255, 255, .035), rgba(112, 0, 255, .08));
            backdrop-filter: blur(18px);
            overflow: hidden;
            display: flex;
            align-items: center;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .42), inset 0 0 45px rgba(0, 229, 255, .035);
            margin-bottom: 28px;
        }

        .ticker {
            display: flex;
            gap: 54px;
            width: max-content;
            white-space: nowrap;
            animation: slideRtl 24s linear infinite;
            color: var(--primary);
            font-size: 18px;
            font-weight: 900;
            text-shadow: 0 0 14px rgba(0, 229, 255, .55);
        }

        @keyframes slideRtl {
            from { transform: translateX(0); }
            to { transform: translateX(50%); }
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 22px;
        }

        .page-kicker {
            color: var(--primary);
            font-size: 13px;
            font-weight: 900;
            text-shadow: 0 0 12px rgba(0, 229, 255, .5);
            margin-bottom: 6px;
        }

        h1 {
            font-size: 34px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--primary);
            text-shadow: 0 0 20px rgba(0, 229, 255, .42);
        }

        .page-head p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .save-btn,
        .add-btn,
        .remove-btn {
            border: 0;
            min-height: 44px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 18px;
            font-family: inherit;
            font-weight: 900;
            cursor: pointer;
            transition: .25s ease;
        }

        .save-btn {
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            box-shadow: 0 0 22px rgba(0, 229, 255, .34);
        }

        .add-btn {
            color: var(--primary);
            background: rgba(0, 229, 255, .1);
            border: 1px solid rgba(0, 229, 255, .34);
        }

        .remove-btn {
            width: 38px;
            height: 38px;
            min-height: 38px;
            padding: 0;
            color: #ff8b8b;
            background: rgba(255, 59, 48, .08);
            border: 1px solid rgba(255, 59, 48, .22);
        }

        .save-btn:hover,
        .add-btn:hover,
        .remove-btn:hover {
            transform: translateY(-2px);
        }

        .status-alert {
            border: 1px solid rgba(37, 211, 102, .3);
            color: var(--green);
            background: rgba(37, 211, 102, .08);
            border-radius: 18px;
            padding: 13px 16px;
            margin-bottom: 18px;
            font-weight: 900;
        }

        .error-box {
            border: 1px solid rgba(255, 59, 48, .28);
            color: #ff9d9d;
            background: rgba(255, 59, 48, .08);
            border-radius: 18px;
            padding: 13px 16px;
            margin-bottom: 18px;
            font-weight: 800;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
            gap: 22px;
            align-items: start;
        }

        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 26px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
            padding: 24px;
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
        }

        .panel-title {
            color: #fff;
            font-size: 19px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title i {
            color: var(--primary);
            filter: drop-shadow(0 0 10px rgba(0, 229, 255, .55));
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 14px;
            align-items: end;
            margin-bottom: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field span,
        .switch span {
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
            font-weight: 900;
        }

        .field input,
        .field select {
            width: 100%;
            height: 44px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 14px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            padding: 0 13px;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .field select option {
            color: #111;
            background: #fff;
        }

        .switch {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 0 13px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(255, 255, 255, .04);
            cursor: pointer;
        }

        .switch input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .segments-list {
            display: grid;
            gap: 12px;
        }

        .segment-row {
            display: grid;
            grid-template-columns: 1.25fr .75fr .8fr 58px 112px 42px;
            gap: 10px;
            align-items: end;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            padding: 13px;
            background: rgba(0, 0, 0, .18);
        }

        .segment-color input {
            padding: 4px;
            cursor: pointer;
        }

        .preview-title {
            text-align: center;
            color: var(--primary);
            font-size: 22px;
            font-weight: 900;
            text-shadow: 0 0 18px rgba(0, 229, 255, .4);
            margin-bottom: 18px;
        }

        .wheel-preview-wrap {
            display: grid;
            place-items: center;
            padding: 10px 0 18px;
        }

        .wheel-preview {
            width: min(360px, 76vw);
            aspect-ratio: 1;
            border-radius: 50%;
            border: 6px solid #050505;
            outline: 2px solid rgba(0, 229, 255, .5);
            position: relative;
            overflow: hidden;
            background: conic-gradient(var(--wheel-gradient, #00e5ff 0deg 90deg, #7000ff 90deg 180deg, #25d366 180deg 270deg, #ffd60a 270deg 360deg));
            box-shadow: 0 0 34px rgba(0, 229, 255, .24);
        }

        .wheel-preview-labels {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }

        .wheel-preview-label {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 104px;
            min-height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 999px;
            color: #fff;
            background: rgba(0, 0, 0, .28);
            border: 1px solid rgba(255, 255, 255, .16);
            font-size: 15px;
            line-height: 1.25;
            font-weight: 900;
            text-align: center;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .75);
            box-shadow: 0 0 18px rgba(0, 0, 0, .18);
            transform:
                translate(-50%, -50%)
                rotate(var(--angle))
                translateY(-116px)
                rotate(calc(var(--angle) * -1));
        }

        .wheel-preview::after {
            content: '';
            position: absolute;
            inset: 38%;
            border-radius: 50%;
            background: #050505;
            border: 2px solid rgba(255, 255, 255, .14);
            box-shadow: 0 0 20px rgba(0, 0, 0, .45);
        }

        .wheel-pointer {
            width: 0;
            height: 0;
            border-inline: 16px solid transparent;
            border-top: 28px solid var(--primary);
            filter: drop-shadow(0 0 10px rgba(0, 229, 255, .65));
            margin-bottom: -6px;
            z-index: 2;
        }

        .preview-segments {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .preview-segment {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 14px;
            padding: 10px 12px;
            color: rgba(255, 255, 255, .74);
            font-weight: 800;
            background: rgba(0, 0, 0, .18);
        }

        .preview-dot {
            width: 13px;
            height: 13px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 7px;
            box-shadow: 0 0 10px currentColor;
        }

        @media(max-width: 1180px) {
            .layout-grid { grid-template-columns: 1fr; }
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
        }

        @media(max-width: 760px) {
            .content { padding: 20px 16px 34px; }
            .page-head,
            .panel-head,
            .form-grid {
                align-items: stretch;
                grid-template-columns: 1fr;
                flex-direction: column;
            }
            .segment-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            <div class="content">
                <div class="hero-strip">
                    <div class="ticker">
                        <span>عجلات الربح والخصومات</span>
                        <span>العجلة الأولى: خصومات أول تسجيل للعميل</span>
                        <span>تحكم بالعنوان والجوائز داخل العجلة</span>
                        <span>عجلات الربح والخصومات</span>
                    </div>
                </div>

                <div class="page-head">
                    <div>
                        <div class="page-kicker">عجلات الربح</div>
                        <h1>عجلة خصومات العملاء</h1>
                        <p>هذه العجلة مخصصة لأول تسجيل كعميل على الموقع. أضف العنوان والخصومات التي ستظهر داخل العجلة.</p>
                    </div>
                    <button class="save-btn" type="submit" form="wheelForm">
                        <i class="ti ti-device-floppy"></i>
                        حفظ العجلة
                    </button>
                </div>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="error-box">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form id="wheelForm" method="POST" action="{{ route('reward-wheels.customer-signup.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="layout-grid">
                        <section class="panel">
                            <div class="panel-head">
                                <div class="panel-title">
                                    <i class="ti ti-settings"></i>
                                    بيانات العجلة
                                </div>
                                <button class="add-btn" type="button" id="addSegmentBtn">
                                    <i class="ti ti-plus"></i>
                                    إضافة خصم
                                </button>
                            </div>

                            <div class="form-grid">
                                <label class="field">
                                    <span>عنوان العجلة</span>
                                    <input type="text" name="title" id="wheelTitleInput" value="{{ old('title', $wheel->title) }}" required>
                                </label>

                                <label class="switch">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $wheel->is_active))>
                                    <span>العجلة مفعلة</span>
                                </label>
                            </div>

                            <div class="segments-list" id="segmentsList">
                                @php
                                    $oldSegments = old('segments');
                                    $segments = $oldSegments
                                        ? collect($oldSegments)
                                        : $wheel->segments->map(fn($segment) => [
                                            'label' => $segment->label,
                                            'discount_value' => $segment->discount_value,
                                            'discount_type' => $segment->discount_type,
                                            'color' => $segment->color,
                                            'is_active' => $segment->is_active,
                                        ]);
                                @endphp

                                @foreach($segments as $segment)
                                    <div class="segment-row" data-segment-row>
                                        <label class="field">
                                            <span>النص داخل العجلة</span>
                                            <input type="text" data-name="label" value="{{ $segment['label'] ?? '' }}" required>
                                        </label>

                                        <label class="field">
                                            <span>قيمة الخصم</span>
                                            <input type="number" min="0" max="100000" data-name="discount_value" value="{{ $segment['discount_value'] ?? '' }}" placeholder="10">
                                        </label>

                                        <label class="field">
                                            <span>نوع الجائزة</span>
                                            <select data-name="discount_type" required>
                                                <option value="percent" @selected(($segment['discount_type'] ?? '') === 'percent')>نسبة %</option>
                                                <option value="amount" @selected(($segment['discount_type'] ?? '') === 'amount')>مبلغ ثابت</option>
                                                <option value="free_shipping" @selected(($segment['discount_type'] ?? '') === 'free_shipping')>توصيل مجاني</option>
                                                <option value="gift" @selected(($segment['discount_type'] ?? '') === 'gift')>هدية</option>
                                            </select>
                                        </label>

                                        <label class="field segment-color">
                                            <span>اللون</span>
                                            <input type="color" data-name="color" value="{{ $segment['color'] ?? '#00e5ff' }}" required>
                                        </label>

                                        <label class="switch">
                                            <input type="checkbox" data-name="is_active" value="1" @checked((bool) ($segment['is_active'] ?? false))>
                                            <span>فعّال</span>
                                        </label>

                                        <button class="remove-btn" type="button" data-remove-segment title="حذف">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <aside class="panel">
                            <div class="panel-head">
                                <div class="panel-title">
                                    <i class="ti ti-disc"></i>
                                    معاينة العجلة
                                </div>
                            </div>

                            <div class="preview-title" id="previewTitle">{{ old('title', $wheel->title) }}</div>
                            <div class="wheel-preview-wrap">
                                <div class="wheel-pointer"></div>
                                <div class="wheel-preview" id="wheelPreview">
                                    <div class="wheel-preview-labels" id="wheelPreviewLabels"></div>
                                </div>
                            </div>
                            <div class="preview-segments" id="previewSegments"></div>
                        </aside>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <template id="segmentTemplate">
        <div class="segment-row" data-segment-row>
            <label class="field">
                <span>النص داخل العجلة</span>
                <input type="text" data-name="label" value="خصم جديد" required>
            </label>

            <label class="field">
                <span>قيمة الخصم</span>
                <input type="number" min="0" max="100000" data-name="discount_value" value="5" placeholder="10">
            </label>

            <label class="field">
                <span>نوع الجائزة</span>
                <select data-name="discount_type" required>
                    <option value="percent">نسبة %</option>
                    <option value="amount">مبلغ ثابت</option>
                    <option value="free_shipping">توصيل مجاني</option>
                    <option value="gift">هدية</option>
                </select>
            </label>

            <label class="field segment-color">
                <span>اللون</span>
                <input type="color" data-name="color" value="#00e5ff" required>
            </label>

            <label class="switch">
                <input type="checkbox" data-name="is_active" value="1" checked>
                <span>فعّال</span>
            </label>

            <button class="remove-btn" type="button" data-remove-segment title="حذف">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </template>

    <script>
        const segmentsList = document.getElementById('segmentsList');
        const segmentTemplate = document.getElementById('segmentTemplate');
        const addSegmentBtn = document.getElementById('addSegmentBtn');
        const wheelTitleInput = document.getElementById('wheelTitleInput');
        const previewTitle = document.getElementById('previewTitle');
        const wheelPreview = document.getElementById('wheelPreview');
        const wheelPreviewLabels = document.getElementById('wheelPreviewLabels');
        const previewSegments = document.getElementById('previewSegments');

        function rows() {
            return Array.from(segmentsList.querySelectorAll('[data-segment-row]'));
        }

        function reindexSegments() {
            rows().forEach((row, index) => {
                row.querySelectorAll('[data-name]').forEach((field) => {
                    const name = field.dataset.name;
                    field.name = `segments[${index}][${name}]`;
                });
            });
        }

        function activeSegments() {
            return rows()
                .map((row) => ({
                    label: row.querySelector('[data-name="label"]')?.value || '',
                    color: row.querySelector('[data-name="color"]')?.value || '#00e5ff',
                    isActive: row.querySelector('[data-name="is_active"]')?.checked ?? false,
                }))
                .filter((segment) => segment.isActive && segment.label.trim() !== '');
        }

        function updatePreview() {
            previewTitle.textContent = wheelTitleInput.value || 'عنوان العجلة';

            const segments = activeSegments();
            if (segments.length === 0) {
                wheelPreview.style.setProperty('--wheel-gradient', '#20242a 0deg 360deg');
                wheelPreviewLabels.innerHTML = '';
                previewSegments.innerHTML = '<div class="preview-segment">لا توجد شرائح فعالة</div>';
                return;
            }

            const step = 360 / segments.length;
            const gradient = segments.map((segment, index) => {
                const start = Math.round(index * step);
                const end = Math.round((index + 1) * step);
                return `${segment.color} ${start}deg ${end}deg`;
            }).join(', ');

            wheelPreview.style.setProperty('--wheel-gradient', gradient);
            wheelPreviewLabels.innerHTML = segments.map((segment, index) => {
                const angle = (index * step) + (step / 2);
                return `<div class="wheel-preview-label" style="--angle:${angle}deg">${escapeHtml(segment.label)}</div>`;
            }).join('');
            previewSegments.innerHTML = segments.map((segment) => `
                <div class="preview-segment">
                    <span><span class="preview-dot" style="background:${segment.color};color:${segment.color}"></span>${escapeHtml(segment.label)}</span>
                    <span>فعّال</span>
                </div>
            `).join('');
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        addSegmentBtn.addEventListener('click', () => {
            const clone = segmentTemplate.content.firstElementChild.cloneNode(true);
            const colors = ['#00e5ff', '#7000ff', '#25d366', '#ffd60a', '#ff3b30'];
            clone.querySelector('[data-name="color"]').value = colors[rows().length % colors.length];
            segmentsList.appendChild(clone);
            reindexSegments();
            updatePreview();
        });

        segmentsList.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-segment]');
            if (!button) return;
            if (rows().length <= 2) {
                alert('العجلة تحتاج على الأقل خصمين.');
                return;
            }
            button.closest('[data-segment-row]').remove();
            reindexSegments();
            updatePreview();
        });

        segmentsList.addEventListener('input', () => {
            reindexSegments();
            updatePreview();
        });

        segmentsList.addEventListener('change', () => {
            reindexSegments();
            updatePreview();
        });

        wheelTitleInput.addEventListener('input', updatePreview);
        reindexSegments();
        updatePreview();
    </script>
</body>

</html>
