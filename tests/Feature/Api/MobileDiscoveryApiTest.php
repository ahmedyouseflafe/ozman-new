<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, FrontOrder, Product, RewardWheel, Shop, ShopSignupReward, User};
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/** Search, favourites and the restaurant welcome wheel, as the app uses them. */
class MobileDiscoveryApiTest extends TestCase
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

    private function shop(string $type, string $name): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => $name, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);

        return [$shop, $category];
    }

    private function product(Shop $shop, Category $category, string $name, float $price, bool $active = true): Product
    {
        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => $name, 'slug' => 's-'.uniqid(), 'price' => $price, 'is_active' => $active]);
    }

    public function test_search_finds_shops_and_products_but_not_hidden_ones(): void
    {
        [$sweets, $category] = $this->shop('sweets', 'Kunafa House');
        $this->product($sweets, $category, 'Nabulsi kunafa', 70);
        $this->product($sweets, $category, 'Hidden kunafa', 50, false);
        [$closed, $closedCategory] = $this->shop('sweets', 'Old kunafa place');
        $closed->update(['is_active' => false]);
        $this->product($closed, $closedCategory, 'Kunafa from a closed shop', 40);

        $json = $this->getJson('/api/v1/search?q=kunafa')->assertOk()->json();
        $this->assertSame(['Kunafa House'], array_column($json['shops'], 'name'));
        $this->assertSame(['Nabulsi kunafa'], array_column($json['products'], 'name'));
        $this->assertSame('Kunafa House', $json['products'][0]['shop_name']);
        Product::create(['shop_id' => $sweets->id, 'category_id' => $category->id, 'name' => 'سلمون رول', 'name_translations' => ['en' => 'Salmon roll'], 'slug' => 'r-'.uniqid(), 'price' => 39, 'is_active' => true]);
        $this->getJson('/api/v1/search?q=roll')->assertOk()->assertJsonCount(1, 'products');
        $this->getJson('/api/v1/search?q=k')->assertStatus(422);
    }

    public function test_liked_products_become_the_favourites_list(): void
    {
        [$shop, $category] = $this->shop('clothing', 'Style');
        $shirt = $this->product($shop, $category, 'Shirt', 99);
        $jacket = $this->product($shop, $category, 'Jacket', 199);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $headers = $this->auth($customer);

        $this->postJson("/api/v1/products/{$shirt->id}/like", [], $headers)->assertOk()->assertJsonPath('liked', true);
        $this->postJson("/api/v1/products/{$jacket->id}/like", [], $headers)->assertOk();
        $this->postJson("/api/v1/products/{$shirt->id}/like", [], $headers)->assertOk()->assertJsonPath('liked', false);

        $this->getJson('/api/v1/me/likes', $headers)->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Jacket')->assertJsonPath('data.0.liked', true);
        $this->getJson('/api/v1/me/likes')->assertUnauthorized();
    }

    public function test_the_welcome_wheel_is_won_in_the_app_and_taken_off_the_first_order_only(): void
    {
        [$shop, $category] = $this->shop('restaurant', 'Burger Spot');
        $burger = $this->product($shop, $category, 'Burger', 50);
        $wheel = RewardWheel::create(['shop_id' => $shop->id, 'key' => 'shop_signup_'.$shop->id, 'wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP, 'title' => 'Welcome gift', 'is_active' => true]);
        foreach (['10% off', '10% too'] as $label) {
            $wheel->segments()->create(['label' => $label, 'discount_type' => 'percent', 'discount_value' => 10, 'color' => '#00cfe8', 'is_active' => true, 'win_quota' => 1]);
        }
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Lina', 'phone' => '0599123456']);
        $headers = $this->auth($customer);

        $this->getJson("/api/v1/shops/{$shop->id}/signup-reward", $headers)->assertOk()
            ->assertJsonPath('available', true)->assertJsonPath('registered', false)->assertJsonCount(2, 'wheel.segments');
        $this->postJson("/api/v1/shops/{$shop->id}/signup-reward/spin", [], $headers)->assertStatus(422);

        $this->postJson("/api/v1/shops/{$shop->id}/signup-reward/register", ['name' => 'Lina', 'phone' => '0599123456', 'address' => 'Ramallah'], $headers)
            ->assertOk()->assertJsonPath('registered', true)->assertJsonPath('used', false)->assertJsonPath('reward', null);
        $spin = $this->postJson("/api/v1/shops/{$shop->id}/signup-reward/spin", [], $headers)->assertOk()->json('reward');
        $this->assertContains($spin['segment_index'], [0, 1]);
        // Spinning again keeps the same prize.
        $this->postJson("/api/v1/shops/{$shop->id}/signup-reward/spin", [], $headers)->assertJsonPath('reward.segment_index', $spin['segment_index']);

        $order = fn () => $this->postJson("/api/v1/shops/{$shop->id}/restaurant-orders", [
            'order_type' => 'pickup', 'skip_signup_reward' => true, 'items' => [['product_id' => $burger->id, 'qty' => 2]],
        ], $headers)->assertSuccessful()->json('order_id');

        $first = FrontOrder::findOrFail($order());
        $this->assertEqualsWithDelta(10.0, (float) $first->discount, 0.01);
        $this->assertEqualsWithDelta(90.0, (float) $first->total, 0.01);
        $this->getJson("/api/v1/orders/{$first->id}", $headers)->assertJsonPath('order.discount_label', $spin['label']);
        $this->assertNotNull(ShopSignupReward::firstOrFail()->redeemed_at);

        $second = FrontOrder::findOrFail($order());
        $this->assertEqualsWithDelta(0.0, (float) $second->discount, 0.01);
        $this->getJson("/api/v1/shops/{$shop->id}/signup-reward", $headers)->assertJsonPath('used', true)->assertJsonPath('reward', null);
    }

    public function test_one_prize_per_phone_even_across_accounts(): void
    {
        [$shop] = $this->shop('restaurant', 'Burger Spot');
        $wheel = RewardWheel::create(['shop_id' => $shop->id, 'key' => 'shop_signup_'.$shop->id, 'wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP, 'title' => 'Gift', 'is_active' => true]);
        foreach (['A', 'B'] as $label) {
            $wheel->segments()->create(['label' => $label, 'discount_type' => 'amount', 'discount_value' => 5, 'color' => '#00cfe8', 'is_active' => true, 'win_quota' => 1]);
        }
        $details = ['name' => 'Lina', 'phone' => '0599123456', 'address' => 'Ramallah'];
        foreach ([true, false] as $first) {
            $headers = $this->auth(User::factory()->create(['role' => 'customer', 'is_active' => true]));
            $this->postJson("/api/v1/shops/{$shop->id}/signup-reward/register", $details, $headers)->assertOk()->assertJsonPath('used', ! $first);
        }
        $this->assertSame(1, ShopSignupReward::count());
    }

    public function test_shops_without_a_welcome_wheel_say_so(): void
    {
        [$shop] = $this->shop('restaurant', 'Plain');
        $headers = $this->auth(User::factory()->create(['role' => 'customer', 'is_active' => true]));
        $this->getJson("/api/v1/shops/{$shop->id}/signup-reward", $headers)->assertOk()->assertJsonPath('available', false);
        $this->postJson("/api/v1/shops/{$shop->id}/signup-reward/register", ['name' => 'A', 'phone' => '0599123456', 'address' => 'x'], $headers)->assertNotFound();
    }
}
