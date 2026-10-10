<?php

namespace Tests\Feature;

use App\Models\{Distributor, DistributorMarketer, FrontOrder, Shop, User};
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

    public function test_empty_permissions_login_lands_on_access_notice_not_orders(): void
    {
        $user = $this->distributor();
        $this->withSession(['url.intended' => route('front-orders.index')])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('front-orders.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk()->assertViewIs('admin.access_pending');
    }

    public function test_empty_permissions_deny_orders_and_catalog_without_grant_mutation(): void
    {
        $user = $this->distributor();
        $this->actingAs($user)->get(route('front-orders.index'))->assertForbidden();
        $this->get(route('products'))->assertForbidden();
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
        $this->get(route('front-orders.index'))->assertForbidden();
        $this->getJson(route('front-orders.index'))->assertForbidden();
    }

    public function test_read_only_distributor_orders_and_statistics_are_isolated_even_when_searching(): void
    {
        $user = $this->distributor();
        $user->employeePermissions()->create(['permission' => 'front_orders.own.view']);
        $profile = $user->distributorProfiles()->firstOrFail();
        $other = Distributor::create(['shop_id' => $profile->shop_id, 'name' => 'Other distributor', 'is_active' => true]);
        $marketer = DistributorMarketer::create(['distributor_id' => $profile->id, 'name' => 'Own marketer', 'tracking_code' => 'entry-marketer', 'is_active' => true]);
        $make = fn ($number, $attributes) => FrontOrder::create(array_merge([
            'shop_id' => $profile->shop_id, 'order_number' => $number,
            'customer_name' => 'Entry customer', 'total' => 100, 'items' => [],
        ], $attributes));
        $direct = $make('OWN-DIRECT', ['distributor_id' => $profile->id]);
        $referred = $make('OWN-MARKETER', ['distributor_marketer_id' => $marketer->id]);
        $foreign = $make('FOREIGN-ORDER', ['distributor_id' => $other->id]);
        $make('UNASSIGNED-ORDER', []);

        $response = $this->actingAs($user)->get(route('front-orders.index'))->assertOk();
        $this->assertEqualsCanonicalizing([$direct->id, $referred->id], $response->viewData('orders')->pluck('id')->all());
        $response->assertViewHas('totalCount', 2)->assertDontSee('FOREIGN-ORDER')->assertDontSee('UNASSIGNED-ORDER');
        $this->assertStringNotContainsString('<form class="status-form"', $response->getContent());
        $response->assertDontSee('href="'.route('products.create').'"', false);
        $this->get(route('products'))->assertForbidden();
        $this->patch(route('front-orders.status', $direct), ['status' => 'completed'])->assertForbidden();
        $this->get(route('front-orders.index', ['search' => $foreign->order_number]))
            ->assertOk()->assertViewHas('orders', fn ($orders) => $orders->isEmpty());
        $this->patch(route('front-orders.status', $foreign), ['status' => 'completed'])->assertForbidden();
    }

    public function test_clearing_saved_permissions_revokes_access_and_editor_has_simple_presets(): void
    {
        $user = $this->distributor();
        $profile = $user->distributorProfiles()->firstOrFail();
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('distributors.permissions.edit', $profile))
            ->assertOk()->assertSee('data-permission-preset="orders"', false)->assertSee('front_orders.own.view');
        $this->put(route('distributors.permissions.update', $profile), ['permissions' => ['front_orders.own.view']])->assertSessionHasNoErrors();
        $this->actingAs($user->fresh())->get(route('front-orders.index'))->assertOk();
        $this->actingAs($admin)->put(route('distributors.permissions.update', $profile), ['permissions' => []])->assertSessionHasNoErrors();
        $this->actingAs($user->fresh())->get(route('front-orders.index'))->assertForbidden();
        $this->get(route('products'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk()->assertViewIs('admin.access_pending');
    }
}
