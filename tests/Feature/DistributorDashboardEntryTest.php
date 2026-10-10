<?php

namespace Tests\Feature;

use App\Models\{Distributor, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorDashboardEntryTest extends TestCase
{
    use RefreshDatabase;

    private function distributor(): User
    {
        $user = User::factory()->create(['role' => 'distributor', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Catalog', 'slug' => 'entry-catalog', 'is_active' => true]);
        Distributor::create(['user_id' => $user->id, 'shop_id' => $shop->id, 'name' => $user->name, 'email' => $user->email, 'is_active' => true]);

        return $user;
    }

    public function test_login_without_custom_permissions_lands_on_accessible_catalog(): void
    {
        $user = $this->distributor();
        $this->withSession(['url.intended' => route('front-orders.index')])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('products'));
        $this->get(route('products'))->assertOk();
        $this->get(route('dashboard'))->assertRedirect(route('products'));
    }

    public function test_old_orders_bookmark_recovers_but_does_not_grant_order_access(): void
    {
        $user = $this->distributor();
        $this->actingAs($user)->get(route('front-orders.index'))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('products'));
        $this->get(route('products'))->assertOk();
        $this->getJson(route('front-orders.index'))->assertForbidden();
        $this->assertDatabaseCount('employee_permissions', 0);
    }

    public function test_explicit_order_permission_preserves_orders_landing_page(): void
    {
        $user = $this->distributor();
        $user->employeePermissions()->create(['permission' => 'front_orders.view']);
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('front-orders.index'));
        $this->get(route('front-orders.index'))->assertOk();
    }

    public function test_custom_catalog_only_permissions_do_not_grant_orders(): void
    {
        $user = $this->distributor();
        $user->employeePermissions()->create(['permission' => 'products.view']);
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('products'));
        $this->get(route('products'))->assertOk();
        $this->get(route('front-orders.index'))->assertRedirect(route('dashboard'));
        $this->getJson(route('front-orders.index'))->assertForbidden();
    }
}
