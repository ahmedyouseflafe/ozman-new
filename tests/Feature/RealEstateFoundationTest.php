<?php

namespace Tests\Feature;

use App\Mail\RealEstatePropertyAlertMail;
use App\Models\RealEstateAlert;
use App\Models\RealEstateAlertDelivery;
use App\Models\RealEstateLead;
use App\Models\RealEstateProperty;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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

    public function test_property_social_preview_contains_its_main_specifications(): void
    {
        $company = $this->company('social-preview');
        $property = $this->property($company, 'social-home', 'Social Home');
        $property->update([
            'purpose' => 'sale',
            'area' => 165,
            'rooms' => 4,
            'bathrooms' => 2,
            'floor' => 3,
        ]);
        $this->withSession(['locale' => 'ar'])
            ->withHeader('User-Agent', 'facebookexternalhit/1.1')
            ->get($property->publicUrl())
            ->assertOk()
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('property="og:title" content="للبيع: Social Home — 2,700 ILS"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('165 م²', false)
            ->assertSee('4.0 غرف', false)
            ->assertSee('2 حمامات', false);
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

    public function test_market_ajax_request_returns_replaceable_filtered_results(): void
    {
        $company = $this->company('ajax-filters');
        $matching = $this->property($company, 'ajax-match', 'AJAX Matching Home');
        $matching->update([
            'purpose' => 'sale',
            'property_type' => 'villa',
            'city' => 'Ramallah',
            'price' => 350000,
            'parking_spaces' => 1,
        ]);
        $this->property($company, 'ajax-wrong', 'AJAX Wrong Home');

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('real-estate.company', [
                'shop' => $company,
                'purpose' => 'sale',
                'property_type' => 'villa',
                'city' => 'Ramallah',
                'min_price' => 300000,
                'max_price' => 400000,
                'parking' => 1,
            ]))
            ->assertOk()
            ->assertSee('id="market-results"', false)
            ->assertSee('id="market-map-data"', false)
            ->assertSee('AJAX Matching Home')
            ->assertDontSee('AJAX Wrong Home');
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

    public function test_matching_property_alert_is_sent_by_email_once(): void
    {
        Mail::fake();
        $company = $this->company('email-delivery');
        $company->update(['name' => 'البرنس للعقارات', 'email' => 'company@example.test']);
        $alert = RealEstateAlert::create(['shop_id' => $company->id, 'name' => 'Email User', 'channel' => 'email', 'email' => 'alerts@example.test', 'filters' => ['city' => 'Nazareth', 'purpose' => 'rent'], 'locale' => 'ar']);
        $property = $this->property($company, 'email-match', 'Email Match');

        $this->artisan('real-estate:send-alerts')->assertSuccessful();
        $this->artisan('real-estate:send-alerts')->assertSuccessful();

        Mail::assertSent(RealEstatePropertyAlertMail::class, function (RealEstatePropertyAlertMail $mail): bool {
            $envelope = $mail->envelope();

            return $envelope->from?->address === config('mail.from.address')
                && $envelope->from?->name === 'البرنس للعقارات عبر Ozman'
                && $envelope->replyTo[0]?->address === 'company@example.test';
        });
        Mail::assertSent(RealEstatePropertyAlertMail::class, 1);
        $this->assertDatabaseHas('real_estate_alert_deliveries', ['real_estate_alert_id' => $alert->id, 'real_estate_property_id' => $property->id, 'status' => 'sent', 'attempts' => 1]);
    }

    public function test_matching_property_alert_uses_whatsapp_cloud_template(): void
    {
        config()->set('services.whatsapp_cloud', ['token' => 'test-token', 'phone_number_id' => '12345', 'graph_version' => 'v23.0', 'property_alert_template' => 'ozman_property_alert', 'template_language' => 'ar', 'default_country_code' => '972']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);
        $company = $this->company('whatsapp-delivery');
        $alert = RealEstateAlert::create(['shop_id' => $company->id, 'name' => 'WhatsApp User', 'channel' => 'whatsapp', 'phone' => '0599000000', 'filters' => ['city' => 'Nazareth', 'purpose' => 'rent'], 'locale' => 'ar']);
        $property = $this->property($company, 'whatsapp-match', 'WhatsApp Match');

        $this->artisan('real-estate:send-alerts')->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v23.0/12345/messages'
            && $request['to'] === '972599000000'
            && $request['template']['name'] === 'ozman_property_alert');
        $this->assertSame('wamid.test', RealEstateAlertDelivery::where('real_estate_alert_id', $alert->id)->value('provider_reference'));
        $this->assertSame('sent', RealEstateAlertDelivery::where('real_estate_property_id', $property->id)->value('status'));
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
