<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    private function shopWithMenu(bool $active = true): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Bankai', 'slug' => 'bankai-'.uniqid(), 'catalog_type' => 'restaurant',
            'is_active' => $active, 'payment_account_number' => 'SECRET-IBAN']);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'سوشي', 'slug' => 'sushi-'.uniqid(), 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'رول', 'slug' => 'roll-'.uniqid(),
            'price' => 40, 'discount_price' => 35, 'merchant_price' => 20, 'main_image' => 'storage/products/roll.jpg', 'is_active' => true]);
        Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Hidden', 'slug' => 'hidden-'.uniqid(), 'price' => 9, 'is_active' => false]);

        return [$shop, $category, $product];
    }

    public function test_catalog_lists_active_shops_menus_and_products_without_private_fields(): void
    {
        [$shop, $category, $product] = $this->shopWithMenu();
        $this->shopWithMenu(false);

        $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('types.0.key', 'restaurant')->assertJsonPath('types.0.shops_count', 1);
        $this->getJson('/api/v1/shops?type=restaurant')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $shop->id);

        $menu = $this->getJson('/api/v1/shops/'.$shop->id)->assertOk()
            ->assertJsonPath('categories.0.id', $category->id)->assertJsonPath('categories.0.products_count', 1)->json();
        $this->assertStringNotContainsString('SECRET-IBAN', json_encode($menu));

        $list = $this->getJson('/api/v1/shops/'.$shop->id.'/products?category_id='.$category->id)->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.final_price', 35)->assertJsonPath('data.0.image', asset('storage/products/roll.jpg'))->json();
        $this->assertStringNotContainsString('merchant_price', json_encode($list));

        $this->getJson('/api/v1/products/'.$product->id)->assertOk()->assertJsonPath('product.price', 40)->assertJsonPath('shop.id', $shop->id);
        $this->getJson('/api/v1/products/'.Product::where('is_active', false)->value('id'))->assertNotFound();
    }

    public function test_language_header_picks_the_translation(): void
    {
        [$shop, , $product] = $this->shopWithMenu();
        $product->update(['name_translations' => ['ar' => 'رول', 'en' => 'Roll']]);
        $this->getJson('/api/v1/products/'.$product->id, ['Accept-Language' => 'en'])->assertJsonPath('product.name', 'Roll');
        $this->getJson('/api/v1/products/'.$product->id.'?lang=ar')->assertJsonPath('product.name', 'رول');
    }

    public function test_login_issues_a_hashed_token_that_signs_requests_and_logout_revokes_it(): void
    {
        $user = User::factory()->create(['email' => 'sama@example.test', 'username' => 'sama', 'password' => Hash::make('Secret-Pass1'), 'role' => 'customer', 'is_active' => true]);

        $this->postJson('/api/v1/auth/login', ['login' => 'sama@example.test', 'password' => 'wrong'])->assertStatus(422)->assertJsonPath('code', 'invalid_credentials');
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();

        $token = $this->postJson('/api/v1/auth/login', ['login' => '@sama', 'password' => 'Secret-Pass1', 'device_name' => 'Pixel 7'])
            ->assertOk()->assertJsonPath('user.id', $user->id)->json('token');
        $this->assertDatabaseMissing('api_tokens', ['token_hash' => $token]);
        $this->assertDatabaseHas('api_tokens', ['token_hash' => hash('sha256', $token), 'name' => 'Pixel 7']);

        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])->assertOk()->assertJsonPath('user.email', 'sama@example.test');
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.str_repeat('x', 64)])->assertUnauthorized();

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
    }

    public function test_disabled_accounts_and_expired_tokens_are_rejected(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        [$token, $plain] = ApiToken::issue($user);
        $user->update(['is_active' => false]);
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$plain])->assertUnauthorized();

        $user->update(['is_active' => true]);
        $token->update(['expires_at' => now()->subDay()]);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$plain])->assertUnauthorized();
    }

    public function test_register_creates_a_customer_and_returns_a_token(): void
    {
        $this->postJson('/api/v1/auth/register', ['name' => 'Nour', 'email' => 'Nour@Example.test', 'password' => 'Secret-Pass1'])
            ->assertCreated()->assertJsonPath('user.role', 'customer')->assertJsonStructure(['token', 'expires_at']);
        $this->assertDatabaseHas('users', ['email' => 'nour@example.test', 'role' => 'customer']);
        $this->postJson('/api/v1/auth/register', ['name' => 'X', 'email' => 'nour@example.test', 'password' => 'Secret-Pass1'])->assertStatus(422);
    }
}
