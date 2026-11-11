<?php

namespace Tests\Feature;

use App\Models\{FrontOrder, Shop, User};
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ShopOrdersBoardTest extends TestCase
{
    use RefreshDatabase;

    private array $pushes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturnUsing(function ($tokens, $title, $body, $url, $data = []) {
            $this->pushes[] = ['tokens' => collect($tokens)->values()->all(), 'data' => $data];
            return 1;
        });
        $this->app->instance(FirebaseMessagingService::class, $firebase);
    }

    private function shop(string $type = 'clothing'): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Style', 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        return [$shop, $owner];
    }

    private function order(Shop $shop, array $overrides = []): FrontOrder
    {
        return FrontOrder::create($overrides + [
            'shop_id' => $shop->id, 'order_number' => 'APP-261111-'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Lina', 'customer_phone' => '0599123456', 'customer_notes' => 'طريقة الاستلام: توصيل للعنوان',
            'customer_address' => 'رام الله', 'items' => [['product_id' => 1, 'name' => 'Shirt · M', 'qty' => 2, 'price' => 80]],
            'subtotal' => 160, 'discount' => 0, 'total' => 160, 'order_channel' => 'app', 'status' => 'new', 'payment_status' => 'pending',
            'customer_push_token' => 'customer-phone',
        ]);
    }

    public function test_the_owner_lands_on_a_live_board_of_cards_like_the_restaurant(): void
    {
        [$shop, $owner] = $this->shop();
        $order = $this->order($shop);
        $drinks = $this->order($shop, ['order_number' => 'ORD-20261111-AAAAA', 'customer_name' => 'Drink purchase']);

        $this->actingAs($owner)->get(route('front-orders.index'))->assertRedirect(route('shop-orders.dashboard', $shop));
        $this->get(route('front-orders.index', ['all' => 1]))->assertOk();

        $this->get(route('shop-orders.dashboard', $shop))->assertOk()
            ->assertSee('id="shop-order-'.$order->id.'"', false)
            ->assertSee('ابدأ التجهيز')
            ->assertDontSee('Drink purchase');

        $feed = $this->getJson(route('shop-orders.feed', $shop))->assertOk()->assertJsonPath('stats.new', 1)->assertJsonPath('latest_id', $order->id);
        $this->assertStringContainsString('shop-order-'.$order->id, $feed->json('html'));

        $this->get(route('shop-orders.show', [$shop, $order]))->assertOk()->assertSee('Shirt · M')->assertSee('رام الله')->assertSee('خرج للتوصيل');
    }

    public function test_changing_the_status_from_a_card_updates_it_and_tells_the_customer(): void
    {
        [$shop, $owner] = $this->shop();
        $order = $this->order($shop);

        $response = $this->actingAs($owner)->patchJson(route('shop-orders.status', $order), ['status' => 'preparing'])->assertOk();

        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertStringContainsString('shop-order-'.$order->id, $response->json('html'));
        $this->assertSame(['customer-phone'], $this->pushes[0]['tokens']);
        $this->assertSame('shop_order_status', $this->pushes[0]['data']['type']);

        $this->patchJson(route('shop-orders.status', $order), ['status' => 'eaten'])->assertStatus(422);
    }

    public function test_other_owners_and_restaurants_stay_out(): void
    {
        [$shop] = $this->shop();
        [, $stranger] = $this->shop();
        $order = $this->order($shop);
        $this->actingAs($stranger)->get(route('shop-orders.dashboard', $shop))->assertForbidden();
        $this->patchJson(route('shop-orders.status', $order), ['status' => 'ready'])->assertForbidden();

        [$restaurant, $chef] = $this->shop('restaurant');
        $this->actingAs($chef)->get(route('shop-orders.dashboard', $restaurant))->assertRedirect(route('restaurant.dashboard', $restaurant));
        $this->get(route('front-orders.index'))->assertRedirect(route('restaurant.dashboard', $restaurant));
    }
}
