<?php
namespace App\Http\Controllers;
use App\Models\{User, PromoterProfile, PromoterUpdate, CommunityPushSubscription};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class CommunityAccountController extends Controller {
    private function active(Request $r): void { abort_unless($r->user()?->is_active,403); }
    public function loginPage() { return response()->view('promoters.community-login')->header('Cache-Control','no-store'); }
    public function login(Request $r) {
        $data=$r->validate(['email'=>['required','email'],'password'=>['required','string']]);
        if (!Auth::attempt([...$data,'is_active'=>true],true)) return back()->withErrors(['email'=>'البريد أو كلمة المرور غير صحيحة.'])->onlyInput('email');
        $r->session()->regenerate();
        return redirect()->route($r->user()->isPromoter() || $r->user()->isSuperAdmin() ? 'promoter.dashboard' : 'community.account');
    }
    public function register(Request $r) {
        abort_if($r->user(),403);
        $data=$r->validate(['name'=>['required','string','max:100'],'email'=>['required','email','max:255','unique:users,email'],
            'phone'=>['nullable','string','max:30'],'password'=>['required','string','min:10','max:128','confirmed']]);
        $user=User::create([...$data,'role'=>'customer','is_active'=>true]); Auth::login($user,true); $r->session()->regenerate();
        return redirect()->route('community.account')->with('status','أهلًا فيك! اكتشف المروّجين وتابع حساباتك المفضلة.');
    }
    public function account(Request $r) {
        $this->active($r); $user=$r->user();
        $profiles=PromoterProfile::published()->whereHas('followers',fn($q)=>$q->where('users.id',$user->id))->paginate(12,['*'],'following');
        $updates=PromoterUpdate::where('user_id',$user->id)->whereHas('post',fn($q)=>$q->visible()->whereHas('profile',fn($p)=>$p->published()->whereHas('followers',fn($f)=>$f->where('users.id',$user->id))))->with('post.profile')->latest()->paginate(15,['*'],'updates');
        $pushEnabled=CommunityPushSubscription::where('user_id',$user->id)->where('endpoint_hash',$r->session()->get('community_push_endpoint_hash') ?? $r->cookie('community_push_device'))->exists();
        return response()->view('promoters.community-account',compact('user','profiles','updates','pushEnabled'))->header('Cache-Control','private, no-store');
    }
    public function update(Request $r) {
        $this->active($r);
        $r->user()->update($r->validate(['name'=>['required','string','max:100'],'phone'=>['nullable','string','max:30']]));
        return back()->with('status','تم حفظ بياناتك.');
    }
    public function read(Request $r, PromoterUpdate $update) {
        $this->active($r); abort_unless((int)$update->user_id===(int)$r->user()->id,403);
        abort_unless($update->post?->isVisible() && $update->post->profile->followers()->where('users.id',$r->user()->id)->exists(),404);
        $update->update(['read_at'=>now()]); return redirect()->to($update->post->profile->publicUrl());
    }
    public function key(Request $r, \App\Services\WebPushService $push) { $this->active($r); return response()->json(['public_key'=>$push->publicKey()])->header('Cache-Control','no-store'); }
    public function subscribe(Request $r) {
        $this->active($r);
        $data=$r->validate(['subscription.endpoint'=>['required','url:https','max:2048'], 'subscription.keys.p256dh'=>['required','string','max:512'],
            'subscription.keys.auth'=>['required','string','max:255'],'subscription.contentEncoding'=>['nullable','in:aesgcm,aes128gcm']]);
        $s=$data['subscription']; $host=strtolower(parse_url($s['endpoint'],PHP_URL_HOST) ?: '');
        // Only browser push providers; never send server requests to arbitrary URLs.
        abort_unless(in_array($host,['fcm.googleapis.com','android.googleapis.com','web.push.apple.com'],true)
            || str_ends_with($host,'.push.apple.com') || str_ends_with($host,'.push.services.mozilla.com') || $host==='updates.push.services.mozilla.com'
            || str_ends_with($host,'.notify.windows.com'),422);
        $hash=hash('sha256',$s['endpoint']);
        DB::transaction(function () use($r,$s,$hash) {
            $device=CommunityPushSubscription::firstOrNew(['endpoint_hash'=>$hash]);
            if ($device->exists && (int)$device->user_id!==(int)$r->user()->id) DB::table('community_push_deliveries')->where('subscription_id',$device->id)->delete();
            $device->fill(['user_id'=>$r->user()->id,'endpoint'=>$s['endpoint'],'public_key'=>$s['keys']['p256dh'],'auth_token'=>$s['keys']['auth'],'content_encoding'=>$s['contentEncoding'] ?? 'aes128gcm'])->save();
        });
        $r->session()->put('community_push_endpoint_hash',$hash);
        return response()->json(['registered'=>true])->cookie('community_push_device',$hash,43200,null,null,true,true,false,'lax');
    }
    public function unsubscribe(Request $r) {
        $this->active($r);
        $r->validate(['endpoint'=>['required','url:https','max:2048']]);
        CommunityPushSubscription::where('user_id',$r->user()->id)->where('endpoint_hash',hash('sha256',$r->input('endpoint')))->delete();
        $r->session()->forget('community_push_endpoint_hash');
        return response()->json(['deleted'=>true])->withoutCookie('community_push_device');
    }
}
