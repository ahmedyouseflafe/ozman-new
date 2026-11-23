<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>عجلات الشراء - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.reward_wheels.purchase_amount')
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
                <form method="GET" action="{{ route('reward-wheels.purchase.index') }}" class="panel"
                    style="margin-bottom:18px;padding:16px;display:flex;align-items:end;gap:12px;flex-wrap:wrap">
                    <label class="field" style="min-width:min(100%,320px);margin:0">
                        <span>المتجر الذي تدير عجلاته</span>
                        <select name="shop_id" onchange="this.form.submit()">
                            @foreach($shops as $wheelShop)
                                <option value="{{ $wheelShop->id }}" @selected((int) $selectedShopId === (int) $wheelShop->id)>
                                    {{ $wheelShop->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <noscript><button class="btn small" type="submit">عرض</button></noscript>
                </form>
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
                                <a class="btn small" href="{{ route('reward-wheels.purchase.index', ['shop_id' => $selectedShopId]) }}"><i
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
                                            <div class="wheel-range">إجمالي فرص العجلة:
                                                {{ number_format((int) ($wheel->win_quota_total ?? 200)) }}</div>
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
                                                <span class="wheel-label" title="{{ $segment->label }}" style="left:{{ 50+32*sin(deg2rad($angle)) }}%;top:{{ 50-32*cos(deg2rad($angle)) }}%;--text-angle:{{ $angle-90+($angle>180?180:0) }}deg;--label-height:{{ min(10,90/$segmentCount) }}cqw;">
                                                    @if ($segment->discount_type === 'gift' && $segment->gift_image)
                                                        <img src="{{ asset($segment->gift_image) }}"
                                                            alt="{{ $segment->label }}">
                                                    @endif<span class="wheel-caption">{{ $segment->label }}</span>
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
                                <input type="hidden" name="shop_id" value="{{ $selectedShopId }}">
                                @php $quotaTotal = (int) old('win_quota_total', $editingWheel?->win_quota_total ?? 200); @endphp
                                <div class="form-grid"><label class="field full"><span>عنوان العجلة</span><input
                                            type="text" name="title"
                                            value="{{ old('title', $editingWheel?->title ?? 'لف العجلة واحصل على جائزتك') }}"
                                            required></label><label class="field"><span>من مبلغ</span><input type="number"
                                            name="min_order_total" min="0" step="0.01"
                                            value="{{ old('min_order_total', $editingWheel?->min_order_total ?? 100) }}"
                                            required></label><label class="field"><span>إلى مبلغ</span><input
                                            type="number" name="max_order_total" min="0" step="0.01"
                                            value="{{ old('max_order_total', $editingWheel?->max_order_total) }}"
                                            placeholder="اتركه فارغ بدون حد أعلى"></label><label class="field"><span>إجمالي فرص العجلة</span><input
                                            type="number" name="win_quota_total" min="1" max="10000" step="1"
                                            data-wheel-quota-total
                                            value="{{ $quotaTotal }}"
                                            required></label><label class="switch"><input
                                            type="checkbox" name="is_active" value="1"
                                            @checked(old('is_active', $editingWheel?->is_active ?? true))><span>العجلة مفعلة</span></label>
                                            <div class="quota-status" data-quota-status></div></div>
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
                                                    <option value="none" @selected(old("segments.$index.discount_type", $segment['discount_type']) === 'none')>حظ أوفر — بدون جائزة</option><option value="gift" @selected(old("segments.$index.discount_type", $segment['discount_type']) === 'gift')>هدية</option>
                                                </select></label><label class="field"><span data-quota-label>ظهور من {{ $quotaTotal }}</span><input
                                                    type="number" data-name="win_quota"
                                                    name="segments[{{ $index }}][win_quota]" min="0" max="{{ $quotaTotal }}"
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
                        <option value="none">حظ أوفر — بدون جائزة</option><option value="gift">هدية</option>
                    </select></label>
                <label class="field"><span data-quota-label>ظهور من {{ $quotaTotal }}</span><input type="number" data-name="win_quota" min="0"
                        max="{{ $quotaTotal }}" value="1"></label>
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
            @php
                // $signupSource comes from PageDataComposers::purchaseWheelSettings.
                $signupCopy = $signupSource ? ['title'=>$signupSource->title,'quota'=>$signupSource->win_quota_total,'segments'=>$signupSource->segments->map(fn($s)=>['label'=>$s->label,'discount_type'=>$s->discount_type,'discount_value'=>$s->discount_value,'win_quota'=>$s->win_quota,'color'=>$s->color,'is_active'=>$s->is_active,'existing_gift_image'=>$s->gift_image])->all()] : null;
            @endphp
            const signupCopy = @json($signupCopy);
            const segmentsList = document.getElementById('segmentsList');
            const segmentTemplate = document.getElementById('segmentTemplate');
            const addSegmentBtn = document.getElementById('addSegmentBtn');
            const quotaTotalInput = document.querySelector('[data-wheel-quota-total]');
            const wheelForm = quotaTotalInput?.closest('form');
            const quotaStatus = document.querySelector('[data-quota-status]');
            const colors = ['#00e5ff', '#7000ff', '#25d366', '#ffd60a', '#ff3b30', '#ff8a00', '#ff4fd8', '#35c2ff'];
            const autosaveKey = `ozman:purchase-wheel-draft:${location.pathname}`;
            const shouldClearAutosave = @json(session('status') !== null);

            if (shouldClearAutosave) {
                localStorage.removeItem(autosaveKey);
            }

            function rows() {
                return Array.from(segmentsList.querySelectorAll('[data-segment-row]'));
            }

            function readAutosave() {
                try {
                    const saved = JSON.parse(localStorage.getItem(autosaveKey) || 'null');
                    if (!saved || Date.now() > (saved.expiresAt || 0)) {
                        localStorage.removeItem(autosaveKey);
                        return null;
                    }
                    return saved;
                } catch (_) {
                    localStorage.removeItem(autosaveKey);
                    return null;
                }
            }

            function dataUrlToFile(dataUrl, name, type) {
                const [header, data] = dataUrl.split(',');
                const mime = type || header.match(/data:(.*?);/)?.[1] || 'image/png';
                const binary = atob(data || '');
                const bytes = new Uint8Array(binary.length);
                for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                return new File([bytes], name || 'gift-image.png', { type: mime });
            }

            function setInputFile(input, file) {
                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                    return true;
                } catch (_) {
                    return false;
                }
            }

            function saveAutosave() {
                if (shouldClearAutosave || !wheelForm) return;

                const payload = {
                    expiresAt: Date.now() + (1000 * 60 * 60 * 24 * 7),
                    title: wheelForm.querySelector('[name="title"]')?.value || '',
                    min_order_total: wheelForm.querySelector('[name="min_order_total"]')?.value || '',
                    max_order_total: wheelForm.querySelector('[name="max_order_total"]')?.value || '',
                    win_quota_total: quotaTotalInput?.value || '',
                    is_active: wheelForm.querySelector('[name="is_active"]')?.checked || false,
                    segments: rows().map((row) => {
                        const segment = {};
                        row.querySelectorAll('[data-name]').forEach((field) => {
                            if (field.type === 'file') return;
                            segment[field.dataset.name] = field.type === 'checkbox' ? field.checked : field.value;
                        });

                        const fileInput = row.querySelector('[data-name="gift_image"]');
                        const file = fileInput?.files?.[0];
                        const cachedImage = fileInput?.dataset.autosaveImage
                            ? {
                                name: fileInput.dataset.autosaveImageName || 'gift-image.png',
                                type: fileInput.dataset.autosaveImageType || 'image/png',
                                dataUrl: fileInput.dataset.autosaveImage,
                            }
                            : null;

                        segment.gift_image_file = file ? {
                            name: file.name,
                            type: file.type,
                            dataUrl: fileInput.dataset.autosaveImage || '',
                        } : cachedImage;

                        return segment;
                    }),
                };

                try {
                    localStorage.setItem(autosaveKey, JSON.stringify(payload));
                } catch (_) {
                    payload.segments = payload.segments.map((segment) => ({
                        ...segment,
                        gift_image_file: null,
                    }));
                    localStorage.setItem(autosaveKey, JSON.stringify(payload));
                }
            }

            function restoreAutosave() {
                if (shouldClearAutosave || !wheelForm) return;

                const saved = readAutosave();
                if (!saved) return;

                const setValue = (selector, value) => {
                    const input = wheelForm.querySelector(selector);
                    if (input && value !== undefined && value !== null) input.value = value;
                };

                setValue('[name="title"]', saved.title);
                setValue('[name="min_order_total"]', saved.min_order_total);
                setValue('[name="max_order_total"]', saved.max_order_total);
                if (quotaTotalInput && saved.win_quota_total) quotaTotalInput.value = saved.win_quota_total;

                const activeInput = wheelForm.querySelector('[name="is_active"]');
                if (activeInput) activeInput.checked = !!saved.is_active;

                const savedSegments = Array.isArray(saved.segments) ? saved.segments : [];
                const targetRows = Math.max(savedSegments.length, 2);

                while (rows().length < targetRows) {
                    segmentsList.appendChild(segmentTemplate.content.firstElementChild.cloneNode(true));
                }

                while (rows().length > targetRows) {
                    rows().at(-1)?.remove();
                }

                rows().forEach((row, index) => {
                    const segment = savedSegments[index];
                    if (!segment) return;

                    row.querySelectorAll('[data-name]').forEach((field) => {
                        const value = segment[field.dataset.name];
                        if (field.type === 'file') return;
                        if (field.type === 'checkbox') {
                            field.checked = !!value;
                        } else if (value !== undefined && value !== null) {
                            field.value = value;
                        }
                    });

                    const fileInput = row.querySelector('[data-name="gift_image"]');
                    const fileData = segment.gift_image_file;
                    if (fileInput && fileData?.dataUrl) {
                        fileInput.dataset.autosaveImage = fileData.dataUrl;
                        fileInput.dataset.autosaveImageName = fileData.name || '';
                        fileInput.dataset.autosaveImageType = fileData.type || '';

                        const restored = setInputFile(fileInput, dataUrlToFile(fileData.dataUrl, fileData.name, fileData.type));
                        const label = row.querySelector('.file-name');
                        if (label) label.textContent = restored ? (fileData.name || 'تمت استعادة الصورة') : 'الصورة محفوظة مؤقتا';
                    }
                });
            }

            function reindexSegments() {
                rows().forEach((row, index) => {
                    row.querySelectorAll('[data-name]').forEach((field) => {
                        field.name = `segments[${index}][${field.dataset.name}]`;
                    });
                });
            }

            function currentQuotaTotal() {
                const value = Number.parseInt(quotaTotalInput?.value || '200', 10);
                return Number.isFinite(value) && value > 0 ? value : 200;
            }

            function syncQuotaLabels() {
                const total = currentQuotaTotal();
                rows().forEach((row) => {
                    const label = row.querySelector('[data-quota-label]');
                    const input = row.querySelector('[data-name="win_quota"]');
                    if (label) label.textContent = `ظهور من ${total}`;
                    if (input) input.max = total;
                });
                updateQuotaStatus();
            }

            function activeQuotaSum() {
                return rows().reduce((sum, row) => {
                    const active = row.querySelector('[data-name="is_active"]')?.checked;
                    const quota = Number.parseInt(row.querySelector('[data-name="win_quota"]')?.value || '0', 10);
                    return active ? sum + (Number.isFinite(quota) ? quota : 0) : sum;
                }, 0);
            }

            function quotaState() {
                const total = currentQuotaTotal();
                const sum = activeQuotaSum();
                return {
                    total,
                    sum,
                    diff: total - sum,
                    valid: sum === total,
                };
            }

            function updateQuotaStatus() {
                if (!quotaStatus) return;

                const state = quotaState();
                quotaStatus.classList.toggle('is-valid', state.valid);

                if (state.valid) {
                    quotaStatus.textContent = `مجموع فرص الجوائز الفعالة ${state.sum} من ${state.total} - صحيح.`;
                } else if (state.diff > 0) {
                    quotaStatus.textContent = `مجموع فرص الجوائز الفعالة ${state.sum} من ${state.total}. ناقص ${state.diff} فرصة.`;
                } else {
                    quotaStatus.textContent = `مجموع فرص الجوائز الفعالة ${state.sum} من ${state.total}. زائد ${Math.abs(state.diff)} فرصة.`;
                }
            }

            function bindFilePicker(input) {
                input.addEventListener('change', () => {
                    const label = input.closest('.file-picker')?.querySelector('.file-name');
                    if (label) label.textContent = input.files?.[0]?.name || 'اختيار صورة';

                    const file = input.files?.[0];
                    if (!file) {
                        delete input.dataset.autosaveImage;
                        delete input.dataset.autosaveImageName;
                        delete input.dataset.autosaveImageType;
                        saveAutosave();
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = () => {
                        input.dataset.autosaveImage = String(reader.result || '');
                        input.dataset.autosaveImageName = file.name;
                        input.dataset.autosaveImageType = file.type;
                        saveAutosave();
                    };
                    reader.readAsDataURL(file);
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
                row.querySelectorAll('[data-name="win_quota"], [data-name="is_active"]').forEach((field) => {
                    field.addEventListener('input', updateQuotaStatus);
                    field.addEventListener('change', updateQuotaStatus);
                });
                row.querySelectorAll('[data-name]').forEach((field) => {
                    if (field.type === 'file') return;
                    field.addEventListener('input', saveAutosave);
                    field.addEventListener('change', saveAutosave);
                });
                syncGiftImageField(row);
            }

            restoreAutosave();
            const copySignupButton = document.createElement('button');
            copySignupButton.type='button';copySignupButton.className='btn small';copySignupButton.textContent='نسخ عجلة التسجيل';
            addSegmentBtn?.before(copySignupButton);
            copySignupButton.disabled=!signupCopy;
            copySignupButton.title=signupCopy?'نسخ الجوائز إلى النموذج فقط؛ عدّل ثم احفظ':'لا توجد عجلة تسجيل محفوظة لهذا المحل';
            copySignupButton.addEventListener('click',()=>{
                if(!signupCopy || !confirm('استبدال خانات النموذج بنسخة من عجلة التسجيل؟ لن يتم الحفظ حتى تضغط حفظ.'))return;
                segmentsList.replaceChildren();
                wheelForm.querySelector('[name="title"]').value=signupCopy.title;
                quotaTotalInput.value=signupCopy.quota;
                signupCopy.segments.forEach(segment=>{
                    const row=segmentTemplate.content.firstElementChild.cloneNode(true);
                    row.querySelectorAll('[data-name]').forEach(field=>{
                        if(field.type==='file')return;
                        if(field.type==='checkbox')field.checked=!!segment[field.dataset.name];
                        else field.value=segment[field.dataset.name]??'';
                    });
                    segmentsList.append(row);bindSegmentRow(row);
                });
                reindexSegments();syncQuotaLabels();updateQuotaStatus();saveAutosave();
            });
            rows().forEach(bindSegmentRow);
            addSegmentBtn?.addEventListener('click', () => {
                const clone = segmentTemplate.content.firstElementChild.cloneNode(true);
                clone.querySelector('[data-name="color"]').value = colors[rows().length % colors.length];
                segmentsList.appendChild(clone);
                bindSegmentRow(clone);
                reindexSegments();
                syncQuotaLabels();
                updateQuotaStatus();
                saveAutosave();
            });
            quotaTotalInput?.addEventListener('input', syncQuotaLabels);
            wheelForm?.querySelectorAll('[name="title"], [name="min_order_total"], [name="max_order_total"], [name="win_quota_total"], [name="is_active"]').forEach((field) => {
                field.addEventListener('input', saveAutosave);
                field.addEventListener('change', saveAutosave);
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
                syncQuotaLabels();
                saveAutosave();
            });
            wheelForm?.addEventListener('submit', (event) => {
                const state = quotaState();
                if (state.valid) return;

                event.preventDefault();
                updateQuotaStatus();
                quotaStatus?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
            reindexSegments();
            syncQuotaLabels();
            updateQuotaStatus();
        </script>
    </body>

    </html>
