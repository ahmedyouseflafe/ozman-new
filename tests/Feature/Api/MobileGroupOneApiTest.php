<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, FrontOrder, Product, ProductVariant, RestaurantDeliveryOffer, Shop, ShopStory, User};
use App\Services\{FirebaseMessagingService, WebPushService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash, Storage};
use Mockery;
use Tests\TestCase;

/** Account editing, stories, the owner's products and the driver screen, as the app uses them. */
class MobileGroupOneApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);
        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldIgnoreMissing();
        $this->app->instance(WebPushService::class, $webPush);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain];
    }

    private function shop(string $type): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);

        return [$shop, $owner, $category];
    }

    public function test_a_customer_edits_their_account_photo_and_password(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'password' => 'old-pass-123']);
        $headers = $this->auth($customer);
        [, $otherPhone] = ApiToken::issue($customer);

        $this->patchJson('/api/v1/auth/me', ['name' => 'Lina Saleh', 'phone' => '0599123456'], $headers)
            ->assertOk()->assertJsonPath('user.name', 'Lina Saleh')->assertJsonPath('user.phone', '0599123456');
        $this->patchJson('/api/v1/auth/me', ['name' => ''], $headers)->assertStatus(422);

        $this->post('/api/v1/auth/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 600, 600)], $headers + ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('user.avatar', fn ($url) => is_string($url) && $url !== '');
        $this->deleteJson('/api/v1/auth/avatar', [], $headers)->assertOk()->assertJsonPath('user.avatar', null);

        $this->postJson('/api/v1/auth/password', ['current_password' => 'wrong', 'password' => 'new-pass-456', 'password_confirmation' => 'new-pass-456'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/auth/password', ['current_password' => 'old-pass-123', 'password' => 'new-pass-456', 'password_confirmation' => 'new-pass-456'], $headers)->assertOk();
        $this->assertTrue(Hash::check('new-pass-456', $customer->fresh()->password));
        // This phone stays signed in; the other one is signed out.
        $this->getJson('/api/v1/auth/me', $headers)->assertOk();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$otherPhone])->assertUnauthorized();
    }

    public function test_owners_publish_stories_customers_watch_them_and_views_are_counted(): void
    {
        [$shop, $owner] = $this->shop('sweets');
        [$otherShop] = $this->shop('clothing');
        $ownerHeaders = $this->auth($owner) + ['Accept' => 'application/json'];

        $this->post("/api/v1/merchant/shops/{$otherShop->id}/stories", ['media' => UploadedFile::fake()->image('x.jpg')], $ownerHeaders)->assertForbidden();
        $this->post("/api/v1/merchant/shops/{$shop->id}/stories", ['caption' => 'Fresh kunafa'], $ownerHeaders)->assertStatus(422);
        $storyId = $this->post("/api/v1/merchant/shops/{$shop->id}/stories", ['media' => UploadedFile::fake()->image('story.jpg'), 'caption' => 'Fresh kunafa'], $ownerHeaders)
            ->assertCreated()->assertJsonPath('story.type', 'image')->json('story.id');

        $this->getJson('/api/v1/stories')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.shop.id', $shop->id)->assertJsonPath('data.0.stories.0.caption', 'Fresh kunafa');

        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->postJson("/api/v1/stories/{$storyId}/view", [], $this->auth($customer))->assertOk()->assertJsonPath('recorded', true);
        $this->postJson("/api/v1/stories/{$storyId}/view", [], $this->auth($customer))->assertOk();
        $this->postJson("/api/v1/stories/{$storyId}/view", [], $this->auth($owner))->assertJsonPath('recorded', false);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/stories", $ownerHeaders)->assertOk()->assertJsonPath('data.0.views', 1);

        // Expired stories leave the feed; deleting removes the file.
        $this->deleteJson("/api/v1/merchant/stories/{$storyId}", [], $this->auth(User::factory()->create(['role' => 'shop_owner', 'is_active' => true])))->assertForbidden();
        $this->deleteJson("/api/v1/merchant/stories/{$storyId}", [], $ownerHeaders)->assertOk();
        $this->assertSame(0, ShopStory::count());
        $this->getJson('/api/v1/stories')->assertJsonCount(0, 'data');
    }

    public function test_an_owner_adds_edits_hides_and_deletes_products_and_sections(): void
    {
        [$shop, $owner, $category] = $this->shop('clothing');
        [$otherShop, , $otherCategory] = $this->shop('sweets');
        $headers = $this->auth($owner) + ['Accept' => 'application/json'];

        $this->post("/api/v1/merchant/shops/{$otherShop->id}/products", ['name' => 'X', 'price' => 5, 'category_id' => $otherCategory->id], $headers)->assertForbidden();
        $this->post("/api/v1/merchant/shops/{$shop->id}/products", ['name' => 'Shirt', 'price' => 100, 'category_id' => $otherCategory->id], $headers)->assertStatus(422);
        $this->post("/api/v1/merchant/shops/{$shop->id}/products", ['name' => 'Shirt', 'price' => 100, 'discount_price' => 120, 'category_id' => $category->id], $headers)->assertStatus(422);

        $id = $this->post("/api/v1/merchant/shops/{$shop->id}/products", [
            'name' => 'قميص قطن', 'price' => 100, 'discount_price' => 80, 'category_id' => $category->id, 'description' => 'ناعم',
            'sizes' => 'S, M ,L', 'colors' => 'أسود,أبيض', 'image' => UploadedFile::fake()->image('shirt.jpg'),
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ], $headers)->assertCreated()->assertJsonPath('product.sizes', ['S', 'M', 'L'])->assertJsonCount(2, 'product.images')->json('product.id');
        $product = Product::findOrFail($id);
        $this->assertTrue($product->is_active);
        Storage::disk('public')->assertExists(substr($product->main_image, 8));
        // The customer side sees it straight away, with its options.
        $this->getJson("/api/v1/products/{$id}")->assertOk()->assertJsonPath('product.final_price', 80)->assertJsonPath('product.colors', ['أسود', 'أبيض']);

        $galleryId = $product->images()->firstOrFail()->id;
        $this->post("/api/v1/merchant/products/{$id}", ['price' => 90, 'is_active' => '0', 'delete_image_ids' => [$galleryId]], $headers)
            ->assertOk()->assertJsonPath('product.price', 90)->assertJsonPath('product.is_active', false)->assertJsonCount(1, 'product.images');
        $this->assertSame('قميص قطن', $product->fresh()->name);
        $this->assertSame(['S', 'M', 'L'], $product->fresh()->catalog_attributes['sizes']);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/products", $headers)->assertOk()->assertJsonPath('data.0.is_active', false)->assertJsonPath('has_sizes', true);

        // Sections: add, rename, and only delete when empty.
        $sectionId = $this->postJson("/api/v1/merchant/shops/{$shop->id}/categories", ['name' => 'شتوي'], $headers)->assertCreated()->json('category.id');
        $this->patchJson("/api/v1/merchant/categories/{$sectionId}", ['name' => 'شتوي 2026'], $headers)->assertOk()->assertJsonPath('category.name', 'شتوي 2026');
        $this->deleteJson("/api/v1/merchant/categories/{$category->id}", [], $headers)->assertStatus(422);
        $this->deleteJson("/api/v1/merchant/categories/{$sectionId}", [], $headers)->assertOk();
        $this->patchJson("/api/v1/merchant/categories/{$otherCategory->id}", ['name' => 'Hack'], $headers)->assertForbidden();

        $this->deleteJson("/api/v1/merchant/products/{$id}", [], $headers)->assertOk();
        $this->assertNull(Product::find($id));
    }

    public function test_saving_from_the_app_keeps_what_the_website_set_up(): void
    {
        [$restaurant, $chef, $menu] = $this->shop('restaurant');
        $meal = Product::create(['shop_id' => $restaurant->id, 'category_id' => $menu->id, 'name' => 'Burger', 'slug' => 'b-'.uniqid(), 'price' => 40, 'is_active' => true,
            'catalog_attributes' => ['meal_size_prices' => ['عادي:40', 'دبل:52'], 'addon_prices' => ['جبنة:3']]]);
        [$tech, $seller, $phones] = $this->shop('electronics');
        $phone = Product::create(['shop_id' => $tech->id, 'category_id' => $phones->id, 'name' => 'Phone', 'slug' => 'p-'.uniqid(), 'price' => 900, 'is_active' => true]);
        $phone->variants()->create(['storage' => '128GB', 'color' => '#000000', 'price' => 900, 'quantity' => 3, 'is_active' => true]);

        $this->post("/api/v1/merchant/products/{$meal->id}", ['name' => 'Burger XL', 'price' => 45], $this->auth($chef) + ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('product.has_website_options', true);
        $this->assertSame(['عادي:40', 'دبل:52'], $meal->fresh()->catalog_attributes['meal_size_prices']);

        $sellerHeaders = $this->auth($seller) + ['Accept' => 'application/json'];
        $this->post("/api/v1/merchant/products/{$phone->id}", ['description' => 'New'], $sellerHeaders)->assertOk();
        $this->assertSame(1, $phone->variants()->count());
        // Devices need storage/colour options, which only the website's form builds.
        $this->getJson("/api/v1/merchant/shops/{$tech->id}/products", $sellerHeaders)->assertJsonPath('can_create', false);
        $this->post("/api/v1/merchant/shops/{$tech->id}/products", ['name' => 'Tablet', 'price' => 500, 'category_id' => $phones->id], $sellerHeaders)->assertStatus(422);
    }

    public function test_a_driver_takes_an_offer_starts_the_delivery_and_completes_it(): void
    {
        [$shop] = $this->shop('restaurant');
        $driverUser = User::factory()->create(['role' => 'restaurant_driver', 'is_active' => true]);
        $driver = $shop->restaurantDrivers()->create(['user_id' => $driverUser->id, 'is_active' => true]);
        $order = FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'RST-1', 'customer_name' => 'Lina', 'customer_phone' => '0599123456',
            'customer_address' => 'Ramallah', 'items' => [['name' => 'Burger', 'qty' => 2, 'price' => 20]], 'subtotal' => 40, 'discount' => 0, 'total' => 40,
            'order_channel' => 'restaurant', 'order_type' => 'delivery', 'status' => 'ready', 'payment_status' => 'pending']);
        $offer = RestaurantDeliveryOffer::create(['front_order_id' => $order->id, 'restaurant_driver_id' => $driver->id, 'status' => 'offered', 'offered_at' => now()]);
        $headers = $this->auth($driverUser);

        $this->getJson('/api/v1/auth/me', $headers)->assertJsonPath('user.is_driver', true);
        // An offer shows where and what, but not the customer's phone.
        $this->getJson('/api/v1/driver', $headers)->assertOk()->assertJsonPath('stats.available', 1)
            ->assertJsonPath('offers.0.offer_id', $offer->id)->assertJsonPath('offers.0.customer_phone', null)->assertJsonPath('offers.0.items.0', '2× Burger');

        $this->patchJson("/api/v1/driver/offers/{$offer->id}/accept", [], $headers)->assertOk();
        $this->getJson('/api/v1/driver', $headers)->assertJsonPath('stats.available', 0)->assertJsonPath('orders.0.customer_phone', '0599123456');

        $this->patchJson("/api/v1/driver/orders/{$order->id}/status", ['status' => 'out_for_delivery'], $headers)->assertStatus(422);
        $this->patchJson("/api/v1/driver/orders/{$order->id}/status", ['status' => 'out_for_delivery', 'estimated_delivery_minutes' => 20], $headers)
            ->assertOk()->assertJsonPath('order.status', 'out_for_delivery');
        $this->patchJson("/api/v1/driver/orders/{$order->id}/delivery-time", ['estimated_delivery_minutes' => 35], $headers)->assertOk();
        $this->patchJson("/api/v1/driver/orders/{$order->id}/status", ['status' => 'completed'], $headers)->assertOk()->assertJsonPath('order.status', 'completed');
        $this->getJson('/api/v1/driver', $headers)->assertJsonPath('stats.delivered_today', 1);

        // Other accounts have no driver screen, and a driver can't touch someone else's order.
        $this->getJson('/api/v1/driver', $this->auth(User::factory()->create(['role' => 'customer', 'is_active' => true])))->assertForbidden();
        $foreign = FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'RST-2', 'customer_name' => 'A', 'items' => [], 'subtotal' => 1, 'discount' => 0, 'total' => 1,
            'order_channel' => 'restaurant', 'order_type' => 'delivery', 'status' => 'ready', 'payment_status' => 'pending']);
        $this->patchJson("/api/v1/driver/orders/{$foreign->id}/status", ['status' => 'out_for_delivery', 'estimated_delivery_minutes' => 5], $headers)->assertForbidden();
    }
}
