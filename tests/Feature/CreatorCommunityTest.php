<?php
namespace Tests\Feature;
use App\Models\{User,PromoterProfile,PromoterPost,PromoterUpdate,CommunityPushSubscription};
use App\Services\{PromoterFollowerNotifications,WebPushService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Auth,Hash,Storage};
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class CreatorCommunityTest extends TestCase {
    use RefreshDatabase;
    private function profile(): PromoterProfile {
        return PromoterProfile::create(['user_id'=>User::factory()->create(['role'=>'promoter','is_active'=>true])->id,'display_name'=>'Creator','slug'=>'creator','is_active'=>true]);
    }
    private function customer(): User { return User::factory()->create(['role'=>'customer','is_active'=>true]); }
    private function device(User $u): CommunityPushSubscription {
        return CommunityPushSubscription::create(['user_id'=>$u->id,'endpoint_hash'=>hash('sha256',(string)$u->id),'endpoint'=>'https://fcm.googleapis.com/fcm/send/'.$u->id,'public_key'=>'test','auth_token'=>'test','content_encoding'=>'aes128gcm']);
    }
    private function content(PromoterProfile $p): PromoterPost {
        return $p->posts()->create(['kind'=>'story','type'=>'image','media'=>'promoters/posts/test.jpg','is_active'=>true,'expires_at'=>now()->addDay()]);
    }
    public function test_admin_invites_without_password_and_link_is_single_use(): void {
        $admin=User::factory()->create(['role'=>'super_admin','is_active'=>true]);
        $r=$this->actingAs($admin)->post(route('promoter.accounts.store'),['display_name'=>'Sara','slug'=>'sara','email'=>'sara@example.test']);
        $r->assertRedirect(route('promoter.accounts'))->assertSessionHas('invitation_url');
        $url=session('invitation_url');$profile=PromoterProfile::sole();
        $this->assertNotSame(basename($url),DB::table('promoter_invitations')->value('token_hash'));
        Auth::logout();$this->get($url)->assertOk()->assertSee('sara@example.test');
        $this->post($url,['password'=>'new-password-123','password_confirmation'=>'new-password-123'])->assertRedirect(route('community.login'));
        $this->assertTrue(Hash::check('new-password-123',$profile->user->fresh()->password));
        $this->post($url,['password'=>'another-pass-123','password_confirmation'=>'another-pass-123'])->assertStatus(410);
        $this->post(route('community.login.store'),['email'=>'sara@example.test','password'=>'new-password-123'])->assertRedirect(route('promoter.dashboard'));
        $this->get(route('promoter.accounts'))->assertForbidden();
    }
    public function test_invitation_rotation_expiry_and_inactive_accounts(): void {
        $p=$this->profile();$admin=User::factory()->create(['role'=>'super_admin','is_active'=>true]);
        $this->actingAs($admin)->post(route('promoter.accounts.invitation',$p))->assertSessionHas('invitation_url');$old=session('invitation_url');
        $this->post(route('promoter.accounts.invitation',$p));$new=session('invitation_url');
        $this->get($old)->assertStatus(410);$this->get($new)->assertOk();
        $p->user->update(['is_active'=>false]);$this->get($new)->assertForbidden();
        $p->user->update(['is_active'=>true]);$this->travel(4)->days();$this->get($new)->assertStatus(410);
    }
    public function test_customer_registration_profile_and_role_isolation(): void {
        $this->get(route('community.login'))->assertOk();
        $this->post(route('community.register'),['name'=>'Visitor','email'=>'visitor@example.test','phone'=>'0591234567','password'=>'visitor-1234','password_confirmation'=>'visitor-1234','role'=>'super_admin'])->assertRedirect(route('community.account'));
        $this->assertSame('customer',Auth::user()->role);
        $this->get(route('community.account'))->assertOk()->assertSee('Visitor');
        $this->put(route('community.update'),['name'=>'Updated','phone'=>'0597654321','role'=>'promoter'])->assertRedirect();
        $this->assertSame('customer',Auth::user()->fresh()->role);
        $this->get(route('promoter.accounts'))->assertForbidden();
        Auth::user()->update(['is_active'=>false]);$this->get(route('community.account'))->assertForbidden();
    }
    public function test_post_upload_creates_only_follower_updates_and_push_is_retried(): void {
        Storage::fake('local');$p=$this->profile();$u=$this->customer();$other=$this->customer();
        $p->followers()->attach($u);$this->device($u);$this->device($other);
        $this->actingAs($p->user)->postJson(route('promoter.posts.store',$p),['kind'=>'story','media'=>UploadedFile::fake()->image('story.jpg')])->assertCreated();
        $this->assertDatabaseCount('promoter_updates',1);$this->assertDatabaseCount('community_push_deliveries',1);
        app(PromoterFollowerNotifications::class)->publish(PromoterPost::sole());$this->assertDatabaseCount('community_push_deliveries',1);
        $this->mock(WebPushService::class,fn($m)=>$m->shouldReceive('sendCommunityDevice')->once()->andReturn('retry'));
        $this->assertSame(0,app(PromoterFollowerNotifications::class)->flush());
        $this->assertNull(DB::table('community_push_deliveries')->value('sent_at'));
        $this->travel(5)->minutes();
        $this->mock(WebPushService::class,fn($m)=>$m->shouldReceive('sendCommunityDevice')->once()->andReturn('sent'));
        $this->assertSame(1,app(PromoterFollowerNotifications::class)->flush());
        $this->assertSame(0,app(PromoterFollowerNotifications::class)->flush());
        $update=PromoterUpdate::sole();
        $this->actingAs($other)->post(route('community.read',$update))->assertForbidden();
        $this->actingAs($u)->get(route('community.account'))->assertOk()->assertSee('Creator');
        $this->post(route('community.read',$update))->assertRedirect($p->publicUrl());$this->assertNotNull($update->fresh()->read_at);
    }
    public function test_unfollow_and_expired_stories_cancel_pending_delivery(): void {
        $p=$this->profile();$u=$this->customer();$p->followers()->attach($u);$this->device($u);
        $post=$this->content($p);app(PromoterFollowerNotifications::class)->publish($post);
        $this->actingAs($u)->deleteJson(route('promoters.follow.destroy',$p))->assertOk();
        $this->mock(WebPushService::class,fn($m)=>$m->shouldNotReceive('sendCommunityDevice'));
        app(PromoterFollowerNotifications::class)->flush();$this->assertNotNull(DB::table('community_push_deliveries')->value('failed_at'));
        $p->followers()->attach($u);$expired=$this->content($p);app(PromoterFollowerNotifications::class)->publish($expired);
        $this->travel(2)->days();app(PromoterFollowerNotifications::class)->flush();
        $this->assertSame(2,DB::table('community_push_deliveries')->whereNotNull('failed_at')->count());
    }
    public function test_device_ownership_and_logout_cleanup_and_rejects_arbitrary_endpoints(): void {
        $u=$this->customer();$v=$this->customer();$body=['subscription'=>['endpoint'=>'https://fcm.googleapis.com/fcm/send/device','keys'=>['p256dh'=>'key','auth'=>'auth']]];
        $this->actingAs($u)->postJson(route('community.push.store'),$body)->assertOk();
        $this->actingAs($v)->postJson(route('community.push.store'),$body)->assertOk();
        $this->assertDatabaseCount('community_push_subscriptions',1);$this->assertSame($v->id,CommunityPushSubscription::sole()->user_id);
        $this->postJson(route('community.push.store'),['subscription'=>[...$body['subscription'],'endpoint'=>'https://127.0.0.1/private']])->assertUnprocessable();
        $this->post(route('logout'))->assertRedirect();$this->assertDatabaseCount('community_push_subscriptions',0);
    }
}
