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
        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --yellow: #ffd60a;
            --danger: #ff3b30;
            --border: rgba(255,255,255,.1);
            --text: #fff;
            --muted: rgba(255,255,255,.66);
            --panel: rgba(255,255,255,.065);
        }
        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 12% 12%, rgba(0,229,255,.14), transparent 30%),
                radial-gradient(circle at 86% 18%, rgba(112,0,255,.15), transparent 32%),
                linear-gradient(180deg,#030303,#08020f);
            color: var(--text);
            font-family: 'Cairo','Segoe UI',Tahoma,sans-serif;
            direction: rtl;
        }
        .main { min-height: 100vh; margin-right: 245px; }
        .content { min-height: calc(100vh - 80px); padding: 28px 34px 46px; }
        .play-shell { width: min(100%, 1120px); margin: 0 auto; }
        .play-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
            margin-bottom: 22px;
        }
        .kicker { color: var(--primary); font-size: 13px; font-weight: 900; margin-bottom: 6px; }
        h1 { color: var(--primary); font-size: 34px; line-height: 1.15; font-weight: 900; text-shadow: 0 0 18px rgba(0,229,255,.42); }
        p { color: var(--muted); font-weight: 700; margin-top: 8px; }
        .stage { display: grid; grid-template-columns: minmax(0, .95fr) minmax(350px, 1.05fr); gap: 22px; align-items: start; }
        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.025));
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 18px 48px rgba(0,0,0,.34);
        }
        .notice,
        .errors {
            border-radius: 16px;
            padding: 12px 14px;
            font-weight: 900;
            margin-bottom: 14px;
        }
        .notice { border: 1px solid rgba(37,211,102,.28); color: var(--green); background: rgba(37,211,102,.1); }
        .errors { border: 1px solid rgba(255,59,48,.32); color: #ffb8b3; background: rgba(255,59,48,.1); }
        .inactive { color: #ffb8b3; border-color: rgba(255,59,48,.32); background: rgba(255,59,48,.08); }
        .question-card { min-height: 520px; display: flex; flex-direction: column; }
        .question-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }
        .step-pill {
            min-height: 36px;
            padding: 0 14px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--yellow));
            font-size: 12px;
            font-weight: 900;
        }
        .progress-track {
            height: 8px;
            background: rgba(255,255,255,.08);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .progress-fill {
            height: 100%;
            width: var(--progress, 0%);
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: inherit;
            box-shadow: 0 0 18px rgba(0,229,255,.45);
            transition: width .35s ease;
        }
        .step-dots { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
        .step-dot {
            min-width: 32px;
            height: 32px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.18);
            color: rgba(255,255,255,.72);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
            transition: border-color .25s ease, color .25s ease, transform .25s ease;
        }
        .step-dot:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-2px); }
        .step-dot.is-current { background: var(--primary); color: #001014; box-shadow: 0 0 14px rgba(0,229,255,.62); }
        .step-dot.is-completed { border-color: var(--green); color: var(--green); background: rgba(37,211,102,.1); }
        .step-dot.is-completed::after { content: '✓'; margin-right: 3px; font-size: 10px; }
        .step-dot.is-current.is-completed { background: var(--primary); color: #001014; }
        .question-title {
            font-size: 24px;
            line-height: 1.45;
            color: #fff;
            font-weight: 900;
            margin-bottom: 18px;
        }
        .choices { display: grid; gap: 12px; margin-bottom: 18px; }
        .choice {
            min-height: 58px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,.055);
            border-radius: 18px;
            padding: 13px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-weight: 900;
            cursor: pointer;
            transition: border-color .25s ease, transform .25s ease, box-shadow .25s ease;
        }
        .choice:hover { border-color: rgba(0,229,255,.5); transform: translateY(-2px); }
        .choice:has(input:checked) { border-color: var(--primary); box-shadow: 0 0 18px rgba(0,229,255,.18); }
        input[type="radio"] { width: 18px; height: 18px; accent-color: var(--primary); flex-shrink: 0; }
        .answer-input {
            width: 100%;
            min-height: 50px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,.06);
            border-radius: 16px;
            color: #fff;
            padding: 0 14px;
            font-family: inherit;
            font-weight: 800;
            outline: none;
        }
        .question-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: auto; }
        .btn {
            border: 0;
            min-height: 46px;
            border-radius: 999px;
            padding: 0 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #001014;
            background: linear-gradient(135deg,var(--primary),var(--accent));
            font-family: inherit;
            font-weight: 900;
            cursor: pointer;
            text-decoration: none;
        }
        .btn.secondary { color: #fff; background: rgba(255,255,255,.075); border: 1px solid var(--border); }
        .btn:disabled { opacity: .55; cursor: not-allowed; }
        .wheel-panel { display: grid; place-items: center; text-align: center; }
        .wheel-wrap { position: relative; display: grid; place-items: center; padding: 18px; }
        .pointer {
            position: absolute;
            top: 4px;
            z-index: 3;
            width: 0;
            height: 0;
            border-left: 18px solid transparent;
            border-right: 18px solid transparent;
            border-top: 34px solid var(--yellow);
            filter: drop-shadow(0 0 10px rgba(255,214,10,.55));
        }
        .wheel {
            width: min(72vw, 430px);
            aspect-ratio: 1;
            border-radius: 50%;
            border: 12px solid #050505;
            box-shadow: 0 0 36px rgba(0,229,255,.26), inset 0 0 40px rgba(0,0,0,.34);
            transition: transform 4s cubic-bezier(.12,.74,.18,1);
            position: relative;
            overflow: hidden;
            background: var(--wheel-bg);
        }
        .wheel::after {
            content: '';
            position: absolute;
            inset: 38%;
            border-radius: 50%;
            background: #050505;
            border: 1px solid rgba(255,255,255,.16);
            box-shadow: 0 0 20px rgba(0,229,255,.22);
            z-index: 2;
        }
        .wheel-labels { position: absolute; inset: 0; pointer-events: none; }
        .wheel-label {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 44%;
            transform-origin: 0 0;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            text-align: center;
            text-shadow: 0 2px 8px rgba(0,0,0,.58);
            z-index: 1;
        }
        .wheel-prize-slot {
            position: absolute;
            inset: 0;
            transform-origin: center;
            z-index: 1;
        }
        .wheel-prize-image {
            position: absolute;
            top: 18px;
            left: 50%;
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,.72);
            background: #050505;
            box-shadow: 0 6px 18px rgba(0,0,0,.32);
            transform-origin: center;
        }
        .result { min-height: 54px; margin-top: 18px; color: var(--green); font-size: 20px; font-weight: 900; text-align: center; }
        .gift-result-img {
            display: block;
            width: min(220px, 80vw);
            max-height: 220px;
            object-fit: cover;
            border-radius: 20px;
            border: 1px solid var(--border);
            margin: 12px auto 0;
            box-shadow: 0 0 24px rgba(0,229,255,.18);
        }
        @media(max-width: 1000px) {
            .main { margin-right: 0; }
            .stage { grid-template-columns: 1fr; }
            .content { padding: 22px 16px 36px; }
            .play-head { flex-direction: column; align-items: stretch; }
        }
        @media(max-width: 640px) {
            h1 { font-size: 28px; }
            .panel { padding: 18px; }
            .question-title { font-size: 21px; }
            .wheel { width: min(84vw, 360px); }
            .wheel-prize-image { top:13px; width:32px; height:32px; border-radius:8px; }
        }
    </style>
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
