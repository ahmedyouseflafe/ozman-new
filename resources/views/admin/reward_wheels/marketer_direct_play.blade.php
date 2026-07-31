<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $wheel->title }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --yellow:#ffd60a; --danger:#ff3b30; --border:rgba(255,255,255,.1); --text:#fff; --muted:rgba(255,255,255,.66); }
        body { min-height:100vh; background:radial-gradient(circle at 12% 12%,rgba(0,229,255,.14),transparent 30%),radial-gradient(circle at 86% 18%,rgba(112,0,255,.15),transparent 32%),linear-gradient(180deg,#030303,#08020f); color:var(--text); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; direction:rtl; }
        .main { min-height:100vh; margin-right:245px; }
        .content { min-height:calc(100vh - 80px); padding:28px 34px 46px; display:grid; place-items:center; }
        .play-shell { width:min(100%,980px); display:grid; gap:22px; }
        .play-head { display:flex; justify-content:space-between; align-items:end; gap:16px; }
        .kicker { color:var(--primary); font-size:13px; font-weight:900; margin-bottom:6px; }
        h1 { color:var(--primary); font-size:34px; line-height:1.15; font-weight:900; text-shadow:0 0 18px rgba(0,229,255,.42); }
        p { color:var(--muted); font-weight:700; margin-top:8px; }
        .panel { border:1px solid var(--border); background:linear-gradient(145deg,rgba(255,255,255,.08),rgba(255,255,255,.025)); border-radius:24px; padding:24px; box-shadow:0 18px 48px rgba(0,0,0,.34); display:grid; place-items:center; text-align:center; }
        .wheel-wrap { position:relative; display:grid; place-items:center; padding:18px; }
        .pointer { position:absolute; top:4px; z-index:3; width:0; height:0; border-left:18px solid transparent; border-right:18px solid transparent; border-top:34px solid var(--yellow); filter:drop-shadow(0 0 10px rgba(255,214,10,.55)); }
        .wheel { width:min(74vw,460px); aspect-ratio:1; border-radius:50%; border:12px solid #050505; box-shadow:0 0 36px rgba(0,229,255,.26), inset 0 0 40px rgba(0,0,0,.34); transition:transform 4s cubic-bezier(.12,.74,.18,1); position:relative; overflow:hidden; background:var(--wheel-bg); }
        .wheel::after { content:''; position:absolute; inset:38%; border-radius:50%; background:#050505; border:1px solid rgba(255,255,255,.16); box-shadow:0 0 20px rgba(0,229,255,.22); z-index:2; }
        .wheel-labels { position:absolute; inset:0; pointer-events:none; }
        .wheel-label { position:absolute; top:50%; left:50%; width:44%; transform-origin:0 0; color:#fff; font-size:13px; font-weight:900; text-align:center; text-shadow:0 2px 8px rgba(0,0,0,.58); z-index:1; }
        .wheel-prize-slot { position:absolute; inset:0; transform-origin:center; z-index:1; }
        .wheel-prize-image { position:absolute; top:18px; left:50%; width:38px; height:38px; object-fit:cover; border-radius:10px; border:2px solid rgba(255,255,255,.72); background:#050505; box-shadow:0 6px 18px rgba(0,0,0,.32); transform-origin:center; }
        .btn { border:0; min-height:46px; border-radius:999px; padding:0 22px; display:inline-flex; align-items:center; justify-content:center; gap:8px; color:#001014; background:linear-gradient(135deg,var(--primary),var(--accent)); font-family:inherit; font-weight:900; cursor:pointer; text-decoration:none; margin-top:18px; }
        .btn.secondary { color:#fff; background:rgba(255,255,255,.075); border:1px solid var(--border); }
        .btn:disabled { opacity:.55; cursor:not-allowed; }
        .result { min-height:54px; margin-top:18px; color:var(--green); font-size:20px; font-weight:900; text-align:center; }
        .gift-result-img { display:block; width:min(220px,80vw); max-height:220px; object-fit:cover; border-radius:20px; border:1px solid var(--border); margin:12px auto 0; box-shadow:0 0 24px rgba(0,229,255,.18); }
        .inactive { color:#ffb8b3; margin-top:12px; font-weight:900; }
        @media(max-width:900px){ .main{margin-right:0}.content{padding:22px 16px 36px}.play-head{flex-direction:column;align-items:stretch} h1{font-size:28px}.wheel{width:min(84vw,370px)}.wheel-prize-image{top:13px;width:32px;height:32px;border-radius:8px} }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => $wheel->title])

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

            <div class="content">
                <div class="play-shell">
                    <header class="play-head">
                        <div>
                            <div class="kicker">عجلة مباشرة</div>
                            <h1>{{ $wheel->title }}</h1>
                            <p>بدون أسئلة. اضغطي لف العجلة فقط، والدورة محسوبة من 20 لفة.</p>
                        </div>
                        @if(auth()->user()?->isSuperAdmin())
                            <a class="btn secondary" href="{{ route('reward-wheels.marketer.direct.edit') }}">
                                <i class="ti ti-settings" aria-hidden="true"></i>
                                تعديل الإعدادات
                            </a>
                        @endif
                    </header>

                    <section class="panel">
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
                                            <span class="wheel-prize-slot" style="transform:rotate({{ $angle + 90 }}deg)"><img class="wheel-prize-image" src="{{ asset($segment->gift_image) }}" alt="{{ $segment->label }}" style="transform:translateX(-50%) rotate({{ $counterAngle - 90 }}deg)"></span>
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
                </div>
            </div>
        </main>
    </div>

    <script>
        const wheel = document.getElementById('wheel');
        const spinBtn = document.getElementById('spinBtn');
        const result = document.getElementById('result');
        const segments = @json($segmentPayload);
        const spinUrl = @json(route('reward-wheels.marketer.direct.spin'));
        const csrfToken = @json(csrf_token());
        let currentRotation = 0;

        spinBtn?.addEventListener('click', async () => {
            if (!segments.length) return;

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

                if (!response.ok) {
                    throw new Error('spin_failed');
                }

                spinPayload = await response.json();
            } catch (error) {
                result.textContent = 'تعذر تنفيذ اللفة، حاولي مرة أخرى.';
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
                result.textContent = `النتيجة: ${selectedSegment.label}`;

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
