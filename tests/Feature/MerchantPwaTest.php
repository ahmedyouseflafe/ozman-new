<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_merchant_login_before_opening_installer(): void
    {
        $this->get(route('merchant-app.index'))
            ->assertRedirect(route('merchant.login', [
                'redirect' => route('merchant-app.index', absolute: false),
            ]));
    }

    public function test_installed_app_returns_to_its_shop_after_owner_login(): void
    {
        [$owner, $shop] = $this->ownerAndShop('Login shop', 'login-shop');
        $launchPath = route('merchant-app.launch', $shop, false);

        $this->get($launchPath)
            ->assertRedirect(route('merchant.login', ['redirect' => $launchPath]));

        $this->post(route('merchant.login.store'), [
            'email' => $owner->email,
            'password' => 'password',
            'redirect' => $launchPath,
        ])->assertRedirect($launchPath);

        $this->get($launchPath)->assertRedirect($shop->publicUrl());
    }

    public function test_owner_gets_a_branded_installer_and_launches_their_public_shop(): void
    {
        [$owner, $shop] = $this->ownerAndShop('Bankai sushi', 'bankai-pwa');

        $this->actingAs($owner)
            ->get(route('merchant-app.index'))
            ->assertOk()
            ->assertSee('تطبيق Bankai sushi')
            ->assertSee(route('merchant-app.manifest', $shop), false)
            ->assertSee(route('merchant-app.icon', ['shop' => $shop, 'size' => 512]), false);

        $this->get(route('merchant-app.launch', $shop))
            ->assertRedirect($shop->publicUrl());

        $this->assertSame($shop->id, session('merchant_shop_id'));
        $this->assertSame($shop->id, session('current_shop_id'));
    }

    public function test_an_owner_cannot_launch_or_subscribe_to_another_shop(): void
    {
        [$owner] = $this->ownerAndShop('First shop', 'first-shop');
        [, $otherShop] = $this->ownerAndShop('Other shop', 'other-shop');

        $this->actingAs($owner)
            ->get(route('merchant-app.launch', $otherShop))
            ->assertForbidden();

        $this->postJson(route('merchant-app.push.store'), $this->subscriptionPayload($otherShop))
            ->assertNotFound();

        $this->assertDatabaseCount('web_push_subscriptions', 0);
    }

    public function test_manifest_and_icons_are_generated_from_the_shop_identity(): void
    {
        [, $shop] = $this->ownerAndShop('Bankai sushi', 'bankai-manifest');

        $manifest = $this->get(route('merchant-app.manifest', $shop))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->json();

        $this->assertSame('Bankai sushi', $manifest['name']);
        $this->assertSame('/merchant-app/shops/'.$shop->id, $manifest['id']);
        $this->assertSame(route('merchant-app.launch', $shop, false), $manifest['start_url']);
        $this->assertCount(2, $manifest['icons']);

        $this->get(route('merchant-app.icon', ['shop' => $shop, 'size' => 192]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_push_subscription_is_bound_to_the_authenticated_owner_and_shop(): void
    {
        [$owner, $shop] = $this->ownerAndShop('Push shop', 'push-shop');

        $this->actingAs($owner)
            ->postJson(route('merchant-app.push.store'), $this->subscriptionPayload($shop))
            ->assertOk()
            ->assertJson(['registered' => true]);

        $this->assertDatabaseHas('web_push_subscriptions', [
            'user_id' => $owner->id,
            'shop_id' => $shop->id,
            'endpoint_hash' => hash('sha256', 'https://push.example.test/subscription-one'),
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('web_push_subscriptions', 0);
    }

    private function ownerAndShop(string $name, string $slug): array
    {
        $distributorOwner = User::create([
            'name' => $name.' distributor',
            'email' => $slug.'-distributor@example.com',
            'password' => 'password',
            'role' => 'distributor',
            'is_active' => true,
        ]);
        $distributorShop = Shop::create([
            'user_id' => $distributorOwner->id,
            'name' => $name.' distributor shop',
            'slug' => $slug.'-distributor-shop',
            'catalog_type' => 'general',
            'is_active' => true,
        ]);
        $distributor = Distributor::create([
            'shop_id' => $distributorShop->id,
            'user_id' => $distributorOwner->id,
            'name' => $name.' distributor',
            'is_active' => true,
        ]);
        $owner = User::create([
            'name' => $name.' owner',
            'email' => $slug.'@example.com',
            'password' => 'password',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $shop = Shop::create([
            'user_id' => $owner->id,
            'distributor_id' => $distributor->id,
            'name' => $name,
            'slug' => $slug,
            'catalog_type' => 'restaurant',
            'is_active' => true,
        ]);

        return [$owner, $shop];
    }

    private function subscriptionPayload(Shop $shop): array
    {
        return [
            'shop_id' => $shop->id,
            'subscription' => [
                'endpoint' => 'https://push.example.test/subscription-one',
                'keys' => [
                    'p256dh' => str_repeat('a', 88),
                    'auth' => str_repeat('b', 24),
                ],
                'contentEncoding' => 'aes128gcm',
            ],
        ];
    }
}
