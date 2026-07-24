<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorMarketer;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAssignShopDistributorTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_assign_change_and_remove_shop_distributor(): void
    {
        $admin = $this->user('admin', 'super_admin');
        $owner = $this->user('owner', 'shop_owner');
        $baseShopA = $this->shop($admin, 'base-a');
        $baseShopB = $this->shop($admin, 'base-b');
        $customerShop = $this->shop($owner, 'customer');
        $distributorA = Distributor::create(['shop_id' => $baseShopA->id, 'name' => 'Distributor A', 'is_active' => true]);
        $distributorB = Distributor::create(['shop_id' => $baseShopB->id, 'name' => 'Distributor B', 'is_active' => true]);
        $marketerA = DistributorMarketer::create([
            'distributor_id' => $distributorA->id,
            'name' => 'Marketer A',
            'tracking_code' => 'marketer-a',
            'is_active' => true,
        ]);
        $customerShop->update([
            'distributor_id' => $distributorA->id,
            'distributor_marketer_id' => $marketerA->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('shops.distributor.assign', $customerShop), ['distributor_id' => $distributorB->id])
            ->assertRedirect();

        $customerShop->refresh();
        $this->assertSame($distributorB->id, $customerShop->distributor_id);
        $this->assertNull($customerShop->distributor_marketer_id);

        $this->patch(route('shops.distributor.assign', $customerShop), ['distributor_id' => null])
            ->assertRedirect();
        $this->assertNull($customerShop->fresh()->distributor_id);
    }

    public function test_shop_owner_cannot_assign_a_distributor(): void
    {
        $admin = $this->user('admin-two', 'super_admin');
        $owner = $this->user('owner-two', 'shop_owner');
        $baseShop = $this->shop($admin, 'base');
        $customerShop = $this->shop($owner, 'customer-two');
        $distributor = Distributor::create(['shop_id' => $baseShop->id, 'name' => 'Distributor', 'is_active' => true]);

        $this->actingAs($owner)
            ->patch(route('shops.distributor.assign', $customerShop), ['distributor_id' => $distributor->id])
            ->assertForbidden();

        $this->assertNull($customerShop->fresh()->distributor_id);
    }

    private function user(string $suffix, string $role): User
    {
        return User::create([
            'name' => $suffix,
            'email' => "{$suffix}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function shop(User $owner, string $suffix): Shop
    {
        return Shop::create([
            'user_id' => $owner->id,
            'name' => "Shop {$suffix}",
            'slug' => "shop-{$suffix}",
            'catalog_type' => 'general',
            'is_active' => true,
        ]);
    }
}
