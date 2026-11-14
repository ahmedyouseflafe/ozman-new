<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RestaurantCustomerRegistrationController;
use App\Models\{RewardWheel, Shop, ShopSignupReward, User, VisitorRegistration};
use App\Services\{RestaurantNotificationRewardService, ShopSignupRewardService};
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request};

/**
 * The restaurant "sign up and spin" welcome wheel, for app users. It runs on exactly the same
 * records as the website's menu page (VisitorRegistration + ShopSignupReward), so a prize won in
 * the app is applied by the same checkout code, once per phone number.
 */
class SignupRewardController extends Controller
{
    public function __construct(private ShopSignupRewardService $rewards) {}

    /** Same 64-character token the website keeps in the browser, derived here from the account instead. */
    public static function token(User $user, Shop $shop): string
    {
        return hash_hmac('sha256', 'app-signup:'.$user->id.':'.$shop->id, (string) config('app.key'));
    }

    public static function eligible(Shop $shop): bool
    {
        return $shop->is_active && $shop->catalog_type === 'restaurant'
            // That restaurant's gift is tied to browser notifications; the app can't unlock it yet.
            && ! RestaurantNotificationRewardService::applies($shop)
            && RewardWheel::where('key', 'shop_signup_'.$shop->id)->where('shop_id', $shop->id)->where('is_active', true)->exists();
    }

    public function show(Request $request, Shop $shop): JsonResponse
    {
        return response()->json($this->state($request->user(), $shop), 200, ['Cache-Control' => 'no-store']);
    }

    public function register(Request $request, Shop $shop, RestaurantCustomerRegistrationController $registrations): JsonResponse
    {
        abort_unless(self::eligible($shop), 404);
        $user = $request->user();
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);
        // Hand over to the website's own registration, field for field.
        $request->merge([
            'registration_token' => self::token($user, $shop),
            'whatsapp' => $request->input('phone'),
            'location_deferred' => ! $request->filled('latitude'),
        ]);
        $registrations->store($request, $shop);

        return response()->json($this->state($user, $shop), 200, ['Cache-Control' => 'no-store']);
    }

    public function spin(Request $request, Shop $shop): JsonResponse
    {
        abort_unless(self::eligible($shop), 404);
        $registration = $this->registration($request->user(), $shop);
        abort_unless($registration, 422, 'سجّل أول عشان تلف العجلة.');
        $this->rewards->state($shop, self::token($request->user(), $shop), true);

        $state = $this->state($request->user(), $shop);
        abort_unless($state['reward'], 422, 'الجائزة مش متاحة لهذا الرقم.');

        return response()->json($state, 200, ['Cache-Control' => 'no-store']);
    }

    /** The prize waiting for this user's next order here, or null. Used by checkout. */
    public static function pendingToken(User $user, Shop $shop): ?string
    {
        if (! self::eligible($shop)) {
            return null;
        }
        $reward = (new self(app(ShopSignupRewardService::class)))->reward($user, $shop);

        return $reward && ! $reward->redeemed_at && $reward->selected_index !== null ? self::token($user, $shop) : null;
    }

    private function registration(User $user, Shop $shop): ?VisitorRegistration
    {
        try {
            return $this->rewards->registration($shop, self::token($user, $shop));
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    private function reward(User $user, Shop $shop): ?ShopSignupReward
    {
        $registration = $this->registration($user, $shop);

        return $registration ? ShopSignupReward::where('shop_id', $shop->id)->where('visitor_registration_id', $registration->id)->first() : null;
    }

    private function state(User $user, Shop $shop): array
    {
        if (! self::eligible($shop)) {
            return ['available' => false, 'registered' => false, 'used' => false, 'wheel' => null, 'reward' => null];
        }
        $registration = $this->registration($user, $shop);
        $reward = $registration ? $this->reward($user, $shop) : null;
        $wheel = RewardWheel::with(['segments' => fn ($q) => $q->where('is_active', true)])
            ->where('key', 'shop_signup_'.$shop->id)->where('shop_id', $shop->id)->first();
        // Before signing up the customer sees the live wheel; afterwards, the copy saved with their entitlement.
        $segments = $reward ? collect($reward->segments) : $wheel->segments->map(fn ($segment) => [
            'label' => $segment->label, 'color' => $segment->color, 'gift_image' => $segment->gift_image,
            'discount_type' => $segment->discount_type, 'discount_value' => $segment->discount_value,
        ]);
        $won = $reward && $reward->selected_index !== null ? $reward->segments[$reward->selected_index] : null;

        return [
            'available' => true,
            'registered' => (bool) $registration,
            // Signed up, but this phone already had its welcome prize (here or on the website).
            'used' => (bool) ($registration && (! $reward || $reward->redeemed_at)),
            'wheel' => [
                'title' => $reward->title ?? $wheel->title,
                'segments' => $segments->map(fn ($segment) => [
                    'label' => $segment['label'], 'color' => $segment['color'],
                    'gift_image' => ! empty($segment['gift_image']) ? asset($segment['gift_image']) : null,
                ])->values(),
            ],
            'reward' => $won && ! $reward->redeemed_at ? [
                'label' => $won['label'],
                'segment_index' => $reward->selected_index,
                'gift_image' => ! empty($won['gift_image']) ? asset($won['gift_image']) : null,
                'discount_type' => $won['discount_type'],
                'discount_value' => $won['discount_value'],
            ] : null,
        ];
    }
}
