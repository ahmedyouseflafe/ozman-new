<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\FrontOrder;
use App\Models\EmployeePermission;
use App\Models\PushDevice;
use App\Models\RestaurantTable;
use App\Models\Shop;
use App\Models\User;
use App\Services\FirebaseMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_order_uses_server_prices_and_keeps_customizations(): void
    {
        [$shop, $product, $table] = $this->restaurant();

        $response = $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'dine_in',
            'table_code' => $table->code,
            'customer_name' => 'طاولة 1',
            'items' => [[
                'product_id' => $product->id, 'qty' => 2, 'size' => 'كبير',
                'addons' => ['جبنة'], 'excluded' => ['بصل'],
                'notes' => 'بدون ملح', 'price' => 1,
            ]],
        ])->assertOk();

        $order = \App\Models\FrontOrder::findOrFail($response->json('order_id') ?: 1);
        $this->assertSame($table->id, $order->restaurant_table_id);
        $this->assertSame('dine_in', $order->order_type);
        $this->assertSame('66.00', $order->total);
        $this->assertSame(['جبنة'], $order->items[0]['addons']);
        $this->assertSame(['بصل'], $order->items[0]['excluded']);
    }

    public function test_new_restaurant_order_push_is_sent_only_to_the_restaurant_owner_devices(): void
    {
        [$shop, $product, $table] = $this->restaurant('push-order');
        $foreignOwner = User::create([
            'name' => 'Foreign owner', 'email' => 'foreign-push@example.com', 'password' => 'password',
            'role' => 'shop_owner', 'is_active' => true,
        ]);
        PushDevice::create(['user_id' => $shop->user_id, 'token' => 'restaurant-owner-token', 'platform' => 'android']);
        PushDevice::create(['user_id' => $foreignOwner->id, 'token' => 'foreign-owner-token', 'platform' => 'android']);

        $firebase = \Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')
            ->once()
            ->withArgs(function ($tokens, $title, $body, $url, $data) use ($shop) {
                return collect($tokens)->values()->all() === ['restaurant-owner-token']
                    && $title === 'طلب جديد · '.$shop->name
                    && str_contains($body, 'طلب طاولة')
                    && str_contains($url, route('restaurant.dashboard', $shop))
                    && ($data['type'] ?? null) === 'restaurant_order'
                    && (int) ($data['shop_id'] ?? 0) === $shop->id;
            })
            ->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);

        $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'dine_in',
            'table_code' => $table->code,
            'customer_name' => 'Push customer',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertOk();
    }

    public function test_customer_gets_secure_live_tracking_and_a_push_on_each_status_change(): void
    {
        [$shop, $product, $table] = $this->restaurant('customer-tracking');
        $this->postJson(route('app.device-token.store'), [
            'token' => 'restaurant-customer-token',
            'platform' => 'android',
        ])->assertOk();

        $response = $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'dine_in',
            'table_code' => $table->code,
            'customer_name' => 'Tracked customer',
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ])->assertOk()
            ->assertJsonPath('estimated_preparation_minutes', 15)
            ->assertJsonPath('tracking.status', 'new')
            ->assertJsonPath('tracking.step', 1);

        $order = FrontOrder::findOrFail($response->json('order_id'));
        $trackingUrl = $response->json('tracking_url');
        $this->assertSame('restaurant-customer-token', $order->customer_push_token);
        $this->assertSame(15, $order->estimated_preparation_minutes);
        $this->assertSame(15, $order->items[0]['preparation_time']);

        $this->getJson($trackingUrl)
            ->assertOk()
            ->assertJsonPath('tracking.order_number', $order->order_number)
            ->assertJsonPath('tracking.status', 'new')
            ->assertJsonMissing(['customer_phone' => $order->customer_phone]);
        $this->get($trackingUrl)
            ->assertOk()
            ->assertSee('تتبّع طلبك')
            ->assertSee($order->order_number);
        $this->getJson(route('restaurant.orders.track', $order))->assertForbidden();

        $firebase = \Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')
            ->once()
            ->withArgs(function ($tokens, $title, $body, $url, $data) use ($order) {
                return collect($tokens)->values()->all() === ['restaurant-customer-token']
                    && $title === 'بدأ تحضير طلبك 🍳'
                    && str_contains($body, $order->order_number)
                    && str_contains($body, '22 دقيقة')
                    && str_contains($url, '/restaurant-orders/'.$order->id.'/tracking')
                    && str_contains($url, 'signature=')
                    && ($data['type'] ?? null) === 'restaurant_order_status'
                    && ($data['status'] ?? null) === 'preparing';
            })
            ->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);

        $this->actingAs($shop->user)
            ->patch(route('restaurant.orders.status', $order), [
                'status' => 'preparing',
                'estimated_preparation_minutes' => 22,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('front_orders', [
            'id' => $order->id,
            'status' => 'preparing',
            'estimated_preparation_minutes' => 22,
        ]);

        $this->getJson($trackingUrl)
            ->assertOk()
            ->assertJsonPath('tracking.status', 'preparing')
            ->assertJsonPath('tracking.step', 2)
            ->assertJsonPath('tracking.estimated_preparation_minutes', 22);
    }

    public function test_app_device_token_is_attached_after_owner_login_and_detached_on_logout(): void
    {
        [$shop] = $this->restaurant('device-login');

        $this->postJson(route('app.device-token.store'), [
            'token' => 'pending-owner-device-token',
            'platform' => 'android',
        ])->assertOk();
        $this->assertDatabaseHas('push_devices', [
            'token' => 'pending-owner-device-token',
            'user_id' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $shop->user->email,
            'password' => 'password',
        ])->assertRedirect(route('restaurant.dashboard', $shop));
        $this->assertDatabaseHas('push_devices', [
            'token' => 'pending-owner-device-token',
            'user_id' => $shop->user_id,
        ]);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('push_devices', [
            'token' => 'pending-owner-device-token',
            'user_id' => null,
        ]);
    }

    public function test_restaurant_order_rejects_forged_addon(): void
    {
        [$shop, $product, $table] = $this->restaurant('forged-addon');
        $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'dine_in', 'table_code' => $table->code, 'customer_name' => 'طاولة',
            'items' => [['product_id' => $product->id, 'qty' => 1, 'addons' => ['إضافة مزورة']]],
        ])->assertStatus(422);
        $this->assertDatabaseMissing('front_orders', ['shop_id' => $shop->id]);
    }

    public function test_product_from_another_restaurant_is_rejected(): void
    {
        [$shop] = $this->restaurant();
        [, $otherProduct] = $this->restaurant('other');

        $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'pickup', 'customer_name' => 'عميل', 'customer_phone' => '0591234567',
            'items' => [['product_id' => $otherProduct->id, 'qty' => 1]],
        ])->assertStatus(422);
    }

    public function test_restaurant_owner_cannot_open_another_restaurant_dashboard(): void
    {
        [$shop] = $this->restaurant('mine');
        [$otherShop] = $this->restaurant('not-mine');

        $this->actingAs($shop->user)
            ->get(route('restaurant.dashboard', $otherShop))
            ->assertForbidden();
    }

    public function test_shop_owner_front_orders_are_scoped_and_restaurant_owner_is_redirected(): void
    {
        [$restaurant] = $this->restaurant('redirect');
        $foreignOwner = User::create(['name' => 'Foreign', 'email' => 'foreign@test.test', 'password' => 'password', 'role' => 'shop_owner', 'is_active' => true]);
        $foreignShop = Shop::create(['user_id' => $foreignOwner->id, 'name' => 'Foreign Shop', 'slug' => 'foreign-shop', 'catalog_type' => 'general', 'is_active' => true]);
        FrontOrder::create(['shop_id' => $foreignShop->id, 'order_number' => 'FOREIGN-ORDER', 'customer_name' => 'Foreign customer', 'order_channel' => 'whatsapp']);

        $this->actingAs($restaurant->user)
            ->get(route('front-orders.index'))
            ->assertRedirect(route('restaurant.dashboard', $restaurant));
    }

    public function test_restaurant_owner_dashboard_entry_opens_the_restaurant_dashboard(): void
    {
        [$restaurant] = $this->restaurant('dashboard-entry');

        $this->actingAs($restaurant->user)
            ->get(route('dashboard'))
            ->assertRedirect(route('restaurant.dashboard', $restaurant));
    }

    public function test_restaurant_dashboard_contains_persistent_new_order_sound_alarm(): void
    {
        [$restaurant] = $this->restaurant('sound-alarm');

        $this->actingAs($restaurant->user)
            ->get(route('restaurant.dashboard', $restaurant))
            ->assertOk()
            ->assertSee('id="restaurant-order-alarm"', false)
            ->assertSee('تفعيل صوت الطلبات')
            ->assertSee('فتح الطلب الجديد وإيقاف صوت التنبيه')
            ->assertSee("ozman.restaurant.{$restaurant->id}.acknowledged-order", false);
    }

    public function test_restaurant_owner_can_open_and_close_only_their_restaurant(): void
    {
        [$restaurant, $product] = $this->restaurant('availability');
        [$otherRestaurant] = $this->restaurant('other-availability');

        $this->actingAs($restaurant->user)
            ->patch(route('restaurant.availability', $restaurant), ['is_accepting_orders' => false])
            ->assertRedirect();

        $this->assertFalse($restaurant->fresh()->is_accepting_orders);

        $this->withSession(['locale' => 'ar'])
            ->get(route('restaurant.menu', $restaurant))
            ->assertOk()
            ->assertSee('المطعم مغلق حالياً')
            ->assertSee('id="send" disabled', false);

        $this->postJson(route('restaurant.orders.store', $restaurant), [
            'order_type' => 'pickup',
            'customer_name' => 'Closed customer',
            'customer_phone' => '0591234567',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertStatus(409)
            ->assertJsonPath('message', 'المطعم مغلق حالياً ولا يستقبل طلبات جديدة.');

        $this->assertDatabaseMissing('front_orders', ['shop_id' => $restaurant->id]);

        $this->patch(route('restaurant.availability', $restaurant), ['is_accepting_orders' => true])
            ->assertRedirect();
        $this->assertTrue($restaurant->fresh()->is_accepting_orders);

        $this->patch(route('restaurant.availability', $otherRestaurant), ['is_accepting_orders' => false])
            ->assertForbidden();
        $this->assertTrue($otherRestaurant->fresh()->is_accepting_orders);
    }

    public function test_restaurant_dashboard_totals_only_its_completed_orders_and_updates_the_feed(): void
    {
        [$shop] = $this->restaurant('completed-sales-total');
        [$otherShop] = $this->restaurant('foreign-completed-sales-total');

        foreach ([
            [$shop->id, 'SALES-COMPLETED-1', 'completed', 125.75],
            [$shop->id, 'SALES-COMPLETED-2', 'completed', 40.50],
            [$shop->id, 'SALES-NEW', 'new', 999.00],
            [$shop->id, 'SALES-CANCELLED', 'cancelled', 777.00],
            [$otherShop->id, 'SALES-FOREIGN', 'completed', 888.00],
        ] as [$shopId, $number, $status, $total]) {
            FrontOrder::create([
                'shop_id' => $shopId,
                'order_number' => $number,
                'customer_name' => 'Sales customer',
                'order_channel' => 'restaurant',
                'order_type' => 'delivery',
                'status' => $status,
                'subtotal' => $total,
                'total' => $total,
            ]);
        }

        $this->actingAs($shop->user)
            ->get(route('restaurant.dashboard', $shop))
            ->assertOk()
            ->assertSee('إجمالي مبيعات المطعم')
            ->assertSee('166.25 ₪');

        $this->getJson(route('restaurant.orders.feed', $shop))
            ->assertOk()
            ->assertJsonPath('stats.sales_total', 166.25);
    }

    public function test_restaurant_status_cannot_move_backwards_or_use_generic_status_route(): void
    {
        [$shop, , $table] = $this->restaurant('status');
        $order = FrontOrder::create([
            'shop_id' => $shop->id, 'restaurant_table_id' => $table->id,
            'order_number' => 'RST-STATUS', 'customer_name' => 'Table',
            'order_channel' => 'restaurant', 'order_type' => 'dine_in', 'status' => 'ready',
        ]);

        $this->actingAs($shop->user)
            ->patch(route('restaurant.orders.status', $order), ['status' => 'preparing'])
            ->assertStatus(422);
        $this->actingAs($shop->user)
            ->patch(route('front-orders.status', $order), ['status' => 'completed'])
            ->assertStatus(422);
    }

    public function test_restaurant_must_set_preparation_time_before_starting_an_old_order(): void
    {
        [$shop, , $table] = $this->restaurant('required-preparation-time');
        $order = FrontOrder::create([
            'shop_id' => $shop->id,
            'restaurant_table_id' => $table->id,
            'order_number' => 'RST-NO-PREP-TIME',
            'customer_name' => 'Old customer',
            'order_channel' => 'restaurant',
            'order_type' => 'dine_in',
            'status' => 'new',
        ]);

        $this->actingAs($shop->user)
            ->patch(route('restaurant.orders.status', $order), ['status' => 'preparing'])
            ->assertSessionHasErrors('estimated_preparation_minutes');
        $this->assertSame('new', $order->fresh()->status);

        $this->actingAs($shop->user)
            ->get(route('restaurant.dashboard', $shop))
            ->assertOk()
            ->assertSee('name="estimated_preparation_minutes"', false)
            ->assertSee('حفظ وإشعار العميل');
    }

    public function test_general_shop_owner_only_sees_orders_for_owned_shop(): void
    {
        $owner = User::create(['name' => 'Owner A', 'email' => 'owner-a@test.test', 'password' => 'password', 'role' => 'shop_owner', 'is_active' => true]);
        $ownShop = Shop::create(['user_id' => $owner->id, 'name' => 'Own', 'slug' => 'own-general', 'catalog_type' => 'general', 'is_active' => true]);
        $otherOwner = User::create(['name' => 'Owner B', 'email' => 'owner-b@test.test', 'password' => 'password', 'role' => 'shop_owner', 'is_active' => true]);
        $otherShop = Shop::create(['user_id' => $otherOwner->id, 'name' => 'Other', 'slug' => 'other-general', 'catalog_type' => 'general', 'is_active' => true]);
        FrontOrder::create(['shop_id' => $ownShop->id, 'order_number' => 'OWN-ORDER', 'customer_name' => 'Own Customer', 'order_channel' => 'whatsapp']);
        FrontOrder::create(['shop_id' => $otherShop->id, 'order_number' => 'SECRET-FOREIGN-ORDER', 'customer_name' => 'Other Customer', 'order_channel' => 'whatsapp']);

        $this->actingAs($owner)->get(route('front-orders.index'))
            ->assertOk()->assertSee('OWN-ORDER')->assertDontSee('SECRET-FOREIGN-ORDER');
    }

    public function test_restaurant_permissions_separate_view_from_table_management(): void
    {
        [$shop] = $this->restaurant('permissions');
        EmployeePermission::create(['user_id' => $shop->user_id, 'permission' => 'restaurant.view']);

        $this->actingAs($shop->user)->get(route('restaurant.dashboard', $shop))->assertOk();
        $this->actingAs($shop->user)->post(route('restaurant.tables.store', $shop), [
            'name' => 'طاولة محظورة', 'capacity' => 4,
        ])->assertForbidden();
        $this->actingAs($shop->user)
            ->patch(route('restaurant.availability', $shop), ['is_accepting_orders' => false])
            ->assertForbidden();
        $this->assertTrue($shop->fresh()->is_accepting_orders);
    }

    public function test_restaurant_view_permission_can_poll_only_its_own_live_orders(): void
    {
        [$shop, , $table] = $this->restaurant('live-feed');
        [$otherShop] = $this->restaurant('foreign-live-feed');
        EmployeePermission::create(['user_id' => $shop->user_id, 'permission' => 'restaurant.view']);
        $ownOrder = FrontOrder::create([
            'shop_id' => $shop->id,
            'restaurant_table_id' => $table->id,
            'order_number' => 'LIVE-OWN-ORDER',
            'customer_name' => 'Own table',
            'order_channel' => 'restaurant',
            'order_type' => 'dine_in',
            'status' => 'new',
        ]);
        FrontOrder::create([
            'shop_id' => $otherShop->id,
            'order_number' => 'LIVE-FOREIGN-SECRET',
            'customer_name' => 'Foreign table',
            'order_channel' => 'restaurant',
            'order_type' => 'dine_in',
            'status' => 'new',
        ]);

        $this->actingAs($shop->user)
            ->getJson(route('restaurant.orders.feed', $shop))
            ->assertOk()
            ->assertJsonPath('stats.new', 1)
            ->assertJsonPath('latest_id', $ownOrder->id)
            ->assertJsonPath('latest_order.number', 'LIVE-OWN-ORDER')
            ->assertSee('LIVE-OWN-ORDER')
            ->assertDontSee('LIVE-FOREIGN-SECRET');

        $this->getJson(route('restaurant.orders.feed', $otherShop))->assertForbidden();
    }

    public function test_restaurant_uses_its_menu_instead_of_general_package_ordering(): void
    {
        [$shop] = $this->restaurant('restaurant-menu');

        $this->withHeader('Accept-Language', 'ar')->get(route('front.shop.slug', $shop))
            ->assertRedirect(route('restaurant.menu', $shop));

        $this->withSession(['locale' => 'ar'])->get(route('restaurant.menu', $shop))
            ->assertOk()
            ->assertSee('قائمة الطعام')
            ->assertSee('اختر حجم الوجبة')
            ->assertSee('تجهيز خلال نحو 15 دقيقة')
            ->assertSee('تتبّع طلبك')
            ->assertDontSee('اختر نوع السعر المناسب قبل إضافة المنتج إلى السلة')
            ->assertDontSee('العبوة');
    }

    public function test_restaurant_menu_uses_device_language_and_shows_language_switcher(): void
    {
        [$shop] = $this->restaurant('restaurant-language');

        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get(route('restaurant.menu', $shop))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Food menu')
            ->assertSee('Choose meal size')
            ->assertSee('data-public-locale="ar"', false)
            ->assertSee('data-public-locale="he"', false)
            ->assertSee('data-public-locale="en"', false);
    }

    public function test_restaurant_menu_shows_active_categories_without_products(): void
    {
        [$shop] = $this->restaurant('empty-category');
        Category::create([
            'shop_id' => $shop->id,
            'name' => 'Empty restaurant category',
            'slug' => 'empty-restaurant-category',
            'is_active' => true,
        ]);

        $this->get(route('restaurant.menu', $shop))
            ->assertOk()
            ->assertSee('Empty restaurant category')
            ->assertSee('data-category-key=', false);
    }

    public function test_delivery_order_requires_and_stores_customer_location(): void
    {
        [$shop, $product] = $this->restaurant('delivery-location');
        $payload = [
            'order_type' => 'delivery',
            'customer_name' => 'Delivery Customer',
            'customer_phone' => '0591234567',
            'customer_address' => 'Nablus',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ];

        $this->postJson(route('restaurant.orders.store', $shop), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        $response = $this->postJson(route('restaurant.orders.store', $shop), $payload + [
            'latitude' => 32.2211000,
            'longitude' => 35.2544000,
        ])->assertOk();

        $order = FrontOrder::findOrFail($response->json('order_id'));
        $this->assertEqualsWithDelta(32.2211, (float) $order->latitude, 0.0000001);
        $this->assertEqualsWithDelta(35.2544, (float) $order->longitude, 0.0000001);
        $this->assertSame('https://www.google.com/maps?q=32.2211,35.2544', $order->map_link);
    }

    public function test_valid_table_qr_forces_table_order_and_dashboard_shows_table_name(): void
    {
        [$shop, $product, $table] = $this->restaurant('qr-table');

        $response = $this->postJson(route('restaurant.orders.store', $shop), [
            'order_type' => 'pickup',
            'table_code' => $table->code,
            'customer_name' => 'QR Customer',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertOk();

        $order = FrontOrder::findOrFail($response->json('order_id'));
        $this->assertSame('dine_in', $order->order_type);
        $this->assertSame($table->id, $order->restaurant_table_id);

        $this->actingAs($shop->user)
            ->get(route('restaurant.dashboard', $shop))
            ->assertOk()
            ->assertSee('طلب طاولة')
            ->assertSee($table->name);
    }

    private function restaurant(string $suffix = 'main'): array
    {
        $owner = User::create(['name' => 'Owner', 'email' => "$suffix@restaurant.test", 'password' => 'password', 'role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => "Restaurant $suffix", 'slug' => "restaurant-$suffix", 'catalog_type' => 'restaurant', 'is_active' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'وجبات', 'slug' => "meals-$suffix", 'is_active' => true]);
        $product = Product::create([
            'shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'برغر', 'slug' => "burger-$suffix",
            'price' => 20, 'quantity' => 50, 'is_active' => true,
            'catalog_attributes' => [
                'meal_size_prices' => ['صغير:20', 'كبير:30'],
                'addon_prices' => ['جبنة:3'],
                'preparation_time' => 15,
                'ingredients' => 'بصل، بندورة',
                'removable_ingredients' => ['بصل'],
            ],
        ]);
        $table = RestaurantTable::create(['shop_id' => $shop->id, 'name' => 'طاولة 1', 'code' => "table-$suffix", 'is_active' => true]);
        return [$shop, $product, $table];
    }
}
