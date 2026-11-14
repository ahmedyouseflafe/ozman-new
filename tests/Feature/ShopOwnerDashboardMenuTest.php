<?php

namespace Tests\Feature;

use App\Models\{Category, Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopOwnerDashboardMenuTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $type): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);

        return [$shop, $owner];
    }

    public function test_owners_land_on_their_orders_and_see_a_menu_without_duplicates(): void
    {
        [$shop, $owner] = $this->shop('sweets');

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('shop-orders.dashboard', $shop));

        $html = $this->actingAs($owner)->get(route('shop-orders.dashboard', $shop))->assertOk()->getContent();
        $menu = substr($html, strpos($html, '<nav class="admin-sidebar-nav">'), 20000);
        $this->assertStringContainsString('الطلبات', $menu);
        $this->assertStringContainsString('بيانات المحل', $menu);
        $this->assertStringContainsString('الشاشات', $menu);
        $this->assertStringNotContainsString('طلبات الواجهة', $menu);
        $this->assertStringNotContainsString('لوحة التحكم الرئيسية', $menu);
        // Orders come before everything else.
        $this->assertLessThan(strpos($menu, 'بيانات المحل'), strpos($menu, 'الطلبات'));
    }

    public function test_restaurants_and_clinics_use_their_own_words(): void
    {
        [$restaurant, $chef] = $this->shop('restaurant');
        $this->actingAs($chef)->get(route('dashboard'))->assertRedirect(route('restaurant.dashboard', $restaurant));
        $this->actingAs($chef)->get(route('restaurant.dashboard', $restaurant))->assertOk()
            ->assertSee('بيانات المطعم')->assertSee('الطاولات وQR');

        [, $dentist] = $this->shop('dental');
        $this->actingAs($dentist)->get(route('settings'))->assertOk()->assertSee('بيانات العيادة');
    }

    public function test_owners_only_see_settings_they_can_save(): void
    {
        [, $owner] = $this->shop('clothing');

        $this->actingAs($owner)->get(route('settings'))->assertOk()
            ->assertSee('تغيير كلمة المرور')
            ->assertDontSee(route('settings.system.update'))
            ->assertDontSee(route('settings.notifications.update'));
    }

    public function test_items_without_a_counted_quantity_are_not_called_out_of_stock(): void
    {
        [$shop, $owner] = $this->shop('sweets');
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Cakes', 'slug' => 'cakes-'.uniqid(), 'is_active' => true]);
        Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Cake', 'slug' => 'cake-'.uniqid(), 'price' => 20, 'is_active' => true]);

        $this->actingAs($owner)->get(route('products', ['shop_id' => $shop->id]))->assertOk()
            ->assertDontSee('نفد المخزون')->assertDontSee('نفدت الكمية')->assertSee('مخفية عن الزبائن');
    }

    public function test_agent_panels_only_show_for_krembo(): void
    {
        [$shop, $owner] = $this->shop('clothing');
        $this->actingAs($owner)->get(route('shops.show', $shop))->assertOk()->assertDontSee('وكلاء المتجر');
    }
}
