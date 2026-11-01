<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Str;

/** Real products for main-store (front-orders) tests: the server prices orders from these rows. */
trait MakesFrontOrderProducts
{
    /**
     * @param  array<string, float|int|null>  $prices  e.g. ['customer_package_price' => 10, 'package_price' => 8]
     */
    protected function frontOrderProduct(?Shop $shop = null, array $prices = [], array $attributes = []): Product
    {
        $shop ??= Shop::create([
            'user_id' => User::factory()->create(['role' => 'shop_owner'])->id,
            'name' => 'Price source '.Str::random(5),
            'slug' => 'price-source-'.Str::lower(Str::random(8)),
            'is_active' => true,
        ]);

        $category = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Front products',
            'slug' => 'front-products-'.Str::lower(Str::random(8)),
        ]);

        $columns = [];
        foreach ($prices as $column => $price) {
            $columns[$column] = $price;
            $columns['show_'.$column] = $price !== null;
        }

        return Product::create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'name' => $attributes['name'] ?? 'Front product',
            'slug' => 'front-product-'.Str::lower(Str::random(10)),
            'price' => $attributes['price'] ?? 1,
            'is_active' => $attributes['is_active'] ?? true,
            ...$columns,
        ]);
    }

    protected function frontOrderItem(Product $product, string $unitKey = 'package', int $qty = 1, array $extra = []): array
    {
        return ['product_id' => $product->id, 'unit_key' => $unitKey, 'qty' => $qty, ...$extra];
    }
}
