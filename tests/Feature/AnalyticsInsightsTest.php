<?php

namespace Tests\Feature;

use App\Models\FrontOrder;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AnalyticsInsightsTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $slug, array $extra = []): Shop
    {
        return Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id,
            'name' => $slug, 'slug' => $slug, 'catalog_type' => 'restaurant', 'is_active' => true, 'is_accepting_orders' => true, ...$extra]);
    }

    private function order(Shop $shop, array $extra = []): FrontOrder
    {
        return FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'IN-'.uniqid(), 'customer_name' => 'Rami', 'customer_phone' => '0521112233',
            'items' => [['name' => 'Roll', 'qty' => 1, 'price' => 50]], 'subtotal' => 50, 'total' => 50, 'status' => 'new', ...$extra]);
    }

    public function test_report_has_funnel_kitchen_alerts_heatmap_trends_and_customers(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $shop = $this->shop('bankai');
        DB::table('shop_visit_days')->insert(['shop_id' => $shop->id, 'day' => now()->toDateString(), 'visitor_hash' => str_repeat('a', 64), 'page_views' => 3]);
        DB::table('shop_visit_days')->insert(['shop_id' => $shop->id, 'day' => now()->toDateString(), 'visitor_hash' => str_repeat('b', 64), 'page_views' => 1]);
        // Done on time (20 promised, 18 real) and late (15 promised, 25 real).
        $this->order($shop, ['status' => 'completed', 'sale_confirmed_at' => now(), 'estimated_preparation_minutes' => 20,
            'preparation_started_at' => now()->subMinutes(30), 'ready_at' => now()->subMinutes(12)]);
        $this->order($shop, ['status' => 'completed', 'sale_confirmed_at' => now(), 'estimated_preparation_minutes' => 15,
            'preparation_started_at' => now()->subMinutes(40), 'ready_at' => now()->subMinutes(15)]);
        $waiting = $this->order($shop);
        FrontOrder::whereKey($waiting->id)->update(['created_at' => now()->subMinutes(25)]);
        $closed = $this->shop('closed-shop', ['is_accepting_orders' => false]);

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $data = $this->actingAs($admin)->getJson('/admin/analytics/data?from='.now()->toDateString().'&to='.now()->toDateString())->assertOk()->json('insights');

        $this->assertEquals(['visitors' => 2, 'orders' => 3, 'sales' => 2, 'visit_to_order' => 150, 'order_to_sale' => 66.7], $data['funnel']['total']);
        $this->assertSame(2, $data['kitchen']['total']['timed']);
        $this->assertEquals(21.5, $data['kitchen']['total']['avg_actual']);
        $this->assertEquals(50, $data['kitchen']['total']['late_pct']);
        $alerts = collect($data['alerts']);
        $this->assertTrue($alerts->contains(fn ($a) => ($a['order_id'] ?? null) === $waiting->id && $a['level'] === 'danger'));
        $this->assertTrue($alerts->contains(fn ($a) => $a['shop_id'] === $closed->id && str_contains($a['text'], 'متوقف')));
        $this->assertSame(3, array_sum(array_map('array_sum', $data['heatmap']['grid'])));
        $this->assertSame('bankai', $data['trends']['rising'][0]['name']);
        $this->assertEquals(['name' => 'Rami', 'phone' => '0521112233', 'shop' => 'bankai', 'orders' => 2, 'spent' => 100], $data['customers'][0]);
        $this->assertArrayHasKey('likes_total', $data['engagement']);
        $this->assertArrayHasKey('wheel_spins', $data['growth']);

        $this->actingAs($admin)->get('/admin/analytics')->assertOk()->assertSee('قمع التحويل')->assertSee('أداء المطبخ')->assertSee('أوقات الذروة')->assertSee('id="alertsList"', false);
    }

    public function test_weekly_report_emails_the_admins(): void
    {
        Mail::fake();
        $shop = $this->shop('bankai');
        $this->order($shop, ['status' => 'completed', 'total' => 120]);
        $this->travel(2)->days(); // The report covers the 7 full days before today.
        User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email' => 'boss@ozman.test']);
        $this->artisan('analytics:weekly-report', ['--dry' => true])->expectsOutputToContain('120.00 ₪')->assertSuccessful();
        $this->artisan('analytics:weekly-report')->expectsOutputToContain('Sent to 1')->assertSuccessful();
    }
}
