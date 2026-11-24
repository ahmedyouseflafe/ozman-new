<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $wheel->title }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.marketer_direct_wheel')
</head>

<body>
    @php
        $segments = $wheel->segments->where('is_active', true)->values();
        $segmentCount = max($segments->count(), 1);
        $slice = 100 / $segmentCount;
        $gradientStartAngle = -180 / $segmentCount;
        $gradientParts = $segments->map(function ($segment, $index) use ($slice) {
            $start = round($index * $slice, 4);
            $end = round(($index + 1) * $slice, 4);
            return ($segment->color ?: '#00e5ff') . ' ' . $start . '% ' . $end . '%';
        })->implode(', ');
        $segmentPayload = $segments
            ->map(function ($segment) {
                return [
                    'label' => $segment->label,
                    'discount_type' => $segment->discount_type,
                    'gift_image' => $segment->gift_image ? asset($segment->gift_image) : null,
                    'win_quota' => max(0, (int) ($segment->win_quota ?? 1)),
                ];
            })
            ->values();
    @endphp

    <main class="page">
        <section class="card" id="customerGate">
            <div class="brand"><i class="ti ti-bolt"></i> Ozman</div>
            <header class="gate-head">
                <div class="kicker">{{ __('أهلاً بك في Ozman') }}</div>
                <h1>{{ __('سجل دخولك لتلف العجلة') }}</h1>
                <p>{{ __('عبّي بياناتك مرة واحدة، وبعدها بتظهرلك العجلة المباشرة الخاصة بالمسوق.') }}</p>
            </header>

            <div class="type-picker" aria-label="{{ __('نوع التسجيل') }}">
                <button class="type-btn active" type="button" aria-pressed="true">
                    <i class="ti ti-user"></i>
                    {{ __('عميل') }}
                </button>
                <button class="type-btn" type="button" aria-pressed="false" disabled title="{{ __('هذه العجلة مخصصة للعملاء') }}">
                    <i class="ti ti-building-store"></i>
                    {{ __('صاحب متجر') }}
                </button>
            </div>

            <form class="form-grid" id="customerGateForm">
                <input type="hidden" name="type" value="customer">
                <input type="hidden" name="distributor_marketer_id" value="{{ $marketer->id }}">
                <input type="hidden" name="distributor_id" value="{{ $marketer->distributor_id }}">
                <input type="hidden" name="marketing_source" value="marketer_direct_wheel">
                <input type="hidden" name="latitude" id="customerLatitude">
                <input type="hidden" name="longitude" id="customerLongitude">
                <input type="hidden" name="map_link" id="customerMapLink">

                <label>
                    {{ __('الاسم') }}
                    <input type="text" name="name" id="customerName" placeholder="{{ __('اكتب اسمك') }}" required>
                </label>
                <label>
                    {{ __('رقم الهاتف') }}
                    <input type="tel" name="phone" id="customerPhone" placeholder="05xxxxxxxx" inputmode="tel" maxlength="16"
                        pattern="(?:05[02345689][0-9]{7}|(?:\+|00)?9705[69][0-9]{7}|(?:\+|00)?9725[023458][0-9]{7})"
                        title="{{ __('أدخل رقم جوال صحيح مثل 0591234567') }}" dir="ltr" required>
                </label>
                <label>
                    {{ __('مكان السكن') }}
                    <textarea name="residence_address" id="customerAddress" placeholder="{{ __('المدينة، الحي، أقرب علامة') }}" required></textarea>
                </label>
                <div class="location-row">
                    <label>
                        {{ __('لوكيشن العميل') }}
                        <input type="text" id="customerLocationPreview" placeholder="{{ __('اضغط تحديد الموقع') }}" readonly required>
                    </label>
                    <button class="btn secondary" type="button" id="detectLocationBtn">
                        <i class="ti ti-map-pin"></i>
                        {{ __('تحديد الموقع') }}
                    </button>
                </div>
                <button class="btn" type="submit" id="saveCustomerBtn">
                    <i class="ti ti-login-2"></i>
                    {{ __('تسجيل وفتح العجلة') }}
                </button>
                <div class="status" id="gateStatus"></div>
            </form>
        </section>

        <section class="card wheel-card hidden" id="wheelStage">
            <header class="wheel-head">
                <div class="kicker">{{ __('عجلة مباشرة') }}</div>
                <h1>{{ $wheel->title }}</h1>
                <p>{{ __('اضغط لف العجلة وشوف جائزتك فوراً.') }}</p>
                <span class="marketer-pill"><i class="ti ti-user-star"></i> {{ __('المسوق: :name', ['name' => $marketer->name]) }}</span>
            </header>

            <section class="wheel-panel">
                @if(! $wheel->is_active)
                    <div class="inactive">{{ __('العجلة غير مفعلة حالياً.') }}</div>
                @endif

                <div class="wheel-wrap">
                    <div class="pointer" aria-hidden="true"></div>
                    <div class="wheel" id="wheel" style="--wheel-bg: conic-gradient(from {{ $gradientStartAngle }}deg, {{ $gradientParts ?: '#00e5ff 0% 100%' }});">
                        <div class="wheel-labels">
                            @foreach($segments as $index => $segment)
                                @php
                                    $angle = ($index * (360 / $segmentCount)) - 90;
                                    $counterAngle = -$angle;
                                @endphp
                                <span class="wheel-label" style="transform: rotate({{ $angle }}deg) translate(25%, -50%);">{{ $segment->label }}</span>
                                @if($segment->discount_type === 'gift' && $segment->gift_image)
                                    <span class="wheel-prize-slot" style="transform:rotate({{ $angle + 90 }}deg)"><img class="wheel-prize-image" src="{{ asset($segment->gift_image) }}" alt="{{ $segment->label }}" style="transform:translateX(-50%) rotate({{ $counterAngle - 90 }}deg)"></span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <button class="btn" id="spinBtn" type="button" @disabled(! $wheel->is_active || $segments->isEmpty())>
                    <i class="ti ti-rotate-clockwise" aria-hidden="true"></i>
                    {{ __('لف العجلة') }}
                </button>
                <div class="result" id="result"></div>
            </section>
        </section>
    </main>

    <script>
        const customerStorageKey = 'ozman_customer_profile';
        const visitorRegistrationStorageKey = 'ozman_visitor_registration_done_v2';
        const visitorTypeStorageKey = 'ozman_visitor_type';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const gate = document.getElementById('customerGate');
        const wheelStage = document.getElementById('wheelStage');
        const gateForm = document.getElementById('customerGateForm');
        const gateStatus = document.getElementById('gateStatus');
        const detectLocationBtn = document.getElementById('detectLocationBtn');
        const latitudeInput = document.getElementById('customerLatitude');
        const longitudeInput = document.getElementById('customerLongitude');
        const mapLinkInput = document.getElementById('customerMapLink');
        const locationPreview = document.getElementById('customerLocationPreview');

        function loadCustomerProfile() {
            try {
                const parsed = JSON.parse(localStorage.getItem(customerStorageKey) || '{}');
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        function hasCustomerProfile(profile = loadCustomerProfile()) {
            return Boolean(String(profile.name || '').trim() && String(profile.phone || profile.whatsapp || '').trim());
        }

        function showWheel() {
            gate?.classList.add('hidden');
            wheelStage?.classList.remove('hidden');
        }

        function setStatus(message, type = '') {
            if (!gateStatus) return;
            gateStatus.textContent = message || '';
            gateStatus.className = `status ${type}`.trim();
        }

        const savedProfile = loadCustomerProfile();
        if (hasCustomerProfile(savedProfile)) {
            localStorage.setItem(visitorRegistrationStorageKey, '1');
            localStorage.setItem(visitorTypeStorageKey, 'customer');
            showWheel();
        }

        detectLocationBtn?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                setStatus('المتصفح لا يدعم تحديد الموقع تلقائياً.', 'error');
                return;
            }

            detectLocationBtn.disabled = true;
            setStatus('جاري تحديد موقعك...');
            navigator.geolocation.getCurrentPosition((position) => {
                const latitude = position.coords.latitude.toFixed(7);
                const longitude = position.coords.longitude.toFixed(7);
                const mapLink = `https://www.google.com/maps?q=${latitude},${longitude}`;
                latitudeInput.value = latitude;
                longitudeInput.value = longitude;
                mapLinkInput.value = mapLink;
                locationPreview.value = mapLink;
                detectLocationBtn.disabled = false;
                setStatus('تم تحديد الموقع بنجاح.', 'ok');
            }, () => {
                detectLocationBtn.disabled = false;
                setStatus('لم نقدر نحدد الموقع. اسمح للموقع بالوصول للّوكيشن وجرب مرة ثانية.', 'error');
            }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
        });

        gateForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!gateForm.reportValidity()) return;
            if (!latitudeInput.value || !longitudeInput.value || !mapLinkInput.value) {
                setStatus('حدد موقعك قبل فتح العجلة.', 'error');
                return;
            }

            const saveBtn = document.getElementById('saveCustomerBtn');
            saveBtn.disabled = true;
            setStatus('جاري حفظ بياناتك...');

            const formData = new FormData(gateForm);

            try {
                const response = await fetch(@json(route('visitor-registrations.store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                    throw new Error(firstError || 'تعذر حفظ بيانات التسجيل.');
                }

                localStorage.setItem(customerStorageKey, JSON.stringify({
                    name: formData.get('name') || '',
                    phone: formData.get('phone') || '',
                    whatsapp: formData.get('phone') || '',
                    address: formData.get('residence_address') || '',
                    latitude: formData.get('latitude') || '',
                    longitude: formData.get('longitude') || '',
                    mapLink: formData.get('map_link') || '',
                }));
                localStorage.setItem(visitorRegistrationStorageKey, '1');
                localStorage.setItem(visitorTypeStorageKey, 'customer');
                setStatus('تم التسجيل. العجلة جاهزة.', 'ok');
                showWheel();
            } catch (error) {
                setStatus(error.message || 'تعذر حفظ بيانات التسجيل.', 'error');
            } finally {
                saveBtn.disabled = false;
            }
        });

        const wheel = document.getElementById('wheel');
        const spinBtn = document.getElementById('spinBtn');
        const result = document.getElementById('result');
        const segments = @json($segmentPayload);
        const spinUrl = @json(route('front.marketer.direct-wheel.spin', ['marketer' => $marketer->tracking_code]));
        let currentRotation = 0;

        spinBtn?.addEventListener('click', async () => {
            if (!segments.length || !hasCustomerProfile()) return;

            spinBtn.disabled = true;
            result.textContent = '';

            let spinPayload;
            try {
                const response = await fetch(spinUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({}),
                });

                if (!response.ok) throw new Error('spin_failed');
                spinPayload = await response.json();
            } catch (error) {
                result.textContent = 'تعذر تنفيذ اللفة، حاول مرة ثانية.';
                spinBtn.disabled = false;
                return;
            }

            const selectedIndex = Number.isInteger(Number.parseInt(spinPayload.selected_index, 10))
                ? Number.parseInt(spinPayload.selected_index, 10)
                : 0;
            const segmentAngle = 360 / segments.length;
            const targetAngle = selectedIndex * segmentAngle;
            const rounds = 5 + Math.floor(Math.random() * 3);
            const currentAngle = ((currentRotation % 360) + 360) % 360;
            const desiredAngle = (360 - targetAngle) % 360;
            const correction = (desiredAngle - currentAngle + 360) % 360;
            currentRotation += rounds * 360 + correction;
            wheel.style.transform = `rotate(${currentRotation}deg)`;

            window.setTimeout(() => {
                const selectedSegment = spinPayload.segment || segments[selectedIndex];
                result.textContent = `مبروك! نتيجتك: ${selectedSegment.label}`;

                if (selectedSegment.discount_type === 'gift' && selectedSegment.gift_image) {
                    const image = document.createElement('img');
                    image.className = 'gift-result-img';
                    image.src = selectedSegment.gift_image;
                    image.alt = selectedSegment.label;
                    result.appendChild(image);
                }

                spinBtn.disabled = false;
            }, 4100);
        });
    </script>
</body>
</html>
