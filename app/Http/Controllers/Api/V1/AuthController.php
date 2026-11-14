<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Services\CommunityUsername;
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
     * Emails the website's reset link, with the website's rules: customers and promoters only, and the
     * same answer whether or not the account exists. The new password is then chosen on the website.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $user = User::query()->where('email', mb_strtolower(trim($data['email'])))->where('is_active', true)
            ->whereIn('role', ['customer', 'promoter'])->first();
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
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'is_active' => true,
        ]);

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
        abort_unless(in_array($user->role, ['customer', 'promoter'], true), 422, 'حسابات أصحاب المحلات والإدارة بتنحذف من خلال فريق أوزمان.');

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
