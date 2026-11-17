<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{RewardWheel, Shop, User};
use App\Services\RewardWheelEditor;
use Illuminate\Http\{JsonResponse, Request};

/** The shop owner edits the shop's reward wheels in the app: the signup wheel and the purchase wheels. Same rules as the dashboard. */
class RewardWheelsController extends Controller
{
    public function __construct(private RewardWheelEditor $editor) {}

    public function index(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        $this->authorizeShop($user, $shop);
        $canPurchase = $this->can($user, 'reward-wheels.purchase.index') && $shop->catalog_type !== 'real_estate';
        $canSignup = $this->can($user, 'reward-wheels.shop-signup.edit') && RewardWheelEditor::signupAllowed($shop);

        return response()->json([
            'signup' => $canSignup ? $this->present($this->editor->signupWheel($shop)) : null,
            'purchase' => $canPurchase ? $this->editor->purchaseWheels($shop->id)->map(fn ($w) => $this->present($w))->values() : null,
            'segment_types' => collect(RewardWheelEditor::SEGMENT_TYPES)->map(fn ($type) => ['key' => $type, 'label' => __(self::typeLabel($type)),
                'signup' => in_array($type, RewardWheelEditor::SIGNUP_SEGMENT_TYPES, true)])->values(),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function saveSignup(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        $this->authorizeShop($user, $shop);
        abort_unless($this->can($user, 'reward-wheels.shop-signup.edit') && RewardWheelEditor::signupAllowed($shop), 403);

        return response()->json(['wheel' => $this->present($this->editor->saveSignup($shop, $request)), 'message' => __('تم حفظ عجلة التسجيل.')]);
    }

    public function storePurchase(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        $this->authorizeShop($user, $shop);
        abort_unless($this->can($user, 'reward-wheels.purchase.index') && $shop->catalog_type !== 'real_estate', 403);
        [$wheel, $created] = $this->editor->savePurchase($shop->id, null, $request);

        return response()->json(['wheel' => $this->present($wheel),
            'message' => $created ? __('تم إنشاء عجلة الشراء.') : __('في عجلة بنفس المبلغ، فحدّثناها.')], $created ? 201 : 200);
    }

    public function updatePurchase(Request $request, RewardWheel $wheel): JsonResponse
    {
        $this->authorizePurchase($request->user(), $wheel);
        [$wheel] = $this->editor->savePurchase((int) $wheel->shop_id, $wheel, $request);

        return response()->json(['wheel' => $this->present($wheel), 'message' => __('تم حفظ عجلة الشراء.')]);
    }

    public function destroyPurchase(Request $request, RewardWheel $wheel): JsonResponse
    {
        $this->authorizePurchase($request->user(), $wheel);
        $wheel->delete();

        return response()->json(['message' => __('انحذفت العجلة.')]);
    }

    private function present(RewardWheel $wheel): array
    {
        return [
            'id' => $wheel->exists ? $wheel->id : null,
            'title' => $wheel->title,
            'is_active' => (bool) $wheel->is_active,
            'min_order_total' => $wheel->min_order_total !== null ? (float) $wheel->min_order_total : null,
            'max_order_total' => $wheel->max_order_total !== null ? (float) $wheel->max_order_total : null,
            'win_quota_total' => (int) ($wheel->win_quota_total ?: $wheel->segments->where('is_active', true)->sum('win_quota')),
            'segments' => $wheel->segments->values()->map(fn ($s) => [
                'label' => $s->label,
                'discount_type' => $s->discount_type,
                'discount_value' => $s->discount_value,
                'color' => $s->color ?: '#08def4',
                'win_quota' => (int) ($s->win_quota ?? 1),
                'is_active' => (bool) $s->is_active,
                'gift_image' => Presenter::media($s->gift_image),
                'existing_gift_image' => $s->gift_image,
            ])->all(),
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'percent' => 'خصم نسبة %',
            'amount' => 'خصم مبلغ ₪',
            'free_shipping' => 'توصيل مجاني',
            'gift' => 'هدية',
            default => 'حظ أوفر',
        };
    }

    private function can(User $user, string $route): bool
    {
        return $user->isSuperAdmin() || $user->canAccessRouteName($route);
    }

    private function authorizeShop(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
    }

    private function authorizePurchase(User $user, RewardWheel $wheel): void
    {
        abort_unless($wheel->wheel_type === RewardWheel::TYPE_PURCHASE_AMOUNT && $wheel->shop, 404);
        $this->authorizeShop($user, $wheel->shop);
        abort_unless($this->can($user, 'reward-wheels.purchase.index'), 403);
    }
}
