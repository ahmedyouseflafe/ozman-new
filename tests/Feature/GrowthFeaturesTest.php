<?php

namespace Tests\Feature;

use App\Models\{ApiToken, Category, FrontOrder, LoyaltyPointEntry, Product, PushDevice, Shop, ShopReview, User};
use App\Services\{FirebaseMessagingService, LoyaltyPoints};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class GrowthFeaturesTest extends TestCase
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

    private function shop(array $extra = []): Shop
    {
        return Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id, 'name' => 'حلويات', 'slug' => 's-'.Str::random(6),
            'catalog_type' => 'sweets', 'is_active' => true, 'is_accepting_orders' => true, 'whatsapp' => '972501234567', ...$extra]);
    }

    private function product(Shop $shop, array $extra = []): Product
    {
        $category = Category::firstOrCreate(['shop_id' => $shop->id, 'slug' => 'c-'.$shop->id], ['name' => 'Main', 'is_active' => true]);

        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'كنافة', 'slug' => 'k-'.Str::random(6), 'price' => 30, 'is_active' => true, ...$extra]);
    }

    private function customer(array $extra = []): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true, ...$extra]);
    }

    public function test_shop_lists_show_ratings_and_home_shows_todays_offers(): void
    {
        $shop = $this->shop();
        $other = $this->shop(['name' => 'مطعم']);
        $offer = $this->product($shop, ['discount_price' => 25]);
        $this->product($shop, ['name' => 'بلا عرض']);
        $this->product($this->shop(['is_accepting_orders' => false]), ['discount_price' => 10, 'name' => 'مسكّر']);
        $order = FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'R-1', 'customer_name' => 'x', 'customer_phone' => '0599000111', 'subtotal' => 30, 'total' => 30, 'status' => 'completed']);
        ShopReview::create(['shop_id' => $shop->id, 'front_order_id' => $order->id, 'rating' => 4, 'is_visible' => true]);

        $home = $this->getJson('/api/v1/home')->assertOk();
        $this->assertSame([$offer->id], collect($home->json('offers'))->pluck('id')->all());
        $this->assertSame('حلويات', $home->json('offers.0.shop_name'));
        $cards = collect($home->json('featured_shops'))->keyBy('id');
        $this->assertEquals(['average' => 4, 'count' => 1], $cards[$shop->id]['rating']);
        $this->assertEquals(['average' => null, 'count' => 0], $cards[$other->id]['rating']);
        $this->assertEquals(4, collect($this->getJson('/api/v1/shops')->json('data'))->firstWhere('id', $shop->id)['rating']['average']);
    }

    public function test_home_types_carry_a_few_shop_names_and_logos_for_the_bubbles(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->shop(['name' => "حلويات {$i}"]);
        }
        $this->shop(['catalog_type' => 'clothing', 'name' => 'ستايل', 'logo' => 'shops/logos/style.png']);

        $types = collect($this->getJson('/api/v1/home')->assertOk()->json('types'))->keyBy('key');
        $this->assertCount(6, $types['sweets']['shops']);
        $this->assertSame('حلويات 7', $types['sweets']['shops'][0]['name']);
        $this->assertSame('ستايل', $types['clothing']['shops'][0]['name']);
        $this->assertStringContainsString('style.png', $types['clothing']['shops'][0]['logo']);
    }

    public function test_followers_get_one_notification_when_a_shop_puts_something_on_offer(): void
    {
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $this->app->instance(FirebaseMessagingService::class, $firebase);
        $shop = $this->shop();
        $fan = $this->customer();
        PushDevice::create(['user_id' => $fan->id, 'token' => 'fan-token', 'platform' => 'android']);
        $h = $this->auth($fan);

        $this->getJson("/api/v1/shops/{$shop->id}/follow", $h)->assertOk()->assertJson(['following' => false, 'followers' => 0]);
        $this->postJson("/api/v1/shops/{$shop->id}/follow", ['follow' => true], $h)->assertOk()->assertJson(['following' => true, 'followers' => 1]);
        $this->postJson("/api/v1/shops/{$shop->id}/follow", ['follow' => true], $h)->assertJson(['followers' => 1]);
        $this->assertSame([$shop->id], collect($this->getJson('/api/v1/me/following', $h)->json('data'))->pluck('id')->all());

        $firebase->shouldReceive('sendToTokens')->once()
            ->withArgs(fn ($tokens, $title, $body, $url, $data) => collect($tokens)->all() === ['fan-token'] && str_contains($body, '25') && $data['type'] === 'shop_offer')
            ->andReturn(1);
        $product = $this->product($shop);
        $product->update(['name' => 'كنافة ناعمة']); // no offer: nothing sent
        $product->update(['discount_price' => 25]); // offer starts: sent
        $this->product($shop, ['discount_price' => 20]); // another offer within 6 hours: not sent again

        $this->postJson("/api/v1/shops/{$shop->id}/follow", ['follow' => false], $h)->assertJson(['following' => false, 'followers' => 0]);
        $this->postJson("/api/v1/shops/{$shop->id}/follow", ['follow' => true])->assertUnauthorized();
    }

    public function test_an_invited_friend_and_the_inviter_get_points_once_after_the_first_completed_order(): void
    {
        app(LoyaltyPoints::class)->saveSettings(true, 50, 3);
        config(['services.invites.points' => 50]);
        $inviter = $this->customer(['name' => 'رنا']);
        $invite = $this->getJson('/api/v1/me/invite', $this->auth($inviter))->assertOk()->json('invite');
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', $invite['code']);
        $this->assertSame([true, 50, 0], [$invite['enabled'], $invite['points'], $invite['joined']]);

        // The friend signs up in the app with the code (any letter case).
        $this->postJson('/api/v1/auth/register', ['name' => 'سامي', 'email' => 'sami@example.test', 'password' => 'Secret-Pass-123', 'invite_code' => strtolower($invite['code'])])->assertCreated();
        $friend = User::where('email', 'sami@example.test')->sole();
        $this->assertSame($inviter->id, $friend->invited_by);

        $shop = $this->shop();
        $order = FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $friend->id, 'order_number' => 'I-1', 'customer_name' => 'سامي', 'customer_phone' => '0599000112', 'subtotal' => 40, 'total' => 40, 'status' => 'new']);
        $this->assertSame(0, LoyaltyPointEntry::where('type', 'invite')->count());
        $order->update(['status' => 'completed']);
        $second = FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $friend->id, 'order_number' => 'I-2', 'customer_name' => 'سامي', 'customer_phone' => '0599000112', 'subtotal' => 40, 'total' => 40, 'status' => 'new']);
        $second->update(['status' => 'completed']);

        $this->assertSame(50, (int) LoyaltyPointEntry::where('type', 'invite')->where('user_id', $inviter->id)->sum('points'));
        $this->assertSame(50, (int) LoyaltyPointEntry::where('type', 'invite')->where('user_id', $friend->id)->sum('points'));
        $this->assertSame(1, $this->getJson('/api/v1/me/invite', $this->auth($inviter))->json('invite.rewarded'));

        // Own code, unknown code: nobody is linked.
        $this->postJson('/api/v1/auth/register', ['name' => 'x', 'email' => 'x@example.test', 'password' => 'Secret-Pass-123', 'invite_code' => 'NOPE99'])->assertCreated();
        $this->assertNull(User::where('email', 'x@example.test')->value('invited_by'));
    }

    public function test_the_invite_link_remembers_the_code_for_the_website_sign_up(): void
    {
        $inviter = $this->customer();
        $code = app(\App\Services\Invites::class)->codeFor($inviter);
        $this->get('/invite/'.$code)->assertRedirect()->assertCookie(\App\Services\Invites::COOKIE, $code);
        $this->get('/invite/ZZZZZZ')->assertRedirect()->assertCookieMissing(\App\Services\Invites::COOKIE);
    }

    public function test_owners_get_a_nightly_summary_of_todays_orders(): void
    {
        $shop = $this->shop(['name' => 'مطعم الديمو']);
        $quiet = $this->shop();
        PushDevice::create(['user_id' => $shop->user_id, 'token' => 'owner-token', 'platform' => 'android']);
        PushDevice::create(['user_id' => $quiet->user_id, 'token' => 'quiet-token', 'platform' => 'android']);
        foreach ([[50, 'new'], [70.5, 'completed'], [99, 'cancelled']] as $i => [$total, $status]) {
            FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'D-'.$i, 'customer_name' => 'x', 'customer_phone' => '0599000111', 'subtotal' => $total, 'total' => $total, 'status' => $status]);
        }
        $old = FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'D-old', 'customer_name' => 'x', 'customer_phone' => '0599000111', 'subtotal' => 10, 'total' => 10, 'status' => 'new']);
        $old->forceFill(['created_at' => now()->subDays(2)])->save();

        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->once()
            ->withArgs(fn ($tokens, $title, $body) => collect($tokens)->all() === ['owner-token'] && str_contains($title, 'مطعم الديمو') && str_contains($body, '2') && str_contains($body, '120.5'))
            ->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);
        $this->artisan('merchant:daily-summary')->assertSuccessful();
    }
}
