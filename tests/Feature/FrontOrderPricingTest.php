<?php

namespace Tests\Feature;

use App\Models\FrontOrder;
use App\Models\ProductCampaign;
use App\Models\RewardWheel;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\MakesFrontOrderProducts;
use Tests\TestCase;

/** The main-store order total must come from the database, never from the browser. */
class FrontOrderPricingTest extends TestCase
{
    use MakesFrontOrderProducts;
    use RefreshDatabase;

    private function shop(): Shop
    {
        return Shop::create([
            'user_id' => User::factory()->create(['role' => 'shop_owner'])->id,
            'name' => 'Kola shop', 'slug' => 'kola-shop', 'is_active' => true,
        ]);
    }

    private function payload(Shop $shop, array $items, array $extra = []): array
    {
        return [
            'shop_id' => $shop->id, 'customer_name' => 'زبون', 'order_channel' => 'whatsapp',
            'visitor_type' => 'customer', 'items' => $items, ...$extra,
        ];
    }

    public function test_forged_prices_and_totals_are_replaced_by_database_prices(): void
    {
        Log::spy();
        $shop = $this->shop();
        $jacket = $this->frontOrderProduct($shop, ['customer_package_price' => 329, 'package_price' => 250], ['name' => 'جاكيت']);

        $this->postJson(route('front-orders.store'), $this->payload($shop, [
            $this->frontOrderItem($jacket, 'package', 2, ['name' => 'Forged', 'price' => '1 ₪']),
        ], ['subtotal' => 1, 'discount' => 0, 'total' => 1]))->assertOk();

        $order = FrontOrder::sole();
        $this->assertSame(['658.00', '0.00', '658.00'], [$order->subtotal, $order->discount, $order->total]);
        $this->assertSame('جاكيت', $order->items[0]['name']);
        $this->assertSame(329, (int) $order->items[0]['unit_price']);
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => $message === 'front_order.total_mismatch'
            && $context['client_total'] === 1.0 && $context['server_total'] === 658.0)->once();
    }

    public function test_five_percent_discount_needs_two_lines_and_is_computed_on_the_server(): void
    {
        $shop = $this->shop();
        $a = $this->frontOrderProduct($shop, ['customer_package_price' => 100]);
        $b = $this->frontOrderProduct($shop, ['customer_carton_price' => 60]);

        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($a)], ['discount' => 50]))->assertOk();
        $this->assertSame(['100.00', '0.00', '100.00'], [FrontOrder::latest('id')->first()->subtotal, FrontOrder::latest('id')->first()->discount, FrontOrder::latest('id')->first()->total]);

        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($a), $this->frontOrderItem($b, 'carton', 2)]))->assertOk();
        $order = FrontOrder::latest('id')->first();
        $this->assertSame(['220.00', '11.00', '209.00'], [$order->subtotal, $order->discount, $order->total]);
    }

    public function test_invalid_items_are_rejected(): void
    {
        $shop = $this->shop();
        $hiddenCarton = $this->frontOrderProduct($shop, ['customer_package_price' => 10, 'customer_carton_price' => null]);
        $inactive = $this->frontOrderProduct($shop, ['customer_package_price' => 10], ['is_active' => false]);
        $closedShop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Closed', 'slug' => 'closed', 'is_active' => false]);
        $fromClosedShop = $this->frontOrderProduct($closedShop, ['customer_package_price' => 10]);

        foreach ([
            [],
            [['unit_key' => 'package', 'qty' => 1]],
            [$this->frontOrderItem($hiddenCarton, 'package', 0)],
            [$this->frontOrderItem($hiddenCarton, 'package', -3)],
            [$this->frontOrderItem($hiddenCarton, 'wholesale_secret')],
            [$this->frontOrderItem($hiddenCarton, 'carton')],
            [$this->frontOrderItem($inactive)],
            [$this->frontOrderItem($fromClosedShop)],
            [['product_id' => 999999, 'unit_key' => 'package', 'qty' => 1]],
        ] as $items) {
            $this->postJson(route('front-orders.store'), $this->payload($shop, $items))->assertStatus(422);
        }

        $this->assertSame(0, FrontOrder::count());
    }

    public function test_customers_cannot_claim_merchant_prices_without_approval(): void
    {
        $shop = $this->shop();
        $product = $this->frontOrderProduct($shop, ['customer_package_price' => 20, 'package_price' => 12]);

        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($product)], ['visitor_type' => 'merchant']))->assertForbidden();
        $this->assertSame(0, FrontOrder::count());
    }

    public function test_running_campaigns_apply_to_customers_only_and_expired_ones_never(): void
    {
        $shop = $this->shop();
        $product = $this->frontOrderProduct($shop, ['customer_package_price' => 10, 'package_price' => 9]);
        ProductCampaign::create(['product_id' => $product->id, 'title' => '3 بـ25', 'type' => 'image', 'offer_type' => 'bundle_price', 'unit_key' => 'package', 'offer_quantity' => 3, 'offer_price' => 25]);
        $expired = $this->frontOrderProduct($shop, ['customer_package_price' => 10]);
        ProductCampaign::create(['product_id' => $expired->id, 'title' => 'انتهى', 'type' => 'image', 'offer_type' => 'bundle_price', 'unit_key' => 'package', 'offer_quantity' => 3, 'offer_price' => 1, 'ends_at' => now()->subDay()]);

        // 7 units: two bundles of 3 (25 each) + one at 10 = 60.
        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($product, 'package', 7)]))->assertOk();
        $this->assertSame('60.00', FrontOrder::latest('id')->first()->total);

        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($expired, 'package', 3)]))->assertOk();
        $this->assertSame('30.00', FrontOrder::latest('id')->first()->total);
    }

    public function test_signup_wheel_discount_must_match_a_real_segment(): void
    {
        $shop = $this->shop();
        $wheel = RewardWheel::create(['key' => RewardWheel::CUSTOMER_SIGNUP_DISCOUNTS, 'wheel_type' => RewardWheel::TYPE_CUSTOMER_SIGNUP, 'title' => 'خصمك الأول', 'is_active' => true]);
        $wheel->segments()->createMany([
            ['label' => '10%', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => true, 'sort_order' => 1],
            ['label' => '5 ₪', 'discount_type' => 'amount', 'discount_value' => 5, 'is_active' => true, 'sort_order' => 2],
            ['label' => '90% (معطّل)', 'discount_type' => 'percent', 'discount_value' => 90, 'is_active' => false, 'sort_order' => 3],
        ]);
        $product = $this->frontOrderProduct($shop, ['customer_package_price' => 100]);
        $item = [$this->frontOrderItem($product)];

        $this->postJson(route('front-orders.store'), $this->payload($shop, $item, ['signup_reward' => ['discount_type' => 'percent', 'discount_value' => 10]]))->assertOk();
        $this->assertSame(['100.00', '10.00', '90.00'], [FrontOrder::latest('id')->first()->subtotal, FrontOrder::latest('id')->first()->discount, FrontOrder::latest('id')->first()->total]);

        foreach ([['percent', 90], ['percent', 100], ['amount', 99], ['gift', 1]] as [$type, $value]) {
            $this->postJson(route('front-orders.store'), $this->payload($shop, $item, ['signup_reward' => ['discount_type' => $type, 'discount_value' => $value]]))->assertOk();
            $this->assertSame('100.00', FrontOrder::latest('id')->first()->total, "forged {$type} {$value} must be ignored");
        }
    }

    public function test_purchase_wheel_eligibility_uses_the_server_total(): void
    {
        $shop = $this->shop();
        $wheel = RewardWheel::create(['shop_id' => $shop->id, 'key' => 'purchase-'.$shop->id, 'wheel_type' => RewardWheel::TYPE_PURCHASE_AMOUNT, 'title' => 'فوق 200', 'min_order_total' => 200, 'is_active' => true]);
        $cheap = $this->frontOrderProduct($shop, ['customer_package_price' => 50]);

        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($cheap)], ['total' => 500, 'reward_wheel_id' => $wheel->id]))->assertStatus(422);
        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($cheap, 'package', 4)], ['reward_wheel_id' => $wheel->id]))->assertOk();
    }

    public function test_the_open_reward_endpoint_is_gone(): void
    {
        $shop = $this->shop();
        $product = $this->frontOrderProduct($shop, ['customer_package_price' => 100]);
        $this->postJson(route('front-orders.store'), $this->payload($shop, [$this->frontOrderItem($product)]))->assertOk();
        $order = FrontOrder::sole();

        $this->patchJson('/front-orders/'.$order->id.'/reward', ['reward_label' => 'خصم 100%', 'reward_discount_type' => 'percent', 'reward_discount_value' => 100])
            ->assertNotFound();
        $this->assertNull($order->fresh()->reward_label);
    }
}
