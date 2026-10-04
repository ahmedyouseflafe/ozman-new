<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Models\VisitorRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantCustomerWelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bankai_welcome_replaces_the_separate_offer_prompt_in_each_language(): void
    {
        $shop = $this->shop('bankai-sushi');
        foreach (['ar' => 'أهلًا فيك في بانكاي', 'en' => 'Welcome to Bankai', 'he' => 'ברוכים הבאים לבנקאי'] as $locale => $title) {
            $this->withSession(['locale' => $locale])->get(route('restaurant.menu', $shop))
                ->assertOk()->assertSee('id="bankaiWelcome"', false)
                ->assertSee($title)->assertSee('id="bankaiEnablePush"', false)
                ->assertSee('"showPrompt":false', false)
                ->assertSee('ozman.restaurant.'.$shop->id.'.customer.v1', false);
        }
    }

    public function test_other_restaurants_keep_their_existing_menu_and_notification_prompt(): void
    {
        $shop = $this->shop('other-restaurant');
        $this->get(route('restaurant.menu', $shop))->assertOk()
            ->assertDontSee('id="bankaiWelcome"', false)
            ->assertDontSee('id="bankaiProfileEdit"', false)
            ->assertSee('"showPrompt":true', false);
    }

    private function shop(string $slug): Shop
    {
        return Shop::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Bankai Sushi', 'slug' => $slug,
            'catalog_type' => 'restaurant', 'is_active' => true,
        ]);
    }

    public function test_registration_saves_customer_details_and_server_generated_map_to_dashboard(): void
    {
        $shop = $this->shop('bankai-sushi');
        $payload = $this->registration();
        $this->postJson(route('restaurant.customer-registration.store', $shop), [
            ...$payload, 'whatsapp' => '٠٥٩١٢٣٤٥٦٧', 'type' => 'merchant', 'status' => 'pending',
            'shop_id' => 999, 'map_link' => 'javascript:alert(1)',
        ])->assertOk()->assertExactJson(['registered' => true]);
        $registration = VisitorRegistration::sole();
        $this->assertSame($shop->id, $registration->shop_id);
        $this->assertSame('customer', $registration->type);
        $this->assertSame('0591234567', $registration->phone);
        $this->assertSame('https://www.google.com/maps?q=31.53,35.1', $registration->map_link);
        $this->assertNotSame($payload['registration_token'], $registration->public_token);
        $this->assertGuest();

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('visitor-registrations.index'))
            ->assertOk()->assertSee('Bankai Sushi')->assertSee('Test Customer')
            ->assertSee('0591234567')->assertSee('Hebron, Test Street')
            ->assertSee('https://wa.me/970591234567', false)->assertSee($registration->map_link, false)
            ->assertDontSee($registration->public_token, false);
    }

    public function test_retries_and_profile_edits_update_the_same_record_and_clear_deferred_location(): void
    {
        $shop = $this->shop('bankai-sushi');
        $url = route('restaurant.customer-registration.store', $shop);
        $payload = $this->registration();
        $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, [...$payload, 'name' => 'Updated Name', 'address' => 'New Address',
            'location_deferred' => true, 'latitude' => null, 'longitude' => null])->assertOk();
        $this->assertDatabaseCount('visitor_registrations', 1);
        $this->assertDatabaseHas('visitor_registrations', ['name' => 'Updated Name', 'residence_address' => 'New Address',
            'latitude' => null, 'longitude' => null, 'map_link' => null]);
    }

    public function test_same_phone_with_another_browser_secret_cannot_overwrite_customer_data(): void
    {
        $shop = $this->shop('bankai-sushi');
        $url = route('restaurant.customer-registration.store', $shop);
        $this->postJson($url, $this->registration())->assertOk();
        $original = VisitorRegistration::sole();
        $this->postJson($url, [...$this->registration(), 'registration_token' => str_repeat('b', 64), 'name' => 'Another Browser'])->assertOk();
        $this->assertSame('Test Customer', $original->fresh()->name);
        $this->assertDatabaseCount('visitor_registrations', 2);
        $this->getJson(route('visitor-registrations.status', $original->public_token))->assertNotFound();
    }

    public function test_invalid_registration_data_cannot_create_a_record(): void
    {
        $shop = $this->shop('bankai-sushi');
        $url = route('restaurant.customer-registration.store', $shop);
        foreach ([
            ['whatsapp' => 'letters0591234567'], ['whatsapp' => ['0591234567']],
            ['registration_token' => 'guessable'], ['latitude' => 91], ['longitude' => null],
            ['name' => ' '], ['address' => ''],
        ] as $invalid) {
            $this->postJson($url, [...$this->registration(), ...$invalid])->assertUnprocessable();
        }
        $this->assertDatabaseCount('visitor_registrations', 0);
    }

    public function test_registration_endpoint_is_limited_to_active_bankai_restaurant(): void
    {
        $other = $this->shop('other-restaurant');
        $this->postJson(route('restaurant.customer-registration.store', $other), $this->registration())->assertNotFound();
        $shop = $this->shop('bankai-sushi');
        $shop->update(['is_active' => false]);
        $this->postJson(route('restaurant.customer-registration.store', $shop), $this->registration())->assertNotFound();
        $shop->update(['is_active' => true, 'catalog_type' => 'cosmetics']);
        $this->postJson(route('restaurant.customer-registration.store', $shop), $this->registration())->assertNotFound();
        $this->assertDatabaseCount('visitor_registrations', 0);
    }

    public function test_dashboard_search_and_shop_filter_find_only_matching_registrations(): void
    {
        $shop = $this->shop('bankai-sushi');
        $this->postJson(route('restaurant.customer-registration.store', $shop), $this->registration())->assertOk();
        VisitorRegistration::create(['type' => 'customer', 'name' => 'General Customer', 'phone' => '0591234568',
            'residence_address' => 'Other Address', 'status' => 'approved']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));
        foreach ([['shop_id' => $shop->id], ['search' => 'Bankai'], ['search' => '0591234567']] as $filter) {
            $this->get(route('visitor-registrations.index', $filter))->assertOk()
                ->assertSee('Test Customer')->assertDontSee('General Customer');
        }
    }

    public function test_guests_and_unauthorized_employees_cannot_view_customer_registrations(): void
    {
        $this->get(route('visitor-registrations.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'employee', 'is_active' => true]))
            ->get(route('visitor-registrations.index'))->assertForbidden();
    }

    private function registration(): array
    {
        return ['registration_token' => str_repeat('a', 64), 'name' => 'Test Customer', 'whatsapp' => '0591234567',
            'address' => 'Hebron, Test Street', 'location_deferred' => false, 'latitude' => 31.53, 'longitude' => 35.1];
    }
}
