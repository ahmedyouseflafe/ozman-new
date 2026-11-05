<?php
namespace App\Http\Controllers;
use App\Models\{User, PromoterProfile, PromoterUpdate, CommunityPushSubscription};
use Illuminate\Http\Request;
use App\Services\{CommunityUsername, CustomerAvatar, GuestFollows, LoyaltyPoints};
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\Rules\Password;

class CommunityAccountController extends Controller {
    private function active(Request $r): void { abort_unless($r->user()?->is_active,403); }
    public function loginPage(Request $r) {
        // Links from promoter pages and store carts bring the visitor back afterwards. Same-site paths only (no //host or backslashes).
        $return=$r->query('return');
        if (!$r->user() && is_string($return) && strlen($return)<=2000 && preg_match('~^/(?![/\\\\])[^\s\\\\]*$~',$return)) redirect()->setIntendedUrl(url($return));
        return response()->view('promoters.community-login',['registerFirst'=>$r->query('tab')==='register'])->header('Cache-Control','no-store');
    }
    public function login(Request $r) {
        // One field for email or @username, like Instagram. Older forms still send "email".
        $r->merge(['login'=>trim((string)($r->input('login') ?? $r->input('email')))]);
        $data=$r->validate(['login'=>['required','string','max:255'],'password'=>['required','string']],['login.required'=>__('اكتب الإيميل أو اسم المستخدم.')]);
        $field=str_contains($data['login'],'@') && !str_starts_with($data['login'],'@') ? 'email' : 'username';
        $value=$field==='username' ? CommunityUsername::normalize($data['login']) : $data['login'];
        if (!Auth::attempt([$field=>$value,'password'=>$data['password'],'is_active'=>true],true)) {
            return back()->withErrors(['login'=>__('الإيميل/اسم المستخدم أو كلمة المرور غير صحيحة.')])->withInput(['login'=>$data['login']]);
        }
        $user=$r->user();
        // Shop owners, staff and drivers have their own login; this page is for the community.
        if (!$user->earnsPoints() && !$user->isSuperAdmin()) {
            Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
            return back()->withErrors(['login'=>__('هذا حساب محل أو موظف. ادخل من صفحة دخول لوحة التحكم.')])->withInput(['login'=>$data['login']])->with('staff_login',true);
        }
        $r->session()->regenerate(); app(GuestFollows::class)->merge($r,$user);
        $creator=$user->isSuperAdmin() || ($user->isPromoter() && !$user->promoterProfile?->isPending());
        return redirect()->intended(route($creator ? 'promoter.dashboard' : 'community.account'));
    }
    public function register(Request $r, CustomerAvatar $avatars) {
        abort_if($r->user(),403);
        $r->merge(['username'=>CommunityUsername::normalize($r->input('username')) ?: null,'account_type'=>$r->input('account_type','customer')]);
        $promoter=$r->input('account_type')==='promoter';
        $data=$r->validate(['account_type'=>['required','in:customer,promoter'],'name'=>['required','string','max:100'],'email'=>['required','email','max:255','unique:users,email'],
            'username'=>CommunityUsername::rules($promoter),'phone'=>['nullable','string','max:30'],'password'=>['required','string',Password::defaults()],...CustomerAvatar::rules()],
            [...CustomerAvatar::messages(),...CommunityUsername::messages()]);
        if ($promoter) {
            $slug=CommunityUsername::slugFor($data['username']);
            if (strlen($slug)<3 || CommunityUsername::slugTaken($slug)) {
                return back()->withErrors(['username'=>__('اسم المستخدم مأخوذ. جرّب اسم ثاني.')])->withInput($r->except('password','avatar'));
            }
        }
        // The photo is optional; it is processed first so a bad image never leaves a half-created account.
        $avatar=isset($data['avatar']) ? $avatars->store($data['avatar']) : null;
        $fields=['name'=>$data['name'],'email'=>$data['email'],'username'=>$data['username'] ?? null,'phone'=>$data['phone'] ?? null,'password'=>$data['password']];
        try {
            $user=DB::transaction(function () use($fields,$avatar,$promoter) {
                $user=User::create([...$fields,'role'=>$promoter ? 'promoter' : 'customer','is_active'=>true,'avatar_path'=>$avatar]);
                if ($promoter) {
                    // The page stays hidden until the admin accepts it; the link is reserved now.
                    $profile=PromoterProfile::create(['user_id'=>$user->id,'slug'=>CommunityUsername::slugFor($user->username),'display_name'=>$user->name,'is_active'=>false,'approved_at'=>null]);
                    DB::table('promoter_profile_slugs')->insert(['slug'=>$profile->slug,'promoter_profile_id'=>$profile->id]);
                }
                return $user;
            });
        } catch (\Throwable $e) { $avatars->delete($avatar); throw $e; }
        Auth::login($user,true); $r->session()->regenerate(); app(GuestFollows::class)->merge($r,$user);
        if ($promoter) {
            return redirect()->route('community.account')->with('status',__('وصل طلبك كمروّج 🎉 الإدارة بتراجعه، وبيوصلك إيميل أول ما ينقبل. لهلأ بتقدر تتابع المروّجين وتجمع نقاط.'));
        }
        return redirect()->intended(route('community.account'))->with('status','أهلًا فيك! اكتشف المروّجين وتابع حساباتك المفضلة.');
    }
    public function account(Request $r, LoyaltyPoints $loyalty) {
        $this->active($r); $user=$r->user();
        // The community account is for customers and promoters; admins and staff go to their own areas.
        if (!$user->earnsPoints()) return redirect()->route($user->isSuperAdmin() ? 'promoter.accounts' : 'dashboard');
        $points=['balance'=>$loyalty->balance($user),'pending'=>$loyalty->pendingPoints($user),'shops'=>$loyalty->shopBalances($user)];
        $pointEntries=$user->loyaltyEntries()->with(['shop:id,name','order:id,order_number'])->latest('id')->paginate(10,['*'],'points');
        $profiles=PromoterProfile::published()->whereHas('followers',fn($q)=>$q->where('users.id',$user->id))->paginate(12,['*'],'following');
        $updates=PromoterUpdate::where('user_id',$user->id)->whereHas('post',fn($q)=>$q->visible()->whereHas('profile',fn($p)=>$p->published()->whereHas('followers',fn($f)=>$f->where('users.id',$user->id))))->with('post.profile')->latest()->paginate(15,['*'],'updates');
        $pushEnabled=CommunityPushSubscription::where('user_id',$user->id)->where('endpoint_hash',$r->session()->get('community_push_endpoint_hash') ?? $r->cookie('community_push_device'))->exists();
        $tab=in_array($r->query('tab'),['overview','following','points','settings'],true) ? $r->query('tab') : 'overview';
        // Page numbers belong to one tab; follow them so pagination keeps its tab.
        if (!$r->has('tab')) $tab=match(true){ $r->has('following') => 'following', $r->has('points') => 'points', default => $tab };
        $market=['listings'=>\App\Models\MarketListing::where('user_id',$user->id)->where('status','active')->count(),
            'sales'=>\App\Models\MarketOrder::where('seller_id',$user->id)->where('status','pending')->count(),
            'purchases'=>\App\Models\MarketOrder::where('buyer_id',$user->id)->whereIn('status',['pending','accepted'])->count()];
        $creator=$user->isPromoter() ? $user->promoterProfile?->loadCount(['followers','guestFollowers','posts']) : null;
        $unread=PromoterUpdate::where('user_id',$user->id)->whereNull('read_at')->count();
        return response()->view('promoters.community-account',compact('user','profiles','updates','pushEnabled','points','pointEntries','tab','market','creator','unread'))->header('Cache-Control','private, no-store');
    }
    /** An existing customer applies to become a promoter; the page waits for admin approval like a new signup. */
    public function becomePromoter(Request $r) {
        $this->active($r); $user=$r->user();
        abort_unless($user->isCustomer(),403);
        $r->merge(['username'=>CommunityUsername::normalize($r->input('username',$user->username)) ?: null]);
        $data=$r->validate(['username'=>CommunityUsername::rules(true,$user->id),'bio'=>['nullable','string','max:600']],CommunityUsername::messages());
        $slug=CommunityUsername::slugFor($data['username']);
        if (strlen($slug)<3 || CommunityUsername::slugTaken($slug)) {
            return back()->withErrors(['username'=>__('اسم المستخدم مأخوذ كرابط صفحة. جرّب اسم ثاني.')])->withInput();
        }
        DB::transaction(function () use($user,$data,$slug) {
            $user->forceFill(['username'=>$data['username'],'role'=>'promoter'])->save();
            $profile=PromoterProfile::create(['user_id'=>$user->id,'slug'=>$slug,'display_name'=>$user->name,'bio'=>$data['bio'] ?? null,'is_active'=>false,'approved_at'=>null]);
            DB::table('promoter_profile_slugs')->insert(['slug'=>$slug,'promoter_profile_id'=>$profile->id]);
        });
        return redirect()->route('community.account')->with('status',__('وصل طلبك كمروّج 🎉 بيوصلك إيميل أول ما الإدارة تقبله.'));
    }
    public function update(Request $r) {
        $this->active($r);
        $user=$r->user();
        $r->merge(['username'=>CommunityUsername::normalize($r->input('username', $user->username)) ?: null]);
        // Promoters keep their username: it matches their page link.
        $rules=['name'=>['required','string','max:100'],'phone'=>['nullable','string','max:30']];
        if (!$user->isPromoter()) $rules['username']=CommunityUsername::rules((bool)$user->username,$user->id);
        $user->update($r->validate($rules,CommunityUsername::messages()));
        return back()->with('status','تم حفظ بياناتك.');
    }
    public function updateAvatar(Request $r, CustomerAvatar $avatars) {
        $this->active($r); abort_unless($r->user()->isCustomer(),403);
        $avatars->replace($r->user(),$r->validate(CustomerAvatar::rules(true),CustomerAvatar::messages())['avatar']);
        return back()->with('status',__('تم تحديث صورتك.'));
    }
    public function destroyAvatar(Request $r, CustomerAvatar $avatars) {
        $this->active($r); abort_unless($r->user()->isCustomer(),403);
        $avatars->remove($r->user());
        return back()->with('status',__('تم حذف صورتك.'));
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
