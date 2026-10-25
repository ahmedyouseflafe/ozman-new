<?php

namespace App\Http\Controllers;

use App\Models\PromoterProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class PromoterFollowController extends Controller
{
    private function published(PromoterProfile $profile): void
    {
        abort_unless($profile->isPublished(), 404);
    }

    public function account(Request $request, PromoterProfile $profile)
    {
        $this->published($profile);
        if ($request->user()) {
            return redirect()->to($profile->publicUrl());
        }

        return response()->view('promoters.follow-account', compact('profile'))->header('Cache-Control', 'private, no-store');
    }

    public function login(Request $request, PromoterProfile $profile)
    {
        $this->published($profile);
        if (! $request->user()) {
            $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
            if (! Auth::attempt([...$credentials, 'is_active' => true], true)) {
                return back()->withErrors(['email' => 'البريد أو كلمة المرور غير صحيحة، أو الحساب غير فعّال.'])->onlyInput('email');
            }
            $request->session()->regenerate();
        }

        return $this->store($request, $profile);
    }

    public function register(Request $request, PromoterProfile $profile)
    {
        $this->published($profile);
        if (! $request->user()) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ]);
            $user = User::create([...$data, 'role' => 'customer', 'is_active' => true]);
            Auth::login($user, true);
            $request->session()->regenerate();
        }

        return $this->store($request, $profile);
    }

    public function store(Request $request, PromoterProfile $profile)
    {
        $this->published($profile);
        abort_unless($request->user()?->is_active, 403);
        abort_if($request->user()->id === $profile->user_id, 422, 'لا يمكنك متابعة حسابك نفسه.');
        // The unique pair and upsert make repeated requests safe, including retries.
        DB::table('promoter_followers')->upsert([
            'promoter_profile_id' => $profile->id, 'user_id' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ], ['promoter_profile_id', 'user_id'], ['updated_at']);

        return $this->result($request, $profile, true);
    }

    public function destroy(Request $request, PromoterProfile $profile)
    {
        $this->published($profile);
        abort_unless($request->user()?->is_active, 403);
        $profile->followers()->detach($request->user()->id);

        return $this->result($request, $profile, false);
    }

    private function result(Request $request, PromoterProfile $profile, bool $following)
    {
        if ($request->expectsJson()) {
            return response()->json(['following' => $following, 'followers_count' => $profile->followers()->count()])
                ->header('Cache-Control', 'private, no-store');
        }

        return redirect()->to($profile->publicUrl())->with('status', $following ? 'تمت متابعة الحساب.' : 'تم إلغاء المتابعة.');
    }
}
