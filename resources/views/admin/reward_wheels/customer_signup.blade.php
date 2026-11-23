<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ isset($shop) ? 'عجلة التسجيل · '.$shop->name : 'عجلة خصومات العملاء' }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.reward_wheels.customer_signup')
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
                        <h1>{{ isset($shop) ? 'عجلة التسجيل · '.$shop->name : 'عجلة خصومات العملاء' }}</h1>
                        <p>{{ isset($shop) ? 'تظهر بعد أول تسجيل جديد، وتُضاف الجائزة تلقائيًا إلى أول طلب: خصم أو هدية. اختر 2–20 خانة وحدد عدد مرات فوز كل جائزة ضمن الدورة.' : 'هذه العجلة مخصصة لأول تسجيل كعميل على الموقع. أضف العنوان والخصومات التي ستظهر داخل العجلة.' }}</p>
                        @isset($shop)<p>بعد اكتمال لفات الدورة يتكرر نفس التوزيع. العدد صفر يمنع الفوز بالخانة. حفظ الإعدادات يبدأ توزيعًا جديدًا للتسجيلات القادمة؛ الجوائز السابقة تبقى محفوظة. إلغاء الطلب لا يعيد الجائزة.</p>@endisset
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

                <form id="wheelForm" method="POST" enctype="multipart/form-data" action="{{ isset($shop) ? route('reward-wheels.shop-signup.update', $shop) : route('reward-wheels.customer-signup.update') }}">
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
                                    إضافة جائزة
                                </button>
                            </div>

                            <div class="form-grid">
                                <label class="field">
                                    <span>عنوان العجلة</span>
                                    <input type="text" name="title" id="wheelTitleInput" value="{{ old('title', $wheel->title) }}" required>
                                </label>

                                <label class="switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $wheel->is_active))>
                                    <span>العجلة مفعلة</span>
                                </label>
                            </div>

                            @isset($shop)
                                <label class="field" style="margin-bottom:18px">
                                    <span>إجمالي لفات الدورة</span>
                                    <input type="number" id="signupQuotaTotal" name="win_quota_total" min="1" max="10000" required value="{{ old('win_quota_total', $wheel->win_quota_total ?? $wheel->segments->where('is_active', true)->sum(fn($segment) => $segment->win_quota ?? 1)) }}">
                                    <small id="signupQuotaSummary" role="status"></small>
                                </label>
                            @endisset
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
                                            'win_quota' => $segment->win_quota ?? 1,
                                            'existing_gift_image' => $segment->gift_image,
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
                                                @unless(isset($shop))
                                                    <option value="free_shipping" @selected(($segment['discount_type'] ?? '') === 'free_shipping')>توصيل مجاني</option>
                                                @endunless
                                                <option value="gift" @selected(($segment['discount_type'] ?? '') === 'gift')>هدية</option>
                                                @isset($shop)<option value="none" @selected(($segment['discount_type'] ?? '') === 'none')>حظ أوفر — بدون جائزة</option>@endisset
                                            </select>
                                        </label>

                                        <label class="field segment-color">
                                            <span>اللون</span>
                                            <input type="color" data-name="color" value="{{ $segment['color'] ?? '#00e5ff' }}" required>
                                        </label>

                                        @isset($shop)
                                            <label class="field"><span>عدد مرات الظهور</span><input type="number" data-name="win_quota" min="0" max="10000" required value="{{ $segment['win_quota'] ?? 1 }}"></label>
                                            <label class="field signup-gift-field" hidden><span>صورة الهدية (حتى 2 MB)</span>
                                                <input type="hidden" data-name="existing_gift_image" value="{{ $segment['existing_gift_image'] ?? '' }}">
                                                <input type="file" data-name="gift_image" accept="image/png,image/jpeg,image/webp,image/gif">
                                                <img class="signup-gift-preview" alt="معاينة الهدية" hidden>
                                            </label>
                                        @endisset
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
                            @isset($shop)<button type="button" class="add-btn" id="previewCustomerWheel" style="display:flex;margin:0 auto 18px">معاينة تجربة العميل</button>@endisset
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
                    @unless(isset($shop))
                        <option value="free_shipping">توصيل مجاني</option>
                    @endunless
                    <option value="gift">هدية</option>
                    @isset($shop)<option value="none">حظ أوفر — بدون جائزة</option>@endisset
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

            @isset($shop)
                <label class="field"><span>عدد مرات الظهور</span><input type="number" data-name="win_quota" min="0" max="10000" required value="1"></label>
                <label class="field signup-gift-field" hidden><span>صورة الهدية (حتى 2 MB)</span>
                    <input type="hidden" data-name="existing_gift_image" value="">
                    <input type="file" data-name="gift_image" accept="image/png,image/jpeg,image/webp,image/gif">
                    <img class="signup-gift-preview" alt="معاينة الهدية" hidden>
                </label>
            @endisset
            <button class="remove-btn" type="button" data-remove-segment title="حذف">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </template>

    @isset($shop)
    @viewStyle('admin.reward_wheels.customer_signup-2')
    @endisset
    @isset($shop)
        @include('front.partials.shop_signup_wheel', ['locale' => 'ar', 'signupPreview' => true])
    @endisset
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
                    giftImage: row.querySelector('[data-name="discount_type"]')?.value === 'gift' && !row.querySelector('.signup-gift-preview')?.hidden
                        ? row.querySelector('.signup-gift-preview')?.src || '' : '',
                    color: row.querySelector('[data-name="color"]')?.value || '#00e5ff',
                    isActive: row.querySelector('[data-name="is_active"]')?.checked ?? false,
                }))
                .filter((segment) => segment.isActive && segment.label.trim() !== '');
        }

        function updatePreview() {
            const totalInput = document.getElementById('signupQuotaTotal');
            if (totalInput) {
                let sum = 0;
                rows().forEach(row => {
                    if (row.querySelector('[data-name="is_active"]').checked) sum += Number(row.querySelector('[data-name="win_quota"]').value || 0);
                    const gift = row.querySelector('[data-name="discount_type"]').value === 'gift';
                    const value = row.querySelector('[data-name="discount_value"]');
                    const noPrize = row.querySelector('[data-name="discount_type"]').value === 'none';
                    value.disabled = gift || noPrize;value.closest('label').hidden = gift || noPrize;
                    if (noPrize && row._previousType !== 'none') row.querySelector('[data-name="label"]').value = 'حظ أوفر';
                    row._previousType = row.querySelector('[data-name="discount_type"]').value;
                    row.querySelector('.signup-gift-field').hidden = !gift;
                    const fileInput = row.querySelector('[data-name="gift_image"]');
                    if (fileInput.files[0] !== row._giftFile) {
                        if (row._giftUrl) URL.revokeObjectURL(row._giftUrl);
                        row._giftFile = fileInput.files[0];row._giftUrl = row._giftFile ? URL.createObjectURL(row._giftFile) : '';
                    }
                    const path = row.querySelector('[data-name="existing_gift_image"]').value;
                    const src = row._giftUrl || (path ? @json(rtrim(asset('/'), '/')) + '/' + path.replace(/^\/+/, '') : '');
                    const img = row.querySelector('.signup-gift-preview');
                    img.hidden = !gift || !src;if (src) img.src = src;
                });
                document.getElementById('signupQuotaSummary').textContent = `مجموع الظهور: ${sum} / ${totalInput.value}. لازم المجموع يساوي إجمالي الدورة.`;
                totalInput.setCustomValidity(sum === Number(totalInput.value) ? '' : 'مجموع مرات الظهور لا يساوي إجمالي الدورة.');
            }
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
                const radians = angle * Math.PI / 180;
                const rotation = angle - 90 + (angle > 180 ? 180 : 0);
                return `<div class="wheel-preview-label ${segment.giftImage ? 'has-gift-image' : ''}" title="${escapeHtml(segment.label)}" style="left:${50+32*Math.sin(radians)}%;top:${50-32*Math.cos(radians)}%;--text-angle:${rotation}deg;--label-height:${Math.min(10,90/segments.length)}cqw">${segment.giftImage ? `<img src="${escapeHtml(segment.giftImage)}" alt="" onerror="this.hidden=true">` : ''}<span>${escapeHtml(segment.label)}</span></div>`;
            }).join('');
            previewSegments.innerHTML = segments.map((segment) => `
                <div class="preview-segment">
                    <span><span class="preview-dot" style="background:${segment.color};color:${segment.color}"></span>${escapeHtml(segment.label)}</span>
                    <span>فعّال</span>
                </div>
            `).join('');
            if (totalInput) {
                const activeRows = rows().filter(row => row.querySelector('[data-name="is_active"]').checked && row.querySelector('[data-name="label"]').value.trim());
                [...previewSegments.children].forEach((item, index) => {
                    const row = activeRows[index];
                    item.lastElementChild.textContent = row.querySelector('[data-name="win_quota"]').value + ' مرة / دورة';
                    const img = row.querySelector('.signup-gift-preview');
                    if (!img.hidden) item.firstElementChild.prepend(img.cloneNode());
                });
            }
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
            if (document.getElementById('signupQuotaTotal') && rows().length >= 20) {alert('الحد الأقصى 20 خانة.');return;}
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
                alert('العجلة تحتاج على الأقل خانتين.');
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
        document.getElementById('signupQuotaTotal')?.addEventListener('input', updatePreview);

        segmentsList.addEventListener('change', () => {
            reindexSegments();
            updatePreview();
        });

        wheelTitleInput.addEventListener('input', updatePreview);
        document.getElementById('previewCustomerWheel')?.addEventListener('click', () => {
            updatePreview();
            const segments = rows().filter(row => row.querySelector('[data-name="is_active"]').checked).map(row => ({
                label: row.querySelector('[data-name="label"]').value,
                color: row.querySelector('[data-name="color"]').value,
                discount_type: row.querySelector('[data-name="discount_type"]').value,
                discount_value: Number(row.querySelector('[data-name="discount_value"]').value || 0),
                win_quota: Number(row.querySelector('[data-name="win_quota"]').value || 0),
                gift_image: row.querySelector('.signup-gift-preview').hidden ? null : row.querySelector('.signup-gift-preview').src,
            }));
            if (segments.length < 2 || !segments.some(segment => segment.win_quota > 0)) {
                alert('فعّل خانتين على الأقل وحدد عدد ظهور موجب لجائزة واحدة على الأقل لمعاينة العجلة.');return;
            }
            window.OzmanSignupWheelPreview({title: wheelTitleInput.value, segments});
        });
        reindexSegments();
        updatePreview();
    </script>
</body>

</html>
