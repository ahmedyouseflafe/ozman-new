<?php

namespace Tests\Feature;

use App\Models\{Category, FrontOrder, Product, Shop, ShopCoupon, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\MakesFrontOrderProducts;
use Tests\TestCase;

/** The discount codes owners make in the app work on the website's checkouts too. */
class WebCouponsTest extends TestCase
{
    use MakesFrontOrderProducts;
    use RefreshDatabase;

    private function coupon(Shop $shop, array $extra = []): ShopCoupon
    {
        return ShopCoupon::create(['shop_id' => $shop->id, 'code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'is_active' => true, 'used_count' => 0, ...$extra]);
    }

    public function test_the_checkout_check_says_what_a_code_gives_and_rejects_wrong_ones(): void
    {
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Shop', 'slug' => 'shop', 'catalog_type' => 'clothing', 'is_active' => true]);
        $this->coupon($shop, ['min_total' => 50]);

        $this->postJson(route('shop.coupon-check', $shop), ['coupon_code' => ' save10 '])->assertOk()
            ->assertJsonPath('coupon.code', 'SAVE10')
            ->assertJsonPath('coupon.label', fn ($label) => str_contains($label, '10%') && str_contains($label, '50'));
        $this->postJson(route('shop.coupon-check', $shop), ['coupon_code' => 'NOPE'])->assertUnprocessable()->assertJsonValidationErrors('coupon_code');
    }

    public function test_catalog_checkout_takes_the_code_off_once_and_shows_it_in_the_whatsapp_message(): void
    {
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Sweets', 'slug' => 'sweets', 'catalog_type' => 'sweets', 'whatsapp' => '972501234567', 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Cakes', 'slug' => 'cakes']);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Cake', 'slug' => 'cake', 'price' => 80, 'is_active' => true]);
        $coupon = $this->coupon($shop, ['min_total' => 100]);
        $payload = ['request_token' => (string) Str::uuid(), 'customer_name' => 'Customer', 'customer_phone' => '0591234567',
            'items' => [['product_id' => $product->id, 'qty' => 1]], 'coupon_code' => 'save10'];

        // Too small: nothing is saved, and the customer hears why.
        $this->postJson(route('catalog.orders.store', $shop), $payload)->assertUnprocessable()->assertJsonValidationErrors('coupon_code');
        $this->assertSame(0, FrontOrder::count());

        $payload['items'][0]['qty'] = 2;
        $response = $this->postJson(route('catalog.orders.store', $shop), $payload)->assertOk()
            ->assertJsonPath('coupon_discount', 16)->assertJsonPath('total', 144);
        $this->assertStringContainsString('SAVE10', rawurldecode($response->json('whatsapp_url')));
        $order = FrontOrder::sole();
        $this->assertSame(['160.00', '16.00', '144.00', 'SAVE10'], [$order->subtotal, $order->discount, $order->total, $order->coupon_code]);

        // A retry of the same order doesn't take it off (or count it) twice.
        $this->postJson(route('catalog.orders.store', $shop), $payload)->assertOk()->assertJsonPath('order_number', $order->order_number);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame('144.00', $order->fresh()->total);
    }

    public function test_the_main_storefront_takes_the_code_off_and_stops_once_its_uses_run_out(): void
    {
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Kola', 'slug' => 'kola', 'is_active' => true]);
        $item = $this->frontOrderProduct($shop, ['customer_package_price' => 100]);
        $coupon = $this->coupon($shop, ['type' => 'amount', 'value' => 15, 'max_uses' => 1]);
        $payload = ['shop_id' => $shop->id, 'customer_name' => 'زبون', 'order_channel' => 'whatsapp', 'visitor_type' => 'customer',
            'items' => [$this->frontOrderItem($item)], 'coupon_code' => 'SAVE10'];

        $this->postJson(route('front-orders.store'), $payload)->assertOk()->assertJsonPath('coupon_discount', 15)->assertJsonPath('total', 85);
        $this->assertSame(['100.00', '15.00', '85.00'], [FrontOrder::sole()->subtotal, FrontOrder::sole()->discount, FrontOrder::sole()->total]);
        $this->assertSame(1, $coupon->fresh()->used_count);

        // Its only use is gone: the next order is stopped before it's saved.
        $this->postJson(route('front-orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('coupon_code');
        $this->assertSame(1, FrontOrder::count());
    }
}
