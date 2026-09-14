<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class OfferPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_subscription_joins_one_global_offer_audience(): void
    {
        $firstShop = $this->shop('Sushi shop', 'offers-sushi');
        $secondShop = $this->shop('Phone shop', 'offers-phones');
        $payload = $this->subscriptionPayload($firstShop);

        $this->postJson(route('offers.push.store'), $payload)
            ->assertOk()
            ->assertJson(['registered' => true, 'audience' => 'all_offers']);

        $this->assertDatabaseHas('offer_push_subscriptions', [
            'user_id' => null,
            'source_shop_id' => $firstShop->id,
            'endpoint_hash' => hash('sha256', 'https://push.example.test/all-offers'),
        ]);

        $payload['source_shop_id'] = $secondShop->id;
        $this->postJson(route('offers.push.store'), $payload)->assertOk();

        $this->assertDatabaseCount('offer_push_subscriptions', 1);
        $this->assertDatabaseHas('offer_push_subscriptions', [
            'source_shop_id' => $secondShop->id,
            'endpoint_hash' => hash('sha256', 'https://push.example.test/all-offers'),
        ]);
    }

    public function test_public_shop_page_loads_the_global_offer_prompt(): void
    {
        $shop = $this->shop('Sushi shop', 'public-offer-prompt', 'restaurant');

        $this->get(route('restaurant.menu', $shop))
            ->assertOk()
            ->assertSee('window.OZMAN_OFFER_NOTIFICATIONS', false)
            ->assertSee(str_replace('/', '\\/', route('offers.push.store')), false)
            ->assertSee(asset('offer-notifications.js'), false);

        $this->get('/offer-notifications.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
    }

    public function test_publishing_a_story_broadcasts_it_to_the_global_audience(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('Sushi shop', 'story-global-offer', 'restaurant', $owner);
        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldReceive('sendOfferToAll')
            ->once()
            ->withArgs(fn ($notifiedShop, $title, $body, $url, $data) =>
                $notifiedShop->is($shop)
                && str_contains($title, $shop->name)
                && $body === 'Weekend sushi offer'
                && $url === $shop->publicUrl()
                && $data['type'] === 'shop_story'
            )
            ->andReturn(1);
        $this->app->instance(WebPushService::class, $webPush);

        $this->actingAs($owner)->post(route('shop-stories.store'), [
            'shop_id' => $shop->id,
            'caption' => 'Weekend sushi offer',
            'media' => UploadedFile::fake()->create('offer.mp4', 10, 'video/mp4'),
        ])->assertRedirect();
    }

    public function test_publishing_an_active_ad_broadcasts_it_to_the_global_audience(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $shop = $this->shop('Phone shop', 'ad-global-offer');
        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldReceive('sendOfferToAll')
            ->once()
            ->withArgs(fn ($notifiedShop, $title, $body, $url, $data) =>
                $notifiedShop->is($shop)
                && str_contains($title, $shop->name)
                && str_contains($body, 'New phone discount')
                && $url === $shop->publicUrl()
                && $data['type'] === 'advertisement'
            )
            ->andReturn(1);
        $this->app->instance(WebPushService::class, $webPush);

        $this->actingAs($admin)->post(route('ads.store'), [
            'shop_id' => $shop->id,
            'title' => 'New phone discount',
            'description' => 'Limited time offer',
            'type' => 'youtube',
            'media' => 'https://www.youtube.com/watch?v=test',
            'is_active' => '1',
        ])->assertRedirect(route('ads'));
    }

    private function shop(string $name, string $slug, string $catalogType = 'general', ?User $owner = null): Shop
    {
        $owner ??= User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        return Shop::create([
            'user_id' => $owner->id,
            'name' => $name,
            'slug' => $slug,
            'catalog_type' => $catalogType,
            'is_active' => true,
        ]);
    }

    private function subscriptionPayload(Shop $shop): array
    {
        return [
            'source_shop_id' => $shop->id,
            'subscription' => [
                'endpoint' => 'https://push.example.test/all-offers',
                'keys' => [
                    'p256dh' => str_repeat('a', 88),
                    'auth' => str_repeat('b', 24),
                ],
                'contentEncoding' => 'aes128gcm',
            ],
        ];
    }
}
