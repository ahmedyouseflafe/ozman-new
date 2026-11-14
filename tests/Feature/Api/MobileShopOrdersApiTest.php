<?php

namespace Tests\Feature\Api;

use App\Models\{AdminNotification, ApiToken, Category, FrontOrder, Product, ProductVariant, PushDevice, Shop, User};
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MobileShopOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{tokens: array, title: string, data: array}> */
    private array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturnUsing(function ($tokens, $title, $body, $url, $data = []) {
            $this->pushes[] = ['tokens' => collect($tokens)->values()->all(), 'title' => $title, 'data' => $data];
            return 1;
        });
        $this->app->instance(FirebaseMessagingService::class, $firebase);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);
        return ['Authorization' => 'Bearer '.$plain];
    }

    private function shop(string $type, bool $open = true): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Style '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => $open]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);
        return [$shop, $category, $owner];
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Lina', 'phone' => '0599123456']);
    }

    public function test_a_clothing_order_uses_server_prices_checks_sizes_and_reaches_the_shop(): void
    {
        [$shop, $category, $owner] = $this->shop('clothing');
        $shirt = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Shirt', 'slug' => 'shirt-'.uniqid(),
            'price' => 100, 'discount_price' => 80, 'is_active' => true, 'catalog_attributes' => ['sizes' => ['S', 'M'], 'colors' => ['أسود']]]);
        PushDevice::create(['token' => 'owner-phone', 'platform' => 'android', 'user_id' => $owner->id]);
        $customer = $this->customer();
        PushDevice::create(['token' => 'customer-phone', 'platform' => 'android', 'user_id' => $customer->id]);
        $headers = $this->auth($customer);
        $this->getJson("/api/v1/shops/{$shop->id}/products")->assertOk()->assertJsonPath('data.0.has_options', true);
        $this->getJson("/api/v1/products/{$shirt->id}")->assertOk()->assertJsonPath('product.sizes', ['S', 'M']);

        $this->postJson("/api/v1/shops/{$shop->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $shirt->id, 'qty' => 1, 'size' => 'XXL']]], $headers)
            ->assertStatus(422);

        $orderId = $this->postJson("/api/v1/shops/{$shop->id}/orders", [
            'order_type' => 'delivery', 'customer_address' => 'رام الله، الماصيون', 'device_token' => 'customer-phone',
            'items' => [['product_id' => $shirt->id, 'qty' => 2, 'size' => 'M', 'color' => 'أسود', 'price' => 1]],
        ], $headers)->assertCreated()->assertJsonPath('total', 160)->json('order_id');

        $order = FrontOrder::findOrFail($orderId);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('app', $order->order_channel);
        $this->assertNull($order->order_type); // Managed from the shop's normal orders page.
        $this->assertSame('customer-phone', $order->customer_push_token);
        $this->assertSame('Shirt · M · أسود', $order->items[0]['name']);
        $this->assertSame('رام الله، الماصيون', $order->customer_address);
        $this->assertStringContainsString('توصيل', $order->customer_notes);
        $this->assertSame('طلب من التطبيق', $order->channelLabel());
        $this->assertTrue(AdminNotification::where('shop_id', $shop->id)->where('subject_id', $orderId)->exists());
        $this->assertSame(['owner-phone'], $this->pushes[0]['tokens']);

        $this->getJson('/api/v1/orders/'.$orderId, $headers)->assertOk()->assertJsonPath('order.items.0.qty', 2);
    }

    public function test_the_customer_hears_when_the_shop_changes_the_status(): void
    {
        [$shop, $category, $owner] = $this->shop('sweets');
        $cake = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Cake', 'slug' => 'cake-'.uniqid(), 'price' => 50, 'is_active' => true]);
        $customer = $this->customer();
        PushDevice::create(['token' => 'customer-phone', 'platform' => 'android', 'user_id' => $customer->id]);
        $orderId = $this->postJson("/api/v1/shops/{$shop->id}/orders", [
            'order_type' => 'pickup', 'device_token' => 'customer-phone', 'items' => [['product_id' => $cake->id, 'qty' => 1]],
        ], $this->auth($customer))->assertCreated()->json('order_id');
        $this->pushes = [];

        $this->actingAs($owner)->patch(route('front-orders.status', $orderId), ['status' => 'ready'])->assertRedirect();

        $this->assertSame('ready', FrontOrder::find($orderId)->status);
        $this->assertSame(['customer-phone'], $this->pushes[0]['tokens']);
        $this->assertSame('shop_order_status', $this->pushes[0]['data']['type']);
        $this->assertSame($orderId, $this->pushes[0]['data']['order_id']);
    }

    public function test_electronics_need_an_option_and_respect_its_stock(): void
    {
        [$shop, $category] = $this->shop('electronics');
        $phone = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Phone', 'slug' => 'phone-'.uniqid(), 'price' => 2000, 'is_active' => true]);
        $variant = $phone->variants()->create(['storage' => '256GB', 'color' => 'Black', 'price' => 2300, 'quantity' => 1, 'is_active' => true]);
        $headers = $this->auth($this->customer());

        $this->postJson("/api/v1/shops/{$shop->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $phone->id, 'qty' => 1]]], $headers)->assertStatus(422);
        $this->postJson("/api/v1/shops/{$shop->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $phone->id, 'variant_id' => $variant->id, 'qty' => 2]]], $headers)->assertStatus(422);
        $this->postJson("/api/v1/shops/{$shop->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $phone->id, 'variant_id' => $variant->id, 'qty' => 1]]], $headers)
            ->assertCreated()->assertJsonPath('total', 2300);

        $this->assertSame(0, ProductVariant::find($variant->id)->quantity);
    }

    public function test_a_delivery_order_keeps_the_map_pin_for_the_shop(): void
    {
        [$shop, $category] = $this->shop('sweets');
        $cake = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Cake', 'slug' => 'cake-'.uniqid(), 'price' => 50, 'is_active' => true]);
        $headers = $this->auth($this->customer());
        $item = [['product_id' => $cake->id, 'qty' => 1]];

        $this->postJson("/api/v1/shops/{$shop->id}/orders", ['order_type' => 'delivery', 'customer_address' => 'Ramallah', 'latitude' => 31.9, 'items' => $item], $headers)
            ->assertStatus(422)->assertJsonValidationErrors('longitude');
        $orderId = $this->postJson("/api/v1/shops/{$shop->id}/orders", [
            'order_type' => 'delivery', 'customer_address' => 'Ramallah, near the clock', 'latitude' => 31.9038, 'longitude' => 35.2034, 'items' => $item,
        ], $headers)->assertCreated()->json('order_id');
        $order = FrontOrder::findOrFail($orderId);
        $this->assertEqualsWithDelta(31.9038, (float) $order->latitude, 0.0001);
        $this->assertSame('https://www.google.com/maps?q=31.9038,35.2034', $order->map_link);

        // A pickup order never keeps a location, even if the app sent one.
        $pickupId = $this->postJson("/api/v1/shops/{$shop->id}/orders", ['order_type' => 'pickup', 'latitude' => 31.9, 'longitude' => 35.2, 'items' => $item], $headers)
            ->assertCreated()->json('order_id');
        $this->assertNull(FrontOrder::findOrFail($pickupId)->map_link);
    }

    public function test_restaurants_closed_shops_and_guests_are_refused(): void
    {
        [$restaurant, $category] = $this->shop('restaurant');
        $roll = Product::create(['shop_id' => $restaurant->id, 'category_id' => $category->id, 'name' => 'Roll', 'slug' => 'roll-'.uniqid(), 'price' => 30, 'is_active' => true]);
        [$closed, $closedCategory] = $this->shop('clothing', false);
        $shirt = Product::create(['shop_id' => $closed->id, 'category_id' => $closedCategory->id, 'name' => 'Shirt', 'slug' => 'shirt-'.uniqid(), 'price' => 30, 'is_active' => true]);
        $headers = $this->auth($this->customer());

        $this->postJson("/api/v1/shops/{$closed->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $shirt->id, 'qty' => 1]]])->assertUnauthorized();
        $this->postJson("/api/v1/shops/{$restaurant->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $roll->id, 'qty' => 1]]], $headers)->assertStatus(422);
        $this->postJson("/api/v1/shops/{$closed->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $shirt->id, 'qty' => 1]]], $headers)->assertStatus(422);
        $this->postJson("/api/v1/shops/{$closed->id}/orders", ['order_type' => 'delivery', 'items' => [['product_id' => $shirt->id, 'qty' => 1]]], $headers)->assertStatus(422);
        $this->assertSame(0, FrontOrder::count());
    }
}
