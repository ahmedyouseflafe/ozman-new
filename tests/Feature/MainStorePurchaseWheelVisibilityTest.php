<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RewardWheel;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainStorePurchaseWheelVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_starts_with_ozman_and_includes_its_purchase_wheel(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'front-admin@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $ozman = Shop::create([
            'user_id' => $admin->id,
            'name' => 'Ozman',
            'slug' => 'ozman',
            'is_active' => true,
        ]);
        Shop::create([
            'user_id' => $admin->id,
            'name' => 'Other Shop',
            'slug' => 'other-shop',
            'is_active' => true,
        ]);
        $wheel = RewardWheel::create([
            'shop_id' => $ozman->id,
            'key' => 'ozman_purchase_test',
            'wheel_type' => RewardWheel::TYPE_PURCHASE_AMOUNT,
            'title' => 'عجلة Ozman',
            'min_order_total' => 100,
            'max_order_total' => 200,
            'win_quota_total' => 2,
            'is_active' => true,
        ]);
        $wheel->segments()->createMany([
            ['label' => 'خصم 5%', 'discount_value' => 5, 'discount_type' => 'percent', 'win_quota' => 1, 'color' => '#00e5ff', 'sort_order' => 0, 'is_active' => true],
            ['label' => 'خصم 10%', 'discount_value' => 10, 'discount_type' => 'percent', 'win_quota' => 1, 'color' => '#7000ff', 'sort_order' => 1, 'is_active' => true],
        ]);

        $response = $this->get(route('home'))->assertOk();
        $frontData = $response->viewData('frontData');

        $this->assertSame($ozman->id, $response->viewData('shop')->id);
        $this->assertSame($ozman->id, $frontData['centersData'][0]['id']);
        $this->assertSame($wheel->id, $frontData['centersData'][0]['purchase_reward_wheels'][0]['id']);
        $this->assertSame($wheel->id, $response->viewData('purchaseRewardWheels')[0]['id']);
    }

    public function test_another_shop_does_not_receive_ozman_wheel(): void
    {
        $admin = User::create([
            'name' => 'Admin Two',
            'email' => 'front-admin-two@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $ozman = Shop::create(['user_id' => $admin->id, 'name' => 'Ozman', 'slug' => 'ozman', 'is_active' => true]);
        $other = Shop::create(['user_id' => $admin->id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        RewardWheel::create([
            'shop_id' => $ozman->id,
            'key' => 'ozman_only',
            'wheel_type' => RewardWheel::TYPE_PURCHASE_AMOUNT,
            'title' => 'Ozman only',
            'min_order_total' => 10,
            'win_quota_total' => 2,
            'is_active' => true,
        ]);

        $response = $this->get(route('front.shop', $other))->assertOk();

        $this->assertSame($other->id, $response->viewData('shop')->id);
        $this->assertSame([], $response->viewData('purchaseRewardWheels'));
    }

    public function test_home_only_embeds_products_for_the_active_shop(): void
    {
        $admin = User::create([
            'name' => 'Performance Admin',
            'email' => 'performance-admin@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $ozman = Shop::create([
            'user_id' => $admin->id,
            'name' => 'Ozman',
            'slug' => 'ozman',
            'is_active' => true,
        ]);
        $other = Shop::create([
            'user_id' => $admin->id,
            'name' => 'Heavy Shop',
            'slug' => 'heavy-shop',
            'is_active' => true,
        ]);
        $category = Category::create([
            'shop_id' => $other->id,
            'name' => 'Heavy Category',
            'slug' => 'heavy-category',
            'is_active' => true,
        ]);
        Product::create([
            'shop_id' => $other->id,
            'category_id' => $category->id,
            'name' => 'Heavy Product',
            'slug' => 'heavy-product',
            'price' => 50,
            'is_active' => true,
        ]);

        $homeData = $this->get(route('home'))->assertOk()->viewData('frontData');
        $homeOther = collect($homeData['centersData'])->firstWhere('id', $other->id);

        $this->assertSame($ozman->id, $homeData['centersData'][0]['id']);
        $this->assertSame([], $homeOther['departments']);
        $this->assertSame([], $homeOther['products_db']);
        $this->assertSame(route('front.shop.slug', $other), $homeOther['public_url']);

        $otherData = $this->get(route('front.shop.slug', $other))->assertOk()->viewData('frontData');

        $this->assertSame($other->id, $otherData['centersData'][0]['id']);
        $this->assertSame('Heavy Product', $otherData['centersData'][0]['products_db']['Heavy Category'][0]['name']);
    }
}
