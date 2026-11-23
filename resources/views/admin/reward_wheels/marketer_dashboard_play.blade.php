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
    @viewStyle('admin.reward_wheels.marketer_dashboard_play')
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => $wheel->title])

            @php
                $progress = $questionsCount > 0 ? round(($currentQuestionNumber / $questionsCount) * 100, 2) : 100;
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
                $previousQuestionNumber = $questionsCount > 0
                    ? ($currentQuestionNumber <= 1 ? $questionsCount : $currentQuestionNumber - 1)
                    : 1;
                $nextQuestionNumber = $questionsCount > 0
                    ? ($currentQuestionNumber >= $questionsCount ? 1 : $currentQuestionNumber + 1)
                    : 1;
            @endphp

            <div class="content">
                <div class="play-shell">
                    <header class="play-head">
                        <div>
                            <div class="kicker">لوحة التحكم</div>
                            <h1>{{ $wheel->title }}</h1>
                            <p>أجيبي سؤالاً واحداً بشكل صحيح لفتح العجلة. عند الإجابة الخاطئة ننتقل للسؤال التالي مباشرة.</p>
                        </div>
                        <form method="POST" action="{{ route('reward-wheels.marketer.reset') }}">
                            @csrf
                            <button class="btn secondary" type="submit">
                                <i class="ti ti-refresh" aria-hidden="true"></i>
                                البدء من جديد
                            </button>
                        </form>
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

                    <div class="stage">
                        <section class="panel question-card">
                            @if(! $wheel->is_active)
                                <div class="notice inactive">العجلة غير مفعلة حالياً.</div>
                            @elseif($allQuestionsCompleted && ! $isUnlocked)
                                <div class="notice success">
                                    تم ربح جميع الأسئلة بنجاح. يمكنك استخدام زر «البدء من جديد» لإعادة الجولة.
                                </div>
                            @elseif(! $isUnlocked)
                                <div class="question-top">
                                    <div>
                                        <div class="kicker">الأسئلة</div>
                                        <div class="question-title">اختاري الإجابة المناسبة</div>
                                    </div>
                                    <span class="step-pill">
                                        <i class="ti ti-list-check" aria-hidden="true"></i>
                                        سؤال {{ $currentQuestionNumber }} من {{ $questionsCount }}
                                    </span>
                                </div>

                                <div class="progress-track" aria-hidden="true">
                                    <div class="progress-fill" style="--progress: {{ $progress }}%"></div>
                                </div>

                                <div class="step-dots" aria-label="اختيار السؤال">
                                    @for($i = 1; $i <= $questionsCount; $i++)
                                        <a class="step-dot {{ $i === $currentQuestionNumber ? 'is-current' : '' }} {{ $completedQuestionIds->contains((int) $questions[$i - 1]->id) ? 'is-completed' : '' }}"
                                           href="{{ route('reward-wheels.marketer.play', ['question' => $i]) }}"
                                           aria-label="السؤال {{ $i }}">{{ $i }}</a>
                                    @endfor
                                </div>

                                @if($currentQuestion)
                                    @php
                                        $questionOptions = collect($currentQuestion->options ?? [])->filter()->values();
                                    @endphp

                                    <form method="POST" action="{{ route('reward-wheels.marketer.unlock') }}">
                                        @csrf
                                        <input type="hidden" name="question_id" value="{{ $currentQuestion->id }}">
                                        <div class="question-title">{{ $currentQuestion->question }}</div>

                                        @if($questionOptions->isNotEmpty())
                                            <div class="choices" role="radiogroup" aria-label="{{ $currentQuestion->question }}">
                                                @foreach($questionOptions as $option)
                                                    <label class="choice">
                                                        <input type="radio" name="answer" value="{{ $option }}" @checked(old('answer') === $option) required>
                                                        <span>{{ $option }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <input class="answer-input" name="answer" value="{{ old('answer') }}" autocomplete="off" placeholder="اكتبي الإجابة هنا">
                                        @endif

                                        <div class="question-actions">
                                            <a class="btn secondary" href="{{ route('reward-wheels.marketer.play', ['question' => $previousQuestionNumber]) }}">
                                                <i class="ti ti-arrow-right" aria-hidden="true"></i>
                                                السابق
                                            </a>
                                            <button class="btn" type="submit">
                                                <i class="ti ti-lock-open" aria-hidden="true"></i>
                                                فتح العجلة
                                            </button>
                                            <a class="btn secondary" href="{{ route('reward-wheels.marketer.play', ['question' => $nextQuestionNumber]) }}">
                                                التالي
                                                <i class="ti ti-arrow-left" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </form>
                                @endif
                            @else
                                <div class="question-top">
                                    <div>
                                        <div class="kicker">جاهزة</div>
                                        <div class="question-title">العجلة مفتوحة وجاهزة للّف</div>
                                    </div>
                                    <span class="step-pill">
                                        <i class="ti ti-circle-check" aria-hidden="true"></i>
                                        تم الفتح
                                    </span>
                                </div>
                                <p>يمكنك لف العجلة الآن، أو البدء من جديد لإعادة عرض الأسئلة من أول سؤال.</p>
                                @if(auth()->user()?->isSuperAdmin())
                                    <div class="question-actions">
                                        <a class="btn secondary" href="{{ route('reward-wheels.marketer.edit') }}">
                                            <i class="ti ti-settings" aria-hidden="true"></i>
                                            تعديل الإعدادات
                                        </a>
                                    </div>
                                @endif
                            @endif
                        </section>

                        <section class="panel wheel-panel">
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

                            <button class="btn" id="spinBtn" type="button" @disabled(! $wheel->is_active || ! $isUnlocked || $segments->isEmpty())>
                                <i class="ti ti-rotate-clockwise" aria-hidden="true"></i>
                                لف العجلة
                            </button>
                            <div class="result" id="result"></div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const wheel = document.getElementById('wheel');
        const spinBtn = document.getElementById('spinBtn');
        const result = document.getElementById('result');
        const segments = @json($segmentPayload);
        const spinUrl = @json(route('reward-wheels.marketer.spin'));
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
                if (spinPayload.next_question_url) {
                    window.setTimeout(() => {
                        window.location.href = spinPayload.next_question_url;
                    }, 2200);
                }
            }, 4100);
        });
    </script>
</body>
</html>
