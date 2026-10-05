<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Models\VisitorRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantCustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_restaurant_owner_can_open_customers_from_dashboard_and_sidebar(): void
    {
        $shop = $this->restaurant('bankai-sushi');
        $shop->user->employeePermissions()->create(['permission' => 'restaurant.view']);
        $customer = $this->customer($shop, 'Bankai Customer');
        $this->actingAs($shop->user)->get(route('restaurant.dashboard', $shop))->assertOk()
            ->assertSee(route('restaurant.customers.index', $shop), false)->assertSee('العملاء المسجّلون');
        $this->get(route('restaurant.customers.index', $shop))->assertOk()
            ->assertSee('Bankai Customer')->assertSee('0591234567')->assertSee('Hebron address')
            ->assertSee('https://wa.me/970591234567', false)
            ->assertSee('https://www.google.com/maps?q=31.5300000,35.1000000', false)
            ->assertDontSee($customer->public_token, false);
        $this->assertDatabaseHas('employee_permissions', ['user_id' => $shop->user_id, 'permission' => 'restaurant.customers.view']);
        $this->get(route('visitor-registrations.index'))->assertForbidden();
    }

    public function test_customer_rows_counts_and_search_cannot_leak_other_shops_or_merchants(): void
    {
        $shop = $this->restaurant('bankai-sushi');
        $foreign = $this->restaurant('foreign');
        $this->customer($shop, 'Own Customer');
        $this->customer($foreign, 'Foreign Customer');
        $merchant = $this->customer($shop, 'Merchant Registration');
        $merchant->update(['type' => 'merchant']);
        $url = route('restaurant.customers.index', $shop);
        $this->actingAs($shop->user)->get($url)->assertOk()->assertViewHas('customersCount', 1)
            ->assertSee('Own Customer')->assertDontSee('Foreign Customer')->assertDontSee('Merchant Registration');
        $this->get($url.'?search=Foreign')->assertOk()->assertViewHas('customers', fn ($rows) => $rows->total() === 0)
            ->assertDontSee('Foreign Customer');
        $this->get($url.'?search=0591234567')->assertOk()->assertViewHas('customers', fn ($rows) => $rows->total() === 1);
        $this->get(route('restaurant.customers.index', $foreign))->assertForbidden();
        $this->get($url.'?shop_id='.$foreign->id)->assertForbidden();
        $this->get($url.'?search[]=bad')->assertSessionHasErrors('search');
    }

    public function test_guests_and_staff_without_customer_permission_cannot_open_customer_data(): void
    {
        $shop = $this->restaurant('bankai-sushi');
        $url = route('restaurant.customers.index', $shop);
        $this->get($url)->assertRedirect(route('login'));
        $employee = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee->employeePermissions()->create(['permission' => 'restaurant.view']);
        $this->actingAs($employee)->get($url)->assertForbidden();
        $employee->employeePermissions()->create(['permission' => 'restaurant.customers.view']);
        $this->actingAs($employee->fresh())->get($url)->assertForbidden(); // No access to this shop.
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->get($url)->assertOk();
    }

    public function test_empty_search_and_pagination_keep_the_restaurant_context(): void
    {
        $shop = $this->restaurant('bankai-sushi');
        $this->actingAs($shop->user)->get(route('restaurant.customers.index', $shop))
            ->assertOk()->assertSee('لم يسجّل أي عميل بعد.');
        for ($i = 1; $i <= 26; $i++) $this->customer($shop, 'Matching '.$i);
        $response = $this->get(route('restaurant.customers.index', ['shop' => $shop, 'search' => 'Matching']));
        $response->assertOk()->assertViewHas('customersCount', 26)
            ->assertViewHas('customers', fn ($rows) => $rows->count() === 25 && str_contains($rows->nextPageUrl(), 'search=Matching'));
        $this->get(route('restaurant.customers.index', ['shop' => $shop, 'search' => 'Matching', 'page' => 2]))
            ->assertOk()->assertViewHas('customers', fn ($rows) => $rows->count() === 1);
    }

    private function restaurant(string $slug): Shop
    {
        return Shop::create(['name' => $slug, 'slug' => $slug, 'catalog_type' => 'restaurant', 'is_active' => true,
            'user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id]);
    }

    private function customer(Shop $shop, string $name): VisitorRegistration
    {
        return VisitorRegistration::create(['shop_id' => $shop->id, 'type' => 'customer', 'status' => 'approved',
            'name' => $name, 'phone' => '0591234567', 'residence_address' => 'Hebron address',
            'public_token' => bin2hex(random_bytes(32)), 'latitude' => 31.53, 'longitude' => 35.1]);
    }
}
