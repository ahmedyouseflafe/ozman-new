<?php

namespace Tests\Feature;

use App\Models\{Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_fills_a_test_copy_once_and_never_runs_on_production(): void
    {
        $this->artisan('ozman:demo-data')->assertSuccessful();
        $this->assertSame(5, Shop::where('slug', 'like', 'demo-%')->count());
        $products = Product::count();
        $this->assertGreaterThan(15, $products);
        $this->assertTrue(User::where('email', 'demo-customer@staging.ozman.test')->where('role', 'customer')->exists());

        // Running again keeps the same data.
        $this->artisan('ozman:demo-data')->assertSuccessful();
        $this->assertSame($products, Product::count());
        $this->getJson('/api/v1/home')->assertOk()->assertJsonCount(5, 'types');

        $this->app['env'] = 'production';
        $this->artisan('ozman:demo-data')->assertFailed();
    }
}
