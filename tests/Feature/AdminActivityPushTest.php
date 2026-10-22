<?php
namespace Tests\Feature;

use App\Models\{AdminPushSubscription, FrontOrder, Shop, ShopActivityEvent, User};
use App\Services\{AdminActivityPush, ShopActivityRecorder, WebPushService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, DB, Hash};
use Tests\TestCase;

class AdminActivityPushTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->create(['role' => 'super_admin', 'is_active' => true]); }
    private function data(string $device = 'a'): array
    {
        $b64 = fn ($v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
        return ['endpoint' => 'https://fcm.googleapis.com/fcm/send/test-'.$device,
            'keys' => ['p256dh' => $b64("\x04".str_repeat('a', 64)), 'auth' => $b64(str_repeat('b', 16))], 'device_name' => 'Test'];
    }
    private function subscribe(User $user, string $device = 'a'): AdminPushSubscription
    {
        $this->actingAs($user)->postJson('/admin/analytics/push/subscription', $this->data($device))->assertOk();
        return AdminPushSubscription::where('endpoint_hash', hash('sha256', $this->data($device)['endpoint']))->firstOrFail();
    }

    public function test_registration_is_admin_only_validates_endpoints_and_never_exposes_credentials(): void
    {
        $this->postJson('/admin/analytics/push/subscription', $this->data())->assertUnauthorized();
        foreach (['shop_owner', 'employee', 'restaurant_driver'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));
            $this->postJson('/admin/analytics/push/subscription', $this->data())->assertForbidden();
            $this->getJson('/admin/analytics/push/key')->assertForbidden();
            $this->postJson('/admin/analytics/push/test', $this->data())->assertForbidden();
        }
        $admin = $this->admin(); $this->actingAs($admin);
        foreach (['http://fcm.googleapis.com/x', 'https://127.0.0.1/x', 'https://fcm.googleapis.com.evil.test/x',
            'https://fcm.googleapis.com:8080/x', 'https://user@fcm.googleapis.com/x'] as $endpoint) {
            $this->postJson('/admin/analytics/push/subscription', [...$this->data(), 'endpoint' => $endpoint])->assertUnprocessable();
        }
        $this->postJson('/admin/analytics/push/subscription', [...$this->data(), 'keys' => ['auth' => 'bad', 'p256dh' => 'bad']])->assertUnprocessable();
        $device = $this->subscribe($admin);
        $this->subscribe($admin); $this->assertDatabaseCount('admin_push_subscriptions', 1);
        $response = $this->postJson('/admin/analytics/push/status', $this->data())->assertOk()->assertJsonPath('registered', true);
        $this->assertStringNotContainsString($device->endpoint, $response->getContent());
        $this->assertStringNotContainsString($device->auth_token, $device->toJson());
        $this->actingAs($this->admin())->postJson('/admin/analytics/push/subscription', $this->data())->assertStatus(409);
        $this->postJson('/admin/analytics/push/status', $this->data())->assertJsonPath('registered', false);
        $this->deleteJson('/admin/analytics/push/subscription', $this->data())->assertOk();
        $this->assertDatabaseCount('admin_push_subscriptions', 1);
    }

    public function test_all_event_kinds_fan_out_once_per_device_and_rollbacks_do_not_send(): void
    {
        $admin = $this->admin(); $a = $this->subscribe($admin); $this->subscribe($admin, 'b');
        $inactive = $this->admin();$this->subscribe($inactive, 'inactive');$inactive->update(['is_active' => false]);
        $recorder = app(ShopActivityRecorder::class);
        foreach (['owner_login', 'product_updated', 'shop_visit', 'reward_spun', 'content_updated', 'sale_confirmed'] as $kind) {
            $recorder->record(null, $kind, 'Event', [], null, $kind);
            $recorder->record(null, $kind, 'Event', [], null, $kind);
        }
        $this->assertDatabaseCount('admin_push_deliveries', 12);
        DB::beginTransaction();$recorder->record(null, 'order_created', 'Rollback');DB::rollBack();
        $this->assertDatabaseCount('admin_push_deliveries', 12);
        $this->assertDatabaseMissing('shop_activity_events', ['title' => 'Rollback']);
        $this->mock(WebPushService::class)->shouldReceive('sendAdminDevice')->times(12)->andReturn('sent');
        $this->assertSame(12, app(AdminActivityPush::class)->flush());
        $this->assertSame(0, app(AdminActivityPush::class)->flush());
        $this->assertEquals(12, DB::table('admin_push_deliveries')->whereNotNull('sent_at')->count());
    }

    public function test_retry_expired_subscriptions_and_revoked_permissions_are_handled(): void
    {
        $admin = $this->admin();$device = $this->subscribe($admin);
        app(ShopActivityRecorder::class)->record(null, 'order_created', 'New order');
        $mock = $this->mock(WebPushService::class);
        $mock->shouldReceive('sendAdminDevice')->once()->andReturn('retry');
        $this->assertSame(0, app(AdminActivityPush::class)->flush());
        $this->assertDatabaseHas('admin_push_deliveries', ['attempts' => 1, 'sent_at' => null, 'failed_at' => null]);
        $this->assertSame(0, app(AdminActivityPush::class)->flush()); // Respect backoff.
        $this->travel(2)->minutes();
        $mock->shouldReceive('sendAdminDevice')->once()->andReturn('expired');
        app(AdminActivityPush::class)->flush();
        $this->assertDatabaseCount('admin_push_subscriptions', 0);
        $this->assertDatabaseCount('admin_push_deliveries', 0);
        $this->subscribe($admin, 'again');
        app(ShopActivityRecorder::class)->record(null, 'product_updated', 'Product');
        $admin->update(['role' => 'employee']);
        app(AdminActivityPush::class)->flush(); // No transport call for revoked admins.
        $this->assertDatabaseCount('admin_push_subscriptions', 0);
    }

    public function test_shop_content_and_wheel_activity_keep_the_correct_shop_and_exclude_internal_bookkeeping(): void
    {
        $admin = $this->admin();$this->subscribe($admin);
        $shop = Shop::create(['user_id' => $admin->id, 'name' => 'Shop A', 'slug' => 'shop-a', 'is_active' => true]);
        $wheel = \App\Models\RewardWheel::create(['shop_id' => $shop->id, 'key' => 'shop_signup_test',
            'title' => 'Welcome', 'wheel_type' => 'customer_signup', 'is_active' => true]);
        $segment = $wheel->segments()->create(['label' => 'Gift', 'discount_type' => 'gift', 'discount_value' => 0,
            'win_quota' => 1, 'color' => '#00ccdd', 'sort_order' => 0, 'is_active' => true]);
        $before = ShopActivityEvent::count();$wheel->update(['spin_cycle' => ['internal' => 1]]);
        $this->assertSame($before, ShopActivityEvent::count());
        $segment->update(['label' => 'New gift']);
        $visitor = \App\Models\VisitorRegistration::create(['shop_id' => $shop->id, 'name' => 'Visitor', 'phone' => '0590000000', 'type' => 'customer', 'residence_address' => 'Test address']);
        $reward = \App\Models\ShopSignupReward::create(['shop_id' => $shop->id, 'visitor_registration_id' => $visitor->id,
            'phone_hash' => str_repeat('a', 64), 'title' => 'Welcome', 'segments' => []]);
        $reward->update(['spun_at' => now(), 'selected_index' => 0]);
        $reward->update(['redeemed_at' => now()]);
        $this->assertDatabaseHas('shop_activity_events', ['shop_id' => $shop->id, 'kind' => 'reward_spun']);
        $this->assertDatabaseHas('shop_activity_events', ['shop_id' => $shop->id, 'kind' => 'reward_redeemed']);
        $this->assertDatabaseHas('shop_activity_events', ['shop_id' => $shop->id, 'subject_type' => 'RewardWheelSegment', 'title' => 'تعديل جائزة عجلة: New gift']);
        $this->assertSame(0, ShopActivityEvent::whereNull('shop_id')->count());
        $this->assertSame(ShopActivityEvent::count(), DB::table('admin_push_deliveries')->count());
    }

    public function test_payload_links_to_order_without_customer_contact_and_test_is_current_device_only(): void
    {
        $admin = $this->admin();$device = $this->subscribe($admin);$other = $this->subscribe($this->admin(), 'other');
        $shop = Shop::create(['user_id' => $admin->id, 'name' => 'Perfumes', 'slug' => 'perfumes', 'is_active' => true]);
        $order = FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'PRIVATE-1', 'customer_name' => 'Private Customer',
            'customer_phone' => '0591234567', 'customer_address' => 'Private Address', 'items' => [], 'total' => 200, 'status' => 'new']);
        $event = ShopActivityEvent::where('subject_type', 'FrontOrder')->where('subject_id', $order->id)->firstOrFail();
        $payload = app(AdminActivityPush::class)->payload($event);
        $this->assertStringContainsString('?order='.$order->id, $payload['url']);
        $this->assertEquals('Perfumes', $payload['title']);
        foreach (['Private Customer', '0591234567', 'Private Address'] as $secret) $this->assertStringNotContainsString($secret, json_encode($payload));
        $this->actingAs($admin);
        $this->postJson('/admin/analytics/push/test', $this->data('other'))->assertUnprocessable();
        $this->mock(WebPushService::class)->shouldReceive('sendAdminDevice')->once()
            ->withArgs(fn ($sub, $data) => $sub->id === $device->id && str_starts_with($data['tag'], 'ozman-admin-test-'))->andReturn('sent');
        $this->postJson('/admin/analytics/push/test', $this->data())->assertOk()->assertJsonPath('accepted', true);
    }

    public function test_logout_disables_only_this_device_and_session_expiry_keeps_subscription(): void
    {
        $admin = $this->admin();$this->subscribe($admin, 'a');$b = $this->subscribe($admin, 'b');
        $this->flushSession(); // Ordinary session expiry is not an explicit logout.
        $this->assertDatabaseCount('admin_push_subscriptions', 2);
        $this->withCookie('admin_push_device', $b->endpoint_hash)->post('/logout')->assertRedirect('/login');
        $this->assertDatabaseMissing('admin_push_subscriptions', ['id' => $b->id]);
        $this->assertDatabaseCount('admin_push_subscriptions', 1);
    }

    public function test_remembered_admin_can_return_without_session_and_pwa_assets_are_public_and_isolated(): void
    {
        $user = $this->admin();$user->update(['password' => Hash::make('Testing-secret-123')]);
        $result = $this->post('/login', ['email' => $user->email, 'password' => 'Testing-secret-123']);
        $name = Auth::guard()->getRecallerName();$cookie = $result->getCookie($name);
        $this->assertNotNull($cookie);$this->assertGreaterThan(time()+86400, $cookie->getExpiresTime());
        $this->flushSession();Auth::forgetGuards();
        $this->withCookie($name, $cookie->getValue())->get('/admin/analytics')->assertOk()->assertSee('تفعيل إشعارات الجوال');
        $this->assertAuthenticatedAs($user);
        Auth::forgetGuards();$this->flushSession();$this->defaultCookies = [];
        $this->get('/admin/analytics/manifest.webmanifest')->assertOk()->assertJsonPath('scope', '/admin/')->assertJsonPath('start_url', '/admin/analytics');
        $this->get('/admin/analytics/sw.js')->assertOk()->assertHeader('Service-Worker-Allowed', '/admin/');
        $this->get('/admin/analytics/icon/180.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/admin/analytics/icon/100.png')->assertNotFound();
        $this->getJson('/admin/analytics/data')->assertUnauthorized();
    }
}
