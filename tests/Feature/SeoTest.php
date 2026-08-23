<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_core_search_metadata(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_sitemap_contains_active_store_and_electronics_product(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Tech Store', 'slug' => 'tech-store', 'catalog_type' => 'electronics', 'is_active' => true,
        ]);
        $category = Category::create([
            'shop_id' => $shop->id, 'name' => 'Phones', 'slug' => 'phones', 'is_active' => true,
        ]);
        $product = Product::create([
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Phone', 'slug' => 'phone', 'price' => 100,
            'quantity' => 1, 'is_active' => true,
        ]);

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('electronics.store', $shop), false)
            ->assertSee(route('electronics.product', [$shop, $product]), false);
    }

    public function test_robots_points_to_sitemap_and_blocks_private_areas(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertSee('Sitemap: '.route('seo.sitemap'), false)
            ->assertSee('Disallow: /dashboard', false);
    }
}
