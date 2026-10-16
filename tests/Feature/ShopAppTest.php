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

        $this->get(route('shop-app.index', $shop))->assertRedirect(route('shop-app.install', $shop));
        $this->get(route('shop-app.install', $shop))
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
        $this->assertSame('/shop-app/'.$shop->slug.'/', $manifest['scope']);
        $this->assertCount(2, $manifest['icons']);
        $this->assertCount(1, $manifest['shortcuts']);

        $this->get(route('shop-app.launch', $shop))
            ->assertOk()->assertViewIs('front.restaurant_menu');
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
            ->get(route('shop-app.install', $shop))
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
        $this->get(route('shop-app.install', $shop))->assertNotFound();
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

    public function test_cosmetics_app_renders_its_store_inside_its_own_scope_even_for_owner(): void
    {
        $shop = $this->shop();
        $shop->update(['slug' => 'elegance-perfumes', 'catalog_type' => 'cosmetics']);

        $this->actingAs($shop->user)->get(route('shop-app.launch', $shop))
            ->assertOk()->assertViewIs('front.cosmetics_store')
            ->assertSee('data-shop-app-installer', false)
            ->assertSee(route('shop-app.manifest', $shop), false)
            ->assertDontSee(route('merchant-app.manifest', $shop), false);
    }

    public function test_customer_app_scopes_do_not_overlap_between_shops(): void
    {
        $shop = $this->shop();
        $other = $shop->replicate();
        $other->slug .= '-second';
        $other->save();

        $first = $this->get(route('shop-app.manifest', $shop))->assertOk()->json();
        $second = $this->get(route('shop-app.manifest', $other))->assertOk()->json();
        $this->assertNotSame($first['id'], $second['id']);
        $this->assertFalse(str_starts_with($second['scope'], $first['scope']));
        $this->assertStringStartsWith($first['scope'], $first['start_url']);
        $this->assertStringStartsWith($first['scope'], route('shop-app.install', $shop, false));
    }

    public function test_each_catalog_type_can_launch_inside_the_customer_app(): void
    {
        $shop = $this->shop();
        foreach (['restaurant', 'advertising_services', 'electronics', 'clothing', 'cosmetics', 'sweets', 'furniture_appliances', 'real_estate', 'general'] as $type) {
            $shop->update(['catalog_type' => $type]);
            $this->get(route('shop-app.launch', $shop))->assertOk()
                ->assertSee(route('shop-app.manifest', $shop), false);
        }
    }

    public function test_unreadable_shop_logo_does_not_break_installation_icons(): void
    {
        $shop = $this->shop();
        $shop->update(['logo' => 'images/logo.svg']);

        foreach ([192, 512] as $size) {
            $image = $this->get(route('merchant-app.icon', ['shop' => $shop, 'size' => $size]))
                ->assertOk()->assertHeader('Content-Type', 'image/png')->getContent();
            $dimensions = getimagesizefromstring($image);
            $this->assertSame([$size, $size], array_slice($dimensions, 0, 2));
        }
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
