<?php

namespace Tests\Feature;

use App\Models\{ApiToken, LoyaltyPointEntry, Shop, StorePointCode, User};
use App\Services\LoyaltyPoints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Points for purchases in the shop: a one-time QR at the till. */
class StorePointsTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Grill', 'slug' => 'grill-'.uniqid(), 'catalog_type' => 'restaurant', 'is_active' => true]);

        return [$shop, $owner];
    }

    private function headers(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    public function test_the_shop_makes_a_code_and_the_customer_gets_the_points_once(): void
    {
        [$shop, $owner] = $this->shop();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Lina Saleh']);
        $other = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        // Default rule: every 100 ₪ = 10 points.
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 50], $this->headers($owner))->assertStatus(422);
        $code = $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 250], $this->headers($owner))
            ->assertCreated()->assertJsonPath('code.points', 20)->assertJsonPath('code.status', 'open')
            ->json('code');
        $this->assertNotEmpty($code['qr']);
        $this->assertSame(strlen($code['qr'][0]), count($code['qr']), 'a square QR');
        $this->assertStringContainsString('/points/claim/', $code['url']);

        // Scanned in the app: the whole link or just the token both work.
        $this->postJson('/api/v1/points/claim', ['token' => $code['url']], $this->headers($customer))
            ->assertOk()->assertJsonPath('points', 20)->assertJsonPath('shop.name', 'Grill')->assertJsonPath('balance', 20);
        $this->postJson('/api/v1/points/claim', ['token' => $code['url']], $this->headers($customer))->assertStatus(422);
        $this->postJson('/api/v1/points/claim', ['token' => $code['url']], $this->headers($other))->assertStatus(422);

        $entry = LoyaltyPointEntry::where('user_id', $customer->id)->sole();
        $this->assertSame([LoyaltyPointEntry::TYPE_STORE, LoyaltyPointEntry::CONFIRMED, $shop->id, 20], [$entry->type, $entry->status, $entry->shop_id, $entry->points]);
        $this->assertSame(20, app(LoyaltyPoints::class)->shopBalance($customer, $shop), 'counted as this shop\'s points');
        // The till sees who got them (first name only).
        $this->getJson("/api/v1/merchant/store-points/{$code['id']}", $this->headers($owner))
            ->assertOk()->assertJsonPath('code.status', 'claimed')->assertJsonPath('code.claimed_by', 'Lina')->assertJsonMissingPath('code.qr');
    }

    public function test_codes_expire_can_be_cancelled_and_only_the_shop_sees_them(): void
    {
        [$shop, $owner] = $this->shop();
        [, $stranger] = $this->shop();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $first = $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 100], $this->headers($owner))->json('code');
        $second = $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 100], $this->headers($owner))->json('code');

        $this->getJson("/api/v1/merchant/store-points/{$first['id']}", $this->headers($stranger))->assertForbidden();
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 100], $this->headers($stranger))->assertForbidden();

        $this->deleteJson("/api/v1/merchant/store-points/{$first['id']}", [], $this->headers($owner))->assertJsonPath('code.status', 'cancelled');
        $this->postJson('/api/v1/points/claim', ['token' => $first['url']], $this->headers($customer))->assertStatus(422);

        $this->travel(11)->minutes();
        $this->postJson('/api/v1/points/claim', ['token' => $second['url']], $this->headers($customer))
            ->assertStatus(422)->assertJsonPath('errors.token.0', 'خلص وقت الكود. اطلب من المحل كود جديد.');
        $this->assertSame(0, LoyaltyPointEntry::count());
    }

    public function test_limits_stop_self_points_and_farming(): void
    {
        [$shop, $owner] = $this->shop();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $make = fn () => $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 100], $this->headers($owner))->json('code.url');

        // The owner can't scan their own code (and isn't a customer account anyway).
        $this->postJson('/api/v1/points/claim', ['token' => $make()], $this->headers($owner))->assertStatus(422);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 5001], $this->headers($owner))->assertStatus(422);

        foreach (range(1, 3) as $i) {
            $this->postJson('/api/v1/points/claim', ['token' => $make()], $this->headers($customer))->assertOk();
        }
        $this->postJson('/api/v1/points/claim', ['token' => $make()], $this->headers($customer))->assertStatus(422);
        $this->assertSame(30, app(LoyaltyPoints::class)->balance($customer));

        // A shop with points switched off can't make codes.
        $shop->update(['loyalty_enabled' => false]);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/store-points", ['amount' => 100], $this->headers($owner))->assertStatus(422);
    }

    public function test_the_phone_camera_opens_the_website_which_signs_in_then_adds_the_points(): void
    {
        [$shop, $owner] = $this->shop();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $code = app(\App\Services\StorePoints::class)->create($shop, $owner, 120);

        $this->get($code->url())->assertRedirect(route('community.login'));
        $this->actingAs($customer)->get($code->url())->assertOk()->assertSee('انضافت 10 نقطة')->assertSee('Grill');
        $this->actingAs($customer)->get($code->url())->assertStatus(422)->assertSee('أخذت نقاط هالكود من قبل.');

        // The app opens the same link as a "store points" screen.
        $this->getJson('/api/v1/links/resolve?url='.urlencode($code->url()))->assertOk()
            ->assertJsonPath('target.type', 'store_points')->assertJsonPath('target.token', $code->token);
    }
}
