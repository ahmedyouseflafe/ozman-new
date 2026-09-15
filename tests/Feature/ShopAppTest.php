<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_installer_is_public_and_branded_for_the_shop(): void
    {
        $shop = $this->shop();

        $this->get(route('shop-app.index', $shop))
            ->assertOk()
            ->assertSee('تطبيق Bankai sushi')
            ->assertSee(route('shop-app.manifest', $shop), false)
            ->assertSee(route('merchant-app.icon', ['shop' => $shop, 'size' => 512]), false)
            ->assertSee(asset('shop-pwa.js'), false)
            ->assertSee('مشاركة رابط التطبيق');
    }

    public function test_customer_app_manifest_opens_the_public_shop_without_login(): void
    {
        $shop = $this->shop();

        $manifest = $this->get(route('shop-app.manifest', $shop))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->json();

        $this->assertSame('Bankai sushi', $manifest['name']);
        $this->assertSame('/shop-app/customers/'.$shop->id, $manifest['id']);
        $this->assertSame(route('shop-app.launch', $shop, false), $manifest['start_url']);
        $this->assertCount(2, $manifest['icons']);
        $this->assertCount(1, $manifest['shortcuts']);

        $this->get(route('shop-app.launch', $shop))
            ->assertRedirect($shop->publicUrl());
    }

    public function test_public_shop_page_is_installable_as_the_customer_app(): void
    {
        $shop = $this->shop();

        $this->get($shop->publicUrl())
            ->assertOk()
            ->assertSee(route('shop-app.manifest', $shop), false)
            ->assertSee(asset('shop-pwa.js'), false)
            ->assertDontSee(route('merchant-app.manifest', $shop), false);
    }

    public function test_owner_installer_stays_separate_and_links_to_customer_installer(): void
    {
        $shop = $this->shop();

        $this->actingAs($shop->user)
            ->get(route('merchant-app.index'))
            ->assertOk()
            ->assertSee(route('merchant-app.manifest', $shop), false)
            ->assertSee(route('shop-app.index', $shop), false)
            ->assertDontSee(route('shop-app.manifest', $shop), false);
    }

    public function test_customer_installer_uses_customer_manifest_even_when_owner_is_logged_in(): void
    {
        $shop = $this->shop();

        $this->actingAs($shop->user)
            ->get(route('shop-app.index', $shop))
            ->assertOk()
            ->assertSee(route('shop-app.manifest', $shop), false)
            ->assertSee(asset('shop-pwa.js'), false)
            ->assertDontSee(route('merchant-app.manifest', $shop), false)
            ->assertDontSee(asset('merchant-pwa.js'), false);
    }

    public function test_inactive_shop_cannot_publish_a_customer_app(): void
    {
        $shop = $this->shop();
        $shop->update(['is_active' => false]);

        $this->get(route('shop-app.index', $shop))->assertNotFound();
        $this->get(route('shop-app.manifest', $shop))->assertNotFound();
        $this->get(route('shop-app.launch', $shop))->assertNotFound();
    }

    public function test_customer_pwa_runtime_asset_is_available_on_split_public_hosting(): void
    {
        $this->assertFileExists(public_path('shop-pwa.js'));

        $this->get('/shop-pwa.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
    }

    private function shop(): Shop
    {
        $owner = User::create([
            'name' => 'Bankai owner',
            'email' => 'bankai-customer-app@example.com',
            'password' => 'password',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        return Shop::create([
            'user_id' => $owner->id,
            'name' => 'Bankai sushi',
            'slug' => 'bankai-customer-app',
            'catalog_type' => 'restaurant',
            'is_active' => true,
        ]);
    }
}
