<?php

namespace Tests\Feature;

use App\Models\{Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MerchantPersistentLoginTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): array
    {
        $owner=User::factory()->create(['role'=>'shop_owner','is_active'=>true]);
        $shop=Shop::create(['user_id'=>$owner->id,'name'=>'Persistent shop','slug'=>'persistent-shop','catalog_type'=>'restaurant','is_active'=>true]);
        return [$owner,$shop];
    }

    private function forgetSession(): void
    {
        $this->app['session']->invalidate();
        Auth::forgetGuards();
        foreach($this->app['cookie']->getQueuedCookies() as $cookie) $this->app['cookie']->unqueue($cookie->getName(),$cookie->getPath());
    }

    public function test_general_login_remembers_owner_without_checkbox_and_restores_after_session_loss(): void
    {
        [$owner,$shop]=$this->owner();
        $response=$this->post(route('login.store'),['email'=>$owner->email,'password'=>'password']);
        $name=Auth::guard()->getRecallerName();
        $cookie=$response->getCookie($name,false);
        $this->assertNotNull($cookie);
        $this->assertGreaterThan(now()->addDays(300)->timestamp,$cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertNotEmpty($owner->fresh()->remember_token);
        $this->forgetSession();$this->travel(3)->hours();
        $this->withUnencryptedCookie($name,$cookie->getValue())->get(route('merchant-app.launch',$shop))
            ->assertRedirect(route($shop->dashboardRouteName(),$shop));
        $this->assertAuthenticatedAs($owner);
        $this->assertSame($shop->id,session('merchant_shop_id'));
    }

    public function test_explicit_logout_invalidates_remembered_sign_in(): void
    {
        [$owner,$shop]=$this->owner();
        $response=$this->post(route('merchant.login.store'),['email'=>$owner->email,'password'=>'password']);
        $name=Auth::guard()->getRecallerName();$cookie=$response->getCookie($name,false);
        $this->assertNotNull($cookie);
        $this->withUnencryptedCookie($name,$cookie->getValue())->post(route('logout'))->assertCookieExpired($name);
        $this->forgetSession();
        $this->withUnencryptedCookie($name,$cookie->getValue())->get(route('merchant-app.launch',$shop))
            ->assertRedirect(route('merchant.login',['redirect'=>route('merchant-app.launch',$shop,false)]));
        $this->assertGuest();
    }

    public function test_app_upgrades_existing_session_only_owner_but_not_admin_impersonation(): void
    {
        [$owner,$shop]=$this->owner();$name=Auth::guard()->getRecallerName();
        $this->actingAs($owner)->get(route('merchant-app.launch',$shop))->assertCookie($name);
        $this->forgetSession();
        $this->actingAs($owner)->withSession(['impersonator_admin_id'=>123])
            ->get(route('merchant-app.launch',$shop))->assertCookieMissing($name);
    }

    public function test_general_admin_login_without_remember_is_still_session_only(): void
    {
        $admin=User::factory()->create(['role'=>'super_admin','is_active'=>true]);
        $name=Auth::guard()->getRecallerName();
        $this->post(route('login.store'),['email'=>$admin->email,'password'=>'password'])->assertCookieMissing($name);
    }

    public function test_deactivated_owner_cannot_launch_using_a_remember_cookie(): void
    {
        [$owner,$shop]=$this->owner();
        $response=$this->post(route('merchant.login.store'),['email'=>$owner->email,'password'=>'password']);
        $name=Auth::guard()->getRecallerName();$cookie=$response->getCookie($name,false);
        $owner->update(['is_active'=>false]);$this->forgetSession();
        $this->withUnencryptedCookie($name,$cookie->getValue())->get(route('merchant-app.launch',$shop))->assertForbidden();
    }
}
