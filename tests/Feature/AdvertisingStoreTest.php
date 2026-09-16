<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisingStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_advertising_shop_uses_a_restaurant_style_service_page(): void
    {
        $shop = Shop::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'مطبعة الاختبار',
            'slug' => 'test-print-shop',
            'catalog_type' => 'advertising_services',
            'whatsapp' => '972501234567',
            'is_active' => true,
        ]);
        $shirts = Category::create([
            'shop_id' => $shop->id, 'name' => 'طباعة على البلايز', 'slug' => 'printed-shirts', 'is_active' => true,
        ]);
        $pens = Category::create([
            'shop_id' => $shop->id, 'name' => 'طباعة على الأقلام', 'slug' => 'printed-pens', 'is_active' => true,
        ]);
        $inactive = Category::create([
            'shop_id' => $shop->id, 'name' => 'قسم مخفي', 'slug' => 'hidden-prints', 'is_active' => false,
        ]);
        Product::create([
            'shop_id' => $shop->id, 'category_id' => $shirts->id, 'name' => 'طباعة بلوزة',
            'slug' => 'printed-shirt', 'price' => 0, 'quantity' => 1, 'is_active' => true,
        ]);
        Product::create([
            'shop_id' => $shop->id, 'category_id' => $pens->id, 'name' => 'قلم مطبوع',
            'slug' => 'printed-pen', 'price' => 12, 'quantity' => 1, 'is_active' => true,
        ]);
        Product::create([
            'shop_id' => $shop->id, 'category_id' => $inactive->id, 'name' => 'خدمة مخفية',
            'slug' => 'hidden-service', 'price' => 5, 'quantity' => 1, 'is_active' => true,
        ]);
        Advertisement::create([
            'shop_id' => $shop->id, 'title' => 'عرض الطباعة', 'type' => 'image',
            'media' => 'storage/ads/printing.jpg', 'is_active' => true,
        ]);

        $this->assertSame(route('advertising.store', $shop), $shop->publicUrl());
        $this->get(route('front.shop.slug', $shop))->assertRedirect($shop->publicUrl());
        $this->get(route('shop-app.launch', $shop))->assertRedirect($shop->publicUrl());

        $this->withSession(['locale' => 'ar'])->get($shop->publicUrl())
            ->assertOk()
            ->assertSee('restaurant-hero-layout')
            ->assertSee('restaurant-display-screen')
            ->assertSee('category-rail')
            ->assertSee('طباعة على البلايز')
            ->assertSee('طباعة على الأقلام')
            ->assertSee('طباعة بلوزة')
            ->assertSee('السعر حسب الطلب')
            ->assertSee('قلم مطبوع')
            ->assertSee('12.00 ₪')
            ->assertSee('storage/ads/printing.jpg')
            ->assertSee('wa.me/972501234567')
            ->assertDontSee('قسم مخفي')
            ->assertDontSee('خدمة مخفية')
            ->assertDontSee('id="cartPanel"', false);
    }

    public function test_other_shop_types_and_inactive_advertising_shops_cannot_use_the_page(): void
    {
        $owner = User::factory()->create();
        $general = Shop::create([
            'user_id' => $owner->id, 'name' => 'General Shop', 'slug' => 'general-shop', 'catalog_type' => 'general', 'is_active' => true,
        ]);
        $inactive = Shop::create([
            'user_id' => $owner->id, 'name' => 'Inactive Printer', 'slug' => 'inactive-printer',
            'catalog_type' => 'advertising_services', 'is_active' => false,
        ]);

        $this->get(route('advertising.store', $general))->assertNotFound();
        $this->get(route('advertising.store', $inactive))->assertNotFound();
    }

    public function test_marketplace_click_opens_the_advertising_store_page(): void
    {
        $script = file_get_contents(public_path('script.js'));

        $this->assertStringContainsString(
            "'advertising_services'].includes(selectedCenter?.catalog_type)",
            $script
        );
        $this->assertStringContainsString(
            "advertising_services: { title: 'خدمات دعائية وطباعة'",
            $script
        );
    }
}
