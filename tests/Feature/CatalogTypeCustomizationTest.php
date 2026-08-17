<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTypeCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_category_can_upload_a_menu_background_video(): void
    {
        Storage::fake('public');
        $owner = User::create([
            'name' => 'Video Owner', 'email' => 'video-owner@example.com', 'password' => 'secret123',
            'role' => 'shop_owner', 'is_active' => true,
        ]);
        $shop = Shop::create([
            'user_id' => $owner->id, 'name' => 'Video Restaurant', 'slug' => 'video-restaurant',
            'catalog_type' => 'restaurant', 'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('categories.store'), [
            'shop_id' => $shop->id,
            'name' => 'مشاوي',
            'is_active' => 1,
            'background_video' => UploadedFile::fake()->create('grills.mp4', 512, 'video/mp4'),
        ])->assertRedirect(route('categories'));

        $category = Category::where('shop_id', $shop->id)->firstOrFail();
        $this->assertNotNull($category->background_video);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $category->background_video));

        $this->get(route('restaurant.menu', $shop))
            ->assertOk()
            ->assertSee($category->background_video, false);
    }

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

    public function test_restaurant_product_saves_menu_price_and_structured_options(): void
    {
        $owner = User::create(['name' => 'Chef', 'email' => 'chef@example.com', 'password' => 'secret123', 'role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Chef Restaurant', 'slug' => 'chef-restaurant', 'catalog_type' => 'restaurant', 'is_active' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'وجبات', 'slug' => 'chef-meals', 'is_active' => true]);

        $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'برغر',
            'customer_package_price' => 22, 'quantity' => 30,
            'show_customer_package_price' => 1, 'show_customer_carton_price' => 0,
            'show_customer_pallet_price' => 0, 'show_package_price' => 0,
            'show_carton_price' => 0, 'show_pallet_price' => 0,
            'catalog_attributes' => [
                'meal_size_prices' => ['صغير:22', '', 'كبير:30'],
                'addon_prices' => ['جبنة:3', 'صوص:2'],
                'removable_ingredients' => 'بصل, مخلل',
                'ingredients' => 'لحم، خبز، بصل',
                'preparation_time' => 15,
            ],
        ])->assertRedirect(route('products'));

        $product = Product::where('shop_id', $shop->id)->firstOrFail();
        $this->assertSame('22.00', $product->price);
        $this->assertSame(['صغير:22', 'كبير:30'], $product->catalog_attributes['meal_size_prices']);
        $this->assertSame(['جبنة:3', 'صوص:2'], $product->catalog_attributes['addon_prices']);
        $this->assertSame(['بصل', 'مخلل'], $product->catalog_attributes['removable_ingredients']);
    }
}
