<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/** Password change and "forgot password" for community accounts (customers and promoters). */
class CommunityPasswordController extends Controller
{
    /** Only community accounts reset here; dashboard staff keep their own login. */
    private const ROLES = ['customer', 'promoter'];

    /** Who may reset a forgotten password by email: community accounts and shop owners (never admins or staff). */
    public const RESETTABLE_ROLES = ['customer', 'promoter', 'shop_owner'];

    public function request()
    {
        return response()->view('promoters.forgot-password')->header('Cache-Control', 'no-store');
    }

    public function email(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $user = User::query()->where('email', $data['email'])->where('is_active', true)->whereIn('role', self::RESETTABLE_ROLES)->first();
        if ($user) {
            // Throttled by the broker too (one link per minute per account).
            Password::broker()->sendResetLink(['email' => $user->email]);
        }

        // Same answer whether or not the account exists, so emails can't be checked here.
        return back()->with('status', __('إذا كان البريد مسجّل عندنا، رح يوصلك رابط لتعيين كلمة مرور جديدة خلال دقائق. افحص كمان مجلد الرسائل غير المرغوبة.'));
    }

    public function edit(Request $request, string $token)
    {
        return response()->view('promoters.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'max:128', PasswordRule::defaults()],
        ]);
        $account = User::query()->where('email', $data['email'])->where('is_active', true)->whereIn('role', self::RESETTABLE_ROLES)->first();
        $allowed = $account !== null;

        $status = $allowed ? Password::broker()->reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            // A pending promoter invitation is no longer needed once the owner picked a password.
            DB::table('promoter_invitations')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        }) : Password::INVALID_USER;

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('الرابط غير صالح أو انتهت مدته. اطلب رابط جديد.')]);
        }

        // Shop owners sign in on the dashboard's page, everyone else on the community one.
        return redirect()->route($account->role === 'shop_owner' ? 'login' : 'community.login')
            ->with('status', __('تم تغيير كلمة المرور. سجّل دخولك بالكلمة الجديدة.'));
    }

    /** Signed-in customer or promoter changes their own password. */
    public function change(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->is_active && in_array($user->role, self::ROLES, true), 403);
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'max:128', 'different:current_password', PasswordRule::defaults()],
        ], [
            'current_password.current_password' => __('كلمة المرور الحالية غير صحيحة.'),
            'password.different' => __('اختار كلمة مرور جديدة مختلفة عن الحالية.'),
        ]);

        // New remember token signs out other devices that used "remember me"; this device stays signed in.
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        DB::table('promoter_invitations')->where('user_id', $user->id)->delete();
        Auth::login($user, true);
        $request->session()->regenerate();

        return back()->with('status', __('تم تغيير كلمة المرور.'));
    }
}
