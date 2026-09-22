<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CosmeticsStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_cosmetics_shop_has_its_own_beauty_storefront_with_display_sound_control(): void
    {
        $shop = Shop::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Elegance Perfumes', 'slug' => 'elegance-perfumes',
            'catalog_type' => 'cosmetics', 'whatsapp' => '972501234567', 'is_active' => true,
        ]);
        $category = Category::create([
            'shop_id' => $shop->id, 'name' => 'Perfumes', 'slug' => 'perfumes', 'is_active' => true,
        ]);
        Product::create([
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Rose Bloom',
            'slug' => 'rose-bloom', 'price' => 125, 'quantity' => 2, 'is_active' => true,
            'catalog_attributes' => ['brand' => 'Elegance', 'volume' => '50 ml'],
        ]);
        Advertisement::create([
            'shop_id' => $shop->id, 'title' => 'Beauty video', 'type' => 'video',
            'media' => 'storage/ads/beauty.mp4', 'is_active' => true,
        ]);

        $this->assertSame(route('cosmetics.store', $shop), $shop->publicUrl());
        $this->get(route('front.shop.slug', $shop))->assertRedirect($shop->publicUrl());
        $this->get($shop->publicUrl())->assertOk()
            ->assertSee('beauty-display', false)
            ->assertSee('data-display-sound-toggle', false)
            ->assertSee('Perfumes')
            ->assertSee('Rose Bloom')
            ->assertSee('wa.me/972501234567');

        $this->assertStringContainsString("'cosmetics'", file_get_contents(public_path('script.js')));
    }

    public function test_non_cosmetics_or_inactive_shop_cannot_use_cosmetics_page(): void
    {
        $owner = User::factory()->create();
        $general = Shop::create(['user_id' => $owner->id, 'name' => 'General', 'slug' => 'general-cosmetics', 'catalog_type' => 'general', 'is_active' => true]);
        $inactive = Shop::create(['user_id' => $owner->id, 'name' => 'Inactive', 'slug' => 'inactive-cosmetics', 'catalog_type' => 'cosmetics', 'is_active' => false]);

        $this->get(route('cosmetics.store', $general))->assertNotFound();
        $this->get(route('cosmetics.store', $inactive))->assertNotFound();
    }
}
