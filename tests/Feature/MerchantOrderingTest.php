<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\EmployeePermission;
use App\Models\FrontOrder;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_creates_a_separate_shop_owner_account_linked_to_him(): void
    {
        $distributorUser = User::create([
            'name' => 'Main distributor',
            'email' => 'main-distributor@example.com',
            'password' => 'secret123',
            'role' => 'distributor',
            'is_active' => true,
        ]);

        $baseShop = Shop::create([
            'user_id' => $distributorUser->id,
            'name' => 'Distributor base shop',
            'slug' => 'distributor-base-shop',
            'is_active' => true,
        ]);

        $distributor = Distributor::create([
            'shop_id' => $baseShop->id,
            'user_id' => $distributorUser->id,
            'name' => 'Main distributor',
            'is_active' => true,
        ]);
        EmployeePermission::create([
            'user_id' => $distributorUser->id,
            'permission' => 'shops.create',
        ]);

        $response = $this->actingAs($distributorUser)->post(route('shops.store'), [
            'name' => 'New linked merchant',
            'owner_email' => 'new-merchant@example.com',
            'owner_password' => 'secret123',
            'owner_password_confirmation' => 'secret123',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('shops'));

        $merchant = User::query()->where('email', 'new-merchant@example.com')->firstOrFail();
        $shop = Shop::query()->where('name', 'New linked merchant')->firstOrFail();

        $this->assertTrue($merchant->isShopOwner());
        $this->assertNotSame($distributorUser->id, $merchant->id);
        $this->assertSame($merchant->id, $shop->user_id);
        $this->assertSame($distributor->id, $shop->distributor_id);
    }

    public function test_shop_owner_can_login_from_the_merchant_screen(): void
    {
        [$merchant, $merchantShop] = $this->merchantLinkedToDistributor();

        $response = $this->post(route('merchant.login.store'), [
            'email' => $merchant->email,
            'password' => 'secret123',
            'redirect' => 'https://malicious.example/steal',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($merchant);
        $this->assertSame($merchantShop->id, session('merchant_shop_id'));
    }

    public function test_authenticated_merchant_order_ignores_spoofed_distributor_and_shop(): void
    {
        [$merchant, $merchantShop, $linkedDistributor] = $this->merchantLinkedToDistributor();
        [, $otherShop, $otherDistributor] = $this->merchantLinkedToDistributor('other');

        $response = $this->actingAs($merchant)->postJson(route('front-orders.store'), [
            'shop_id' => $otherShop->id,
            'distributor_id' => $otherDistributor->id,
            'marketing_source' => 'distributor',
            'customer_name' => 'متجر الاختبار',
            'customer_phone' => '0590000000',
            'items' => [['name' => 'كرتونة', 'price' => '10', 'qty' => 2]],
            'subtotal' => 20,
            'discount' => 0,
            'total' => 20,
            'order_channel' => 'whatsapp',
            'visitor_type' => 'customer',
        ]);

        $response->assertOk();

        $order = FrontOrder::query()->latest('id')->firstOrFail();
        $this->assertSame($merchantShop->id, $order->shop_id);
        $this->assertSame($linkedDistributor->id, $order->distributor_id);
        $this->assertSame('merchant_account', $order->marketing_source);
        $this->assertNotSame($otherDistributor->id, $order->distributor_id);
    }

    private function merchantLinkedToDistributor(string $suffix = 'main'): array
    {
        $distributorUser = User::create([
            'name' => "Distributor {$suffix}",
            'email' => "distributor-{$suffix}@example.com",
            'password' => 'secret123',
            'role' => 'distributor',
            'is_active' => true,
        ]);

        $baseShop = Shop::create([
            'user_id' => $distributorUser->id,
            'name' => "Base shop {$suffix}",
            'slug' => "base-shop-{$suffix}",
            'is_active' => true,
        ]);

        $distributor = Distributor::create([
            'shop_id' => $baseShop->id,
            'user_id' => $distributorUser->id,
            'name' => "Distributor {$suffix}",
            'whatsapp' => '970599000000',
            'is_active' => true,
        ]);

        $merchant = User::create([
            'name' => "Merchant {$suffix}",
            'email' => "merchant-{$suffix}@example.com",
            'password' => 'secret123',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $merchantShop = Shop::create([
            'user_id' => $merchant->id,
            'distributor_id' => $distributor->id,
            'name' => "Merchant shop {$suffix}",
            'slug' => "merchant-shop-{$suffix}",
            'phone' => '0590000000',
            'is_active' => true,
        ]);

        return [$merchant, $merchantShop, $distributor];
    }
}
