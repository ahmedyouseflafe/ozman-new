<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorPermissionAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_without_login_still_has_permission_button_and_is_guided_to_create_account(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-permissions@example.com',
            'password' => 'secret123', 'role' => 'super_admin', 'is_active' => true,
        ]);
        $shop = Shop::create([
            'user_id' => $admin->id, 'name' => 'Ozman', 'slug' => 'ozman', 'is_active' => true,
        ]);
        $distributor = Distributor::create([
            'shop_id' => $shop->id, 'name' => 'موزع بلا حساب', 'email' => 'new-distributor@example.com', 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('distributors'))
            ->assertOk()
            ->assertSee(route('distributors.permissions.edit', $distributor));

        $this->actingAs($admin)
            ->get(route('distributors.permissions.edit', $distributor))
            ->assertRedirect(route('distributors.edit', $distributor))
            ->assertSessionHasErrors('user_id');
    }

    public function test_adding_login_to_existing_distributor_redirects_directly_to_permissions(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-login@example.com',
            'password' => 'secret123', 'role' => 'super_admin', 'is_active' => true,
        ]);
        $shop = Shop::create([
            'user_id' => $admin->id, 'name' => 'Ozman', 'slug' => 'ozman', 'is_active' => true,
        ]);
        $distributor = Distributor::create([
            'shop_id' => $shop->id, 'name' => 'موزع جديد', 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('distributors.update', $distributor), [
                'shop_id' => $shop->id,
                'name' => 'موزع جديد',
                'email' => 'distributor-login@example.com',
                'login_password' => 'password123',
                'is_active' => '1',
            ])
            ->assertRedirect(route('distributors.permissions.edit', $distributor));

        $distributor->refresh();
        $this->assertNotNull($distributor->user_id);
        $this->assertSame('distributor', $distributor->user->role);
    }
}
