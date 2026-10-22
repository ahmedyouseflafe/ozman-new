<?php

namespace Tests\Feature;

use App\Models\{Category, FrontOrder, Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_sweets_fashion_and_furniture_save_before_whatsapp_with_authoritative_prices_and_retry_safety(): void
    {
        foreach (['sweets', 'clothing', 'furniture_appliances'] as $catalog) {
            $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => $catalog, 'slug' => $catalog, 'catalog_type' => $catalog, 'whatsapp' => '972501234567']);
            $category = Category::create(['shop_id' => $shop->id, 'name' => 'Products', 'slug' => 'products-'.$catalog]);
            $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Real product', 'slug' => 'real-product-'.$catalog, 'price' => 100, 'discount_price' => 80, 'is_active' => true, 'catalog_attributes' => ['sizes' => ['M'], 'colors' => ['Blue']]]);
            $payload = ['request_token' => (string) \Illuminate\Support\Str::uuid(), 'customer_name' => 'Customer', 'customer_phone' => '0591234567',
                'items' => [['product_id' => $product->id, 'qty' => 2, 'price' => 1, 'name' => 'Forged', 'size' => $catalog === 'clothing' ? 'M' : null, 'color' => null]], 'total' => 1];
            $response = $this->postJson(route('catalog.orders.store', $shop), $payload)->assertOk();
            $url = $response->json('whatsapp_url');
            $this->assertStringStartsWith('https://wa.me/972501234567?', $url);
            $this->assertStringContainsString('160.00', rawurldecode($url));
            $this->assertStringNotContainsString('Forged', rawurldecode($url));
            $order = FrontOrder::where('shop_id', $shop->id)->firstOrFail();
            $this->assertSame('160.00', $order->total);
            $this->assertSame('new', $order->status);
            $this->assertNull($order->sale_confirmed_at);
            $this->postJson(route('catalog.orders.store', $shop), $payload)->assertOk()->assertJsonPath('order_number', $order->order_number);
            $this->assertEquals(1, FrontOrder::where('shop_id', $shop->id)->count());
            $changed = $payload; $changed['items'][0]['qty'] = 3;
            $this->postJson(route('catalog.orders.store', $shop), $changed)->assertConflict();
            $payload['request_token'] = (string) \Illuminate\Support\Str::uuid();
            $payload['items'][0]['product_id'] = 999999;
            $this->postJson(route('catalog.orders.store', $shop), $payload)->assertUnprocessable();
            $this->assertDatabaseHas('shop_activity_events', ['kind' => 'order_created', 'subject_id' => $order->id]);
            $this->get($shop->publicUrl())->assertOk()->assertSee('OzmanSaveCatalogOrder', false);
            $this->actingAs($shop->user)->get(route('front-orders.index', ['shop_id' => $shop->id]))->assertOk();
            $this->patchJson(route('front-orders.status', $order), ['status' => 'completed'])->assertOk();
            $this->assertDatabaseHas('shop_activity_events', ['kind' => 'sale_confirmed', 'subject_id' => $order->id]);
        }
    }
}
