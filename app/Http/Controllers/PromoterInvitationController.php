<?php
namespace App\Http\Controllers;
use App\Models\{User, PromoterProfile};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth};
use Illuminate\Support\Str;

class PromoterInvitationController extends Controller {
    public static function issue(User $user): string {
        $token = Str::random(64);
        DB::table('promoter_invitations')->updateOrInsert(['user_id'=>$user->id], [
            'token_hash'=>hash('sha256',$token), 'expires_at'=>now()->addDays(3), 'used_at'=>null,
        ]);
        return route('promoter.invitation', $token);
    }
    public function renew(Request $request, PromoterProfile $profile) {
        abort_unless($request->user()?->is_active && $request->user()->isSuperAdmin(),403);
        abort_unless($profile->isPublished(),422);
        return back()->with('invitation_url', self::issue($profile->user));
    }
    private function invitation(string $token) {
        abort_unless(strlen($token)===64,404);
        return DB::table('promoter_invitations')->where('token_hash',hash('sha256',$token))
            ->whereNull('used_at')->where('expires_at','>',now())->first();
    }
    public function show(string $token) {
        $invite=$this->invitation($token); abort_unless($invite,410);
        $user=User::findOrFail($invite->user_id); abort_unless($user->is_active && $user->isPromoter() && $user->promoterProfile?->is_active,403);
        return response()->view('promoters.invitation',compact('token','user'))->header('Cache-Control','no-store')->header('Referrer-Policy','no-referrer');
    }
    public function accept(Request $request, string $token) {
        $data=$request->validate(['password'=>['required','string','min:10','max:128','confirmed']]);
        $user=DB::transaction(function () use ($token,$data) {
            $invite=DB::table('promoter_invitations')->where('token_hash',hash('sha256',$token))->lockForUpdate()->first();
            abort_unless($invite && !$invite->used_at && now()->lt($invite->expires_at),410);
            $user=User::findOrFail($invite->user_id); abort_unless($user->is_active && $user->isPromoter() && $user->promoterProfile?->is_active,403);
            $user->forceFill(['password'=>$data['password'],'remember_token'=>Str::random(60)])->save();
            DB::table('promoter_invitations')->where('id',$invite->id)->update(['used_at'=>now()]);
            return $user;
        });
        // Do not change the administrator's signed-in account when testing a link.
        return redirect()->route('community.login')->with('status','تم تعيين كلمة المرور. ادخل ببريدك لبدء النشر.');
    }
}
