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

    public function test_entering_legacy_admin_owned_shop_creates_a_dedicated_shop_owner(): void
    {
        $admin = User::create([
            'name' => 'Legacy Admin',
            'email' => 'legacy-admin@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $shop = Shop::create([
            'user_id' => $admin->id,
            'name' => 'Legacy Shop',
            'slug' => 'legacy-shop',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('shops.enter-dashboard', $shop))
            ->assertRedirect(route('shops.show', $shop));

        $shop->refresh();
        $this->assertNotSame($admin->id, $shop->user_id);
        $this->assertTrue($shop->user->isShopOwner());
        $this->assertTrue($shop->user->is_active);
        $this->assertAuthenticatedAs($shop->user);
    }

    public function test_shop_dashboard_uses_the_exact_permissions_configured_by_admin(): void
    {
        $admin = User::create([
            'name' => 'Permissions Admin',
            'email' => 'permissions-admin@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        [$owner, $shop] = $this->shopOwner('permissions');

        $this->actingAs($admin)
            ->put(route('shops.permissions.update', $shop), [
                'permissions' => ['products.view', 'products.preview'],
            ])
            ->assertRedirect(route('shops'));

        $this->post(route('shops.enter-dashboard', $shop))
            ->assertRedirect(route('shops.show', $shop));
        $this->assertAuthenticatedAs($owner);

        $this->get(route('products'))->assertOk();
        $this->get(route('categories'))->assertForbidden();
        $this->get(route('users'))->assertForbidden();
        $this->post(route('admin.return-from-shop'))->assertRedirect(route('shops'));
        $this->assertAuthenticatedAs($admin);
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
