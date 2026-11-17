<?php

namespace Tests\Feature\Api;

use App\Models\{Advertisement, ApiToken, MainScreen, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The shop's ads and page screens in the app, through the dashboard's own code. */
class MobileShopAdsApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function shop(User $owner): Shop
    {
        return Shop::create(['user_id' => $owner->id, 'name' => 'Shop', 'slug' => 's-'.uniqid(), 'catalog_type' => 'clothing', 'is_active' => true]);
    }

    public function test_the_owner_manages_ads_and_screens_and_strangers_cannot(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop($owner);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/ads", $h)->assertOk()->assertJsonPath('ads', [])->assertJsonPath('screens', [])
            ->assertJsonPath('display_url', route('display.shop', $shop));

        $this->post("/api/v1/merchant/shops/{$shop->id}/ads", ['title' => 'خصم 20%', 'type' => 'image'], $h)->assertStatus(422)->assertJsonValidationErrors('media_file');
        $ad = $this->post("/api/v1/merchant/shops/{$shop->id}/ads", ['title' => 'خصم 20%', 'description' => 'على كل الفساتين', 'type' => 'image',
            'media_file' => UploadedFile::fake()->image('ad.jpg'), 'duration' => 8, 'is_active' => '1'], $h)
            ->assertCreated()->assertJsonPath('ad.is_active', true)->assertJsonPath('ad.duration', 8)->json('ad');
        $this->assertStringContainsString('/storage/ads/images/', $ad['media_url']);
        $this->post("/api/v1/merchant/ads/{$ad['id']}", ['title' => 'خصم 30%', 'type' => 'youtube', 'media' => 'https://youtu.be/abc', 'is_active' => '0'], $h)
            ->assertOk()->assertJsonPath('ad.title', 'خصم 30%')->assertJsonPath('ad.media_url', 'https://youtu.be/abc')->assertJsonPath('ad.is_active', false);

        $screen = $this->post("/api/v1/merchant/shops/{$shop->id}/screens", ['title' => 'بانر الصيف', 'type' => 'image', 'placement' => 'bottom',
            'media_file' => UploadedFile::fake()->image('s.jpg'), 'is_active' => '1'], $h)->assertCreated()->assertJsonPath('screen.placement', 'bottom')->json('screen');
        $this->post("/api/v1/merchant/screens/{$screen['id']}", ['title' => 'بانر الصيف ٢', 'type' => 'image', 'placement' => 'top', 'is_active' => '1'], $h)
            ->assertOk()->assertJsonPath('screen.title', 'بانر الصيف ٢')->assertJsonPath('screen.placement', 'top');
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/ads", $h)->assertJsonCount(1, 'ads')->assertJsonCount(1, 'screens');

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->shop($stranger);
        $sh = $this->auth($stranger);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/ads", $sh)->assertForbidden();
        $this->post("/api/v1/merchant/ads/{$ad['id']}", ['title' => 'x', 'type' => 'youtube', 'media' => 'https://youtu.be/x'], $sh)->assertForbidden();
        $this->deleteJson("/api/v1/merchant/screens/{$screen['id']}", [], $sh)->assertForbidden();
        $this->assertSame($shop->id, Advertisement::find($ad['id'])->shop_id);

        $this->deleteJson("/api/v1/merchant/ads/{$ad['id']}", [], $h)->assertOk();
        $this->deleteJson("/api/v1/merchant/screens/{$screen['id']}", [], $h)->assertOk();
        $this->assertSame(0, Advertisement::count() + MainScreen::count());
    }

    public function test_the_dashboard_no_longer_lets_another_owner_take_over_an_ad(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $ad = Advertisement::create(['shop_id' => $this->shop($owner)->id, 'title' => 'x', 'type' => 'youtube', 'media' => 'https://youtu.be/a', 'is_active' => true]);
        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->shop($stranger);
        $this->actingAs($stranger)->put(route('ads.update', $ad), ['title' => 'mine', 'type' => 'youtube', 'media' => 'https://youtu.be/b'])->assertForbidden();
        $this->assertSame('x', $ad->fresh()->title);
    }
}
