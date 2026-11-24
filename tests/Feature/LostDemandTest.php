<?php

namespace Tests\Feature;

use App\Models\{ApiToken, Category, Product, PushDevice, SearchMiss, Shop, User};
use App\Services\Demand\LostDemand;
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/** Searches that found nothing are kept anonymously and shown to the shops that could sell the thing. */
class LostDemandTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $type, string $slug, array $products = []): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Shop '.$slug, 'slug' => $slug, 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.$slug, 'is_active' => true]);
        foreach ($products as $name) {
            Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => $name, 'slug' => 'p-'.uniqid(), 'price' => 10, 'quantity' => 5, 'rating' => 0, 'is_active' => true]);
        }

        return [$shop, $owner];
    }

    private function headers(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    public function test_spellings_fold_together(): void
    {
        $this->assertSame('فطاير جبنه', LostDemand::normalize('فطائر جبنة'));
        $this->assertSame('فطاير جبنه', LostDemand::normalize('  فَطاير   جبنه! '));
        $this->assertSame('برغر', LostDemand::normalize('البرغر'));
        $this->assertSame('iphone 15', LostDemand::normalize('iPhone 15'));
    }

    public function test_empty_app_searches_are_kept_anonymously_with_the_kind_of_shop(): void
    {
        [$bakery] = $this->shop('restaurant', 'bakery', ['فطائر زعتر']);
        $this->shop('clothing', 'clothes', ['قميص قطن']);

        $this->getJson('/api/v1/search?q=فطاير جبنه')->assertOk()->assertJsonCount(0, 'products');
        $this->getJson('/api/v1/search?q=قميص')->assertOk()->assertJsonCount(1, 'products');
        $this->getJson("/api/v1/shops/{$bakery->id}/products?q=كرواسون")->assertOk();
        $this->getJson("/api/v1/shops/{$bakery->id}/products?q=زعتر")->assertOk();

        $misses = SearchMiss::orderBy('id')->get();
        $this->assertCount(2, $misses);
        // Typed in the whole app: matched to restaurants through "فطائر" in a restaurant's products.
        $this->assertSame(['فطاير جبنه', 'app', null, 'restaurant'], [$misses[0]->term, $misses[0]->source, $misses[0]->shop_id, $misses[0]->catalog_type]);
        $this->assertSame(['كرواسون', 'app_shop', $bakery->id, 'restaurant'], [$misses[1]->term, $misses[1]->source, $misses[1]->shop_id, $misses[1]->catalog_type]);
        $this->assertArrayNotHasKey('user_id', $misses[0]->getAttributes());
    }

    public function test_repeats_junk_and_narrowed_assistant_searches_are_not_counted(): void
    {
        $demand = app(LostDemand::class);
        $this->assertNotNull($demand->record('كنافة', 'app'));
        $this->assertNull($demand->record('كنافه', 'app'), 'same visitor, same thing');
        $this->assertNull($demand->record('!!', 'app'));
        $this->assertNull($demand->record(str_repeat('كلمة ', 20), 'app'));
        $this->assertNull($demand->record('كنافة', 'nowhere'));
        Cache::flush();
        $this->assertNotNull($demand->record('كنافة', 'app'), 'counted again after a while');
        $this->assertSame(2, SearchMiss::count());
    }

    public function test_fulla_finding_nothing_counts_but_a_price_filtered_search_does_not(): void
    {
        [$shop] = $this->shop('sweets', 'sweets', ['كنافة نابلسية']);
        $tools = app(\App\Services\Assistant\CustomerAssistantTools::class);
        $cards = [];

        $this->assertSame(0, $tools->run('search_products', ['query' => 'قطايف'], null, $cards)['count']);
        $this->assertSame(0, $tools->run('search_products', ['query' => 'بقلاوة', 'max_price' => 1], null, $cards)['count']);
        $this->assertSame(1, $tools->run('search_products', ['query' => 'كنافة'], null, $cards)['count']);

        $this->assertSame(['قطايف'], SearchMiss::pluck('term')->all());
        $this->assertSame('assistant', SearchMiss::first()->source);
    }

    public function test_the_website_search_box_reports_a_miss(): void
    {
        $this->postJson('/api/v1/search-misses', ['q' => 'شاحن سامسونج'])->assertOk();
        $this->postJson('/api/v1/search-misses', ['q' => ''])->assertStatus(422);
        $this->assertDatabaseHas('search_misses', ['term' => 'شاحن سامسونج', 'source' => 'web', 'shop_id' => null]);
    }

    public function test_a_shop_owner_sees_what_was_missed_in_the_shop_and_in_its_kind_of_shop(): void
    {
        [$shop, $owner] = $this->shop('restaurant', 'grill', ['برغر لحم']);
        [$other, $stranger] = $this->shop('clothing', 'style');
        $demand = app(LostDemand::class);
        foreach (range(1, 3) as $i) {
            Cache::flush();
            $demand->record('شاورما', 'app_shop', $shop);
            $demand->record('فطائر جبنة', 'app', null, 'restaurant');
        }
        Cache::flush();
        $demand->record('برغر لحم', 'app_shop', $shop);
        $demand->record('جاكيت', 'app', null, 'clothing');

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/demand", $this->headers($stranger))->assertForbidden();
        $response = $this->getJson("/api/v1/merchant/shops/{$shop->id}/demand", $this->headers($owner))->assertOk()
            ->assertJsonPath('days', 7)
            ->assertJsonPath('in_shop.0.shown', 'شاورما')->assertJsonPath('in_shop.0.searches', 3)->assertJsonPath('in_shop.0.added', false)
            ->assertJsonPath('in_type.0.shown', 'فطائر جبنة')->assertJsonPath('in_type.0.searches', 3)
            ->assertJsonCount(1, 'in_type');
        // Searched before the shop had it: marked as added now.
        $this->assertTrue(collect($response->json('in_shop'))->firstWhere('term', 'برغر لحم')['added']);
    }

    public function test_the_weekly_note_goes_to_owners_with_enough_searches(): void
    {
        [$shop, $owner] = $this->shop('restaurant', 'grill');
        [$quiet, $quietOwner] = $this->shop('restaurant', 'quiet');
        PushDevice::create(['token' => 'owner-phone', 'platform' => 'android', 'user_id' => $owner->id]);
        PushDevice::create(['token' => 'quiet-phone', 'platform' => 'android', 'user_id' => $quietOwner->id]);
        foreach (range(1, 4) as $i) {
            Cache::flush();
            app(LostDemand::class)->record('شاورما', 'app_shop', $shop);
        }
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->once()
            ->withArgs(fn ($tokens, $title, $body, $url, $data) => collect($tokens)->all() === ['owner-phone'] && str_contains($body, '4') && str_contains($body, 'شاورما') && $data['type'] === 'lost_demand')
            ->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);

        $this->artisan('demand:weekly-summary')->expectsOutputToContain('sent: 1')->assertSuccessful();
    }

    public function test_the_dashboard_page_shows_a_shop_its_own_misses_and_the_admin_all_of_ozman(): void
    {
        [$shop, $owner] = $this->shop('restaurant', 'grill');
        [$other, $otherOwner] = $this->shop('restaurant', 'other');
        $demand = app(LostDemand::class);
        $demand->record('شاورما', 'app_shop', $shop);
        $demand->record('مناقيش', 'app_shop', $other);
        Cache::flush();
        $demand->record('بوظة', 'web', null, 'sweets');

        $this->actingAs($owner)->get('/lost-demand')->assertOk()
            ->assertSee('شاورما')->assertDontSee('مناقيش')->assertDontSee('بوظة')
            ->assertSee(route('products.create', ['shop_id' => $shop->id, 'name' => 'شاورما']));
        // Another shop's id in the address doesn't open it.
        $this->actingAs($owner)->get('/lost-demand?shop_id='.$other->id)->assertForbidden();

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/lost-demand')->assertOk()->assertSee('شاورما')->assertSee('مناقيش')->assertSee('بوظة');
        $this->actingAs($admin)->get('/lost-demand?shop_id='.$other->id.'&days=30')->assertOk()->assertSee('مناقيش')->assertDontSee('شاورما');

        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($customer)->get('/lost-demand')->assertRedirect();
    }

    public function test_the_add_product_page_takes_the_searched_name(): void
    {
        [$shop, $owner] = $this->shop('restaurant', 'grill');
        $this->actingAs($owner)->get(route('products.create', ['shop_id' => $shop->id, 'name' => 'شاورما عربي']))
            ->assertOk()->assertSee('value="شاورما عربي"', false);
    }
}
