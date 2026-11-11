<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, FrontOrder, Shop, User};
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MerchantApiTest extends TestCase
{
    use RefreshDatabase;

    private array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturnUsing(function ($tokens, $title, $body, $url, $data = []) {
            $this->pushes[] = ['tokens' => collect($tokens)->values()->all(), 'data' => $data];
            return 1;
        });
        $this->app->instance(FirebaseMessagingService::class, $firebase);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);
        return ['Authorization' => 'Bearer '.$plain];
    }

    private function shop(string $type): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Shop '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        return [$shop, $owner];
    }

    private function order(Shop $shop, array $extra = []): FrontOrder
    {
        return FrontOrder::create($extra + [
            'shop_id' => $shop->id, 'order_number' => 'X-'.strtoupper(substr(uniqid(), -6)), 'customer_name' => 'Lina',
            'customer_phone' => '0599123456', 'items' => [['name' => 'Item', 'qty' => 2, 'price' => 20]],
            'subtotal' => 40, 'discount' => 0, 'total' => 40, 'order_channel' => 'app', 'status' => 'new',
            'payment_status' => 'pending', 'customer_push_token' => 'customer-phone',
        ]);
    }

    public function test_an_owner_sees_their_shops_and_live_orders(): void
    {
        [$shop, $owner] = $this->shop('clothing');
        [$other] = $this->shop('sweets');
        $order = $this->order($shop);
        $headers = $this->auth($owner);

        $this->getJson('/api/v1/auth/me', $headers)->assertJsonPath('user.is_merchant', true);
        $this->getJson('/api/v1/merchant/shops', $headers)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.new_orders', 1);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/orders", $headers)->assertOk()
            ->assertJsonPath('stats.new', 1)->assertJsonPath('data.0.id', $order->id)->assertJsonPath('data.0.items_count', 2);
        $this->getJson("/api/v1/merchant/orders/{$order->id}", $headers)->assertOk()
            ->assertJsonPath('order.customer_phone', '0599123456')->assertJsonPath('order.statuses.0.key', 'new');

        $this->getJson("/api/v1/merchant/shops/{$other->id}/orders", $headers)->assertForbidden();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->getJson('/api/v1/auth/me', $this->auth($customer))->assertJsonPath('user.is_merchant', false);
        $this->getJson("/api/v1/merchant/orders/{$order->id}", $this->auth($customer))->assertForbidden();
    }

    public function test_status_changes_use_the_dashboards_and_reach_the_customer(): void
    {
        [$shop, $owner] = $this->shop('clothing');
        $order = $this->order($shop);
        $this->patchJson("/api/v1/merchant/orders/{$order->id}/status", ['status' => 'ready'], $this->auth($owner))
            ->assertOk()->assertJsonPath('order.status', 'ready');
        $this->assertSame(['customer-phone'], $this->pushes[0]['tokens']);

        [$restaurant, $chef] = $this->shop('restaurant');
        $meal = $this->order($restaurant, ['order_type' => 'pickup', 'order_number' => 'RST-1']);
        $headers = $this->auth($chef);
        $this->patchJson("/api/v1/merchant/orders/{$meal->id}/status", ['status' => 'preparing'], $headers)->assertStatus(422);
        $this->patchJson("/api/v1/merchant/orders/{$meal->id}/status", ['status' => 'preparing', 'estimated_preparation_minutes' => 15], $headers)
            ->assertOk()->assertJsonPath('order.status', 'preparing')->assertJsonPath('order.estimated_preparation_minutes', 15);
        $this->assertNotNull($meal->fresh()->preparation_started_at);

        $this->patchJson("/api/v1/merchant/shops/{$restaurant->id}/availability", ['is_accepting_orders' => false], $headers)
            ->assertOk()->assertJsonPath('shop.is_accepting_orders', false);
    }
}
