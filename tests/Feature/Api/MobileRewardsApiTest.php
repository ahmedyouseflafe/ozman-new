<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, FrontOrder, Product, RewardWheel, Shop, User};
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MobileRewardsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);
        return ['Authorization' => 'Bearer '.$plain];
    }

    private function shopWithProduct(): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Style', 'slug' => 'style-'.uniqid(), 'catalog_type' => 'clothing', 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Shirt', 'slug' => 'shirt-'.uniqid(), 'price' => 150, 'is_active' => true]);
        return [$shop, $product, $owner];
    }

    public function test_a_signed_in_customer_can_like_a_product_and_see_it(): void
    {
        [, $product] = $this->shopWithProduct();
        $headers = $this->auth(User::factory()->create(['role' => 'customer', 'is_active' => true]));

        $this->postJson("/api/v1/products/{$product->id}/like")->assertUnauthorized();
        $this->postJson("/api/v1/products/{$product->id}/like", [], $headers)->assertOk()->assertJsonPath('liked', true)->assertJsonPath('count', 1);
        $this->getJson("/api/v1/products/{$product->id}", $headers)->assertJsonPath('product.liked', true)->assertJsonPath('product.likes_count', 1);
        $this->postJson("/api/v1/products/{$product->id}/like", [], $headers)->assertJsonPath('liked', false)->assertJsonPath('count', 0);
    }

    public function test_app_orders_earn_points_and_can_spin_the_shop_wheel_once(): void
    {
        [$shop, $product, $owner] = $this->shopWithProduct();
        $wheel = RewardWheel::create(['shop_id' => $shop->id, 'key' => 'purchase-'.$shop->id, 'wheel_type' => RewardWheel::TYPE_PURCHASE_AMOUNT, 'title' => 'عجلة', 'min_order_total' => 100, 'is_active' => true]);
        $wheel->segments()->create(['label' => 'خصم 10%', 'discount_type' => 'percent', 'discount_value' => 10, 'win_quota' => 1, 'sort_order' => 1, 'is_active' => true]);
        $wheel->segments()->create(['label' => 'هدية', 'discount_type' => 'gift', 'discount_value' => 0, 'win_quota' => 1, 'sort_order' => 2, 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'phone' => '0599123456']);
        $headers = $this->auth($customer);

        $orderId = $this->postJson("/api/v1/shops/{$shop->id}/orders", ['order_type' => 'pickup', 'items' => [['product_id' => $product->id, 'qty' => 1]]], $headers)
            ->assertCreated()->json('order_id');

        $this->getJson('/api/v1/me/points', $headers)->assertOk()->assertJsonPath('enabled', true)->assertJsonPath('pending', 10);
        $this->getJson("/api/v1/orders/{$orderId}", $headers)->assertJsonPath('order.wheel_available', true);
        $this->getJson("/api/v1/orders/{$orderId}/wheel", $headers)->assertOk()->assertJsonCount(2, 'wheel.segments');

        $prize = $this->postJson("/api/v1/orders/{$orderId}/wheel/spin", [], $headers)->assertOk()->json('reward.label');
        $this->assertContains($prize, ['خصم 10%', 'هدية']);
        $this->postJson("/api/v1/orders/{$orderId}/wheel/spin", [], $headers)->assertJsonPath('reward.label', $prize);
        $this->getJson("/api/v1/orders/{$orderId}", $headers)->assertJsonPath('order.wheel_available', false)->assertJsonPath('order.reward.label', $prize);

        $this->actingAs($owner)->patchJson(route('shop-orders.status', $orderId), ['status' => 'delivered'])->assertOk();
        $this->getJson('/api/v1/me/points', $headers)->assertJsonPath('balance', 10)->assertJsonPath('shops.0.shop_name', 'Style');

        $stranger = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->postJson("/api/v1/orders/{$orderId}/wheel/spin", [], $this->auth($stranger))->assertNotFound();
    }
}
