<?php

namespace Tests\Feature;

use App\Models\{MarketListing, Product, PromoterPost, Shop, User};
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_fills_a_test_copy_once_and_never_runs_on_production(): void
    {
        Storage::fake('local');
        $this->artisan('ozman:demo-data')->assertSuccessful();
        $this->assertSame(7, Shop::where('slug', 'like', 'demo-%')->count());
        $this->assertSame(1, Shop::where('slug', 'demo-krembo')->sole()->outlets()->count());
        $this->assertTrue(\App\Models\Distributor::whereHas('user', fn ($q) => $q->where('email', 'demo-distributor@staging.ozman.test'))->exists());
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
        $this->getJson('/api/v1/home')->assertOk()->assertJsonCount(7, 'types');

        $this->app['env'] = 'production';
        $this->artisan('ozman:demo-data')->assertFailed();
    }
}
