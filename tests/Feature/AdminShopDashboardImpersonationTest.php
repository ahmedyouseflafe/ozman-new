<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShopDashboardImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_enter_shop_dashboard_and_return_to_admin_account(): void
    {
        $admin = User::create([
            'name' => 'Main Admin',
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        [$owner, $shop] = $this->shopOwner('target');

        $this->actingAs($admin)
            ->post(route('shops.enter-dashboard', $shop))
            ->assertRedirect(route('shops.show', $shop))
            ->assertSessionHas('impersonator_admin_id', $admin->id)
            ->assertSessionHas('impersonated_shop_id', $shop->id);

        $this->assertAuthenticatedAs($owner);

        $this->post(route('admin.return-from-shop'))
            ->assertRedirect(route('shops'))
            ->assertSessionMissing('impersonator_admin_id')
            ->assertSessionMissing('impersonated_shop_id');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_super_admin_cannot_enter_another_shop_dashboard(): void
    {
        [$ownerA] = $this->shopOwner('a');
        [, $shopB] = $this->shopOwner('b');

        $this->actingAs($ownerA)
            ->post(route('shops.enter-dashboard', $shopB))
            ->assertForbidden();

        $this->assertAuthenticatedAs($ownerA);
    }

    private function shopOwner(string $suffix): array
    {
        $owner = User::create([
            'name' => "Owner {$suffix}",
            'email' => "owner-{$suffix}@example.com",
            'password' => 'secret123',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => "Shop {$suffix}",
            'slug' => "shop-{$suffix}",
            'is_active' => true,
        ]);

        return [$owner, $shop];
    }
}
