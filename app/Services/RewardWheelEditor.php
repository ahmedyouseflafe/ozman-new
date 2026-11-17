<?php

namespace App\Services;

use App\Models\{RewardWheel, RewardWheelSegment, Shop};
use Illuminate\Http\Request;
use Illuminate\Support\{Arr, Collection};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Saving a shop's reward wheels: the signup wheel (new customers) and the purchase wheels (by order total).
 * The dashboard and the app both go through here, so the rules (quotas, discounts, gift images) are the same.
 */
class RewardWheelEditor
{
    public const SEGMENT_TYPES = ['percent', 'amount', 'free_shipping', 'gift', 'none'];
    public const SIGNUP_SEGMENT_TYPES = ['percent', 'amount', 'gift', 'none'];

    /** Which shops have a signup wheel at all (the website shows it on these storefronts only). */
    public static function signupAllowed(Shop $shop): bool
    {
        return $shop->is_active && ($shop->catalog_type === 'restaurant' || ($shop->catalog_type === 'cosmetics' && $shop->slug === 'elegance-perfumes'));
    }

    // ── Signup wheel ─────────────────────────────────────────────────────────

    /** The shop's signup wheel, or an inactive draft with two starter segments. */
    public function signupWheel(Shop $shop): RewardWheel
    {
        $wheel = RewardWheel::with('segments')->where('key', 'shop_signup_'.$shop->id)->first();
        if ($wheel) {
            return $wheel;
        }
        $wheel = new RewardWheel(['title' => 'هدية تسجيلك في '.$shop->name, 'is_active' => false]);

        return $wheel->setRelation('segments', collect([
            new RewardWheelSegment(['label' => 'خصم 5%', 'discount_type' => 'percent', 'discount_value' => 5, 'color' => '#00cfe8', 'is_active' => true]),
            new RewardWheelSegment(['label' => 'خصم 10%', 'discount_type' => 'percent', 'discount_value' => 10, 'color' => '#7000ff', 'is_active' => true]),
        ]));
    }

    public function saveSignup(Shop $shop, Request $request): RewardWheel
    {
        $data = Validator::make($request->all(), array_replace($this->rules(), [
            'segments' => ['required', 'array', 'min:2', 'max:20'],
            'segments.*.label' => ['required', 'string', 'max:60'],
            'segments.*.discount_type' => ['required', Rule::in(self::SIGNUP_SEGMENT_TYPES)],
            'segments.*.win_quota' => ['required', 'integer', 'min:0', 'max:10000'],
            'win_quota_total' => ['required', 'integer', 'min:1', 'max:10000'],
            'segments.*.gift_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]))->validate();
        foreach ($data['segments'] as $index => $segment) {
            if (in_array($segment['discount_type'], ['percent', 'amount'], true)
                && ((! isset($segment['discount_value']) || $segment['discount_value'] < 1)
                    || ($segment['discount_type'] === 'percent' && $segment['discount_value'] > 100))) {
                throw ValidationException::withMessages(["segments.$index.discount_value" => 'أدخل قيمة خصم صحيحة؛ النسبة بين 1 و100.']);
            }
        }
        if (($data['is_active'] ?? false) && collect($data['segments'])->where('is_active', true)->count() < 2) {
            throw ValidationException::withMessages(['segments' => 'فعّل شريحتين على الأقل لتشغيل العجلة.']);
        }
        $this->assertQuotaTotal($data['segments'], (int) $data['win_quota_total'], 'مجموع مرات ظهور الشرائح الفعالة يجب أن يساوي إجمالي لفات الدورة.');

        return DB::transaction(function () use ($data, $shop, $request) {
            $wheel = RewardWheel::firstOrCreate(['key' => 'shop_signup_'.$shop->id], [
                'shop_id' => $shop->id, 'wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP,
                'title' => $data['title'], 'is_active' => false,
            ]);
            $wheel = RewardWheel::whereKey($wheel->id)->lockForUpdate()->firstOrFail();
            $ownedImages = $wheel->segments()->whereNotNull('gift_image')->pluck('gift_image')->all();
            foreach ($data['segments'] as $index => $segment) {
                if (! empty($segment['existing_gift_image']) && ! in_array($segment['existing_gift_image'], $ownedImages, true)) {
                    throw ValidationException::withMessages(["segments.$index.existing_gift_image" => 'اختر صورة هدية من هذا المحل أو ارفع صورة جديدة.']);
                }
            }
            $wheel->update(['title' => $data['title'], 'is_active' => (bool) ($data['is_active'] ?? false),
                'win_quota_total' => $data['win_quota_total'], 'spin_cycle' => null]);
            $this->replaceSegments($wheel, collect($data['segments'])->map(fn ($segment) =>
                [...Arr::only($segment, ['label', 'discount_type', 'discount_value', 'color', 'is_active', 'win_quota', 'existing_gift_image']),
                    'discount_value' => in_array($segment['discount_type'], ['gift', 'none'], true) ? null : ($segment['discount_value'] ?? null)]
            )->all(), $request);

            return $wheel->load('segments');
        });
    }

    // ── Purchase wheels ──────────────────────────────────────────────────────

    /** The shop's purchase wheels, one per order-total range (older duplicates of a range are merged for display). */
    public function purchaseWheels(int $shopId): Collection
    {
        return RewardWheel::query()
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->where('shop_id', $shopId)
            ->with('segments')
            ->orderBy('min_order_total')
            ->latest()
            ->get()
            ->groupBy(fn (RewardWheel $wheel) => $this->rangeKey($wheel->min_order_total, $wheel->max_order_total))
            ->map(function ($group) {
                $wheel = $group->first();
                if ($group->count() > 1) {
                    $wheel->setRelation('segments', $group->flatMap->segments->sortBy('sort_order')->values());
                }

                return $wheel;
            })
            ->values();
    }

    /**
     * Creates a purchase wheel, or, when $wheel is given, updates it. Creating with the range of an existing wheel
     * updates that wheel instead. Returns [wheel, created].
     *
     * @return array{0: RewardWheel, 1: bool}
     */
    public function savePurchase(int $shopId, ?RewardWheel $wheel, Request $request): array
    {
        $validated = Validator::make($request->all(), $this->rules() + [
            'min_order_total' => ['required', 'numeric', 'min:0'],
            'max_order_total' => ['nullable', 'numeric', 'gte:min_order_total'],
            'win_quota_total' => ['required', 'integer', 'min:1', 'max:10000'],
        ])->validate();
        $this->assertQuotaTotal($validated['segments'], (int) $validated['win_quota_total'],
            'مجموع ظهور جوائز عجلة الشراء الفعالة يجب أن يساوي إجمالي فرص العجلة.');
        $created = false;

        $wheel = DB::transaction(function () use ($validated, $request, $shopId, $wheel, &$created) {
            $maxOrderTotal = ($validated['max_order_total'] ?? null) === '' ? null : ($validated['max_order_total'] ?? null);
            if ($wheel) {
                $wheel->update([
                    'title' => $validated['title'],
                    'min_order_total' => $validated['min_order_total'],
                    'max_order_total' => $maxOrderTotal ?: null,
                    'win_quota_total' => (int) $validated['win_quota_total'],
                    'is_active' => (bool) ($validated['is_active'] ?? false),
                    'spin_cycle' => null,
                ]);
                $this->rangeWheels($wheel->shop_id, $wheel->min_order_total, $wheel->max_order_total)->whereKeyNot($wheel->getKey())->delete();
            } else {
                $wheel = $this->rangeWheels($shopId, $validated['min_order_total'], $maxOrderTotal)->first();
                if (! $wheel) {
                    $created = true;
                    $wheel = RewardWheel::create([
                        'shop_id' => $shopId,
                        'key' => 'purchase_amount_'.now()->format('YmdHis').'_'.random_int(100, 999),
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
            }
            $this->replaceSegments($wheel, $validated['segments'], $request);

            return $wheel;
        });

        return [$wheel->load('segments'), $created];
    }

    // ── Shared ───────────────────────────────────────────────────────────────

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'segments' => ['required', 'array', 'min:1'],
            'segments.*.label' => ['required', 'string', 'max:255'],
            'segments.*.id' => ['nullable', 'integer'],
            'segments.*.discount_value' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'segments.*.discount_type' => ['required', Rule::in(self::SEGMENT_TYPES)],
            'segments.*.gift_image' => ['nullable', 'image', 'max:2048'],
            'segments.*.existing_gift_image' => ['nullable', 'string', 'max:255'],
            'segments.*.win_quota' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'segments.*.color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'segments.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function assertQuotaTotal(array $segments, int $expectedTotal, string $message): void
    {
        $activeQuotaTotal = collect($segments)
            ->filter(fn ($segment) => (bool) ($segment['is_active'] ?? false))
            ->sum(fn ($segment) => (int) ($segment['win_quota'] ?? 0));
        if ($activeQuotaTotal !== $expectedTotal) {
            throw ValidationException::withMessages(['segments' => $message]);
        }
    }

    public function rangeWheels($shopId, $minOrderTotal, $maxOrderTotal)
    {
        $maxOrderTotal = $maxOrderTotal === '' ? null : $maxOrderTotal;

        return RewardWheel::query()
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->where('shop_id', $shopId)
            ->where('min_order_total', $minOrderTotal)
            ->when($maxOrderTotal === null, fn ($query) => $query->whereNull('max_order_total'))
            ->when($maxOrderTotal !== null, fn ($query) => $query->where('max_order_total', $maxOrderTotal));
    }

    public function rangeKey($minOrderTotal, $maxOrderTotal): string
    {
        return number_format((float) $minOrderTotal, 2, '.', '').':'.($maxOrderTotal === null ? 'null' : number_format((float) $maxOrderTotal, 2, '.', ''));
    }

    public function replaceSegments(RewardWheel $wheel, array $segments, ?Request $request = null): void
    {
        $wheel->segments()->delete();
        $this->appendSegments($wheel, $segments, $request);
    }

    public function appendSegments(RewardWheel $wheel, array $segments, ?Request $request = null): void
    {
        $nextSortOrder = ((int) $wheel->segments()->max('sort_order')) + 1;
        foreach (array_values($segments) as $index => $segment) {
            $isGift = ($segment['discount_type'] ?? null) === 'gift';
            $giftImage = $isGift ? ($segment['existing_gift_image'] ?? null) : null;
            if ($isGift && $request?->hasFile("segments.$index.gift_image")) {
                $giftImage = 'storage/'.$request->file("segments.$index.gift_image")->store('reward-gifts', 'public');
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
}
