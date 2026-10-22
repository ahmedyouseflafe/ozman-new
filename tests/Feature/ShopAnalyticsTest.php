<?php

namespace Tests\Feature;

use App\Models\{FrontOrder, Shop, ShopActivityEvent, User, Product, Category, VisitorRegistration};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Route, Auth};
use Tests\TestCase;

class ShopAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $slug = 'analytics-shop'): Shop
    {
        return Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id,
            'name' => $slug, 'slug' => $slug, 'catalog_type' => 'cosmetics', 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function order(Shop $shop, string $status = 'new', array $extra = []): FrontOrder
    {
        return FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'TEST-'.uniqid(), 'customer_name' => 'Customer',
            'customer_phone' => '0591234567', 'items' => [['product_id' => 1, 'name' => 'Perfume', 'qty' => 2, 'price' => 50]],
            'subtotal' => 100, 'discount' => 10, 'total' => 90, 'status' => $status, ...$extra]);
    }

    public function test_all_analytics_endpoints_are_exclusive_to_active_super_admins(): void
    {
        $shop = $this->shop(); $order = $this->order($shop);
        $urls = ['/admin/analytics', '/admin/analytics/data', '/admin/analytics/export', '/admin/analytics/activity',
            '/admin/analytics/notifications', '/admin/analytics/orders/'.$order->id];
        foreach ($urls as $url) $this->getJson($url)->assertUnauthorized();
        foreach (['shop_owner', 'employee', 'distributor', 'agent', 'marketer', 'restaurant_driver'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));
            foreach ($urls as $url) $this->getJson($url)->assertForbidden();
            $this->postJson('/admin/analytics/read', ['through' => 100])->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => false]))->getJson('/admin/analytics/data')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/analytics')->assertOk()->assertSee('مركز متابعة المحلات')->assertSee('color-scheme:light', false);
        $this->getJson('/admin/analytics/orders/'.$order->id)->assertOk()->assertJsonPath('number', $order->order_number)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_sales_are_counted_by_completion_date_not_whatsapp_creation_and_are_scoped(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 22)->setTime(12, 0));
        $a = $this->shop('a'); $b = $this->shop('b');
        $old = $this->order($a, 'new');
        $old->forceFill(['created_at' => '2026-09-01 10:00:00'])->save();
        $old->update(['status' => 'completed']);
        $this->order($a, 'new'); $this->order($a, 'cancelled');
        $this->order($b, 'delivered', ['total' => 200]);
        $prior = $this->order($a, 'completed', ['total' => 50]);
        DB::table('front_orders')->where('id', $prior->id)->update(['sale_confirmed_at' => '2026-10-21 12:00:00', 'created_at' => '2026-10-21 10:00:00']);
        DB::table('shop_visit_days')->insert(['shop_id' => $a->id, 'day' => '2026-10-22', 'visitor_hash' => str_repeat('a',64), 'page_views' => 3]);
        $params = '?from=2026-10-22&to=2026-10-22&shop_id='.$a->id;
        $data = $this->actingAs($this->admin())->getJson('/admin/analytics/data'.$params)->assertOk()->json();
        $this->assertEquals(90, $data['summary']['revenue']);
        $this->assertEquals(1, $data['summary']['sales']);
        $this->assertEquals(2, $data['summary']['orders']);
        $this->assertEquals(1, $data['summary']['cancelled']);
        $this->assertEquals(2, $data['summary']['units']);
        $this->assertEquals(50, $data['summary']['previous_revenue']);
        $this->assertEquals(80, $data['summary']['revenue_change']);
        $this->assertEquals(1, $data['summary']['returning_buyers']);
        $this->assertEquals(3, $data['summary']['views']);
        $this->assertCount(1, $data['shops']);
        $this->assertEquals(90, $data['chart'][0]['revenue']);
        $old->update(['status' => 'cancelled']);
        $this->getJson('/admin/analytics/data'.$params)->assertOk()->assertJsonPath('summary.sales', 0);
        $this->assertDatabaseHas('shop_activity_events', ['subject_id' => $old->id, 'kind' => 'sale_reversed']);
        $this->getJson('/admin/analytics/data?from=2020-01-01&to=2026-10-22')->assertUnprocessable();
        $this->getJson('/admin/analytics/data?from=2026-10-23&to=2026-10-22')->assertUnprocessable();
        $this->getJson('/admin/analytics/data?shop_id=999999')->assertUnprocessable();
    }

    public function test_sale_notifications_are_durable_unique_and_read_state_is_per_admin(): void
    {
        $shop = $this->shop(); $order = $this->order($shop);
        $this->assertSame(0, ShopActivityEvent::where('kind', 'sale_confirmed')->count());
        $order->update(['status' => 'completed']); $firstDate = $order->sale_confirmed_at;
        $order->update(['status' => 'delivered']); $order->update(['customer_notes' => 'changed']);
        $this->assertSame(1, ShopActivityEvent::where('kind', 'sale_confirmed')->count());
        $this->assertSame($firstDate, $order->sale_confirmed_at);
        $adminA = $this->admin(); $adminB = $this->admin();
        $data = $this->actingAs($adminA)->getJson('/admin/analytics/notifications')->assertOk()->json();
        $this->assertEquals(6, $data['unread']); // Shop, order, two status changes, sale, and order edit.
        $this->assertEquals('order_updated', $data['events'][0]['kind']);
        $this->assertContains('sale_confirmed', array_column($data['events'], 'kind'));
        $this->postJson('/admin/analytics/read', ['through' => $data['latest']])->assertOk();
        $this->getJson('/admin/analytics/notifications')->assertJsonPath('unread', 0);
        $this->actingAs($adminB)->getJson('/admin/analytics/notifications')->assertJsonPath('unread', 6);
        $this->actingAs($adminA); $new = $this->order($shop);
        $this->postJson('/admin/analytics/read', ['through' => $data['latest']])->assertOk();
        $this->getJson('/admin/analytics/notifications')->assertJsonPath('unread', 1);
        $new->update(['status' => 'cancelled']);
        $this->getJson('/admin/analytics/notifications')->assertJsonPath('unread', 2);
        $this->assertDatabaseHas('shop_activity_events', ['kind' => 'order_cancelled', 'subject_id' => $new->id]);
    }

    public function test_failed_transactions_do_not_create_phantom_sales(): void
    {
        $order = $this->order($this->shop());
        DB::beginTransaction(); $order->update(['status' => 'completed']); DB::rollBack();
        $this->assertDatabaseMissing('shop_activity_events', ['kind' => 'sale_confirmed', 'subject_id' => $order->id]);
        $this->assertSame('new', $order->fresh()->status);
    }

    public function test_visits_are_session_deduplicated_and_exclude_admins_bots_errors_and_assets(): void
    {
        $shop = $this->shop();
        Route::middleware('web')->get('/analytics-fixture/{shop}', fn (Shop $shop) => response('store'))->name('cosmetics.store');
        $this->withSession(['analytics_visitor_id' => 'same-browser']);
        $this->get('/analytics-fixture/'.$shop->id)->assertOk(); $this->get('/analytics-fixture/'.$shop->id)->assertOk();
        $this->assertDatabaseCount('shop_visit_days', 1);
        $this->assertDatabaseHas('shop_visit_days', ['shop_id' => $shop->id, 'page_views' => 2]);
        $this->assertSame(1, ShopActivityEvent::where('kind', 'shop_visit')->where('shop_id', $shop->id)->count());
        $this->get('/analytics-fixture/'.$shop->id, ['User-Agent' => 'Googlebot'])->assertOk();
        $this->get('/analytics-fixture/99999')->assertNotFound();
        $this->actingAs($this->admin())->get('/analytics-fixture/'.$shop->id)->assertOk();
        $this->assertDatabaseHas('shop_visit_days', ['page_views' => 2]);
    }

    public function test_product_changes_and_registrations_are_audited_without_customer_secrets(): void
    {
        $shop = $this->shop(); $this->actingAs($shop->user);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Perfumes', 'slug' => 'perfumes']);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Perfume', 'slug' => 'perfume', 'price' => 20]);
        $product->update(['price' => 30]);
        $event = ShopActivityEvent::where('kind', 'product_updated')->firstOrFail();
        $this->assertEquals($shop->user_id, $event->actor_id);
        $this->assertEquals(20, $event->data['price']['from']);
        $this->assertEquals(30, $event->data['price']['to']);
        VisitorRegistration::create(['shop_id' => $shop->id, 'name' => 'Customer', 'phone' => '0591234567', 'type' => 'customer', 'residence_address' => 'Secret address', 'public_token' => 'private-token']);
        $registered = ShopActivityEvent::where('kind', 'customer_registered')->firstOrFail();
        $this->assertStringNotContainsString('private-token', $registered->toJson());
        $this->assertStringNotContainsString('Secret address', $registered->toJson());
        $this->actingAs($this->admin())->getJson('/admin/analytics/activity?shop_id='.$shop->id.'&kind=product_updated')->assertOk()->assertJsonCount(1, 'events');
    }

    public function test_owner_login_is_recorded_once_for_remember_me_and_exports_are_safe(): void
    {
        $shop = $this->shop();
        Auth::login($shop->user); Auth::login($shop->user, true);
        $this->assertSame(1, ShopActivityEvent::where('kind', 'owner_login')->count());
        $shop->update(['name' => '=HYPERLINK("malicious")']);
        $response = $this->actingAs($this->admin())->get('/admin/analytics/export')->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }

    public function test_migration_backfills_old_sales_without_fabricating_sale_alerts(): void
    {
        $shop = $this->shop();
        $migration = require database_path('migrations/2026_10_22_000001_create_shop_analytics_tables.php');
        $migration->down();
        DB::table('front_orders')->insert([
            'shop_id' => $shop->id, 'order_number' => 'OLD-SALE', 'customer_name' => 'Old customer', 'status' => 'completed',
            'total' => 100, 'created_at' => '2026-10-01 10:00:00', 'updated_at' => '2026-10-03 12:00:00',
        ]);
        $migration->up();
        $this->assertDatabaseHas('front_orders', ['order_number' => 'OLD-SALE', 'sale_confirmed_at' => '2026-10-03 12:00:00', 'sale_date_estimated' => true]);
        $this->assertDatabaseCount('shop_activity_events', 0);
    }
}
