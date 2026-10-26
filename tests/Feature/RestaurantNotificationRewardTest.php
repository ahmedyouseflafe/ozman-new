<?php

namespace Tests\Feature;

use App\Models\{Shop, User, Product, RewardWheel, ShopSignupReward, FrontOrder, VisitorRegistration};
use App\Services\ShopSignupRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantNotificationRewardTest extends TestCase
{
    use RefreshDatabase;

    private function setupShop(): array
    {
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id,
            'name' => 'Bankai', 'slug' => 'bankai-sushi', 'catalog_type' => 'restaurant', 'is_active' => true, 'is_accepting_orders' => true]);
        $category = \App\Models\Category::create(['shop_id' => $shop->id, 'name' => 'Meals', 'slug' => 'meals', 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Sushi', 'slug' => 'sushi', 'price' => 100, 'is_active' => true]);
        $wheel = RewardWheel::create(['shop_id' => $shop->id, 'key' => 'shop_signup_'.$shop->id, 'title' => 'Gift', 'is_active' => true]);
        foreach (['A', 'B'] as $label) $wheel->segments()->create(['label' => $label, 'color' => '#00cfe8', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => true, 'win_quota' => 1]);
        return [$shop, $product];
    }

    private function subscribe(Shop $shop, string $endpoint = 'one'): void
    {
        $this->postJson(route('offers.push.store'), ['source_shop_id' => $shop->id,
            'subscription' => ['endpoint' => 'https://push.example.test/'.$endpoint, 'keys' => ['p256dh' => 'public', 'auth' => 'secret']]])->assertOk();
    }

    private function spin(Shop $shop, string $token = 'a'): ?array
    {
        return $this->postJson(route('restaurant.signup-reward', $shop), ['registration_token' => str_repeat($token, 64), 'spin' => true])->assertOk()->json('reward');
    }

    private function order(Product $product): array
    {
        return ['order_type' => 'pickup', 'customer_name' => 'Customer', 'customer_phone' => '0591234567',
            'items' => [['product_id' => $product->id, 'qty' => 1]], 'registration_token' => str_repeat('a', 64)];
    }

    public function test_wheel_requires_saved_subscription_and_does_not_create_customer_before_order(): void
    {
        [$shop, $product] = $this->setupShop();
        $url = route('restaurant.signup-reward', $shop);
        $this->postJson($url, ['registration_token' => str_repeat('a', 64), 'spin' => true, 'notifications_enabled' => true])->assertForbidden();
        $this->subscribe($shop);
        $first = $this->spin($shop);
        $again = $this->spin($shop, 'b');
        $this->assertSame($first, $again);
        $this->assertDatabaseCount('visitor_registrations', 0);
        $this->assertDatabaseCount('shop_signup_rewards', 1);
        $this->deleteJson(route('offers.push.destroy'), ['endpoint' => 'https://push.example.test/one'])->assertOk();
        $this->postJson($url, ['registration_token' => str_repeat('a', 64), 'spin' => true])->assertForbidden();
    }

    public function test_successful_order_registers_customer_and_redeems_gift_once(): void
    {
        [$shop, $product] = $this->setupShop();$this->subscribe($shop);$this->spin($shop);
        $url = route('restaurant.orders.store', $shop);
        $this->postJson($url, $this->order($product))->assertOk();
        $this->assertSame('90.00', FrontOrder::sole()->total);
        $this->assertDatabaseHas('visitor_registrations', ['shop_id' => $shop->id, 'type' => 'customer', 'name' => 'Customer', 'phone' => '0591234567']);
        $this->assertNull($this->spin($shop));
        $this->postJson($url, [...$this->order($product), 'customer_name' => 'Changed'])->assertOk();
        $this->assertSame('100.00', FrontOrder::latest('id')->first()->total);
        $this->assertDatabaseCount('visitor_registrations', 1);
        $this->assertSame('Customer', VisitorRegistration::sole()->name);
        $this->actingAs($shop->user)->get(route('restaurant.customers.index', $shop))->assertOk()->assertSee('Customer');
    }

    public function test_customer_can_order_without_notifications_and_invalid_orders_do_not_register(): void
    {
        [$shop, $product] = $this->setupShop();$url = route('restaurant.orders.store', $shop);
        $this->postJson($url, [...$this->order($product), 'customer_name' => ''])->assertUnprocessable();
        $this->assertDatabaseCount('visitor_registrations', 0);
        $shop->update(['is_accepting_orders' => false]);
        $this->postJson($url, $this->order($product))->assertStatus(409);
        $this->assertDatabaseCount('visitor_registrations', 0);
        $shop->update(['is_accepting_orders' => true]);
        $this->postJson($url, $this->order($product))->assertOk();
        $this->assertSame('100.00', FrontOrder::sole()->total);
        $this->assertDatabaseCount('visitor_registrations', 1);
        $this->assertDatabaseCount('shop_signup_rewards', 0);
    }

    public function test_another_subscription_cannot_redeem_second_gift_with_same_phone(): void
    {
        [$shop, $product] = $this->setupShop();$url = route('restaurant.orders.store', $shop);
        $this->subscribe($shop);$this->spin($shop);$this->postJson($url, $this->order($product))->assertOk();
        $this->subscribe($shop, 'second');$this->spin($shop, 'b');
        $payload = [...$this->order($product), 'customer_phone' => '00970591234567'];
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('signup_reward_used');
        $this->assertDatabaseCount('front_orders', 1);
        $this->postJson($url, [...$payload, 'skip_signup_reward' => true])->assertOk();
        $this->assertSame('100.00', FrontOrder::latest('id')->first()->total);
        $this->assertDatabaseCount('visitor_registrations', 1);
    }

    public function test_legacy_reward_is_preserved_and_reused_after_enabling_notifications(): void
    {
        [$shop, $product] = $this->setupShop();
        $this->postJson(route('restaurant.customer-registration.store', $shop), ['registration_token' => str_repeat('a', 64),
            'name' => 'Old customer', 'whatsapp' => '0591234567', 'address' => 'Old address', 'location_deferred' => true])->assertOk();
        app(ShopSignupRewardService::class)->issue($shop, VisitorRegistration::sole());
        $this->subscribe($shop);$this->spin($shop);
        $this->assertDatabaseCount('shop_signup_rewards', 1);
        $this->postJson(route('restaurant.orders.store', $shop), $this->order($product))->assertOk();
        $this->assertNotNull(ShopSignupReward::sole()->redeemed_at);
    }
}
