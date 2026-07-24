<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTypeCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_keeps_only_attributes_defined_for_its_shop_type(): void
    {
        $owner = User::create([
            'name' => 'Fashion Owner',
            'email' => 'fashion@example.com',
            'password' => 'secret123',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Fashion Shop',
            'slug' => 'fashion-shop',
            'catalog_type' => 'clothing',
            'is_active' => true,
        ]);
        $category = Category::create([
            'shop_id' => $shop->id,
            'name' => 'قمصان',
            'slug' => 'fashion-shirts',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'قميص صيفي',
            'quantity' => 12,
            'show_customer_package_price' => 1,
            'show_customer_carton_price' => 0,
            'show_customer_pallet_price' => 0,
            'show_package_price' => 0,
            'show_carton_price' => 0,
            'show_pallet_price' => 0,
            'catalog_attributes' => [
                'sizes' => 'S, M, L, M',
                'colors' => 'أسود, أبيض',
                'material' => 'قطن',
                'preparation_time' => 90,
            ],
            'variants' => [
                ['size' => 'S', 'color' => 'أسود', 'sku' => 'SHIRT-S-B', 'quantity' => 3, 'is_active' => 1],
                ['size' => 'M', 'color' => 'أبيض', 'sku' => 'SHIRT-M-W', 'quantity' => 7, 'is_active' => 1],
            ],
        ])->assertRedirect(route('products'));

        $product = Product::query()->where('shop_id', $shop->id)->firstOrFail();

        $this->assertSame(['S', 'M', 'L'], $product->catalog_attributes['sizes']);
        $this->assertSame(['أسود', 'أبيض'], $product->catalog_attributes['colors']);
        $this->assertSame('قطن', $product->catalog_attributes['material']);
        $this->assertArrayNotHasKey('preparation_time', $product->catalog_attributes);
        $this->assertSame(10, $product->fresh()->quantity);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size' => 'S',
            'color' => 'أسود',
            'quantity' => 3,
        ]);
    }

    public function test_shop_catalog_type_can_be_changed_to_supported_type(): void
    {
        $this->assertArrayHasKey('restaurant', config('catalog_types'));
        $this->assertArrayHasKey('cosmetics', config('catalog_types'));
        $this->assertArrayHasKey('sweets', config('catalog_types'));
        $this->assertArrayHasKey('shoes', config('catalog_types'));
    }
}
