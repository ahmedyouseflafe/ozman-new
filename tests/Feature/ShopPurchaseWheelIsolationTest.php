<?php

namespace Tests\Feature;

use App\Models\RewardWheel;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopPurchaseWheelIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_owner_created_purchase_wheel_is_bound_to_his_shop(): void
    {
        [$owner, $shop] = $this->shopOwner('create');

        $this->actingAs($owner)->post(route('reward-wheels.purchase.store'), [
            'shop_id' => $shop->id,
            'title' => 'عجلة متجري الجديدة',
            'min_order_total' => 50,
            'max_order_total' => 100,
            'win_quota_total' => 2,
            'is_active' => '1',
            'segments' => [
                [
                    'label' => 'خصم 5%',
                    'discount_value' => 5,
                    'discount_type' => 'percent',
                    'win_quota' => 1,
                    'color' => '#00e5ff',
                    'is_active' => '1',
                ],
                [
                    'label' => 'خصم 10%',
                    'discount_value' => 10,
                    'discount_type' => 'percent',
                    'win_quota' => 1,
                    'color' => '#7000ff',
                    'is_active' => '1',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('reward_wheels', [
            'shop_id' => $shop->id,
            'wheel_type' => RewardWheel::TYPE_PURCHASE_AMOUNT,
            'title' => 'عجلة متجري الجديدة',
        ]);
    }

    public function test_shop_owner_only_manages_purchase_wheels_for_his_shop(): void
    {
        [$ownerA, $shopA] = $this->shopOwner('a');
        [, $shopB] = $this->shopOwner('b');
        $wheelA = $this->purchaseWheel($shopA, 'عجلة متجر ألف');
        $wheelB = $this->purchaseWheel($shopB, 'عجلة متجر باء');

        $response = $this->actingAs($ownerA)->get(route('reward-wheels.purchase.index'));

        $response->assertOk()
            ->assertSee($wheelA->title)
            ->assertDontSee($wheelB->title);

        $this->actingAs($ownerA)
            ->get(route('reward-wheels.purchase.edit', $wheelB))
            ->assertForbidden();

        $this->actingAs($ownerA)
            ->get(route('reward-wheels.purchase.index', ['shop_id' => $shopB->id]))
            ->assertForbidden();
    }

    public function test_order_rejects_purchase_wheel_from_another_shop(): void
    {
        [, $shopA] = $this->shopOwner('order-a');
        [, $shopB] = $this->shopOwner('order-b');
        $wheelA = $this->purchaseWheel($shopA, 'عجلة الطلب الصحيحة');
        $wheelB = $this->purchaseWheel($shopB, 'عجلة متجر مختلف');

        $payload = [
            'shop_id' => $shopA->id,
            'customer_name' => 'عميل عادي',
            'items' => [['name' => 'منتج', 'price' => '150', 'qty' => 1]],
            'subtotal' => 150,
            'discount' => 0,
            'total' => 150,
            'order_channel' => 'whatsapp',
            'visitor_type' => 'customer',
        ];

        $this->postJson(route('front-orders.store'), [
            ...$payload,
            'reward_wheel_id' => $wheelB->id,
        ])->assertStatus(422);

        $this->postJson(route('front-orders.store'), [
            ...$payload,
            'reward_wheel_id' => $wheelA->id,
        ])->assertOk();

        $this->assertDatabaseHas('front_orders', [
            'shop_id' => $shopA->id,
            'reward_wheel_id' => $wheelA->id,
        ]);
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

    private function purchaseWheel(Shop $shop, string $title): RewardWheel
    {
        $wheel = RewardWheel::create([
            'shop_id' => $shop->id,
            'key' => 'purchase_' . $shop->id . '_' . strtolower(str_replace(' ', '_', $title)),
            'wheel_type' => RewardWheel::TYPE_PURCHASE_AMOUNT,
            'title' => $title,
            'min_order_total' => 100,
            'max_order_total' => 200,
            'win_quota_total' => 2,
            'is_active' => true,
        ]);

        $wheel->segments()->createMany([
            [
                'label' => 'خصم 5%',
                'discount_value' => 5,
                'discount_type' => 'percent',
                'win_quota' => 1,
                'color' => '#00e5ff',
                'sort_order' => 0,
                'is_active' => true,
            ],
            [
                'label' => 'خصم 10%',
                'discount_value' => 10,
                'discount_type' => 'percent',
                'win_quota' => 1,
                'color' => '#7000ff',
                'sort_order' => 1,
                'is_active' => true,
            ],
        ]);

        return $wheel;
    }
}
