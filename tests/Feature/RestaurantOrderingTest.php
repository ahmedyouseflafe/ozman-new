<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_order_uses_server_prices_and_keeps_customizations(): void
    {
        [$shop, $product, $table] = $this->restaurant();

        $response = $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'dine_in',
            'table_code' => $table->code,
            'customer_name' => 'طاولة 1',
            'items' => [[
                'product_id' => $product->id, 'qty' => 2, 'size' => 'كبير',
                'addons' => ['جبنة', 'إضافة مزورة'], 'excluded' => ['بصل'],
                'notes' => 'بدون ملح', 'price' => 1,
            ]],
        ])->assertOk();

        $order = \App\Models\FrontOrder::findOrFail($response->json('order_id') ?: 1);
        $this->assertSame($table->id, $order->restaurant_table_id);
        $this->assertSame('dine_in', $order->order_type);
        $this->assertSame('66.00', $order->total);
        $this->assertSame(['جبنة'], $order->items[0]['addons']);
        $this->assertSame(['بصل'], $order->items[0]['excluded']);
    }

    public function test_product_from_another_restaurant_is_rejected(): void
    {
        [$shop] = $this->restaurant();
        [, $otherProduct] = $this->restaurant('other');

        $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'pickup', 'customer_name' => 'عميل', 'customer_phone' => '0591234567',
            'items' => [['product_id' => $otherProduct->id, 'qty' => 1]],
        ])->assertStatus(422);
    }

    private function restaurant(string $suffix = 'main'): array
    {
        $owner = User::create(['name' => 'Owner', 'email' => "$suffix@restaurant.test", 'password' => 'password', 'role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => "Restaurant $suffix", 'slug' => "restaurant-$suffix", 'catalog_type' => 'restaurant', 'is_active' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'وجبات', 'slug' => "meals-$suffix", 'is_active' => true]);
        $product = Product::create([
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'برغر', 'slug' => "burger-$suffix",
            'price' => 20, 'quantity' => 50, 'is_active' => true,
            'catalog_attributes' => ['meal_size_prices' => ['صغير:20', 'كبير:30'], 'addon_prices' => ['جبنة:3'], 'ingredients' => 'بصل، بندورة'],
        ]);
        $table = RestaurantTable::create(['shop_id' => $shop->id, 'name' => 'طاولة 1', 'code' => "table-$suffix", 'is_active' => true]);
        return [$shop, $product, $table];
    }
}
