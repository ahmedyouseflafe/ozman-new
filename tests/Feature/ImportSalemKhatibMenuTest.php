<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportSalemKhatibMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_all_menu_items_with_prices_photos_and_is_idempotent(): void
    {
        $shop = Shop::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'مطعم ومشاوي سالم الخطيب',
            'slug' => 'salem-al-khatib',
            'catalog_type' => 'restaurant',
            'is_active' => true,
        ]);

        $this->artisan('restaurant:import-salem-menu', [
            'shop' => $shop->slug,
            '--dry-run' => true,
        ])->assertExitCode(0);
        $this->assertSame(0, Category::where('shop_id', $shop->id)->count());

        $this->artisan('restaurant:import-salem-menu', ['shop' => $shop->slug])->assertExitCode(0);
        $this->artisan('restaurant:import-salem-menu', ['shop' => $shop->slug])->assertExitCode(0);

        $this->assertSame(4, Category::where('shop_id', $shop->id)->count());
        $this->assertSame(25, Product::where('shop_id', $shop->id)->count());
        $this->assertSame(0, Product::where('shop_id', $shop->id)->whereNull('main_image')->count());

        $hummus = Product::where('shop_id', $shop->id)->where('name', 'حمص حب / فول / مسبحة')->firstOrFail();
        $this->assertSame('33.00', $hummus->price);
        $this->assertTrue(data_get($hummus->catalog_attributes, 'photo_is_illustrative'));
        $this->assertTrue(is_file(public_path($hummus->main_image)));

        $this->withSession(['locale' => 'ar'])->get(route('restaurant.menu', $shop))
            ->assertOk()
            ->assertSee('صورة توضيحية')
            ->assertSee('حمص حب / فول / مسبحة')
            ->assertSee('باجيت شنيتسل');
    }

    public function test_import_rejects_an_unrelated_restaurant(): void
    {
        $shop = Shop::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'مطعم آخر',
            'slug' => 'another-restaurant',
            'catalog_type' => 'restaurant',
        ]);

        $this->artisan('restaurant:import-salem-menu', ['shop' => $shop->slug])->assertExitCode(1);
        $this->assertSame(0, Category::where('shop_id', $shop->id)->count());
    }
}
