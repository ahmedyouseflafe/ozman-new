<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Shop, ShopCoupon};
use App\Services\CouponService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** The shop's discount codes: the owner creates and manages them, the customer checks one against the basket. */
class CouponController extends Controller
{
    private const TZ = 'Asia/Hebron';

    /** How much a code takes off this basket, before placing the order. */
    public function check(Request $request, Shop $shop, CouponService $coupons): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40'], 'amount' => ['required', 'numeric', 'min:0']]);
        $coupon = $coupons->find($shop, $data['code'], $request->user());
        $off = $coupons->discount($coupon, (float) $data['amount']);

        return response()->json(['code' => $coupon->code, 'discount' => $off, 'label' => $this->label($coupon)]);
    }

    public function index(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request, $shop);

        return response()->json([
            'data' => ShopCoupon::where('shop_id', $shop->id)->latest()->orderByDesc('id')->get()->map(fn ($c) => $this->present($c))->values(),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request, $shop);
        $coupon = ShopCoupon::create(['shop_id' => $shop->id, 'used_count' => 0] + $this->validated($request, $shop));

        return response()->json(['coupon' => $this->present($coupon), 'message' => __('انعمل الكود.')], 201);
    }

    public function update(Request $request, ShopCoupon $coupon): JsonResponse
    {
        $this->authorizeShop($request, $coupon->shop);
        $coupon->update($this->validated($request, $coupon->shop, $coupon));

        return response()->json(['coupon' => $this->present($coupon->fresh()), 'message' => __('انحفظ الكود.')]);
    }

    public function destroy(Request $request, ShopCoupon $coupon): JsonResponse
    {
        $this->authorizeShop($request, $coupon->shop);
        $coupon->delete();

        return response()->json(['message' => __('انحذف الكود.')]);
    }

    private function validated(Request $request, Shop $shop, ?ShopCoupon $coupon = null): array
    {
        $request->merge(['code' => ShopCoupon::normalize((string) $request->input('code', ''))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[\p{L}\p{N}_-]+$/u',
                Rule::unique('shop_coupons', 'code')->where('shop_id', $shop->id)->ignore($coupon?->id)],
            'type' => ['required', Rule::in(['percent', 'amount'])],
            'value' => ['required', 'numeric', 'gt:0', $request->input('type') === 'percent' ? 'max:100' : 'max:100000'],
            'min_total' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'per_customer' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'ends_at' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('starts_at') ? 'after_or_equal:starts_at' : null]),
            'is_active' => ['nullable', 'boolean'],
        ], ['code.unique' => __('في كود بنفس الاسم بهالمحل.'), 'code.regex' => __('الكود حروف وأرقام بدون مسافات.')]);

        // Days are the shop's local days: from the start of the first to the end of the last.
        return [
            'code' => $data['code'], 'type' => $data['type'], 'value' => $data['value'],
            'min_total' => $data['min_total'] ?? null, 'max_uses' => $data['max_uses'] ?? null, 'per_customer' => $data['per_customer'] ?? null,
            'starts_at' => filled($data['starts_at'] ?? null) ? Carbon::createFromFormat('Y-m-d', $data['starts_at'], self::TZ)->startOfDay()->utc() : null,
            'ends_at' => filled($data['ends_at'] ?? null) ? Carbon::createFromFormat('Y-m-d', $data['ends_at'], self::TZ)->endOfDay()->utc() : null,
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function present(ShopCoupon $c): array
    {
        $expired = $c->ends_at && $c->ends_at->isPast();
        $finished = $c->max_uses !== null && $c->used_count >= $c->max_uses;

        return [
            'id' => $c->id, 'code' => $c->code, 'type' => $c->type, 'value' => $c->value, 'label' => $this->label($c),
            'min_total' => $c->min_total, 'max_uses' => $c->max_uses, 'per_customer' => $c->per_customer, 'used_count' => $c->used_count,
            'starts_at' => $c->starts_at?->timezone(self::TZ)->toDateString(), 'ends_at' => $c->ends_at?->timezone(self::TZ)->toDateString(),
            'is_active' => $c->is_active,
            'state' => ! $c->is_active ? 'off' : ($expired ? 'expired' : ($finished ? 'used_up' : ($c->starts_at?->isFuture() ? 'scheduled' : 'active'))),
        ];
    }

    private function label(ShopCoupon $c): string
    {
        $value = rtrim(rtrim(number_format($c->value, 2, '.', ''), '0'), '.');

        return $c->type === 'percent' ? __('خصم :value%', ['value' => $value]) : __('خصم :value ₪', ['value' => $value]);
    }

    private function authorizeShop(Request $request, ?Shop $shop): void
    {
        $user = $request->user();
        abort_unless($shop, 404);
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
    }
}
