<?php

namespace Tests\Feature;

use App\Models\{ApiToken, Category, Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** An offer with an end: the app gets the end for its countdown, and the discount switches off when it passes. */
class OfferEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function product(array $extra = []): Product
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'حلويات', 'slug' => 's-'.Str::random(6), 'catalog_type' => 'sweets',
            'is_active' => true, 'is_accepting_orders' => true, 'whatsapp' => '972501234567']);
        $category = Category::create(['shop_id' => $shop->id, 'slug' => 'c-'.$shop->id, 'name' => 'Main', 'is_active' => true]);

        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'كنافة', 'slug' => 'k-'.Str::random(6),
            'price' => 30, 'is_active' => true, ...$extra]);
    }

    public function test_the_app_gets_the_end_of_an_offer_and_it_switches_off_when_it_passes(): void
    {
        $product = $this->product(['discount_price' => 24, 'discount_ends_at' => now()->addHours(3)]);
        $this->getJson("/api/v1/products/{$product->id}")->assertOk()
            ->assertJsonPath('product.final_price', 24)
            ->assertJsonPath('product.discount_ends_at', $product->discount_ends_at->toIso8601String());

        $this->travel(3)->hours();
        $this->travel(1)->minutes();
        $this->artisan('products:end-offers')->assertSuccessful();

        $product->refresh();
        $this->assertNull($product->discount_price);
        $this->assertNull($product->discount_ends_at);
        $this->getJson("/api/v1/products/{$product->id}")->assertOk()
            ->assertJsonPath('product.final_price', 30)
            ->assertJsonPath('product.discount_ends_at', null);
    }

    public function test_an_offer_without_an_end_stays_and_removing_the_discount_drops_the_end(): void
    {
        $open = $this->product(['discount_price' => 20]);
        $this->artisan('products:end-offers')->assertSuccessful();
        $this->assertEquals(20, (float) $open->refresh()->discount_price);
        $this->getJson("/api/v1/products/{$open->id}")->assertJsonPath('product.discount_ends_at', null);

        $ending = $this->product(['discount_price' => 20, 'discount_ends_at' => now()->addDay()]);
        $ending->update(['discount_price' => null]);
        $this->assertNull($ending->refresh()->discount_ends_at);
    }

    public function test_the_shop_owner_sets_the_end_from_the_app_and_it_must_be_in_the_future(): void
    {
        $product = $this->product();
        $owner = $product->shop->owner ?? User::find($product->shop->user_id);
        $headers = $this->auth($owner);
        $ends = now()->addDays(2)->startOfMinute();

        $this->post("/api/v1/merchant/products/{$product->id}", ['discount_price' => 25, 'discount_ends_at' => $ends->toIso8601String()], $headers)
            ->assertOk();
        $this->assertTrue($product->refresh()->discount_ends_at->equalTo($ends));

        $this->post("/api/v1/merchant/products/{$product->id}", ['discount_ends_at' => now()->subHour()->toIso8601String()], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('discount_ends_at');
    }
}
