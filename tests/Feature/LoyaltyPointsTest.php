<?php

namespace Tests\Feature;

use App\Models\{Category, FrontOrder, LoyaltyPointEntry, Product, Shop, User};
use App\Services\LoyaltyPoints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoyaltyPointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Program settings live in the local settings file; never touch the real one.
        Storage::fake('local');
    }

    private function shop(array $extra = []): Shop
    {
        return Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id, 'name' => 'Zara', 'slug' => 'zara-'.Str::random(5),
            'catalog_type' => 'clothing', 'whatsapp' => '972501234567', 'is_active' => true, ...$extra]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true]);
    }

    /** Places a real catalog order of `$qty` × 125 ₪ (prices are computed on the server). */
    private function order(Shop $shop, int $qty = 2): FrontOrder
    {
        $category = Category::firstOrCreate(['shop_id' => $shop->id, 'slug' => 'c-'.$shop->id], ['name' => 'Clothes']);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Coat', 'slug' => 'coat-'.Str::random(6), 'price' => 125, 'is_active' => true,
            'catalog_attributes' => ['sizes' => ['M'], 'colors' => ['Blue']]]);
        $number = $this->postJson(route('catalog.orders.store', $shop), ['request_token' => (string) Str::uuid(), 'customer_name' => 'Lina', 'customer_phone' => '0591234567',
            'items' => [['product_id' => $product->id, 'qty' => $qty, 'size' => 'M', 'color' => null]]])->assertOk()->json('order_number');

        return FrontOrder::where('order_number', $number)->firstOrFail();
    }

    private function setStatus(FrontOrder $order, string $status): void
    {
        $customer = auth()->user();
        $this->actingAs($order->shop->user)->patchJson(route('front-orders.status', $order), ['status' => $status])->assertOk();
        if ($customer) $this->actingAs($customer);
    }

    private function balance(User $user): array
    {
        $points = app(LoyaltyPoints::class);

        return [$points->balance($user), $points->pendingPoints($user)];
    }

    public function test_points_count_only_full_steps_of_the_final_amount(): void
    {
        $points = app(LoyaltyPoints::class);
        $this->assertSame([20, 0, 10, 10, 0], array_map(fn ($a) => $points->pointsFor($a), [250, 99, 100, 199.99, 0]));

        $points->saveSettings(true, 50, 3);
        $this->assertSame(15, app(LoyaltyPoints::class)->pointsFor(250));
        $this->assertSame(['enabled' => true, 'amount' => 50, 'points' => 3], app(LoyaltyPoints::class)->settings());
    }

    public function test_customer_points_are_pending_until_the_shop_completes_the_order(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer);
        $order = $this->order($this->shop());

        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame([0, 20], $this->balance($customer));
        $this->assertDatabaseHas('loyalty_point_entries', ['front_order_id' => $order->id, 'type' => 'earn', 'status' => 'pending', 'points' => 20, 'shop_id' => $order->shop_id]);

        $this->setStatus($order, 'preparing');
        $this->assertSame([0, 20], $this->balance($customer));
        $this->setStatus($order, 'completed');
        $this->assertSame([20, 0], $this->balance($customer));

        $this->get(route('community.account'))->assertOk()->assertSee('id="points"', false)->assertSee($order->order_number)->assertSee('⭐ 20', false);
    }

    public function test_cancelling_after_confirmation_withdraws_the_points_once(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer);
        $order = $this->order($this->shop());
        $this->setStatus($order, 'delivered');
        $this->setStatus($order, 'cancelled');
        $this->setStatus($order, 'not_delivered');

        $this->assertSame([0, 0], $this->balance($customer));
        $this->assertSame(1, LoyaltyPointEntry::where('front_order_id', $order->id)->where('type', 'reverse')->where('points', -20)->count());

        $pendingOnly = $this->order($this->shop());
        $this->setStatus($pendingOnly, 'cancelled');
        $this->assertDatabaseHas('loyalty_point_entries', ['front_order_id' => $pendingOnly->id, 'type' => 'earn', 'status' => 'cancelled']);
        $this->assertSame([0, 0], $this->balance($customer));
    }

    public function test_repeated_status_changes_never_count_twice(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer);
        $order = $this->order($this->shop());
        foreach (['completed', 'delivered', 'completed', 'preparing', 'completed'] as $status) {
            $this->setStatus($order, $status);
        }
        $this->assertSame([20, 0], $this->balance($customer));
        $this->assertSame(1, LoyaltyPointEntry::where('front_order_id', $order->id)->count());

        // A cancelled order that the shop later completes earns its points exactly once.
        $reopened = $this->order($this->shop());
        foreach (['cancelled', 'new', 'completed', 'cancelled', 'completed', 'delivered'] as $status) {
            $this->setStatus($reopened, $status);
        }
        $this->assertSame([40, 0], $this->balance($customer));
        $this->assertSame(20, (int) LoyaltyPointEntry::where('front_order_id', $reopened->id)->where('status', 'confirmed')->sum('points'));
    }

    public function test_non_participating_shops_and_a_disabled_program_earn_nothing(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer);
        $order = $this->order($this->shop(['loyalty_enabled' => false]));
        $this->setStatus($order, 'completed');
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame(0, LoyaltyPointEntry::count());

        app(LoyaltyPoints::class)->saveSettings(false, 100, 10);
        $this->order($this->shop());
        $this->assertSame(0, LoyaltyPointEntry::count());
        $this->view('front.partials.loyalty_hint', ['shop' => $this->shop(), 'total' => '#cartTotal'])->assertDontSee('data-loyalty-hint', false);
    }

    public function test_guests_earn_nothing_and_are_invited_to_sign_in(): void
    {
        $shop = $this->shop();
        $order = $this->order($shop);
        $this->assertNull($order->user_id);
        $this->actingAs($shop->user)->patchJson(route('front-orders.status', $order), ['status' => 'completed'])->assertOk();
        $this->assertSame(0, LoyaltyPointEntry::count());

        $this->app['auth']->forgetGuards();
        $this->view('front.partials.loyalty_hint', ['shop' => $shop, 'total' => '#cartTotal'])
            ->assertSee('data-total="#cartTotal"', false)->assertSee('community/login?return=', false)->assertSee('سجّل دخول واكسب :points نقطة على هالطلب');
        $this->actingAs($this->customer());
        $this->view('front.partials.loyalty_hint', ['shop' => $shop, 'total' => '#cartTotal'])->assertSee('رح تكسب :points نقطة على هالطلب ⭐')->assertDontSee('data-login', false);
        $this->actingAs($shop->user);
        $this->view('front.partials.loyalty_hint', ['shop' => $shop, 'total' => '#cartTotal'])->assertDontSee('data-loyalty-hint', false);
    }

    public function test_sign_in_link_returns_to_the_store_page_only_for_local_paths(): void
    {
        $this->get(route('community.login', ['return' => '/stores/zara?tab=new']))->assertOk()->assertSessionHas('url.intended', url('/stores/zara?tab=new'));
        foreach (['//evil.example', 'https://evil.example', '/\\evil.example'] as $evil) {
            $this->flushSession();
            $this->get(route('community.login', ['return' => $evil]))->assertOk()->assertSessionMissing('url.intended');
        }
    }

    public function test_front_order_discounts_are_untouched_and_wheel_discounts_lower_pending_points(): void
    {
        $customer = $this->customer();
        $shop = $this->shop();
        $this->actingAs($customer)->postJson(route('front-orders.store'), ['shop_id' => $shop->id, 'customer_name' => 'Lina', 'order_channel' => 'whatsapp', 'visitor_type' => 'customer',
            'subtotal' => 263.16, 'discount' => 13.16, 'total' => 250])->assertOk();
        $order = FrontOrder::where('shop_id', $shop->id)->firstOrFail();
        $this->assertSame(['263.16', '13.16', '250.00'], [$order->subtotal, $order->discount, $order->total]);
        $this->assertSame([0, 20], $this->balance($customer));

        $order->update(['reward_discount_type' => 'amount', 'reward_discount_value' => 60]);
        $this->assertSame([0, 10], $this->balance($customer));
        $order->update(['reward_discount_type' => 'gift', 'reward_discount_value' => null]);
        $this->assertSame([0, 20], $this->balance($customer));
        $this->assertSame('250.00', $order->fresh()->total);
    }

    public function test_admin_can_adjust_points_with_a_required_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $customer = $this->customer();
        $this->actingAs($admin);
        $url = route('customer-points.adjust', $customer);

        $this->post($url, ['points' => 50])->assertSessionHasErrors('note');
        $this->post($url, ['points' => 0, 'note' => 'test'])->assertSessionHasErrors('points');
        $this->post($url, ['points' => 50, 'note' => 'Welcome gift'])->assertRedirect();
        $this->post($url, ['points' => -80, 'note' => 'Too much'])->assertSessionHasErrors('points');
        $this->post($url, ['points' => -20, 'note' => 'Correction'])->assertRedirect();

        $this->assertSame([30, 0], $this->balance($customer));
        $this->assertDatabaseHas('loyalty_point_entries', ['user_id' => $customer->id, 'type' => 'manual_adjust', 'points' => 50, 'note' => 'Welcome gift', 'created_by' => $admin->id]);
        $this->get(route('customer-points.index', ['search' => $customer->email, 'customer' => $customer->id]))->assertOk()->assertSee($customer->name)->assertSee('Welcome gift');

        $this->put(route('settings.loyalty.update'), ['loyalty_enabled' => '1', 'loyalty_amount' => 200, 'loyalty_points' => 5])->assertRedirect();
        $this->assertSame(['enabled' => true, 'amount' => 200, 'points' => 5], app(LoyaltyPoints::class)->settings());

        $this->actingAs($this->shop()->user)->get(route('customer-points.index'))->assertForbidden();
        $this->actingAs($customer)->post($url, ['points' => 50, 'note' => 'self'])->assertRedirect(route('login'));
        $this->assertSame([30, 0], $this->balance($customer));
    }
}
