<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, FrontOrder, Product, PushDevice, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);
        return ['Authorization' => 'Bearer '.$plain];
    }

    private function restaurant(bool $open = true): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Bankai', 'slug' => 'bankai-'.uniqid(), 'catalog_type' => 'restaurant', 'is_active' => true, 'is_accepting_orders' => $open]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Sushi', 'slug' => 'sushi-'.uniqid(), 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Roll', 'slug' => 'roll-'.uniqid(), 'price' => 40, 'discount_price' => 35, 'is_active' => true]);
        return [$shop, $product];
    }

    public function test_app_places_a_restaurant_order_with_the_website_checkout_and_lists_it(): void
    {
        [$shop, $product] = $this->restaurant();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Sama', 'phone' => '0599123456']);
        PushDevice::create(['token' => 'device-abc', 'platform' => 'android', 'user_id' => $customer->id]);
        $headers = $this->auth($customer);

        $this->postJson('/api/v1/shops/'.$shop->id.'/restaurant-orders', ['order_type' => 'pickup', 'items' => [['product_id' => $product->id, 'qty' => 2]]])->assertUnauthorized();

        $orderId = $this->postJson('/api/v1/shops/'.$shop->id.'/restaurant-orders', [
            'order_type' => 'pickup', 'device_token' => 'device-abc',
            'items' => [['product_id' => $product->id, 'qty' => 2, 'price' => 1]],
        ], $headers)->assertOk()->assertJsonPath('ok', true)->json('order_id');

        $order = FrontOrder::findOrFail($orderId);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame('Sama', $order->customer_name);
        $this->assertSame('device-abc', $order->customer_push_token);
        $this->assertEquals(70, $order->total); // Server prices, never the app's.

        $this->getJson('/api/v1/orders', $headers)->assertOk()->assertJsonPath('data.0.id', $orderId)->assertJsonPath('data.0.items_count', 2);
        $this->getJson('/api/v1/orders/'.$orderId, $headers)->assertOk()->assertJsonPath('order.items.0.qty', 2)->assertJsonPath('order.status', 'new');

        $stranger = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->getJson('/api/v1/orders/'.$orderId, $this->auth($stranger))->assertNotFound();
    }

    public function test_closed_restaurants_and_missing_delivery_address_are_refused(): void
    {
        [$closed, $product] = $this->restaurant(false);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'phone' => '0599123456']);
        $headers = $this->auth($customer);
        $this->postJson('/api/v1/shops/'.$closed->id.'/restaurant-orders', ['order_type' => 'pickup', 'items' => [['product_id' => $product->id, 'qty' => 1]]], $headers)->assertStatus(409);

        [$open, $roll] = $this->restaurant();
        $this->postJson('/api/v1/shops/'.$open->id.'/restaurant-orders', ['order_type' => 'delivery', 'items' => [['product_id' => $roll->id, 'qty' => 1]]], $headers)->assertStatus(422);
        $this->assertSame(0, FrontOrder::count());
    }

    public function test_device_tokens_are_saved_for_the_signed_in_user(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->postJson('/api/v1/devices', ['token' => 'fcm-1', 'platform' => 'android'], $this->auth($user))->assertOk();
        $this->assertDatabaseHas('push_devices', ['token' => 'fcm-1', 'user_id' => $user->id]);
    }
}
