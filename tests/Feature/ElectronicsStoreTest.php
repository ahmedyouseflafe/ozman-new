<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectronicsStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_electronics_specs_and_independent_options(): void
    {
        [$shop, $category, $owner] = $this->store('admin');
        $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Phone Pro',
            'catalog_attributes' => ['brand' => 'OzTech', 'model' => 'P1', 'condition' => 'مستعمل', 'battery_health' => 91, 'network' => '5G', 'supports_esim' => 1, 'unknown' => 'drop'],
            'variants' => [
                ['storage' => '128GB', 'ram' => '8GB', 'color' => '#111111', 'color_name' => 'أسود', 'price' => 1100, 'quantity' => 2, 'is_active' => 1],
                ['storage' => '256GB', 'ram' => '12GB', 'color' => '#1565c0', 'color_name' => 'أزرق', 'price' => 1350, 'quantity' => 1, 'is_active' => 1],
            ],
        ])->assertRedirect(route('products'));

        $product = Product::where('shop_id', $shop->id)->firstOrFail();
        $this->assertSame('OzTech', $product->catalog_attributes['brand']);
        $this->assertArrayNotHasKey('unknown', $product->catalog_attributes);
        $this->assertSame(3, $product->quantity);
        $this->assertSame('1100.00', $product->price);
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'storage' => '256GB', 'ram' => '12GB', 'color' => '#1565c0', 'color_name' => 'أزرق', 'quantity' => 1]);
    }

    public function test_same_storage_and_ram_can_have_multiple_independent_colors(): void
    {
        [$shop, $category, $owner] = $this->store('multi-color');

        $this->actingAs($owner)->post(route('products.store'), [
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => 'Multi Color Phone',
            'variants' => [
                ['storage' => '128GB', 'ram' => '8GB', 'color' => '#111111', 'color_name' => 'Black', 'price' => 1100, 'quantity' => 2, 'is_active' => 1],
                ['storage' => '128GB', 'ram' => '8GB', 'color' => '#f5f5f5', 'color_name' => 'White', 'price' => 1100, 'quantity' => 4, 'is_active' => 1],
                ['storage' => '256GB', 'ram' => '8GB', 'color' => '#111111', 'color_name' => 'Black', 'price' => 1350, 'quantity' => 1, 'is_active' => 1],
            ],
        ])->assertRedirect(route('products'));

        $product = Product::where('shop_id', $shop->id)->where('name', 'Multi Color Phone')->firstOrFail();

        $this->assertSame(3, $product->variants()->count());
        $this->assertSame(2, $product->variants()->where('storage', '128GB')->count());
        $this->assertSame(7, $product->quantity);
    }

    public function test_storefront_filters_and_server_priced_order_decrements_only_selected_option(): void
    {
        [$shop, $category] = $this->store('sale');
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Galaxy Test', 'slug' => 'galaxy-test', 'price' => 1, 'quantity' => 5, 'main_image' => 'storage/products/main/galaxy.webp', 'is_active' => true, 'catalog_attributes' => ['brand' => 'Samsung', 'condition' => 'جديد', 'network' => '5G']]);
        $variant = $product->variants()->create(['storage' => '128GB', 'ram' => '8GB', 'color' => '#111111', 'color_name' => 'أسود', 'price' => 2200, 'quantity' => 3, 'is_active' => true]);
        $product->variants()->create(['storage' => '256GB', 'ram' => '12GB', 'color' => '#1565c0', 'price' => 2600, 'quantity' => 2, 'is_active' => true]);

        $storefront = $this->get(route('electronics.store', [$shop, 'brand' => 'Samsung', 'storage' => '128GB', 'min_price' => 2000, 'max_price' => 2300]));
        $storefront->assertOk()->assertSee('Galaxy Test')->assertSee('data-tilt', false);
        $storefront->assertSee('id="electronicsWebgl"', false)->assertSee('electronics-3d.js', false);
        $this->assertStringContainsString('/storage/products/main/galaxy.webp', $storefront->getContent());
        $this->assertStringNotContainsString('/storage/storage/', $storefront->getContent());
        $response = $this->postJson(route('electronics.orders.store', $shop), [
            'customer_name' => 'Customer', 'customer_phone' => '0591234567', 'customer_address' => 'Ramallah',
            'items' => [['variant_id' => $variant->id, 'qty' => 2, 'price' => 1]],
        ])->assertOk();
        $order = FrontOrder::findOrFail($response->json('order_id'));
        $this->assertSame('4400.00', $order->total);
        $this->assertSame('أسود', $order->items[0]['color_name']);
        $this->assertSame(1, $variant->fresh()->quantity);
        $this->assertSame(3, $product->fresh()->quantity);
    }

    public function test_order_rejects_option_from_another_store_and_insufficient_stock(): void
    {
        [$shop] = $this->store('mine');
        [$other, $category] = $this->store('other');
        $product = Product::create(['shop_id' => $other->id, 'category_id' => $category->id, 'name' => 'Foreign', 'slug' => 'foreign-device', 'price' => 100, 'quantity' => 1, 'is_active' => true]);
        $variant = $product->variants()->create(['storage' => '64GB', 'color' => '#111111', 'price' => 100, 'quantity' => 1, 'is_active' => true]);
        $payload = ['customer_name' => 'Customer', 'customer_phone' => '0591234567', 'customer_address' => 'Address', 'items' => [['variant_id' => $variant->id, 'qty' => 1]]];
        $this->postJson(route('electronics.orders.store', $shop), $payload)->assertStatus(422);
        $payload['items'][0]['qty'] = 2;
        $this->postJson(route('electronics.orders.store', $other), $payload)->assertStatus(422);
        $this->assertDatabaseCount('front_orders', 0);
    }

    public function test_main_market_exposes_electronics_store_url_and_storefront_shows_empty_categories(): void
    {
        [$shop, $category] = $this->store('navigation');

        $home = $this->get(route('home'));
        $home->assertOk()->assertSee('electronics');
        $this->assertStringContainsString(
            route('electronics.store', $shop),
            str_replace('\/', '/', $home->getContent())
        );

        $this->get(route('electronics.store', $shop))
            ->assertOk()
            ->assertSee($category->name);

        $script = file_get_contents(public_path('script.js'));
        $this->assertStringContainsString("['restaurant', 'electronics'].includes", $script);
    }

    private function store(string $suffix): array
    {
        $owner = User::create(['name' => 'Owner '.$suffix, 'email' => $suffix.'@electronics.test', 'password' => 'password', 'role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Tech '.$suffix, 'slug' => 'tech-'.$suffix, 'catalog_type' => 'electronics', 'is_active' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'جوالات', 'slug' => 'phones-'.$suffix, 'is_active' => true]);
        return [$shop, $category, $owner];
    }
}
