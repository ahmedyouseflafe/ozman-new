<?php

namespace Tests\Feature;

use App\Models\ModularServiceRequest;
use App\Models\Shop;
use App\Models\User;
use App\Services\ModularCatalogDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModularServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_bootstraps_the_modular_catalog_and_links_every_category(): void
    {
        [, $shop] = $this->company('catalog');

        $response = $this->withSession(['locale' => 'en'])->get(route('real-estate.company', $shop));

        $response->assertOk()
            ->assertSee('Mobile homes')
            ->assertSee('Bathroom units')
            ->assertSee('Custom builds');
        $this->assertCount(5, $shop->fresh()->modularCategories);
        foreach ($shop->modularCategories as $category) {
            $response->assertSee($category->publicUrl(), false);
            $this->assertTrue($category->services()->exists());
        }
    }

    public function test_customer_can_open_a_service_and_see_the_real_configuration_choices(): void
    {
        [, $shop] = $this->company('service-page');
        app(ModularCatalogDefaults::class)->ensure($shop);
        $category = $shop->modularCategories()->where('slug', 'bathroom-units')->firstOrFail();
        $service = $category->services()->firstOrFail();

        $this->withSession(['locale' => 'ar'])->get($category->publicUrl())
            ->assertOk()
            ->assertSee($service->name)
            ->assertSee($service->publicUrl(), false);

        $this->withSession(['locale' => 'ar'])->get($service->publicUrl())
            ->assertOk()
            ->assertSee('اختر المواصفات')
            ->assertSee('بدون قعادة')
            ->assertSee('سيراميك كامل')
            ->assertSee(route('real-estate.services.whatsapp', [$shop, $category, $service]), false)
            ->assertDontSee('السعر النهائي');
    }

    public function test_whatsapp_request_is_validated_saved_and_redirected_to_the_company(): void
    {
        [, $shop] = $this->company('whatsapp', ['whatsapp' => '059-900-1122']);
        app(ModularCatalogDefaults::class)->ensure($shop);
        $category = $shop->modularCategories()->where('slug', 'bathroom-units')->firstOrFail();
        $service = $category->services()->with('optionGroups.values')->firstOrFail();
        $options = $service->optionGroups->mapWithKeys(fn ($group) => [$group->id => $group->values->first()->id])->all();

        $response = $this->post(route('real-estate.services.whatsapp', [$shop, $category, $service]), [
            'customer_name' => 'أحمد علي',
            'customer_phone' => '0599999999',
            'city' => 'رام الله',
            'notes' => 'يرجى التواصل مساءً',
            'options' => $options,
        ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/972599001122?text=', $location);
        $this->assertStringContainsString(rawurlencode('المواصفات المختارة'), $location);
        $this->assertDatabaseHas('modular_service_requests', [
            'shop_id' => $shop->id,
            'modular_service_id' => $service->id,
            'customer_name' => 'أحمد علي',
            'status' => 'new',
        ]);
        $this->assertCount($service->optionGroups->count(), ModularServiceRequest::firstOrFail()->selections);
    }

    public function test_a_value_from_another_service_cannot_be_injected_into_the_request(): void
    {
        [, $shop] = $this->company('invalid-option', ['whatsapp' => '972599001122']);
        app(ModularCatalogDefaults::class)->ensure($shop);
        $services = $shop->modularCategories()->with('services.optionGroups.values')->get()->pluck('services')->flatten();
        $service = $services->first();
        $otherService = $services->skip(1)->first();
        $service->load('category', 'optionGroups.values');
        $options = $service->optionGroups->mapWithKeys(fn ($group) => [$group->id => $group->values->first()->id])->all();
        $options[$service->optionGroups->first()->id] = $otherService->optionGroups->first()->values->first()->id;

        $this->from($service->publicUrl())->post(route('real-estate.services.whatsapp', [$shop, $service->category, $service]), [
            'customer_name' => 'Invalid Customer',
            'customer_phone' => '0590000000',
            'options' => $options,
        ])->assertRedirect($service->publicUrl())->assertSessionHasErrors();

        $this->assertDatabaseCount('modular_service_requests', 0);
    }

    public function test_owner_can_create_a_service_with_custom_option_groups(): void
    {
        [$owner, $shop] = $this->company('dashboard');
        $this->actingAs($owner)->get(route('real-estate.dashboard', $shop))->assertOk();
        $category = $shop->fresh()->modularCategories()->firstOrFail();

        $response = $this->actingAs($owner)->post(route('real-estate.dashboard.services.store', $shop), [
            'modular_category_id' => $category->id,
            'name' => 'وحدة خاصة',
            'name_en' => 'Special unit',
            'short_description' => 'خدمة قابلة للتخصيص',
            'is_active' => '1',
            'is_featured' => '0',
            'option_groups' => [[
                'name' => 'نوع التشطيب',
                'name_en' => 'Finish',
                'help_text' => 'اختر التشطيب.',
                'is_required' => '1',
                'values' => [
                    ['name' => 'عادي', 'name_en' => 'Standard', 'is_default' => '1'],
                    ['name' => 'فاخر', 'name_en' => 'Premium', 'is_default' => '0'],
                ],
            ]],
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
        $service = $category->services()->where('name', 'وحدة خاصة')->firstOrFail();
        $response->assertRedirect(route('real-estate.dashboard.services.edit', [$shop, $service]));
        $this->assertDatabaseHas('modular_option_groups', ['modular_service_id' => $service->id, 'name' => 'نوع التشطيب']);
        $this->assertDatabaseHas('modular_option_values', ['name' => 'فاخر']);
    }

    private function company(string $suffix, array $attributes = []): array
    {
        $owner = User::factory()->create([
            'email' => $suffix.'@modular.test',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $shop = Shop::create(array_merge([
            'user_id' => $owner->id,
            'name' => ucfirst($suffix).' Modular Company',
            'slug' => $suffix.'-modular-company',
            'catalog_type' => 'real_estate',
            'is_active' => true,
        ], $attributes));

        return [$owner, $shop];
    }
}
