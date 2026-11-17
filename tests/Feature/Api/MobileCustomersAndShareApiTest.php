<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, FrontOrder, Shop, User, VisitorRegistration};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The shop's registered customers in the app, and the customer's order sent to the shop on WhatsApp. */
class MobileCustomersAndShareApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    public function test_the_owner_sees_and_searches_the_shops_registered_customers_only(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Shop', 'slug' => 's-'.uniqid(), 'catalog_type' => 'clothing', 'is_active' => true]);
        $other = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Other', 'slug' => 'o-'.uniqid(), 'catalog_type' => 'clothing', 'is_active' => true]);
        VisitorRegistration::create(['shop_id' => $shop->id, 'type' => 'customer', 'name' => 'ليلى', 'phone' => '0591111111', 'residence_address' => 'رام الله', 'latitude' => 31.9, 'longitude' => 35.2]);
        VisitorRegistration::create(['shop_id' => $shop->id, 'type' => 'customer', 'name' => 'سامي', 'phone' => '0592222222', 'residence_address' => 'نابلس']);
        VisitorRegistration::create(['shop_id' => $shop->id, 'type' => 'merchant', 'name' => 'تاجر', 'phone' => '0593333333', 'residence_address' => 'x']);
        VisitorRegistration::create(['shop_id' => $other->id, 'type' => 'customer', 'name' => 'غريب', 'phone' => '0594444444', 'residence_address' => 'x']);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/customers", $h)->assertOk()->assertJsonPath('total', 2)->assertJsonCount(2, 'data')
            ->assertJsonPath('next_page', null);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/customers?search=نابلس", $h)->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'سامي')
            ->assertJsonPath('total', 2);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/customers?search=ليلى", $h)->assertJsonPath('data.0.map_link', fn ($link) => str_contains($link, '31.9'));
        $this->getJson("/api/v1/merchant/shops/{$other->id}/customers", $h)->assertForbidden();
    }

    public function test_the_customer_gets_a_whatsapp_link_with_the_order_written_out(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Shop', 'slug' => 's-'.uniqid(), 'catalog_type' => 'clothing', 'is_active' => true, 'whatsapp' => '0599000111']);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $order = FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $customer->id, 'order_number' => 'APP-1', 'order_channel' => 'app',
            'customer_name' => 'Lina', 'customer_phone' => '0599123456', 'items' => [['name' => 'فستان', 'qty' => 2, 'price' => 50]], 'subtotal' => 100, 'total' => 100, 'status' => 'new']);

        $url = $this->getJson("/api/v1/orders/{$order->id}", $this->auth($customer))->assertOk()->json('order.whatsapp_url');
        $this->assertStringStartsWith('https://wa.me/972599000111?text=', $url);
        $this->assertStringContainsString('APP-1', rawurldecode($url));
        $this->assertStringContainsString('فستان', rawurldecode($url));
        $this->assertStringContainsString('🛍 الأصناف', rawurldecode($url));
        $this->assertStringNotContainsString('نوع الطلب', rawurldecode($url));

        $order->update(['status' => 'cancelled']);
        $this->getJson("/api/v1/orders/{$order->id}", $this->auth($customer))->assertJsonPath('order.whatsapp_url', null);
    }
}
