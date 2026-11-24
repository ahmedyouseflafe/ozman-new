<?php

namespace Tests\Feature;

use App\Models\{ApiToken, FrontOrder, PushDevice, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppStorePoliciesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_and_account_deletion_pages_are_public(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('سياسة الخصوصية')->assertSee('Firebase');
        $this->get('/delete-account')->assertOk()->assertSee('حذف حسابك')->assertSee('حسابي');
    }

    public function test_the_business_page_invites_shops_on_ozman_whatsapp(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner']);
        Shop::create(['user_id' => $owner->id, 'name' => 'Ozman', 'slug' => 'ozman', 'catalog_type' => 'general', 'is_active' => true, 'whatsapp' => '+972 59-912-3456']);

        $this->get('/business')->assertOk()->assertSee('أوزمان للمتاجر')
            ->assertSee('https://wa.me/972599123456?text=', false)->assertDontSee('[رقم الواتساب]')->assertDontSee('[الأسعار');
    }

    public function test_a_customer_deletes_their_account_from_the_app(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Lina', 'phone' => '0599123456', 'password' => Hash::make('secret-pass')]);
        [, $plain] = ApiToken::issue($user);
        $headers = ['Authorization' => 'Bearer '.$plain];
        PushDevice::create(['token' => 'phone-1', 'platform' => 'android', 'user_id' => $user->id]);
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'S', 'slug' => 's-'.uniqid(), 'catalog_type' => 'clothing', 'is_active' => true]);
        $order = FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $user->id, 'order_number' => 'APP-1', 'customer_name' => 'Lina', 'order_channel' => 'app', 'customer_push_token' => 'phone-1']);

        $this->postJson('/api/v1/auth/delete-account', ['password' => 'wrong'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/auth/delete-account', ['password' => 'secret-pass'], $headers)->assertOk();

        $user->refresh();
        $this->assertSame('حساب محذوف', $user->name);
        $this->assertNull($user->phone);
        $this->assertFalse((bool) $user->is_active);
        $this->assertSame(0, PushDevice::where('user_id', $user->id)->count());
        $this->assertNull($order->fresh()->customer_push_token);
        $this->getJson('/api/v1/auth/me', $headers)->assertUnauthorized();
    }

    public function test_a_shop_owner_asks_for_deletion_from_the_app_and_the_team_is_told_once(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true, 'password' => Hash::make('secret-pass')]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'S', 'slug' => 's-'.uniqid(), 'catalog_type' => 'clothing', 'is_active' => true]);
        [, $plain] = ApiToken::issue($owner);
        $headers = ['Authorization' => 'Bearer '.$plain];

        $this->postJson('/api/v1/auth/delete-account', ['password' => 'wrong'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/auth/delete-account', ['password' => 'secret-pass'], $headers)
            ->assertOk()->assertJsonPath('requested', true)->assertJsonPath('deleted', false);
        $this->postJson('/api/v1/auth/delete-account', ['password' => 'secret-pass'], $headers)->assertOk();

        // The shop keeps working until the team handles it; the team gets one notice, not one per tap.
        $this->assertTrue((bool) $owner->fresh()->is_active);
        $notices = \App\Models\AdminNotification::where('type', 'account_deletion_requested')->get();
        $this->assertCount(1, $notices);
        $this->assertSame($shop->id, $notices[0]->shop_id);
        $this->assertSame($owner->id, $notices[0]->subject_id);
    }

    public function test_an_admin_account_is_not_deleted_from_the_app(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'password' => Hash::make('secret-pass')]);
        [, $plain] = ApiToken::issue($admin);
        $this->postJson('/api/v1/auth/delete-account', ['password' => 'secret-pass'], ['Authorization' => 'Bearer '.$plain])->assertStatus(422);
        $this->assertTrue((bool) $admin->fresh()->is_active);
    }
}
