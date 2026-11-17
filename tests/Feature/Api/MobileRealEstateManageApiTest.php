<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, ModularCategory, RealEstateProperty, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Companies manage properties (with photos) and services (with sections and options) in the app, through the dashboard's code. */
class MobileRealEstateManageApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function company(?User $owner = null): Shop
    {
        $owner ??= User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        return Shop::create(['user_id' => $owner->id, 'name' => 'شركة البيت', 'slug' => 'company-'.uniqid(), 'catalog_type' => 'real_estate', 'is_active' => true]);
    }

    private function property(array $extra = []): array
    {
        return ['purpose' => 'rent', 'property_type' => 'apartment', 'title' => 'شقة 3 غرف بالماصيون', 'price' => 3000, 'currency' => 'ILS',
            'city' => 'رام الله', 'latitude' => 31.9, 'longitude' => 35.2, 'status' => 'published', 'rooms' => 3, 'has_elevator' => '1',
            'amenities_text' => "تكييف\nمطبخ جديد", ...$extra];
    }

    public function test_the_company_adds_edits_and_removes_a_property_with_photos(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->company($owner);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/properties", $h)->assertOk()->assertJsonPath('data', [])
            ->assertJsonPath('options.purposes.0.key', 'rent');
        $this->post("/api/v1/merchant/shops/{$shop->id}/properties", $this->property(['title' => '']), $h)->assertStatus(422)->assertJsonValidationErrors('title');

        $property = $this->post("/api/v1/merchant/shops/{$shop->id}/properties", $this->property([
            'images' => [UploadedFile::fake()->image('a.jpg', 800, 600), UploadedFile::fake()->image('b.jpg', 800, 600)],
        ]), $h)->assertCreated()->assertJsonCount(2, 'property.images')->assertJsonPath('property.images.0.is_cover', true)
            ->assertJsonPath('property.has_elevator', true)->assertJsonPath('property.amenities_text', "تكييف\nمطبخ جديد")->json('property');
        $this->assertNotNull(RealEstateProperty::find($property['id'])->published_at);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/properties", $h)->assertJsonPath('data.0.images_count', 2)->assertJsonPath('data.0.status', 'published');

        // Edit, add a photo, make the second photo the cover, delete the first one.
        $this->post("/api/v1/merchant/properties/{$property['id']}", $this->property(['price' => 2800, 'status' => 'rented', 'title_en' => '3-room flat',
            'images' => [UploadedFile::fake()->image('c.jpg', 800, 600)]]), $h)->assertOk()->assertJsonPath('property.price', 2800)
            ->assertJsonPath('property.title_en', '3-room flat')->assertJsonCount(3, 'property.images');
        $second = $property['images'][1]['id'];
        $this->patchJson("/api/v1/merchant/property-images/$second/cover", [], $h)->assertOk()->assertJsonPath('property.images.1.is_cover', true);
        $this->deleteJson("/api/v1/merchant/property-images/{$property['images'][0]['id']}", [], $h)->assertOk()->assertJsonCount(2, 'property.images');

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->company($stranger);
        $this->getJson("/api/v1/merchant/properties/{$property['id']}", $this->auth($stranger))->assertForbidden();
        $this->post("/api/v1/merchant/properties/{$property['id']}", $this->property(), $this->auth($stranger))->assertForbidden();
        $this->deleteJson("/api/v1/merchant/property-images/$second", [], $this->auth($stranger))->assertForbidden();

        $this->deleteJson("/api/v1/merchant/properties/{$property['id']}", [], $h)->assertOk();
        $this->assertDatabaseMissing('real_estate_properties', ['id' => $property['id']]);

        // Only companies have properties.
        $sweets = Shop::create(['user_id' => $owner->id, 'name' => 'Sweets', 'slug' => 's-'.uniqid(), 'catalog_type' => 'sweets', 'is_active' => true]);
        $this->getJson("/api/v1/merchant/shops/{$sweets->id}/properties", $h)->assertNotFound();
    }

    public function test_the_company_manages_services_sections_and_options(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->company($owner);
        $h = $this->auth($owner);

        $this->postJson("/api/v1/merchant/shops/{$shop->id}/service-categories", ['name' => 'كرفانات', 'is_active' => true], $h)->assertCreated();
        $category = ModularCategory::where('shop_id', $shop->id)->firstOrFail();

        $groups = [['name' => 'المساحة', 'name_en' => 'Size', 'is_required' => '1', 'values' => [['name' => '20 متر', 'is_default' => '1'], ['name' => '30 متر']]]];
        $service = $this->post("/api/v1/merchant/shops/{$shop->id}/services", ['modular_category_id' => $category->id, 'name' => 'كرفان سكني',
            'short_description' => 'جاهز للسكن', 'is_active' => '1', 'option_groups' => $groups, 'cover_image' => UploadedFile::fake()->image('c.jpg', 600, 400),
            'images' => [UploadedFile::fake()->image('g.jpg', 600, 400)]], $h)->assertCreated()
            ->assertJsonPath('service.option_groups.0.name_en', 'Size')->assertJsonPath('service.option_groups.0.values.0.is_default', true)
            ->assertJsonCount(1, 'service.images')->json('service');
        $this->assertNotNull($service['cover']);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/services", $h)->assertOk()->assertJsonPath('categories.0.services.0.name', 'كرفان سكني');

        $groups[0]['values'][] = ['name' => '40 متر'];
        $this->post("/api/v1/merchant/services/{$service['id']}", ['modular_category_id' => $category->id, 'name' => 'كرفان سكني مطوّر', 'is_active' => '0',
            'option_groups' => $groups], $h)->assertOk()->assertJsonPath('service.is_active', false)->assertJsonCount(3, 'service.option_groups.0.values')
            ->assertJsonPath('service.option_groups.0.name_en', 'Size');
        $this->post("/api/v1/merchant/services/{$service['id']}", ['modular_category_id' => $category->id, 'name' => 'x', 'option_groups' => []], $h)->assertStatus(422);

        $this->deleteJson("/api/v1/merchant/service-images/{$service['images'][0]['id']}", [], $h)->assertOk()->assertJsonCount(0, 'service.images');
        $this->postJson("/api/v1/merchant/service-categories/{$category->id}", ['name' => 'كرفانات وبيوت متنقلة', 'is_active' => true], $h)->assertOk();
        $this->assertSame('كرفانات وبيوت متنقلة', $category->fresh()->name);

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->company($stranger);
        $this->getJson("/api/v1/merchant/services/{$service['id']}", $this->auth($stranger))->assertForbidden();
        $this->deleteJson("/api/v1/merchant/service-categories/{$category->id}", [], $this->auth($stranger))->assertForbidden();

        $this->deleteJson("/api/v1/merchant/services/{$service['id']}", [], $h)->assertOk();
        $this->deleteJson("/api/v1/merchant/service-categories/{$category->id}", [], $h)->assertOk();
        $this->assertSame(0, ModularCategory::where('shop_id', $shop->id)->count());
    }
}
