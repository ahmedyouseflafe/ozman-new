<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, FrontOrder, Product, PushDevice, RestaurantDriver, SalonAppointment, Shop, ShopCoupon, ShopReview, User};
use App\Services\{FirebaseMessagingService, WebPushService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

/** Reviews, coupons, shop insights, the driver on the map and salon reminders, as the app uses them. */
class MobileLaunchFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private $pushes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pushes = collect();
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturnUsing(function ($tokens, $title, $body) {
            $this->pushes->push(['tokens' => collect($tokens)->all(), 'title' => $title, 'body' => $body]);

            return 1;
        });
        $this->app->instance(FirebaseMessagingService::class, $firebase);
        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldIgnoreMissing();
        $this->app->instance(WebPushService::class, $webPush);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    /** @return array{0: User, 1: Shop, 2: Product} */
    private function shop(string $type = 'clothing'): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Shop', 'slug' => 's-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'فستان', 'slug' => 'p-'.uniqid(), 'price' => 100, 'is_active' => true]);

        return [$owner, $shop, $product];
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'ليلى أحمد', 'phone' => '0599123456']);
    }

    public function test_a_customer_reviews_a_finished_order_and_the_owner_replies(): void
    {
        [$owner, $shop] = $this->shop();
        PushDevice::create(['token' => 'owner-token', 'user_id' => $owner->id, 'platform' => 'android']);
        $customer = $this->customer();
        $h = $this->auth($customer);
        $order = FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $customer->id, 'order_number' => 'A-1', 'customer_name' => 'Lina',
            'customer_phone' => '0599123456', 'items' => [], 'subtotal' => 50, 'total' => 50, 'status' => 'preparing']);

        $this->postJson("/api/v1/orders/{$order->id}/review", ['rating' => 5], $h)->assertStatus(422);
        $order->update(['status' => 'completed']);
        $this->getJson("/api/v1/orders/{$order->id}", $h)->assertJsonPath('order.can_review', true);
        $this->postJson("/api/v1/orders/{$order->id}/review", ['rating' => 6], $h)->assertStatus(422);
        $this->postJson("/api/v1/orders/{$order->id}/review", ['rating' => 4, 'comment' => 'خدمة ممتازة'], $h)->assertCreated()
            ->assertJsonPath('review.name', 'ليلى أ.');
        $this->postJson("/api/v1/orders/{$order->id}/review", ['rating' => 1], $h)->assertStatus(422);
        $this->assertSame(['owner-token'], $this->pushes->last()['tokens']);

        $stranger = $this->customer();
        $other = FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $customer->id, 'order_number' => 'A-2', 'customer_name' => 'Lina',
            'customer_phone' => '0599123456', 'items' => [], 'subtotal' => 50, 'total' => 50, 'status' => 'completed']);
        $this->postJson("/api/v1/orders/{$other->id}/review", ['rating' => 1], $this->auth($stranger))->assertNotFound();
        $this->postJson("/api/v1/orders/{$other->id}/review", ['rating' => 2], $h)->assertCreated();

        $this->getJson("/api/v1/shops/{$shop->id}/reviews")->assertOk()->assertJsonPath('summary.average', 3)
            ->assertJsonPath('summary.count', 2)->assertJsonPath('summary.stars.4', 1)->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/shops/{$shop->id}")->assertJsonPath('shop.rating.count', 2);
        $this->getJson("/api/v1/orders/{$order->id}", $h)->assertJsonPath('order.can_review', false)->assertJsonPath('order.review.rating', 4);

        $oh = $this->auth($owner);
        $review = ShopReview::where('front_order_id', $order->id)->sole();
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/reviews", $oh)->assertOk()->assertJsonPath('data.1.order_number', 'A-1')
            ->assertJsonPath('data.1.name', 'ليلى أحمد');
        $this->postJson("/api/v1/merchant/reviews/{$review->id}/reply", ['reply' => 'شكراً إلك!'], $oh)->assertOk()->assertJsonPath('review.reply', 'شكراً إلك!');
        [$otherOwner] = $this->shop();
        $this->postJson("/api/v1/merchant/reviews/{$review->id}/reply", ['reply' => 'x'], $this->auth($otherOwner))->assertForbidden();
        $this->getJson("/api/v1/shops/{$shop->id}/reviews")->assertJsonPath('data.1.reply', 'شكراً إلك!');
    }

    public function test_the_owner_makes_coupons_and_the_customer_uses_one_on_a_shop_order(): void
    {
        [$owner, $shop, $product] = $this->shop();
        $oh = $this->auth($owner);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/coupons", ['code' => 'bad!code', 'type' => 'percent', 'value' => 10], $oh)->assertStatus(422);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/coupons", ['code' => 'summer', 'type' => 'percent', 'value' => 150], $oh)->assertStatus(422);
        $coupon = $this->postJson("/api/v1/merchant/shops/{$shop->id}/coupons", ['code' => ' summer20 ', 'type' => 'percent', 'value' => 20,
            'min_total' => 150, 'max_uses' => 2, 'per_customer' => 1], $oh)->assertCreated()->assertJsonPath('coupon.code', 'SUMMER20')
            ->assertJsonPath('coupon.state', 'active')->json('coupon');
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/coupons", ['code' => 'SUMMER20', 'type' => 'amount', 'value' => 5], $oh)->assertStatus(422);

        $customer = $this->customer();
        $h = $this->auth($customer);
        $this->postJson("/api/v1/shops/{$shop->id}/coupons/check", ['code' => 'nope', 'amount' => 200], $h)->assertStatus(422)->assertJsonValidationErrors('coupon_code');
        $this->postJson("/api/v1/shops/{$shop->id}/coupons/check", ['code' => 'summer20', 'amount' => 100], $h)->assertStatus(422);
        $this->postJson("/api/v1/shops/{$shop->id}/coupons/check", ['code' => 'summer20', 'amount' => 200], $h)->assertOk()
            ->assertJsonPath('discount', 40)->assertJsonPath('label', 'خصم 20%');

        $order = ['order_type' => 'pickup', 'items' => [['product_id' => $product->id, 'qty' => 2]], 'coupon_code' => 'SUMMER20'];
        $placed = $this->postJson("/api/v1/shops/{$shop->id}/orders", $order, $h)->assertCreated()->assertJsonPath('total', 160)->json();
        $this->getJson("/api/v1/orders/{$placed['order_id']}", $h)->assertJsonPath('order.coupon_code', 'SUMMER20')
            ->assertJsonPath('order.discount_label', 'كود SUMMER20')->assertJsonPath('order.subtotal', 200);
        // Once per customer.
        $this->postJson("/api/v1/shops/{$shop->id}/orders", $order, $h)->assertStatus(422)->assertJsonValidationErrors('coupon_code');
        // Under the minimum, nothing is placed.
        $this->postJson("/api/v1/shops/{$shop->id}/orders", [...$order, 'items' => [['product_id' => $product->id, 'qty' => 1]]], $this->auth($this->customer()))
            ->assertStatus(422);
        $this->postJson("/api/v1/shops/{$shop->id}/orders", $order, $this->auth($this->customer()))->assertCreated();
        // Two uses allowed in all.
        $this->postJson("/api/v1/shops/{$shop->id}/orders", $order, $this->auth($this->customer()))->assertStatus(422);
        $this->assertSame(2, ShopCoupon::find($coupon['id'])->used_count);
        $this->assertSame(2, FrontOrder::count());

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/coupons", $oh)->assertJsonPath('data.0.state', 'used_up');
        $this->postJson("/api/v1/merchant/coupons/{$coupon['id']}", ['code' => 'SUMMER20', 'type' => 'amount', 'value' => 30, 'max_uses' => 10,
            'ends_at' => now('Asia/Hebron')->subDay()->toDateString()], $oh)->assertOk()->assertJsonPath('coupon.state', 'expired');
        $this->postJson("/api/v1/shops/{$shop->id}/coupons/check", ['code' => 'SUMMER20', 'amount' => 500], $this->auth($this->customer()))->assertStatus(422);
        [$otherOwner] = $this->shop();
        $this->deleteJson("/api/v1/merchant/coupons/{$coupon['id']}", [], $this->auth($otherOwner))->assertForbidden();
        $this->deleteJson("/api/v1/merchant/coupons/{$coupon['id']}", [], $oh)->assertOk();
    }

    public function test_the_owner_sees_the_shops_numbers(): void
    {
        [$owner, $shop] = $this->shop();
        $base = ['shop_id' => $shop->id, 'customer_name' => 'Lina', 'customer_phone' => '0599123456', 'subtotal' => 80, 'total' => 80];
        $done = FrontOrder::create($base + ['order_number' => 'I-1', 'status' => 'completed', 'items' => [['product_id' => 1, 'name' => 'فستان', 'qty' => 2, 'price' => 40]]]);
        $done->forceFill(['sale_confirmed_at' => now()])->save();
        FrontOrder::create($base + ['order_number' => 'I-2', 'status' => 'new', 'items' => []]);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/insights?period=today", $this->auth($owner))->assertOk()
            ->assertJsonPath('summary.revenue', 80)->assertJsonPath('summary.orders', 2)->assertJsonPath('top_products.0.name', 'فستان')
            ->assertJsonPath('top_products.0.units', 2)->assertJsonCount(24, 'hours')->assertJsonCount(1, 'daily');
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/insights?period=month", $this->auth($owner))->assertOk()->assertJsonPath('period', 'month')
            ->assertJsonPath('daily.0.day', now('Asia/Hebron')->startOfMonth()->toDateString())
            ->assertJsonPath('daily', fn ($days) => collect($days)->sum('revenue') == 80);
        [$otherOwner] = $this->shop();
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/insights", $this->auth($otherOwner))->assertForbidden();
    }

    public function test_the_customer_sees_the_driver_on_the_map_only_while_delivering(): void
    {
        [, $restaurant] = $this->shop('restaurant');
        $driverUser = User::factory()->create(['role' => 'restaurant_driver', 'is_active' => true, 'name' => 'Sami']);
        $driver = RestaurantDriver::create(['shop_id' => $restaurant->id, 'user_id' => $driverUser->id, 'is_active' => true]);
        $customer = $this->customer();
        $order = FrontOrder::create(['shop_id' => $restaurant->id, 'user_id' => $customer->id, 'restaurant_driver_id' => $driver->id, 'order_number' => 'R-1',
            'order_type' => 'delivery', 'order_channel' => 'restaurant', 'customer_name' => 'Lina', 'customer_phone' => '0599123456',
            'customer_address' => 'Ramallah', 'latitude' => 31.9, 'longitude' => 35.2, 'items' => [], 'subtotal' => 30, 'total' => 30, 'status' => 'ready']);
        $dh = $this->auth($driverUser);

        $this->postJson('/api/v1/driver/location', ['latitude' => 31.91, 'longitude' => 35.21], $dh)->assertOk()->assertJsonPath('shared', false);
        $this->assertNull($driver->fresh()->latitude);
        $order->update(['status' => 'out_for_delivery']);
        $this->postJson('/api/v1/driver/location', ['latitude' => 31.91, 'longitude' => 35.21], $dh)->assertOk()->assertJsonPath('shared', true);
        $this->getJson("/api/v1/orders/{$order->id}", $this->auth($customer))->assertJsonPath('order.driver.location.latitude', 31.91)
            ->assertJsonPath('order.delivery_location.latitude', 31.9);

        // An old position isn't shown.
        $driver->forceFill(['location_at' => now()->subMinutes(30)])->save();
        $this->getJson("/api/v1/orders/{$order->id}", $this->auth($customer))->assertJsonPath('order.driver.location', null);
        $this->postJson('/api/v1/driver/location', ['latitude' => 31.91, 'longitude' => 35.21], $this->auth($customer))->assertForbidden();
    }

    public function test_salon_customers_get_a_reminder_the_day_before_and_an_hour_before(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00', 'UTC')); // 12:00 in Hebron
        [, $salon] = $this->shop('cosmetics');
        $customer = $this->customer();
        PushDevice::create(['token' => 'customer-token', 'user_id' => $customer->id, 'platform' => 'android']);
        $make = fn (string $at, string $status = 'confirmed') => SalonAppointment::create(['shop_id' => $salon->id, 'user_id' => $customer->id,
            'service' => 'قص شعر', 'appointment_at' => Carbon::parse($at, 'UTC'), 'slot_key' => $at, 'customer_name' => 'Lina',
            'customer_phone' => '0599123456', 'status' => $status, 'locale' => 'ar']);
        // Times are the salon's wall clock.
        $tomorrow = $make('2026-11-19 11:00');
        $soon = $make('2026-11-18 12:40');
        $far = $make('2026-11-21 11:00');
        $cancelled = $make('2026-11-19 10:00', 'cancelled');

        $this->artisan('salon:send-reminders')->assertSuccessful();
        $this->assertNotNull($tomorrow->fresh()->reminded_day_at);
        $this->assertNotNull($soon->fresh()->reminded_hour_at);
        $this->assertNull($far->fresh()->reminded_day_at);
        $this->assertNull($cancelled->fresh()->reminded_day_at);
        $this->assertCount(2, $this->pushes);
        $this->assertSame('تذكير بموعدك بكرا ⏰', $this->pushes->first()['title']);
        $this->assertStringContainsString('الساعة 11:00', $this->pushes->first()['body']);
        $this->assertSame('موعدك بعد ساعة ⏰', $this->pushes->last()['title']);

        // Each reminder goes once.
        $this->artisan('salon:send-reminders')->assertSuccessful();
        $this->assertCount(2, $this->pushes);
        Carbon::setTestNow();
    }

    public function test_order_again_returns_what_can_still_be_bought(): void
    {
        [, $shop, $product] = $this->shop();
        $gone = Product::create(['shop_id' => $shop->id, 'category_id' => $product->category_id, 'name' => 'قديم', 'slug' => 'old-'.uniqid(), 'price' => 10, 'is_active' => false]);
        $customer = $this->customer();
        $order = FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $customer->id, 'order_number' => 'O-1', 'customer_name' => 'Lina', 'customer_phone' => '0599123456',
            'items' => [['product_id' => $product->id, 'name' => 'فستان', 'qty' => 2, 'price' => 100, 'size' => 'M'], ['product_id' => $gone->id, 'name' => 'قديم', 'qty' => 1, 'price' => 10],
                ['product_id' => $product->id, 'name' => 'فستان مع إضافات', 'qty' => 1, 'price' => 100, 'addons' => ['x']]],
            'subtotal' => 210, 'total' => 210, 'status' => 'completed']);
        $this->getJson("/api/v1/orders/{$order->id}/reorder", $this->auth($customer))->assertOk()->assertJsonCount(1, 'lines')
            ->assertJsonPath('lines.0.product.id', $product->id)->assertJsonPath('lines.0.qty', 2)->assertJsonPath('lines.0.size', 'M')
            ->assertJsonPath('skipped', ['قديم', 'فستان مع إضافات']);
        $this->getJson("/api/v1/orders/{$order->id}/reorder", $this->auth($this->customer()))->assertNotFound();
    }
}
