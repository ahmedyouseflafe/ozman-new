<?php

namespace Tests\Feature;

use App\Models\{MarketListing, Product, PromoterPost, Shop, ShopCoupon, User};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_fills_a_test_copy_once_and_never_runs_on_production(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Http::fake(['raw.githubusercontent.com/*' => Http::response('glTF-demo-chair', 200)]);
        $this->artisan('ozman:demo-data')->assertSuccessful();
        $this->assertSame(8, Shop::where('slug', 'like', 'demo-%')->count());
        $this->assertSame(1, Shop::where('slug', 'demo-krembo')->sole()->outlets()->count());
        $this->assertTrue(\App\Models\Distributor::whereHas('user', fn ($q) => $q->where('email', 'demo-distributor@staging.ozman.test'))->exists());
        $this->assertSame(3, Shop::where('slug', 'demo-krembo')->sole()->products()->count());
        $this->assertTrue(ShopCoupon::where('code', 'DEMO10')->exists());
        $this->assertSame('0599000111', User::where('email', 'demo-customer@staging.ozman.test')->value('phone'));
        $chair = Product::where('name', 'كرسي صالون مخمل')->sole();
        Storage::disk('public')->assertExists($chair->model_3d);
        $this->getJson('/api/v1/products/'.$chair->id)->assertOk()->assertJsonPath('product.ar_url', route('products.ar', $chair));
        $products = Product::count();
        $this->assertGreaterThan(15, $products);
        $this->assertTrue(User::where('email', 'demo-customer@staging.ozman.test')->where('role', 'customer')->exists());

        // Running again keeps the same data.
        $this->artisan('ozman:demo-data')->assertSuccessful();
        $this->assertSame($products, Product::count());
        $this->assertSame(6, PromoterPost::count());
        $this->assertSame(3, MarketListing::count());
        Storage::disk('local')->assertExists(PromoterPost::first()->media);
        $this->getJson('/api/v1/promoters')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/market')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/home')->assertOk()->assertJsonCount(8, 'types');

        $this->app['env'] = 'production';
        $this->artisan('ozman:demo-data')->assertFailed();
    }
}
