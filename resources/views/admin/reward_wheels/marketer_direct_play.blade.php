<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $wheel->title }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.reward_wheels.marketer_direct_play')
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
