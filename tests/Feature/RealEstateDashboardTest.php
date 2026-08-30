<?php

namespace Tests\Feature;

use App\Models\RealEstateLead;
use App\Models\RealEstateProperty;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RealEstateDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_estate_owner_is_redirected_to_and_can_open_his_dashboard(): void
    {
        [$owner, $shop] = $this->company('owner');

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertRedirect(route('real-estate.dashboard', $shop));

        $this->actingAs($owner)->get(route('real-estate.dashboard', $shop))
            ->assertOk()
            ->assertSee('إدارة عقارات '.$shop->name);
    }

    public function test_owner_cannot_access_or_mutate_another_company_properties(): void
    {
        [$owner, $shop] = $this->company('alpha');
        [, $otherShop] = $this->company('beta');
        $foreignProperty = $this->property($otherShop, 'foreign-home');

        $this->actingAs($owner)->get(route('real-estate.dashboard', $otherShop))->assertForbidden();
        $this->actingAs($owner)->get(route('real-estate.dashboard.properties.edit', [$shop, $foreignProperty]))->assertForbidden();
        $this->actingAs($owner)->delete(route('real-estate.dashboard.properties.destroy', [$shop, $foreignProperty]))->assertForbidden();
        $this->assertDatabaseHas('real_estate_properties', ['id' => $foreignProperty->id]);
    }

    public function test_owner_can_create_publish_and_upload_images_for_a_property(): void
    {
        Storage::fake('public');
        [$owner, $shop] = $this->company('create');

        $response = $this->actingAs($owner)->post(route('real-estate.dashboard.properties.store', $shop), [
            'reference' => 'REF-100',
            'title' => 'شقة جديدة في رام الله',
            'purpose' => 'sale',
            'property_type' => 'apartment',
            'price' => 350000,
            'currency' => 'ILS',
            'city' => 'Ramallah',
            'latitude' => 31.9038,
            'longitude' => 35.2034,
            'status' => 'published',
            'rooms' => 4,
            'area' => 160,
            'has_elevator' => 1,
            'images' => [UploadedFile::fake()->image('home.webp')],
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $property = RealEstateProperty::where('shop_id', $shop->id)->firstOrFail();
        $response->assertRedirect(route('real-estate.dashboard.properties.edit', [$shop, $property]));
        $this->assertSame('published', $property->status);
        $this->assertNotNull($property->published_at);
        $this->assertTrue($property->has_elevator);
        $this->assertCount(1, $property->images);
        $this->assertTrue($property->images->first()->is_cover);
        Storage::disk('public')->assertExists($property->images->first()->path);

        $this->actingAs($owner)
            ->get(route('real-estate.dashboard.properties.edit', [$shop, $property]))
            ->assertOk()
            ->assertSee('تجهيز منشور فيسبوك')
            ->assertSee('property-location-map', false)
            ->assertSee('موقعي الحالي')
            ->assertSee(route('real-estate.dashboard.facebook-images', [$shop, $property]), false);

        $this->get(route('real-estate.property', [$shop, $property]))
            ->assertOk()
            ->assertSee(url(Storage::url($property->images->first()->path)), false);

        $this->actingAs($owner)
            ->get(route('real-estate.dashboard.facebook-images', [$shop, $property, 'images' => [$property->images->first()->id]]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertDownload($property->slug.'-facebook-images.zip');
    }

    public function test_owner_can_update_a_lead_belonging_to_his_company_only(): void
    {
        [$owner, $shop] = $this->company('leads');
        [, $otherShop] = $this->company('other-leads');
        $lead = RealEstateLead::create(['shop_id' => $shop->id, 'name' => 'Customer', 'phone' => '0599000000']);
        $foreignLead = RealEstateLead::create(['shop_id' => $otherShop->id, 'name' => 'Foreign', 'phone' => '0500000000']);

        $this->actingAs($owner)->patch(route('real-estate.dashboard.leads.update', [$shop, $lead]), [
            'status' => 'viewing',
            'viewing_at' => now()->addDay()->toDateTimeString(),
        ])->assertRedirect();

        $this->assertSame('viewing', $lead->fresh()->status);
        $this->actingAs($owner)->patch(route('real-estate.dashboard.leads.update', [$shop, $foreignLead]), [
            'status' => 'lost',
        ])->assertForbidden();
        $this->assertSame('new', $foreignLead->fresh()->status);
    }

    public function test_non_real_estate_shop_cannot_open_real_estate_dashboard(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'General Shop',
            'slug' => 'general-shop',
            'catalog_type' => 'general',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->get(route('real-estate.dashboard', $shop))->assertNotFound();
    }

    private function company(string $suffix): array
    {
        $owner = User::factory()->create([
            'email' => $suffix.'@dashboard.test',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => ucfirst($suffix).' Real Estate',
            'slug' => $suffix.'-real-estate-dashboard',
            'catalog_type' => 'real_estate',
            'is_active' => true,
        ]);

        return [$owner, $shop];
    }

    private function property(Shop $shop, string $slug): RealEstateProperty
    {
        return RealEstateProperty::create([
            'shop_id' => $shop->id,
            'slug' => $slug,
            'purpose' => 'rent',
            'property_type' => 'apartment',
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'price' => 2700,
            'city' => 'Nazareth',
            'status' => 'draft',
        ]);
    }
}
