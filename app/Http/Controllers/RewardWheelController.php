<?php

namespace App\Http\Controllers;

use App\Models\DistributorMarketer;
use App\Models\RewardWheel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RewardWheelController extends Controller
{
    public function edit(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $wheel = $this->customerSignupWheel();

        return view('admin.reward_wheels.customer_signup', [
            'wheel' => $wheel->load('segments'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $validated = $request->validate($this->wheelValidationRules());

        DB::transaction(function () use ($validated) {
            $wheel = $this->customerSignupWheel();
            $wheel->update([
                'wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP,
                'title' => $validated['title'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->replaceSegments($wheel, $validated['segments']);
        });

        return redirect()
            ->route('reward-wheels.customer-signup.edit')
            ->with('status', 'تم حفظ بيانات عجلة خصومات العملاء بنجاح.');
    }

    public function purchaseIndex(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $wheels = RewardWheel::query()
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->with('segments')
            ->orderBy('min_order_total')
            ->latest()
            ->get()
            ->groupBy(fn(RewardWheel $wheel) => $this->purchaseRangeKey($wheel->min_order_total, $wheel->max_order_total))
            ->map(function ($group) {
                $wheel = $group->first();

                if ($group->count() > 1) {
                    $wheel->setRelation('segments', $group->flatMap->segments->sortBy('sort_order')->values());
                }

                return $wheel;
            })
            ->values();

        return view('admin.reward_wheels.purchase_amount', [
            'wheels' => $wheels,
            'editWheel' => null,
        ]);
    }

    public function purchaseStore(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $validated = $request->validate($this->wheelValidationRules() + [
            'min_order_total' => ['required', 'numeric', 'min:0'],
            'max_order_total' => ['nullable', 'numeric', 'gte:min_order_total'],
            'win_quota_total' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);
        $this->assertActiveSegmentsQuotaTotal(
            $validated['segments'],
            (int) $validated['win_quota_total'],
            'مجموع ظهور جوائز عجلة الشراء الفعالة يجب أن يساوي إجمالي فرص العجلة.'
        );

        $created = false;
        $wheel = null;

        DB::transaction(function () use ($validated, $request, &$created, &$wheel) {
            $maxOrderTotal = $validated['max_order_total'] ?? null;

            $wheel = RewardWheel::query()
                ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
                ->where('min_order_total', $validated['min_order_total'])
                ->when($maxOrderTotal === null || $maxOrderTotal === '', fn($query) => $query->whereNull('max_order_total'))
                ->when($maxOrderTotal !== null && $maxOrderTotal !== '', fn($query) => $query->where('max_order_total', $maxOrderTotal))
                ->first();

            if (! $wheel) {
                $created = true;
                $wheel = RewardWheel::create([
                    'key' => 'purchase_amount_' . now()->format('YmdHis') . '_' . random_int(100, 999),
                    'wheel_type' => RewardWheel::TYPE_PURCHASE_AMOUNT,
                    'title' => $validated['title'],
                    'min_order_total' => $validated['min_order_total'],
                    'max_order_total' => $maxOrderTotal ?: null,
                    'win_quota_total' => (int) $validated['win_quota_total'],
                    'is_active' => (bool) ($validated['is_active'] ?? false),
                    'spin_cycle' => null,
                ]);
            } else {
                $wheel->update([
                    'title' => $validated['title'],
                    'win_quota_total' => (int) $validated['win_quota_total'],
                    'is_active' => (bool) ($validated['is_active'] ?? false),
                    'spin_cycle' => null,
                ]);
            }

            $this->replaceSegments($wheel, $validated['segments'], $request);
        });

        return redirect()
            ->route('reward-wheels.purchase.edit', $wheel)
            ->with('status', $created ? 'تم إنشاء عجلة الشراء بنجاح.' : 'تم تحديث العجلة الموجودة بنفس النطاق.');
    }

    public function purchaseEdit(RewardWheel $wheel): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        abort_unless($wheel->wheel_type === RewardWheel::TYPE_PURCHASE_AMOUNT, 404);

        $rangeWheels = $this->purchaseRangeWheels($wheel->min_order_total, $wheel->max_order_total)
            ->with('segments')
            ->get();

        if ($rangeWheels->count() > 1) {
            $wheel->setRelation('segments', $rangeWheels->flatMap->segments->sortBy('sort_order')->values());
        } else {
            $wheel->load('segments');
        }

        $wheels = RewardWheel::query()
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->with('segments')
            ->orderBy('min_order_total')
            ->latest()
            ->get()
            ->groupBy(fn(RewardWheel $wheel) => $this->purchaseRangeKey($wheel->min_order_total, $wheel->max_order_total))
            ->map(function ($group) {
                $wheel = $group->first();

                if ($group->count() > 1) {
                    $wheel->setRelation('segments', $group->flatMap->segments->sortBy('sort_order')->values());
                }

                return $wheel;
            })
            ->values();

        return view('admin.reward_wheels.purchase_amount', [
            'wheels' => $wheels,
            'editWheel' => $wheel,
        ]);
    }

    public function purchaseUpdate(Request $request, RewardWheel $wheel): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        abort_unless($wheel->wheel_type === RewardWheel::TYPE_PURCHASE_AMOUNT, 404);

        $validated = $request->validate($this->wheelValidationRules() + [
            'min_order_total' => ['required', 'numeric', 'min:0'],
            'max_order_total' => ['nullable', 'numeric', 'gte:min_order_total'],
            'win_quota_total' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);
        $this->assertActiveSegmentsQuotaTotal(
            $validated['segments'],
            (int) $validated['win_quota_total'],
            'مجموع ظهور جوائز عجلة الشراء الفعالة يجب أن يساوي إجمالي فرص العجلة.'
        );

        DB::transaction(function () use ($validated, $request, $wheel) {
            $maxOrderTotal = $validated['max_order_total'] ?? null;

            $wheel->update([
                'title' => $validated['title'],
                'min_order_total' => $validated['min_order_total'],
                'max_order_total' => $maxOrderTotal ?: null,
                'win_quota_total' => (int) $validated['win_quota_total'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'spin_cycle' => null,
            ]);

            $this->purchaseRangeWheels($wheel->min_order_total, $wheel->max_order_total)
                ->whereKeyNot($wheel->getKey())
                ->delete();

            $this->replaceSegments($wheel, $validated['segments'], $request);
        });

        return redirect()
            ->route('reward-wheels.purchase.edit', $wheel)
            ->with('status', 'تم حفظ تعديلات عجلة الشراء بنجاح.');
    }

    public function purchaseDestroy(RewardWheel $wheel): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);
        abort_unless($wheel->wheel_type === RewardWheel::TYPE_PURCHASE_AMOUNT, 404);

        $wheel->delete();

        return redirect()
            ->route('reward-wheels.purchase.index')
            ->with('status', 'تم حذف عجلة الشراء بنجاح.');
    }

    public function marketerEdit(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $wheel = $this->marketerDashboardWheel();

        return view('admin.reward_wheels.marketer_dashboard_settings', [
            'wheel' => $wheel->load(['segments', 'questions']),
        ]);
    }

    public function marketerUpdate(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $validated = $request->validate(array_merge($this->wheelValidationRules(), [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string', 'max:255'],
            'questions.*.answer' => ['nullable', 'string', 'max:255'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:255'],
            'questions.*.is_active' => ['nullable', 'boolean'],
            'segments.*.win_quota' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]));

        $this->assertActiveSegmentsQuotaTotal($validated['segments'], 20, 'مجموع ظهور الجوائز الفعالة يجب أن يساوي 20.');

        DB::transaction(function () use ($validated, $request) {
            $wheel = $this->marketerDashboardWheel();
            $wheel->update([
                'wheel_type' => RewardWheel::TYPE_MARKETER_DASHBOARD,
                'title' => $validated['title'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->replaceSegments($wheel, $validated['segments'], $request);
            $this->replaceQuestions($wheel, $validated['questions']);
        });

        return redirect()
            ->route('reward-wheels.marketer.edit')
            ->with('status', 'تم حفظ عجلة المسوقة وأسئلتها بنجاح.');
    }

    public function marketerPlay(Request $request): View
    {
        abort_unless(auth()->user()?->isMarketer() || $this->canAccessCurrentRoute(), 403);

        $wheel = $this->marketerDashboardWheel()->load(['segments', 'questions']);
        $activeQuestions = $wheel->questions->where('is_active', true)->values();
        $isUnlocked = $activeQuestions->isEmpty() || $request->session()->get($this->marketerUnlockSessionKey($wheel)) === $wheel->updated_at?->timestamp;
        $currentQuestionIndex = (int) $request->session()->get($this->marketerQuestionSessionKey($wheel), 0);

        if ($activeQuestions->isNotEmpty() && $request->filled('question')) {
            $currentQuestionIndex = max(0, min($activeQuestions->count() - 1, $request->integer('question') - 1));
            $request->session()->put($this->marketerQuestionSessionKey($wheel), $currentQuestionIndex);
        }

        if ($activeQuestions->isNotEmpty() && ! $activeQuestions->has($currentQuestionIndex)) {
            $currentQuestionIndex = 0;
            $request->session()->put($this->marketerQuestionSessionKey($wheel), $currentQuestionIndex);
        }

        return view('admin.reward_wheels.marketer_dashboard_play', [
            'wheel' => $wheel,
            'questions' => $activeQuestions,
            'currentQuestion' => $activeQuestions->get($currentQuestionIndex),
            'currentQuestionNumber' => $activeQuestions->isEmpty() ? 0 : $currentQuestionIndex + 1,
            'questionsCount' => $activeQuestions->count(),
            'isUnlocked' => $isUnlocked,
        ]);
    }

    public function marketerUnlock(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isMarketer() || $this->canAccessCurrentRoute(), 403);

        $wheel = $this->marketerDashboardWheel()->load('questions');
        $questions = $wheel->questions->where('is_active', true)->values();

        if ($questions->isEmpty()) {
            $request->session()->put($this->marketerUnlockSessionKey($wheel), $wheel->updated_at?->timestamp);

            return redirect()->route('reward-wheels.marketer.play');
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'answer' => ['nullable', 'string', 'max:255'],
        ]);

        $question = $questions->firstWhere('id', (int) $validated['question_id']);

        if (! $question) {
            throw ValidationException::withMessages([
                'answer' => 'السؤال غير متاح حاليا.',
            ]);
        }

        $expectedAnswer = trim((string) $question->answer);
        $givenAnswer = trim((string) ($validated['answer'] ?? ''));
        $availableOptions = collect($question->options ?? [])
            ->map(fn($option) => trim((string) $option))
            ->filter()
            ->values();

        if ($availableOptions->isNotEmpty() && ! $availableOptions->containsStrict($givenAnswer)) {
            throw ValidationException::withMessages([
                'answer' => 'اختاري إجابة من الخيارات المتاحة.',
            ]);
        }

        if ($expectedAnswer !== '' && Str::lower($givenAnswer) !== Str::lower($expectedAnswer)) {
            $currentIndex = max(0, $questions->search(fn($item) => $item->id === $question->id));
            $nextIndex = ($currentIndex + 1) % $questions->count();
            $request->session()->put($this->marketerQuestionSessionKey($wheel), $nextIndex);

            return redirect()
                ->route('reward-wheels.marketer.play')
                ->withErrors(['answer' => 'الإجابة غير صحيحة، انتقلنا للسؤال التالي.']);
        }

        $request->session()->put($this->marketerUnlockSessionKey($wheel), $wheel->updated_at?->timestamp);
        $request->session()->forget($this->marketerQuestionSessionKey($wheel));

        return redirect()
            ->route('reward-wheels.marketer.play')
            ->with('status', 'تم فتح العجلة بنجاح.');

        foreach ($questions as $question) {
            $expectedAnswer = trim((string) $question->answer);
            if ($expectedAnswer === '') {
                continue;
            }

            $givenAnswer = trim((string) ($validated['answers'][$question->id] ?? ''));
            $availableOptions = collect($question->options ?? [])
                ->map(fn($option) => trim((string) $option))
                ->filter()
                ->values();

            if ($availableOptions->isNotEmpty() && ! $availableOptions->containsStrict($givenAnswer)) {
                throw ValidationException::withMessages([
                    "answers.$question->id" => 'اختاري إجابة من الخيارات المتاحة.',
                ]);
            }

            if (Str::lower($givenAnswer) !== Str::lower($expectedAnswer)) {
                throw ValidationException::withMessages([
                    "answers.$question->id" => 'الإجابة غير صحيحة، تأكدي من الإجابات وحاولي مرة أخرى.',
                ]);
            }
        }

        $request->session()->put($this->marketerUnlockSessionKey($wheel), $wheel->updated_at?->timestamp);

        return redirect()
            ->route('reward-wheels.marketer.play')
            ->with('status', 'تم فتح العجلة بنجاح.');
    }

    public function marketerReset(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isMarketer() || $this->canAccessCurrentRoute(), 403);

        $wheel = $this->marketerDashboardWheel();
        $request->session()->forget($this->marketerUnlockSessionKey($wheel));
        $request->session()->put($this->marketerQuestionSessionKey($wheel), 0);

        return redirect()
            ->route('reward-wheels.marketer.play')
            ->with('status', 'تمت إعادة الأسئلة من البداية.');
    }

    public function marketerSpin(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isMarketer() || $this->canAccessCurrentRoute(), 403);

        return $this->spinMarketerWheel($request, $this->marketerDashboardWheel()->load('segments'), true);
    }

    public function marketerDirectEdit(): View
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $wheel = $this->marketerDirectWheel();

        return view('admin.reward_wheels.marketer_direct_settings', [
            'wheel' => $wheel->load('segments'),
        ]);
    }

    public function marketerDirectUpdate(Request $request): RedirectResponse
    {
        abort_unless($this->canAccessCurrentRoute(), 403);

        $validated = $request->validate(array_merge($this->wheelValidationRules(), [
            'segments.*.win_quota' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]));

        $this->assertActiveSegmentsQuotaTotal($validated['segments'], 20, 'مجموع ظهور الجوائز الفعالة يجب أن يساوي 20.');

        DB::transaction(function () use ($validated, $request) {
            $wheel = $this->marketerDirectWheel();
            $wheel->update([
                'wheel_type' => RewardWheel::TYPE_MARKETER_DIRECT,
                'title' => $validated['title'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->replaceSegments($wheel, $validated['segments'], $request);
        });

        return redirect()
            ->route('reward-wheels.marketer.direct.edit')
            ->with('status', 'تم حفظ العجلة المباشرة بنجاح.');
    }

    public function marketerDirectPlay(): View
    {
        abort_unless(auth()->user()?->isMarketer() || $this->canAccessCurrentRoute(), 403);

        return view('admin.reward_wheels.marketer_direct_play', [
            'wheel' => $this->marketerDirectWheel()->load('segments'),
        ]);
    }

    public function marketerDirectSpin(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isMarketer() || $this->canAccessCurrentRoute(), 403);

        return $this->spinMarketerWheel($request, $this->marketerDirectWheel()->load('segments'), false);
    }

    public function publicMarketerDirect(DistributorMarketer $marketer): View
    {
        abort_unless($marketer->is_active && $marketer->distributor?->is_active, 404);

        return view('front.marketer_direct_wheel', [
            'wheel' => $this->marketerDirectWheel()->load('segments'),
            'marketer' => $marketer->loadMissing('distributor.shop'),
        ]);
    }

    public function publicMarketerDirectSpin(Request $request, DistributorMarketer $marketer): JsonResponse
    {
        abort_unless($marketer->is_active && $marketer->distributor?->is_active, 404);

        return $this->spinMarketerWheel($request, $this->marketerDirectWheel()->load('segments'), false);
    }

    private function spinMarketerWheel(Request $request, RewardWheel $wheel, bool $requiresUnlock): JsonResponse
    {
        if ($requiresUnlock) {
            abort_unless(
                $wheel->is_active && $request->session()->get($this->marketerUnlockSessionKey($wheel)) === $wheel->updated_at?->timestamp,
                403
            );
        } else {
            abort_unless($wheel->is_active, 403);
        }


        $segments = $wheel->segments->where('is_active', true)->values();
        abort_if($segments->isEmpty(), 422);

        $cycleKey = $this->marketerSpinCycleSessionKey($wheel);
        $cycle = $request->session()->get($cycleKey);

        if (! is_array($cycle) || empty($cycle)) {
            $cycle = $this->marketerSpinCycle($segments);
        }

        $cycleIndex = random_int(0, count($cycle) - 1);
        $segmentId = (int) $cycle[$cycleIndex];
        array_splice($cycle, $cycleIndex, 1);
        $request->session()->put($cycleKey, $cycle);

        $segment = $segments->firstWhere('id', $segmentId) ?? $segments->first();
        $selectedIndex = max(0, $segments->search(fn($item) => $item->id === $segment->id));

        return response()->json([
            'selected_index' => $selectedIndex,
            'remaining_spins' => count($cycle),
            'segment' => [
                'label' => $segment->label,
                'discount_type' => $segment->discount_type,
                'gift_image' => $segment->gift_image ? asset($segment->gift_image) : null,
            ],
        ]);
    }

    private function customerSignupWheel(): RewardWheel
    {
        $wheel = RewardWheel::query()->firstOrCreate(
            ['key' => RewardWheel::CUSTOMER_SIGNUP_DISCOUNTS],
            [
                'wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP,
                'title' => 'لف العجلة واحصل على خصمك الأول',
                'is_active' => true,
            ]
        );

        if ($wheel->wheel_type !== RewardWheel::TYPE_CUSTOMER_SIGNUP) {
            $wheel->update(['wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP]);
        }

        if (! $wheel->segments()->exists()) {
            $this->replaceSegments($wheel, [
                ['label' => 'خصم 5%', 'discount_value' => 5, 'discount_type' => 'percent', 'color' => '#00e5ff', 'is_active' => true],
                ['label' => 'خصم 10%', 'discount_value' => 10, 'discount_type' => 'percent', 'color' => '#7000ff', 'is_active' => true],
                ['label' => 'خصم 15%', 'discount_value' => 15, 'discount_type' => 'percent', 'color' => '#25d366', 'is_active' => true],
                ['label' => 'توصيل مجاني', 'discount_value' => null, 'discount_type' => 'free_shipping', 'color' => '#ffd60a', 'is_active' => true],
            ]);
        }

        return $wheel;
    }

    private function marketerDashboardWheel(): RewardWheel
    {
        $wheel = RewardWheel::query()->firstOrCreate(
            ['key' => RewardWheel::MARKETER_DASHBOARD_WHEEL],
            [
                'wheel_type' => RewardWheel::TYPE_MARKETER_DASHBOARD,
                'title' => 'عجلة المسوقة',
                'is_active' => true,
            ]
        );

        if ($wheel->wheel_type !== RewardWheel::TYPE_MARKETER_DASHBOARD) {
            $wheel->update(['wheel_type' => RewardWheel::TYPE_MARKETER_DASHBOARD]);
        }

        if (! $wheel->segments()->exists()) {
            $this->replaceSegments($wheel, [
                ['label' => 'هدية', 'discount_value' => null, 'discount_type' => 'gift', 'color' => '#00e5ff', 'is_active' => true],
                ['label' => 'خصم 10%', 'discount_value' => 10, 'discount_type' => 'percent', 'color' => '#7000ff', 'is_active' => true],
                ['label' => 'خصم 20%', 'discount_value' => 20, 'discount_type' => 'percent', 'color' => '#25d366', 'is_active' => true],
                ['label' => 'توصيل مجاني', 'discount_value' => null, 'discount_type' => 'free_shipping', 'color' => '#ffd60a', 'is_active' => true],
            ]);
        }

        if (! $wheel->questions()->exists()) {
            $this->replaceQuestions($wheel, [
                ['question' => 'ما اسم البراند؟', 'answer' => 'Ozman', 'is_active' => true],
            ]);
        }

        return $wheel;
    }

    private function marketerDirectWheel(): RewardWheel
    {
        $wheel = RewardWheel::query()->firstOrCreate(
            ['key' => RewardWheel::MARKETER_DIRECT_WHEEL],
            [
                'wheel_type' => RewardWheel::TYPE_MARKETER_DIRECT,
                'title' => 'عجلة المسوقة المباشرة',
                'is_active' => true,
            ]
        );

        if ($wheel->wheel_type !== RewardWheel::TYPE_MARKETER_DIRECT) {
            $wheel->update(['wheel_type' => RewardWheel::TYPE_MARKETER_DIRECT]);
        }

        if (! $wheel->segments()->exists()) {
            $this->replaceSegments($wheel, [
                ['label' => 'هدية', 'discount_value' => null, 'discount_type' => 'gift', 'win_quota' => 5, 'color' => '#00e5ff', 'is_active' => true],
                ['label' => 'خصم 10%', 'discount_value' => 10, 'discount_type' => 'percent', 'win_quota' => 5, 'color' => '#7000ff', 'is_active' => true],
                ['label' => 'خصم 20%', 'discount_value' => 20, 'discount_type' => 'percent', 'win_quota' => 5, 'color' => '#25d366', 'is_active' => true],
                ['label' => 'حظا أوفر', 'discount_value' => null, 'discount_type' => 'gift', 'win_quota' => 5, 'color' => '#ffd60a', 'is_active' => true],
            ]);
        }

        return $wheel;
    }

    private function wheelValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'segments' => ['required', 'array', 'min:2'],
            'segments.*.label' => ['required', 'string', 'max:255'],
            'segments.*.discount_value' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'segments.*.discount_type' => ['required', Rule::in(['percent', 'amount', 'free_shipping', 'gift'])],
            'segments.*.gift_image' => ['nullable', 'image', 'max:2048'],
            'segments.*.existing_gift_image' => ['nullable', 'string', 'max:255'],
            'segments.*.win_quota' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'segments.*.color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'segments.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    private function assertActiveSegmentsQuotaTotal(array $segments, int $expectedTotal, string $message): void
    {
        $activeQuotaTotal = collect($segments)
            ->filter(fn($segment) => (bool) ($segment['is_active'] ?? false))
            ->sum(fn($segment) => (int) ($segment['win_quota'] ?? 0));

        if ($activeQuotaTotal !== $expectedTotal) {
            throw ValidationException::withMessages([
                'segments' => $message,
            ]);
        }
    }

    private function purchaseRangeWheels($minOrderTotal, $maxOrderTotal)
    {
        $maxOrderTotal = $maxOrderTotal === '' ? null : $maxOrderTotal;

        return RewardWheel::query()
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->where('min_order_total', $minOrderTotal)
            ->when($maxOrderTotal === null, fn($query) => $query->whereNull('max_order_total'))
            ->when($maxOrderTotal !== null, fn($query) => $query->where('max_order_total', $maxOrderTotal));
    }

    private function purchaseRangeKey($minOrderTotal, $maxOrderTotal): string
    {
        return number_format((float) $minOrderTotal, 2, '.', '') . ':' . ($maxOrderTotal === null ? 'null' : number_format((float) $maxOrderTotal, 2, '.', ''));
    }

    private function replaceSegments(RewardWheel $wheel, array $segments, ?Request $request = null): void
    {
        $wheel->segments()->delete();
        $this->appendSegments($wheel, $segments, $request);
    }

    private function appendSegments(RewardWheel $wheel, array $segments, ?Request $request = null): void
    {
        $nextSortOrder = ((int) $wheel->segments()->max('sort_order')) + 1;

        foreach (array_values($segments) as $index => $segment) {
            $isGift = ($segment['discount_type'] ?? null) === 'gift';
            $giftImage = $isGift ? ($segment['existing_gift_image'] ?? null) : null;
            if ($isGift && $request?->hasFile("segments.$index.gift_image")) {
                $giftImage = 'storage/' . $request
                    ->file("segments.$index.gift_image")
                    ->store('reward-gifts', 'public');
            }

            $wheel->segments()->create([
                'label' => $segment['label'],
                'discount_value' => $segment['discount_value'] ?? null,
                'discount_type' => $segment['discount_type'],
                'gift_image' => $giftImage,
                'win_quota' => (int) ($segment['win_quota'] ?? 1),
                'color' => $segment['color'],
                'sort_order' => $nextSortOrder + $index,
                'is_active' => (bool) ($segment['is_active'] ?? false),
            ]);
        }
    }

    private function replaceQuestions(RewardWheel $wheel, array $questions): void
    {
        $wheel->questions()->delete();

        foreach (array_values($questions) as $index => $question) {
            $options = collect($question['options'] ?? [])
                ->map(fn($option) => trim((string) $option))
                ->filter()
                ->values()
                ->all();
            $answer = trim((string) ($question['answer'] ?? ''));

            if ($options && ($answer === '' || ! in_array($answer, $options, true))) {
                $answer = $options[0];
            }

            $wheel->questions()->create([
                'question' => $question['question'],
                'answer' => $answer ?: null,
                'options' => $options,
                'sort_order' => $index + 1,
                'is_active' => (bool) ($question['is_active'] ?? false),
            ]);
        }
    }

    private function marketerUnlockSessionKey(RewardWheel $wheel): string
    {
        return 'reward_wheels.marketer.unlocked.' . auth()->id() . '.' . $wheel->getKey();
    }

    private function marketerQuestionSessionKey(RewardWheel $wheel): string
    {
        return 'reward_wheels.marketer.question.' . auth()->id() . '.' . $wheel->getKey();
    }

    private function marketerSpinCycleSessionKey(RewardWheel $wheel): string
    {
        return 'reward_wheels.marketer.spin_cycle.' . auth()->id() . '.' . $wheel->getKey() . '.' . $wheel->updated_at?->timestamp;
    }

    private function marketerSpinCycle($segments): array
    {
        $cycle = [];

        foreach ($segments as $segment) {
            $quota = max(0, (int) ($segment->win_quota ?? 0));

            for ($index = 0; $index < $quota; $index++) {
                $cycle[] = (int) $segment->id;
            }
        }

        if ($cycle) {
            return $cycle;
        }

        return $segments
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }
}
