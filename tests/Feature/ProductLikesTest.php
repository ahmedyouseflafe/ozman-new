<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductLikeController;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLikesTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $shop = [], array $attributes = []): Product
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Likes Shop', 'slug' => 'likes-'.uniqid(), 'is_active' => true, ...$shop]);

        $category = \App\Models\Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);

        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Rose', 'slug' => 'rose-'.uniqid(), 'price' => 10, 'quantity' => 5, 'is_active' => true, ...$attributes]);
    }

    public function test_guest_likes_and_unlikes_once_per_device(): void
    {
        $product = $this->product();
        $first = $this->postJson(route('product-likes.toggle', $product))->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $cookie = $first->getCookie(ProductLikeController::COOKIE);
        $this->assertNotNull($cookie);
        $this->assertSame(40, strlen($cookie->getValue()));

        // Same device: the state shows it liked; a second tap removes it.
        $this->withCredentials()->withCookie(ProductLikeController::COOKIE, $cookie->getValue());
        $this->getJson(route('product-likes.state', ['ids' => $product->id]))->assertOk()
            ->assertJsonPath('counts.'.$product->id, 1)->assertJsonPath('liked', [$product->id]);
        $this->postJson(route('product-likes.toggle', $product))->assertJson(['liked' => false, 'count' => 0]);
        $this->assertDatabaseCount('product_likes', 0);
        $this->assertSame(0, $product->fresh()->likes_count);
    }

    public function test_different_visitors_and_accounts_each_count(): void
    {
        $product = $this->product();
        $this->postJson(route('product-likes.toggle', $product))->assertJson(['count' => 1]);
        $this->flushSession(); $this->defaultCookies = []; $this->unencryptedCookies = [];
        $this->postJson(route('product-likes.toggle', $product))->assertJson(['count' => 2]);

        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($user)->postJson(route('product-likes.toggle', $product))->assertJson(['liked' => true, 'count' => 3]);
        $this->assertDatabaseHas('product_likes', ['product_id' => $product->id, 'user_id' => $user->id, 'visitor_hash' => null]);
        $this->actingAs($user)->getJson(route('product-likes.state', ['ids' => $product->id.',abc,-4']))->assertJsonPath('liked', [$product->id]);
        $this->actingAs($user)->postJson(route('product-likes.toggle', $product))->assertJson(['liked' => false, 'count' => 2]);
    }

    public function test_hidden_products_cannot_be_liked(): void
    {
        $this->postJson(route('product-likes.toggle', $this->product([], ['is_active' => false])))->assertNotFound();
        $this->postJson(route('product-likes.toggle', $this->product(['is_active' => false])))->assertNotFound();
        $this->getJson(route('product-likes.state'))->assertOk()->assertJson(['liked' => []]);
    }

    public function test_storefronts_show_the_heart_on_product_cards(): void
    {
        foreach (['restaurant' => 'restaurant.menu', 'sweets' => 'sweets.store', 'furniture_appliances' => 'furniture.store', 'clothing' => 'clothing.store',
            'cosmetics' => 'cosmetics.store', 'electronics' => 'electronics.store', 'advertising_services' => 'advertising.store', 'dental' => 'dental.clinic'] as $type => $route) {
            $product = $this->product(['catalog_type' => $type, 'is_accepting_orders' => true]);
            $this->get(route($route, $product->shop))->assertOk()
                ->assertSee('data-like-product="'.$product->id.'"', false)
                ->assertSee('window.OZMAN_LIKES', false)
                ->assertSee('data-share-url="'.route('share.product', $product).'"', false)
                ->assertSee('class="oz-page-share"', false);
        }
        $krembo = $this->product(['catalog_type' => 'krembo']);
        $this->get(route('krembo.store', $krembo->shop))->assertOk()->assertSee('data-like-product="'.$krembo->id.'"', false);
        // The general store builds its cards in JS; the page loads the like script and the card template.
        $general = $this->product();
        $this->get(route('front.shop.slug', $general->shop))->assertOk()->assertSee('window.OzmanLikes', false);
        $this->assertStringContainsString('data-like-product="${Number(prod.id)}"', file_get_contents(public_path('script.js')));
    }

    public function test_shared_product_link_previews_the_product_and_opens_it_in_the_store(): void
    {
        $meal = $this->product(['catalog_type' => 'restaurant', 'name' => 'Bankai'], ['name' => 'ريد دراغون رول', 'main_image' => 'products/roll.jpg', 'description' => 'تونا حمراء']);
        $target = $meal->shop->publicUrl().'?product='.$meal->id;
        $this->get(route('share.product', $meal))->assertOk()
            ->assertSee('<meta property="og:title" content="ريد دراغون رول — Bankai">', false)
            ->assertSee('<meta property="og:image" content="'.asset('storage/products/roll.jpg').'">', false)
            ->assertSee('content="0;url='.e($target).'"', false);

        $phone = $this->product(['catalog_type' => 'electronics']);
        $this->get(route('share.product', $phone))->assertOk()->assertSee(route('electronics.product', [$phone->shop, $phone]), false);

        $this->get(route('share.product', $this->product([], ['is_active' => false])))->assertNotFound();
    }
}
