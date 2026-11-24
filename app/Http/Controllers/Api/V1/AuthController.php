<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Services\{CommunityUsername, MerchantRegistration};
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/** Mobile login: the same accounts as the website, with a Bearer token instead of a session. */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);
        $login = trim($data['login']);
        // Email or @username, like the website's community login.
        $user = str_contains($login, '@') && ! str_starts_with($login, '@')
            ? User::where('email', mb_strtolower($login))->first()
            : User::where('username', CommunityUsername::normalize($login))->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => __('الإيميل/اسم المستخدم أو كلمة المرور غير صحيحة.'), 'code' => 'invalid_credentials'], 422);
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'mobile');
    }

    /**
     * Emails the website's reset link, with the website's rules: customers, promoters and shop owners, and the
     * same answer whether or not the account exists. The new password is then chosen on the website.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $user = User::query()->where('email', mb_strtolower(trim($data['email'])))->where('is_active', true)
            ->whereIn('role', \App\Http\Controllers\CommunityPasswordController::RESETTABLE_ROLES)->first();
        if ($user) {
            \Illuminate\Support\Facades\Password::broker()->sendResetLink(['email' => $user->email]);
        }

        return response()->json(['message' => __('إذا كان البريد مسجّل عندنا، رح يوصلك رابط لتعيين كلمة مرور جديدة خلال دقائق. افحص كمان مجلد الرسائل غير المرغوبة.')]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:120'],
            'invite_code' => ['nullable', 'string', 'max:12'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'is_active' => true,
        ]);
        app(\App\Services\Invites::class)->attach($user, $data['invite_code'] ?? null);

        return $this->tokenResponse($user, $data['device_name'] ?? 'mobile', 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => Presenter::user($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['logged_out' => true]);
    }

    /**
     * Deletes a customer account from the app (Google Play requires this). Orders stay for the
     * shops' records, but the person's name, contact details, sign-ins and devices are removed.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate(['password' => ['required', 'string']]);
        if (! \Illuminate\Support\Facades\Hash::check($request->input('password'), $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['password' => 'كلمة السر مش صحيحة.']);
        }
        abort_if($user->isSuperAdmin(), 422, 'حسابات الإدارة بتنحذف من لوحة التحكم.');

        // An account tied to a shop (owner, staff, driver, distributor…) can't vanish on its own: the request
        // reaches the Ozman team, who delete it within 7 days as the account-deletion page says.
        if (! in_array($user->role, ['customer', 'promoter'], true)) {
            $alreadyAsked = \App\Models\AdminNotification::where('type', 'account_deletion_requested')
                ->where('subject_type', $user::class)->where('subject_id', $user->id)
                ->where('created_at', '>=', now()->subDays(7))->exists();
            if (! $alreadyAsked) {
                $this->notifySuperAdmin(
                    'account_deletion_requested',
                    $user,
                    'طلب حذف حساب',
                    "{$user->name} ({$user->email}) طلب حذف حسابه من التطبيق. لازم ينحذف خلال 7 أيام.",
                    route('users'),
                    ['shop_id' => $user->shops()->value('id')]
                );
            }

            return response()->json([
                'deleted' => false,
                'requested' => true,
                'message' => 'وصل طلب حذف حسابك لفريق أوزمان، ومنحذفه خلال 7 أيام. إذا في محل مربوط بالحساب، منتواصل معك قبل.',
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            ApiToken::where('user_id', $user->id)->delete();
            \App\Models\PushDevice::where('user_id', $user->id)->delete();
            \App\Models\FrontOrder::where('user_id', $user->id)->update(['customer_push_token' => null]);
            $user->forceFill([
                'name' => 'حساب محذوف',
                'email' => 'deleted-'.$user->id.'-'.\Illuminate\Support\Str::random(8).'@deleted.ozman.online',
                'username' => null,
                'phone' => null,
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(40)),
                'is_active' => false,
                'remember_token' => null,
            ])->save();
        });

        return response()->json(['deleted' => true]);
    }

    /** Who a referral code (a distributor's or marketer's QR) belongs to, before the shop form is filled in. */
    public function shopReferral(Request $request, MerchantRegistration $registration): JsonResponse
    {
        $code = (string) $request->validate(['code' => ['required', 'string', 'max:500']])['code'];
        $referral = $registration->referralFromCode($code);
        abort_unless($referral, 404, __('رمز الإحالة مش صالح أو مش فعّال. امسح QR الموزّع أو المسوّق كمان مرة.'));

        return response()->json(['referral' => $registration->describe($referral)]);
    }

    /** A new shop signs up in the app through a distributor's or marketer's referral, like the website, and is signed in. */
    public function registerShop(Request $request, MerchantRegistration $registration): JsonResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $orphanOwner = $registration->orphanOwner($request->input('email'));
        $data = $request->validate($registration->rules($orphanOwner) + [
            'referral_code' => ['required', 'string', 'max:500'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ], $registration->messages());
        $referral = $registration->referralFromCode($data['referral_code']);
        if (! $referral) {
            throw ValidationException::withMessages(['referral_code' => __('رمز الإحالة مش صالح أو مش فعّال. امسح QR الموزّع أو المسوّق كمان مرة.')]);
        }
        [$user, $shop] = $registration->create($data, $referral, $orphanOwner, $request);

        $response = $this->tokenResponse($user, $data['device_name'] ?? 'mobile', 201);

        return $response->setData($response->getData(true) + ['shop' => Presenter::shop($shop)]);
    }

    private function tokenResponse(User $user, string $device, int $status = 200): JsonResponse
    {
        [$token, $plain] = ApiToken::issue($user, $device);

        return response()->json([
            'token' => $plain,
            'expires_at' => $token->expires_at->toIso8601String(),
            'user' => Presenter::user($user),
        ], $status);
    }
}
