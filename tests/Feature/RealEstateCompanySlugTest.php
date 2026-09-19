<?php

namespace Tests\Feature;

use App\Models\RealEstateProperty;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealEstateCompanySlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_slug_follows_its_name_and_old_public_links_redirect(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Old Company',
            'slug' => 'old-company-link',
            'catalog_type' => 'real_estate',
            'is_active' => true,
        ]);
        $property = RealEstateProperty::create([
            'shop_id' => $shop->id,
            'slug' => 'family-home',
            'purpose' => 'sale',
            'property_type' => 'house',
            'title' => 'Family Home',
            'price' => 500000,
            'city' => 'Ramallah',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $oldCompanyUrl = route('real-estate.company', $shop);
        $oldPropertyUrl = route('real-estate.property', [$shop, $property]);
        $oldQrUrl = route('real-estate.company.qr', $shop);

        $this->actingAs($admin)
            ->put(route('shops.update', $shop), [
                'name' => 'קבוצת אמיר מבנים יבילים',
                'slug' => 'old-company-link',
                'catalog_type' => 'real_estate',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('shops'));

        $shop->refresh();
        $this->assertSame('קבוצת-אמיר-מבנים-יבילים', $shop->slug);
        $this->assertDatabaseHas('shop_slug_redirects', [
            'shop_id' => $shop->id,
            'slug' => 'old-company-link',
        ]);

        $this->get($oldCompanyUrl)->assertRedirect($shop->publicUrl())->assertStatus(301);
        $this->get($oldPropertyUrl)->assertRedirect($property->fresh()->publicUrl())->assertStatus(301);
        $this->get($oldQrUrl)->assertRedirect(route('real-estate.company.qr', $shop))->assertStatus(301);
        $this->get($shop->publicUrl())->assertOk()->assertSee('קבוצת אמיר מבנים יבילים');
    }

    public function test_explicitly_edited_company_slug_is_respected(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Old Company',
            'slug' => 'old-company',
            'catalog_type' => 'real_estate',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('shops.update', $shop), [
                'name' => 'New Company Name',
                'slug' => 'custom-company-address',
                'catalog_type' => 'real_estate',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('custom-company-address', $shop->fresh()->slug);
    }
}
