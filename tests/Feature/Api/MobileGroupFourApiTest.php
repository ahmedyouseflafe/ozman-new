<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, Distributor, DistributorMarketer, Product, RaffleCard, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Tests\TestCase;

/** Shop details, website links, raffle cards, the marketer wheel, photo try-on and the agents' network in the app. */
class MobileGroupFourApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function shop(string $type, ?User $owner = null, array $extra = []): Shop
    {
        $owner ??= User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        return Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, ...$extra]);
    }

    public function test_the_owner_edits_the_shop_details(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner, ['phone' => '0590000000']);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/settings", $h)->assertOk()->assertJsonPath('settings.phone', '0590000000');
        $this->post("/api/v1/merchant/shops/{$shop->id}/settings", ['name' => 'حلويات أبو سليم', 'open_time' => '09:00:00', 'close_time' => '23:00',
            'social' => ['instagram' => 'https://instagram.com/abusaleem'], 'logo' => UploadedFile::fake()->image('logo.png', 300, 300)], $h)
            ->assertOk()->assertJsonPath('settings.name', 'حلويات أبو سليم')->assertJsonPath('settings.open_time', '09:00')
            ->assertJsonPath('settings.social.instagram', 'https://instagram.com/abusaleem');
        $shop->refresh();
        $this->assertSame('0590000000', $shop->phone);
        Storage::disk('public')->assertExists(substr($shop->logo, strlen('storage/')));
        $this->getJson("/api/v1/shops/{$shop->id}")->assertJsonPath('shop.name', 'حلويات أبو سليم');

        $this->post("/api/v1/merchant/shops/{$shop->id}/settings", ['name' => ''], $h)->assertStatus(422);
        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->post("/api/v1/merchant/shops/{$shop->id}/settings", ['name' => 'X'], $this->auth($stranger))->assertForbidden();
    }

    public function test_website_links_open_the_matching_app_screen(): void
    {
        $shop = $this->shop('restaurant');
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.uniqid(), 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Burger', 'slug' => 'burger-'.uniqid(), 'price' => 30, 'is_active' => true]);
        $resolve = fn (string $url) => $this->getJson('/api/v1/links/resolve?url='.urlencode($url))->assertOk()->json('target');

        $this->assertSame(['type' => 'shop', 'id' => $shop->id, 'name' => $shop->name], $resolve(url('/restaurants/'.$shop->slug)));
        $this->assertSame(['type' => 'product', 'id' => $product->id], $resolve(url('/restaurants/'.$shop->slug).'?product='.$product->id));
        $this->assertSame(['type' => 'product', 'id' => $product->id], $resolve(route('share.product', $product)));
        $this->assertSame(['type' => 'table', 'slug' => $shop->slug, 'code' => 'abc123'], $resolve(url('/restaurants/'.$shop->slug.'/table/abc123')));
        $this->assertSame(['type' => 'promoter', 'slug' => 'lina'], $resolve(url('/promoters/lina')));
        $this->assertSame(['type' => 'market_item', 'id' => 7], $resolve(url('/free-market/item/7')));
        $this->assertSame('raffle_card', $resolve(url('/raffle-card/123456'))['type']);
        $this->assertSame('web', $resolve('https://example.com/x')['type']);
        $this->assertSame('web', $resolve(url('/business'))['type']);
    }

    public function test_raffle_card_check_and_marketer_wheel(): void
    {
        RaffleCard::create(['card_number' => '123456', 'prize_title' => 'سماعة بلوتوث', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'phone' => '0599123456']);
        $h = $this->auth($customer);

        $this->postJson('/api/v1/raffle/check', ['card_number' => '123456'], $h)->assertOk()->assertJsonPath('status', 'winner')->assertJsonPath('prize_title', 'سماعة بلوتوث');
        $this->postJson('/api/v1/raffle/check', ['card_number' => '123456'], $h)->assertStatus(409)->assertJsonPath('status', 'used');
        $this->postJson('/api/v1/raffle/check', ['card_number' => '654321', 'customer' => ['name' => 'Guest', 'phone' => '0599888777']])
            ->assertOk()->assertJsonPath('status', 'live_draw');

        $company = $this->shop('krembo');
        $distributor = Distributor::create(['shop_id' => $company->id, 'name' => 'موزع', 'is_active' => true]);
        DistributorMarketer::create(['distributor_id' => $distributor->id, 'name' => 'سارة', 'tracking_code' => 'sara-1', 'is_active' => true]);
        $wheel = $this->getJson('/api/v1/marketer-wheel/sara-1')->assertOk()->assertJsonPath('marketer.name', 'سارة')->json();
        $this->assertCount(4, $wheel['segments']);
        $spin = $this->postJson('/api/v1/marketer-wheel/sara-1/spin')->assertOk()->json();
        $this->assertSame($wheel['segments'][$spin['selected_index']]['label'], $spin['segment']['label']);
        $this->getJson('/api/v1/marketer-wheel/nope')->assertNotFound();
    }

    public function test_photo_try_on_from_the_app(): void
    {
        $shop = $this->shop('clothing');
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Shirts', 'slug' => 'shirts-'.uniqid(), 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Shirt', 'slug' => 'shirt-'.uniqid(), 'price' => 90,
            'is_active' => true, 'main_image' => 'storage/products/shirt.png']);
        $this->getJson("/api/v1/products/{$product->id}")->assertJsonPath('product.try_on', null);

        config(['services.fashn.key' => 'test-secret', 'services.fashn.shops' => [$shop->slug]]);
        Http::fake(['*/run' => Http::response(['id' => 'provider-demo']), '*/status/*' => Http::response(['status' => 'completed', 'output' => ['data:image/png;base64,YWJj']])]);
        $this->getJson("/api/v1/products/{$product->id}")->assertJsonPath('product.try_on', 'clothing');
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $h = $this->auth($customer);
        $photo = UploadedFile::fake()->image('me.jpg', 600, 800);

        $this->post("/api/v1/products/{$product->id}/try-on", ['photo' => $photo], $h)->assertStatus(422);
        $job = $this->post("/api/v1/products/{$product->id}/try-on", ['photo' => $photo, 'consent' => '1'], $h)->assertStatus(202)->json('job');
        $this->getJson("/api/v1/shops/{$shop->id}/try-on/$job", $h)->assertOk()->assertJsonPath('status', 'completed');
        $other = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->getJson("/api/v1/shops/{$shop->id}/try-on/$job", $this->auth($other))->assertNotFound();
    }

    public function test_distributors_and_marketers_see_their_network(): void
    {
        $company = $this->shop('krembo');
        $distributorUser = User::factory()->create(['role' => 'distributor', 'is_active' => true]);
        $distributor = Distributor::create(['shop_id' => $company->id, 'user_id' => $distributorUser->id, 'name' => 'موزع الشمال', 'is_active' => true]);
        $marketerUser = User::factory()->create(['role' => 'marketer', 'is_active' => true]);
        $marketer = DistributorMarketer::create(['distributor_id' => $distributor->id, 'user_id' => $marketerUser->id, 'name' => 'سارة', 'tracking_code' => 'sara-2',
            'commission_rate' => 5, 'is_active' => true]);
        $this->shop('sweets', null, ['distributor_id' => $distributor->id, 'distributor_marketer_id' => $marketer->id, 'name' => 'محل جديد']);

        $this->getJson('/api/v1/auth/me', $this->auth($marketerUser))->assertJsonPath('user.network_role', 'marketer');
        $this->getJson('/api/v1/me/network', $this->auth($marketerUser))->assertOk()->assertJsonPath('role', 'marketer')
            ->assertJsonPath('marketers.0.code', 'sara-2')->assertJsonPath('marketers.0.shops.0.name', 'محل جديد')->assertJsonPath('marketers.0.commission_rate', 5);
        $this->getJson('/api/v1/me/network', $this->auth($distributorUser))->assertOk()->assertJsonPath('role', 'distributor')
            ->assertJsonPath('distributors.0.marketers.0.name', 'سارة')->assertJsonPath('distributors.0.shops.0.name', 'محل جديد');
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->getJson('/api/v1/me/network', $this->auth($customer))->assertForbidden();
    }
}
