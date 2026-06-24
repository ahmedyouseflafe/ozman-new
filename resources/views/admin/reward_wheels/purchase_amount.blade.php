<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>عجلات الشراء - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --yellow: #ffd60a;
            --danger: #ff3b30;
            --border: rgba(255, 255, 255, .1);
            --muted: rgba(255, 255, 255, .64)
        }

        body {
            min-height: 100vh;
            direction: rtl;
            color: #fff;
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            background: radial-gradient(circle at 16% 12%, rgba(112, 0, 255, .16), transparent 28%), radial-gradient(circle at 78% 10%, rgba(0, 229, 255, .14), transparent 34%), linear-gradient(180deg, #030303, #050505 55%, #08020f);
            overflow-x: hidden
        }

        body:before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image: linear-gradient(rgba(255, 255, 255, .026) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .026) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, #000, transparent 82%)
        }

        .main {
            min-height: 100vh;
            margin-right: 245px;
            position: relative;
            z-index: 1;
            overflow-x: hidden
        }

        .content {
            padding: 28px 34px 46px;
            width: 100%;
            max-width: 1420px;
            margin: auto
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 22px
        }

        .page-kicker {
            color: var(--primary);
            font-size: 13px;
            font-weight: 900;
            text-shadow: 0 0 12px rgba(0, 229, 255, .5);
            margin-bottom: 6px
        }

        h1 {
            font-size: 34px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--primary);
            text-shadow: 0 0 20px rgba(0, 229, 255, .42)
        }

        .page-head p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700
        }

        .layout-grid {
            display: grid;
            grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
            gap: 22px;
            align-items: start
        }

        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 26px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
            padding: 24px;
            min-width: 0;
            overflow: hidden
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px
        }

        .panel-title {
            font-size: 19px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px
        }

        .panel-title i {
            color: var(--primary)
        }

        .btn {
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
            text-decoration: none;
            color: var(--primary);
            background: rgba(0, 229, 255, .09);
            border: 1px solid rgba(0, 229, 255, .28)
        }

        .btn.primary {
            width: 100%;
            margin-top: 16px;
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: 0
        }

        .btn.danger {
            color: #ff9d9d;
            background: rgba(255, 59, 48, .08);
            border-color: rgba(255, 59, 48, .22)
        }

        .btn.small {
            min-height: 38px;
            padding: 0 14px;
            font-size: 13px
        }

        .btn.icon {
            width: 44px;
            padding: 0;
            flex-shrink: 0
        }

        .status-alert,
        .error-box {
            border-radius: 18px;
            padding: 13px 16px;
            margin-bottom: 18px;
            font-weight: 900
        }

        .status-alert {
            border: 1px solid rgba(37, 211, 102, .3);
            color: var(--green);
            background: rgba(37, 211, 102, .08)
        }

        .error-box {
            border: 1px solid rgba(255, 59, 48, .28);
            color: #ff9d9d;
            background: rgba(255, 59, 48, .08)
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px
        }

        .field {
            display: grid;
            gap: 8px;
            min-width: 0
        }

        .field.full {
            grid-column: 1/-1
        }

        .field span,
        .switch span {
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
            font-weight: 900
        }

        .field input,
        .field select {
            width: 100%;
            height: 44px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 14px;
            color: #fff;
            outline: 0;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            padding: 0 13px
        }

        .field select option {
            color: #111;
            background: #fff
        }

        .switch {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 13px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(255, 255, 255, .04);
            cursor: pointer
        }

        .switch input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary)
        }

        .segments-list {
            display: grid;
            gap: 12px
        }

        .segment-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) 100px 44px;
            gap: 12px;
            align-items: end;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            padding: 14px;
            background: rgba(0, 0, 0, .18)
        }

        .segment-row.is-non-gift .file-picker {
            display: none
        }

        .segment-row.is-non-gift .switch {
            grid-column: 1
        }

        .segment-row .field span {
            line-height: 1.25;
            white-space: normal
        }

        .segment-row input[data-name="win_quota"] {
            min-width: 0;
            text-align: center;
            padding-inline: 8px
        }

        .segment-row .file-picker {
            grid-column: 1 / span 2
        }

        .segment-row .switch {
            grid-column: 3
        }

        .segment-row .btn.icon {
            grid-column: 6;
            grid-row: 2
        }

        .segment-color input {
            padding: 4px
        }

        .file-picker input[type=file] {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none
        }

        .file-picker-ui {
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(0, 229, 255, .28);
            border-radius: 14px;
            background: rgba(0, 229, 255, .08);
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            padding: 0 12px;
            overflow: hidden
        }

        .file-name {
            max-width: 115px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .wheel-list {
            display: grid;
            gap: 14px
        }

        .wheel-item {
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 20px;
            padding: 16px;
            background: rgba(0, 0, 0, .18)
        }

        .wheel-item.is-editing {
            border-color: rgba(0, 229, 255, .42);
            box-shadow: 0 0 24px rgba(0, 229, 255, .12)
        }

        .wheel-item-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 14px
        }

        .wheel-item h3 {
            font-size: 17px;
            font-weight: 900
        }

        .wheel-range {
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            margin-top: 4px
        }

        .wheel-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .wheel-preview-wrap {
            display: grid;
            place-items: center;
            padding: 8px 0 2px
        }

        .wheel-pointer {
            width: 0;
            height: 0;
            border-inline: 14px solid transparent;
            border-top: 24px solid var(--primary);
            filter: drop-shadow(0 0 8px rgba(0, 229, 255, .65));
            margin-bottom: -5px;
            z-index: 2
        }

        .wheel-preview {
            width: min(340px, 76vw);
            aspect-ratio: 1;
            border-radius: 50%;
            border: 6px solid #050505;
            outline: 2px solid rgba(0, 229, 255, .48);
            position: relative;
            overflow: hidden;
            background: conic-gradient(var(--wheel-gradient));
            box-shadow: 0 0 30px rgba(0, 229, 255, .2)
        }

        .wheel-preview:after {
            content: '';
            position: absolute;
            inset: 38%;
            border-radius: 50%;
            background: #050505;
            border: 2px solid rgba(255, 255, 255, .14);
            z-index: 2
        }

        .wheel-label {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 100px;
            min-height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 5px 9px;
            border-radius: 999px;
            color: #fff;
            background: rgba(0, 0, 0, .3);
            border: 1px solid rgba(255, 255, 255, .14);
            font-size: 13px;
            line-height: 1.15;
            font-weight: 900;
            text-align: center;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .75);
            z-index: 1;
            transform: translate(-50%, -50%) rotate(var(--angle)) translateY(-106px) rotate(calc(var(--angle) * -1))
        }

        .wheel-label img {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, .3);
            flex-shrink: 0
        }

        @media(max-width:1180px) {
            .layout-grid {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:900px) {
            .main {
                margin-right: 0
            }
        }

        @media(max-width:760px) {
            .content {
                padding: 20px 16px 34px
            }

            .page-head,
            .panel-head,
            .wheel-item-head {
                flex-direction: column;
                align-items: stretch
            }

            .form-grid,
            .segment-row {
                grid-template-columns: 1fr
            }
        }

        @media(min-width:761px) and (max-width:1280px) {
            .segment-row {
                grid-template-columns: repeat(4, minmax(0, 1fr)) 100px 44px
            }

            .segment-row .file-picker {
                grid-column: span 2
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            <div class="content">
                <div class="page-head">
                    <div>
                        <div class="page-kicker">عجلات الربح</div>
                        <h1>عجلات الشراء حسب المبلغ</h1>
                        <p>كل نطاق مبلغ له عجلة واحدة. اضغط تعديل لتغيير جوائز نفس العجلة.</p>
                    </div>
                </div>
                @if (session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="error-box">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                @php $editingWheel = $editWheel ?? null; @endphp
                <div class="layout-grid">
                    <section class="panel">
                        <div class="panel-head">
                            <div class="panel-title"><i class="ti ti-disc"></i> العجلات الحالية</div>
                            @if ($editingWheel)
                                <a class="btn small" href="{{ route('reward-wheels.purchase.index') }}"><i
                                        class="ti ti-plus"></i> عجلة جديدة</a>
                            @endif
                        </div>
                        <div class="wheel-list">
                            @forelse($wheels as $wheel)
                                <article class="wheel-item @if ($editingWheel?->id === $wheel->id) is-editing @endif">
                                    <div class="wheel-item-head">
                                        <div>
                                            <h3>{{ $wheel->title }}</h3>
                                            <div class="wheel-range">من
                                                {{ number_format((float) $wheel->min_order_total, 2) }} إلى
                                                {{ $wheel->max_order_total ? number_format((float) $wheel->max_order_total, 2) : 'بدون حد أعلى' }}
                                                شيكل</div>
                                        </div>
                                        <div class="wheel-actions"><a class="btn small"
                                                href="{{ route('reward-wheels.purchase.edit', $wheel) }}"><i
                                                    class="ti ti-edit"></i> تعديل</a>
                                            <form method="POST"
                                                action="{{ route('reward-wheels.purchase.destroy', $wheel) }}">@csrf
                                                @method('DELETE')<button class="btn danger small" type="submit"><i
                                                        class="ti ti-trash"></i> حذف</button></form>
                                        </div>
                                    </div>
                                    @php
                                        $previewSegments = $wheel->segments->values();
                                        $segmentCount = max($previewSegments->count(), 1);
                                        $segmentStep = 360 / $segmentCount;
                                        $gradientParts = $previewSegments
                                            ->map(function ($segment, $index) use ($segmentStep) {
                                                $start = round($index * $segmentStep, 2);
                                                $end = round(($index + 1) * $segmentStep, 2);
                                                return "{$segment->color} {$start}deg {$end}deg";
                                            })
                                            ->implode(', ');
                                    @endphp
                                    <div class="wheel-preview-wrap">
                                        <div class="wheel-pointer"></div>
                                        <div class="wheel-preview"
                                            style="--wheel-gradient: {{ $gradientParts ?: '#00e5ff 0deg 360deg' }};">
                                            @foreach ($previewSegments as $index => $segment)
                                                @php $angle=($index*$segmentStep)+($segmentStep/2); @endphp
                                                <span class="wheel-label" style="--angle: {{ $angle }}deg;">
                                                    @if ($segment->discount_type === 'gift' && $segment->gift_image)
                                                        <img src="{{ asset($segment->gift_image) }}"
                                                            alt="{{ $segment->label }}">
                                                    @endif{{ $segment->label }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </article>
                                @empty <div class="wheel-item">لا توجد عجلات شراء حتى الآن.</div>
                                @endforelse
                            </div>
                        </section>
                        <section class="panel">
                            <div class="panel-head">
                                <div class="panel-title"><i class="ti ti-settings"></i>
                                    {{ $editingWheel ? 'تعديل جوائز العجلة' : 'عجلة شراء جديدة' }}</div><button
                                    class="btn small" type="button" id="addSegmentBtn"><i class="ti ti-plus"></i> إضافة
                                    جائزة</button>
                            </div>
                            <form method="POST"
                                action="{{ $editingWheel ? route('reward-wheels.purchase.update', $editingWheel) : route('reward-wheels.purchase.store') }}"
                                enctype="multipart/form-data">@csrf @if ($editingWheel)
                                    @method('PUT')
                                @endif
                                <div class="form-grid"><label class="field full"><span>عنوان العجلة</span><input
                                            type="text" name="title"
                                            value="{{ old('title', $editingWheel?->title ?? 'لف العجلة واحصل على جائزتك') }}"
                                            required></label><label class="field"><span>من مبلغ</span><input type="number"
                                            name="min_order_total" min="0" step="0.01"
                                            value="{{ old('min_order_total', $editingWheel?->min_order_total ?? 100) }}"
                                            required></label><label class="field"><span>إلى مبلغ</span><input
                                            type="number" name="max_order_total" min="0" step="0.01"
                                            value="{{ old('max_order_total', $editingWheel?->max_order_total) }}"
                                            placeholder="اتركه فارغ بدون حد أعلى"></label><label class="switch"><input
                                            type="checkbox" name="is_active" value="1"
                                            @checked(old('is_active', $editingWheel?->is_active ?? true))><span>العجلة مفعلة</span></label></div>
                                @php $segments=old('segments') ?: ($editingWheel?$editingWheel->segments->map(fn($segment)=>['label'=>$segment->label,'discount_value'=>$segment->discount_value,'discount_type'=>$segment->discount_type,'gift_image'=>$segment->gift_image,'existing_gift_image'=>$segment->gift_image,'win_quota'=>$segment->win_quota ?? 1,'color'=>$segment->color,'is_active'=>$segment->is_active])->values()->all():[['label'=>'خصم 5%','discount_value'=>5,'discount_type'=>'percent','win_quota'=>50,'color'=>'#00e5ff','is_active'=>true],['label'=>'خصم 10%','discount_value'=>10,'discount_type'=>'percent','win_quota'=>50,'color'=>'#7000ff','is_active'=>true],['label'=>'هدية','discount_value'=>null,'discount_type'=>'gift','win_quota'=>50,'color'=>'#25d366','is_active'=>true],['label'=>'توصيل مجاني','discount_value'=>null,'discount_type'=>'free_shipping','win_quota'=>50,'color'=>'#ffd60a','is_active'=>true]]); @endphp
                                <div class="segments-list" id="segmentsList">
                                    @foreach ($segments as $index => $segment)
                                        <div class="segment-row" data-segment-row><label
                                                class="field"><span>الجائزة</span><input type="text" data-name="label"
                                                    name="segments[{{ $index }}][label]"
                                                    value="{{ old("segments.$index.label", $segment['label']) }}"
                                                    required></label><label class="field"><span>القيمة</span><input
                                                    type="number" data-name="discount_value"
                                                    name="segments[{{ $index }}][discount_value]" min="0"
                                                    max="100000"
                                                    value="{{ old("segments.$index.discount_value", $segment['discount_value']) }}"></label><label
                                                class="field"><span>النوع</span><select data-name="discount_type"
                                                    name="segments[{{ $index }}][discount_type]" required>
                                                    <option value="percent" @selected(old("segments.$index.discount_type", $segment['discount_type']) === 'percent')>نسبة %</option>
                                                    <option value="amount" @selected(old("segments.$index.discount_type", $segment['discount_type']) === 'amount')>مبلغ ثابت</option>
                                                    <option value="free_shipping" @selected(old("segments.$index.discount_type", $segment['discount_type']) === 'free_shipping')>توصيل مجاني
                                                    </option>
                                                    <option value="gift" @selected(old("segments.$index.discount_type", $segment['discount_type']) === 'gift')>هدية</option>
                                                </select></label><label class="field"><span>ظهور من 200</span><input
                                                    type="number" data-name="win_quota"
                                                    name="segments[{{ $index }}][win_quota]" min="0" max="200"
                                                    value="{{ old("segments.$index.win_quota", $segment['win_quota'] ?? 1) }}"></label><label
                                                class="field segment-color"><span>اللون</span><input type="color"
                                                    data-name="color" name="segments[{{ $index }}][color]"
                                                    value="{{ old("segments.$index.color", $segment['color']) }}"
                                                    required></label><label class="field file-picker"><span>صورة
                                                    الهدية</span><input type="hidden" data-name="existing_gift_image"
                                                    name="segments[{{ $index }}][existing_gift_image]"
                                                    value="{{ old("segments.$index.existing_gift_image", $segment['existing_gift_image'] ?? '') }}"><input
                                                    type="file" data-name="gift_image"
                                                    name="segments[{{ $index }}][gift_image]"
                                                    accept="image/*"><span class="file-picker-ui"><i
                                                        class="ti ti-photo-plus"></i><span
                                                        class="file-name">{{ !empty($segment['gift_image'] ?? null) ? 'تغيير الصورة' : 'اختيار صورة' }}</span></span></label><label
                                                class="switch"><input type="checkbox" data-name="is_active"
                                                    name="segments[{{ $index }}][is_active]" value="1"
                                                    @checked(old("segments.$index.is_active", $segment['is_active'] ?? true))><span>فعال</span></label><button
                                                class="btn danger icon" type="button" data-remove-segment
                                                title="حذف"><i class="ti ti-trash"></i></button></div>
                                    @endforeach
                                </div>
                                <button class="btn primary" type="submit"><i class="ti ti-device-floppy"></i>
                                    {{ $editingWheel ? 'حفظ تعديل العجلة' : 'حفظ عجلة الشراء' }}</button>
                            </form>
                        </section>
                    </div>
                </div>
            </main>
        </div>
        <template id="segmentTemplate">
            <div class="segment-row" data-segment-row>
                <label class="field"><span>الجائزة</span><input type="text" data-name="label" value="جائزة جديدة"
                        required></label>
                <label class="field"><span>القيمة</span><input type="number" data-name="discount_value" min="0"
                        max="100000"></label>
                <label class="field"><span>النوع</span><select data-name="discount_type" required>
                        <option value="percent">نسبة %</option>
                        <option value="amount">مبلغ ثابت</option>
                        <option value="free_shipping">توصيل مجاني</option>
                        <option value="gift">هدية</option>
                    </select></label>
                <label class="field"><span>ظهور من 200</span><input type="number" data-name="win_quota" min="0"
                        max="200" value="1"></label>
                <label class="field segment-color"><span>اللون</span><input type="color" data-name="color"
                        value="#00e5ff" required></label>
                <label class="field file-picker"><span>صورة الهدية</span><input type="hidden"
                        data-name="existing_gift_image" value=""><input type="file" data-name="gift_image"
                        accept="image/*"><span class="file-picker-ui"><i class="ti ti-photo-plus"></i><span
                            class="file-name">اختيار صورة</span></span></label>
                <label class="switch"><input type="checkbox" data-name="is_active" value="1"
                        checked><span>فعال</span></label>
                <button class="btn danger icon" type="button" data-remove-segment title="حذف"><i
                        class="ti ti-trash"></i></button>
            </div>
        </template>
        <script>
            const segmentsList = document.getElementById('segmentsList');
            const segmentTemplate = document.getElementById('segmentTemplate');
            const addSegmentBtn = document.getElementById('addSegmentBtn');
            const colors = ['#00e5ff', '#7000ff', '#25d366', '#ffd60a', '#ff3b30', '#ff8a00', '#ff4fd8', '#35c2ff'];

            function rows() {
                return Array.from(segmentsList.querySelectorAll('[data-segment-row]'));
            }

            function reindexSegments() {
                rows().forEach((row, index) => {
                    row.querySelectorAll('[data-name]').forEach((field) => {
                        field.name = `segments[${index}][${field.dataset.name}]`;
                    });
                });
            }

            function bindFilePicker(input) {
                input.addEventListener('change', () => {
                    const label = input.closest('.file-picker')?.querySelector('.file-name');
                    if (label) label.textContent = input.files?.[0]?.name || 'اختيار صورة';
                });
            }

            function syncGiftImageField(row) {
                const type = row.querySelector('[data-name="discount_type"]')?.value;
                const isGift = type === 'gift';
                const fileInput = row.querySelector('[data-name="gift_image"]');
                const existingInput = row.querySelector('[data-name="existing_gift_image"]');
                const fileName = row.querySelector('.file-name');

                row.classList.toggle('is-non-gift', !isGift);
                if (!isGift) {
                    if (fileInput) fileInput.value = '';
                    if (existingInput) existingInput.value = '';
                    if (fileName) fileName.textContent = 'اختيار صورة';
                }
            }

            function bindSegmentRow(row) {
                const fileInput = row.querySelector('.file-picker input[type="file"]');
                const typeSelect = row.querySelector('[data-name="discount_type"]');
                if (fileInput) bindFilePicker(fileInput);
                typeSelect?.addEventListener('change', () => syncGiftImageField(row));
                syncGiftImageField(row);
            }

            rows().forEach(bindSegmentRow);
            addSegmentBtn?.addEventListener('click', () => {
                const clone = segmentTemplate.content.firstElementChild.cloneNode(true);
                clone.querySelector('[data-name="color"]').value = colors[rows().length % colors.length];
                segmentsList.appendChild(clone);
                bindSegmentRow(clone);
                reindexSegments();
            });
            segmentsList?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-segment]');
                if (!button) return;
                if (rows().length <= 2) {
                    alert('العجلة تحتاج على الأقل جائزتين.');
                    return;
                }
                button.closest('[data-segment-row]').remove();
                reindexSegments();
            });
            reindexSegments();
        </script>
    </body>

    </html>
