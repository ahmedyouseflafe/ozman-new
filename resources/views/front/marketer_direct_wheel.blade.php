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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary:#00e5ff;
            --accent:#7000ff;
            --green:#25d366;
            --yellow:#ffd60a;
            --danger:#ff3b30;
            --panel:rgba(13,18,20,.82);
            --panel-strong:rgba(255,255,255,.075);
            --border:rgba(0,229,255,.22);
            --soft-border:rgba(255,255,255,.11);
            --text:#fff;
            --muted:rgba(255,255,255,.68);
        }
        body {
            min-height:100vh;
            background:
                radial-gradient(circle at 14% 18%,rgba(0,229,255,.16),transparent 30%),
                radial-gradient(circle at 84% 12%,rgba(112,0,255,.17),transparent 34%),
                linear-gradient(180deg,#030303,#08020f 62%,#020405);
            color:var(--text);
            font-family:'Cairo','Segoe UI',Tahoma,sans-serif;
            direction:rtl;
        }
        .page {
            width:min(100%,1080px);
            margin:0 auto;
            min-height:100vh;
            padding:34px 18px 46px;
            display:grid;
            align-items:center;
        }
        .brand {
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            color:var(--primary);
            font-weight:900;
            margin-bottom:18px;
            text-shadow:0 0 18px rgba(0,229,255,.34);
        }
        .card {
            width:min(100%,700px);
            margin:0 auto;
            border:1px solid var(--border);
            border-radius:28px;
            background:linear-gradient(145deg,rgba(0,229,255,.07),rgba(255,255,255,.035));
            box-shadow:0 24px 70px rgba(0,0,0,.42), inset 0 1px 0 rgba(255,255,255,.06);
            padding:28px;
        }
        .gate-head,
        .wheel-head {
            display:grid;
            gap:8px;
            text-align:center;
            margin-bottom:24px;
        }
        .kicker { color:var(--muted); font-weight:900; font-size:15px; }
        h1 {
            color:var(--primary);
            font-size:clamp(30px,6vw,52px);
            line-height:1.14;
            font-weight:900;
            text-shadow:0 0 22px rgba(0,229,255,.42);
        }
        p { color:var(--muted); font-weight:800; line-height:1.8; }
        .type-picker {
            display:flex;
            justify-content:center;
            gap:18px;
            margin:18px 0 24px;
        }
        .type-btn {
            width:132px;
            aspect-ratio:1;
            border-radius:50%;
            border:1px solid var(--soft-border);
            background:rgba(255,255,255,.055);
            color:#fff;
            font:inherit;
            font-weight:900;
            display:grid;
            place-items:center;
            gap:8px;
            cursor:pointer;
        }
        .type-btn.active {
            border-color:var(--primary);
            box-shadow:0 0 26px rgba(0,229,255,.18);
        }
        .type-btn i {
            width:48px;
            height:48px;
            border-radius:50%;
            display:grid;
            place-items:center;
            background:rgba(0,229,255,.13);
            color:var(--primary);
            font-size:25px;
        }
        .form-grid { display:grid; gap:16px; }
        label { color:rgba(255,255,255,.78); font-weight:900; display:grid; gap:8px; }
        input,
        textarea {
            width:100%;
            min-height:58px;
            border-radius:18px;
            border:1px solid var(--soft-border);
            background:rgba(255,255,255,.065);
            color:#fff;
            padding:0 16px;
            font:inherit;
            font-weight:800;
            outline:none;
        }
        textarea { min-height:96px; padding-top:14px; resize:vertical; }
        input:focus,
        textarea:focus {
            border-color:var(--primary);
            box-shadow:0 0 0 3px rgba(0,229,255,.12);
        }
        .location-row {
            display:grid;
            grid-template-columns:1fr auto;
            gap:10px;
            align-items:end;
        }
        .btn {
            min-height:56px;
            border:0;
            border-radius:999px;
            padding:0 24px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:9px;
            background:linear-gradient(135deg,var(--primary),var(--accent));
            color:#001014;
            font:inherit;
            font-weight:900;
            cursor:pointer;
            text-decoration:none;
            white-space:nowrap;
        }
        .btn.secondary {
            color:#fff;
            background:rgba(255,255,255,.08);
            border:1px solid var(--soft-border);
        }
        .btn:disabled { opacity:.58; cursor:not-allowed; }
        .status {
            min-height:24px;
            color:var(--muted);
            font-weight:800;
            text-align:center;
        }
        .status.error { color:#ff9b94; }
        .status.ok { color:var(--green); }
        .hidden { display:none !important; }
        .wheel-card {
            width:min(100%,880px);
            padding:26px;
        }
        .wheel-panel {
            border:1px solid var(--soft-border);
            background:linear-gradient(145deg,rgba(255,255,255,.075),rgba(255,255,255,.025));
            border-radius:26px;
            padding:24px;
            display:grid;
            place-items:center;
            text-align:center;
            box-shadow:0 18px 52px rgba(0,0,0,.35);
        }
        .marketer-pill {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:38px;
            padding:0 15px;
            border-radius:999px;
            border:1px solid var(--border);
            color:var(--primary);
            background:rgba(0,229,255,.08);
            font-weight:900;
            margin-top:8px;
        }
        .wheel-wrap { position:relative; display:grid; place-items:center; padding:18px; }
        .pointer {
            position:absolute;
            top:4px;
            z-index:3;
            width:0;
            height:0;
            border-left:18px solid transparent;
            border-right:18px solid transparent;
            border-top:34px solid var(--yellow);
            filter:drop-shadow(0 0 10px rgba(255,214,10,.55));
        }
        .wheel {
            width:min(78vw,470px);
            aspect-ratio:1;
            border-radius:50%;
            border:12px solid #050505;
            box-shadow:0 0 36px rgba(0,229,255,.26), inset 0 0 40px rgba(0,0,0,.34);
            transition:transform 4s cubic-bezier(.12,.74,.18,1);
            position:relative;
            overflow:hidden;
            background:var(--wheel-bg);
        }
        .wheel::after {
            content:'';
            position:absolute;
            inset:38%;
            border-radius:50%;
            background:#050505;
            border:1px solid rgba(255,255,255,.16);
            box-shadow:0 0 20px rgba(0,229,255,.22);
            z-index:2;
        }
        .wheel-labels { position:absolute; inset:0; pointer-events:none; }
        .wheel-label {
            position:absolute;
            top:50%;
            left:50%;
            width:44%;
            transform-origin:0 0;
            color:#fff;
            font-size:13px;
            font-weight:900;
            text-align:center;
            text-shadow:0 2px 8px rgba(0,0,0,.58);
            z-index:1;
        }
        .wheel-prize-image {
            position:absolute;
            top:50%;
            left:50%;
            width:58px;
            height:58px;
            object-fit:cover;
            border-radius:16px;
            border:2px solid rgba(255,255,255,.72);
            background:#050505;
            box-shadow:0 6px 18px rgba(0,0,0,.32);
            transform-origin:0 0;
            z-index:1;
        }
        .result {
            min-height:54px;
            margin-top:18px;
            color:var(--green);
            font-size:21px;
            font-weight:900;
            text-align:center;
        }
        .gift-result-img {
            display:block;
            width:min(220px,80vw);
            max-height:220px;
            object-fit:cover;
            border-radius:20px;
            border:1px solid var(--soft-border);
            margin:12px auto 0;
            box-shadow:0 0 24px rgba(0,229,255,.18);
        }
        .inactive {
            color:#ffb8b3;
            margin-bottom:12px;
            font-weight:900;
        }
        @media (max-width: 640px) {
            .page { padding:18px 12px 30px; align-items:start; }
            .card { padding:20px 14px; border-radius:22px; }
            .type-picker { gap:12px; }
            .type-btn { width:112px; }
            .location-row { grid-template-columns:1fr; }
            .wheel { width:min(88vw,360px); border-width:9px; }
        }
    </style>
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
                <div class="kicker">أهلاً بك في Ozman</div>
                <h1>سجل دخولك لتلف العجلة</h1>
                <p>عبّي بياناتك مرة واحدة، وبعدها بتظهرلك العجلة المباشرة الخاصة بالمسوق.</p>
            </header>

            <div class="type-picker" aria-label="نوع التسجيل">
                <button class="type-btn active" type="button" aria-pressed="true">
                    <i class="ti ti-user"></i>
                    عميل
                </button>
                <button class="type-btn" type="button" aria-pressed="false" disabled title="هذه العجلة مخصصة للعملاء">
                    <i class="ti ti-building-store"></i>
                    صاحب متجر
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
                    الاسم
                    <input type="text" name="name" id="customerName" placeholder="اكتب اسمك" required>
                </label>
                <label>
                    رقم الهاتف
                    <input type="tel" name="phone" id="customerPhone" placeholder="05xxxxxxxx" dir="ltr" required>
                </label>
                <label>
                    مكان السكن
                    <textarea name="residence_address" id="customerAddress" placeholder="المدينة، الحي، أقرب علامة" required></textarea>
                </label>
                <div class="location-row">
                    <label>
                        لوكيشن العميل
                        <input type="text" id="customerLocationPreview" placeholder="اضغط تحديد الموقع" readonly required>
                    </label>
                    <button class="btn secondary" type="button" id="detectLocationBtn">
                        <i class="ti ti-map-pin"></i>
                        تحديد الموقع
                    </button>
                </div>
                <button class="btn" type="submit" id="saveCustomerBtn">
                    <i class="ti ti-login-2"></i>
                    تسجيل وفتح العجلة
                </button>
                <div class="status" id="gateStatus"></div>
            </form>
        </section>

        <section class="card wheel-card hidden" id="wheelStage">
            <header class="wheel-head">
                <div class="kicker">عجلة مباشرة</div>
                <h1>{{ $wheel->title }}</h1>
                <p>اضغط لف العجلة وشوف جائزتك فوراً.</p>
                <span class="marketer-pill"><i class="ti ti-user-star"></i> المسوق: {{ $marketer->name }}</span>
            </header>

            <section class="wheel-panel">
                @if(! $wheel->is_active)
                    <div class="inactive">العجلة غير مفعلة حالياً.</div>
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
                                    <img class="wheel-prize-image" src="{{ asset($segment->gift_image) }}" alt="{{ $segment->label }}" style="transform: rotate({{ $angle }}deg) translate(min(28vw, 124px), -50%) rotate({{ $counterAngle }}deg);">
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <button class="btn" id="spinBtn" type="button" @disabled(! $wheel->is_active || $segments->isEmpty())>
                    <i class="ti ti-rotate-clockwise" aria-hidden="true"></i>
                    لف العجلة
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
