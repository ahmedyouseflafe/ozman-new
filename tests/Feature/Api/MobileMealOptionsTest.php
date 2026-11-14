<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, FrontOrder, Product, Shop, User};
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MobileMealOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);
    }

    public function test_the_app_gets_meal_choices_and_orders_them_at_server_prices(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Burger', 'slug' => 'burger-'.uniqid(), 'catalog_type' => 'restaurant', 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);
        $burger = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Burger', 'slug' => 'b-'.uniqid(), 'price' => 30, 'is_active' => true,
            'catalog_attributes' => [
                'meal_size_prices' => ['وسط:30', 'كبير:38'],
                'addon_prices' => ['جبنة:3', 'بيض:4'],
                'removable_ingredients' => ['بصل', 'مخلل'],
                'meal_choice_groups' => [['id' => 'drink', 'name' => 'مشروب', 'min' => 1, 'max' => 1, 'allow_repeat' => false, 'source' => 'manual',
                    'options' => [['id' => 'cola', 'name' => 'كولا', 'price' => 0], ['id' => 'juice', 'name' => 'عصير', 'price' => 5]]]],
            ]]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'phone' => '0599123456']);
        [, $plain] = ApiToken::issue($customer);
        $headers = ['Authorization' => 'Bearer '.$plain];

        $this->getJson("/api/v1/shops/{$shop->id}/products")->assertJsonPath('data.0.has_options', true);
        $this->getJson("/api/v1/products/{$burger->id}")->assertOk()
            ->assertJsonPath('product.meal.sizes.1', ['name' => 'كبير', 'price' => 38])
            ->assertJsonPath('product.meal.addons.0.name', 'جبنة')
            ->assertJsonPath('product.meal.removable', ['بصل', 'مخلل'])
            ->assertJsonPath('product.meal.groups.0.options.1.price', 5);

        $orderId = $this->postJson("/api/v1/shops/{$shop->id}/restaurant-orders", ['order_type' => 'pickup', 'skip_signup_reward' => true, 'items' => [[
            'product_id' => $burger->id, 'qty' => 2, 'size' => 'كبير', 'addons' => ['جبنة'], 'excluded' => ['بصل'], 'notes' => 'مستوي منيح',
            'choices' => [['group_id' => 'drink', 'options' => [['option_id' => 'juice', 'qty' => 1]]]],
        ]]], $headers)->assertOk()->json('order_id');

        $order = FrontOrder::findOrFail($orderId);
        $this->assertEquals((38 + 3 + 5) * 2, $order->total);
        $this->assertSame(['بصل'], $order->items[0]['excluded']);
        $this->assertSame('عصير', $order->items[0]['choices'][0]['options'][0]['name']);

        // A missing required choice is refused, like on the website.
        $this->postJson("/api/v1/shops/{$shop->id}/restaurant-orders", ['order_type' => 'pickup', 'skip_signup_reward' => true,
            'items' => [['product_id' => $burger->id, 'qty' => 1]]], $headers)->assertStatus(422);
    }
}
