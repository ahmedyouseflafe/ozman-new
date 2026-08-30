<?php

namespace Tests\Feature;

use App\Models\RealEstateAlert;
use App\Models\RealEstateLead;
use App\Models\RealEstateProperty;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealEstateFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_estate_company_has_its_own_public_url_and_catalog_type(): void
    {
        $company = $this->company('alpha');

        $this->assertArrayHasKey('real_estate', config('catalog_types'));
        $this->assertSame(route('real-estate.company', $company), $company->publicUrl());

        $this->get(route('front.shop.slug', $company))
            ->assertRedirect(route('real-estate.company', $company));
        $this->get($company->publicUrl())
            ->assertOk()
            ->assertSee($company->name);
    }

    public function test_company_page_only_lists_its_own_published_properties(): void
    {
        $alpha = $this->company('alpha');
        $beta = $this->company('beta');
        $alphaProperty = $this->property($alpha, 'alpha-home', 'Alpha Home');
        $this->property($beta, 'beta-home', 'Beta Home');
        $this->property($alpha, 'draft-home', 'Draft Home', 'draft');

        $this->get(route('real-estate.company', $alpha))
            ->assertOk()
            ->assertSee('Alpha Home')
            ->assertDontSee('Beta Home')
            ->assertDontSee('Draft Home')
            ->assertSee(route('real-estate.property', [$alpha, $alphaProperty]), false);
        $this->assertSame(route('real-estate.property', [$alpha, $alphaProperty]), $alphaProperty->publicUrl());

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee($alphaProperty->publicUrl(), false)
            ->assertDontSee(route('real-estate.property', [$alpha, $alpha->realEstateProperties()->where('slug', 'draft-home')->first()]), false);
    }

    public function test_property_cannot_be_opened_through_another_company_url(): void
    {
        $alpha = $this->company('alpha');
        $beta = $this->company('beta');
        $property = $this->property($alpha, 'private-scope', 'Scoped Property');

        $this->get(route('real-estate.property', [$alpha, $property]))->assertOk();
        $this->get(route('real-estate.property', [$beta, $property]))->assertNotFound();
    }

    public function test_leads_are_owned_by_the_company_and_optionally_linked_to_a_property(): void
    {
        $company = $this->company('alpha');
        $property = $this->property($company, 'lead-home', 'Lead Home');
        $lead = RealEstateLead::create([
            'shop_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Interested User',
            'phone' => '0500000000',
        ]);

        $this->assertTrue($lead->shop->is($company));
        $this->assertTrue($lead->property->is($property));
        $this->assertTrue($company->realEstateLeads->contains($lead));
    }

    public function test_market_filters_properties_without_leaking_drafts_or_other_catalogs(): void
    {
        $company = $this->company('filters');
        $matching = $this->property($company, 'matching-villa', 'Matching Villa');
        $matching->update([
            'purpose' => 'sale',
            'property_type' => 'villa',
            'city' => 'Ramallah',
            'price' => 400000,
            'area' => 240,
            'rooms' => 4,
            'has_garden' => true,
        ]);
        $this->property($company, 'wrong-home', 'Wrong Home');
        $this->property($company, 'hidden-villa', 'Hidden Villa', 'draft')->update([
            'purpose' => 'sale',
            'property_type' => 'villa',
            'city' => 'Ramallah',
            'has_garden' => true,
        ]);

        $this->get(route('real-estate.index', [
            'purpose' => 'sale',
            'property_type' => 'villa',
            'city' => 'Ramallah',
            'min_area' => 200,
            'garden' => 1,
        ]))
            ->assertOk()
            ->assertSee('Matching Villa')
            ->assertDontSee('Wrong Home')
            ->assertDontSee('Hidden Villa');
    }

    public function test_property_inquiry_is_saved_for_the_property_company(): void
    {
        $company = $this->company('inquiry');
        $property = $this->property($company, 'viewing-home', 'Viewing Home');

        $this->post(route('real-estate.inquiries.store', [$company, $property]), [
            'name' => 'Ahmad Buyer',
            'phone' => '0599000000',
            'email' => 'buyer@example.test',
            'viewing_at' => now()->addDay()->toDateTimeString(),
            'source' => 'website',
        ])->assertRedirect();

        $this->assertDatabaseHas('real_estate_leads', [
            'shop_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Ahmad Buyer',
            'phone' => '0599000000',
        ]);
    }

    public function test_search_alert_keeps_only_supported_filters(): void
    {
        $company = $this->company('alerts');

        $this->post(route('real-estate.alerts.store'), [
            'shop_id' => $company->id,
            'name' => 'Search User',
            'channel' => 'email',
            'email' => 'search@example.test',
            'filters' => [
                'city' => 'Nazareth',
                'purpose' => 'rent',
                'unexpected' => 'must-not-be-saved',
            ],
        ])->assertRedirect();

        $alert = RealEstateAlert::firstOrFail();
        $this->assertSame($company->id, $alert->shop_id);
        $this->assertSame(['city' => 'Nazareth', 'purpose' => 'rent'], $alert->filters);
    }

    public function test_comparison_only_shows_published_properties_from_active_real_estate_companies(): void
    {
        $company = $this->company('compare');
        $first = $this->property($company, 'compare-one', 'Compare One');
        $draft = $this->property($company, 'compare-draft', 'Compare Draft', 'draft');

        $this->get(route('real-estate.compare', ['ids' => $first->id.','.$draft->id]))
            ->assertOk()
            ->assertSee('Compare One')
            ->assertDontSee('Compare Draft');
    }

    private function company(string $suffix): Shop
    {
        $owner = User::factory()->create(['email' => $suffix.'@real-estate.test']);

        return Shop::create([
            'user_id' => $owner->id,
            'name' => ucfirst($suffix).' Real Estate',
            'slug' => $suffix.'-real-estate',
            'catalog_type' => 'real_estate',
            'is_active' => true,
        ]);
    }

    private function property(Shop $shop, string $slug, string $title, string $status = 'published'): RealEstateProperty
    {
        return RealEstateProperty::create([
            'shop_id' => $shop->id,
            'slug' => $slug,
            'purpose' => 'rent',
            'property_type' => 'apartment',
            'title' => $title,
            'price' => 2700,
            'city' => 'Nazareth',
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }
}
