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
use App\Services\WebPushService;
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

        $webPush = \Mockery::mock(WebPushService::class);
        $webPush->shouldReceive('sendToShop')
            ->once()
            ->withArgs(function ($targetShop, $title, $body, $url, $data) use ($shop) {
                return $targetShop->is($shop)
                    && str_contains($title, $shop->name)
                    && str_contains($url, route('restaurant.dashboard', $shop))
                    && ($data['type'] ?? null) === 'restaurant_order'
                    && (int) ($data['shop_id'] ?? 0) === $shop->id;
            })
            ->andReturn(1);
        $this->app->instance(WebPushService::class, $webPush);

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

    public function test_restaurant_status_circles_update_by_ajax_and_keep_transition_rules(): void
    {
        [$shop, , $table] = $this->restaurant('ajax-status');
        $order = FrontOrder::create([
            'shop_id' => $shop->id,
            'restaurant_table_id' => $table->id,
            'order_number' => 'RST-AJAX-STATUS',
            'customer_name' => 'Ajax customer',
            'order_channel' => 'restaurant',
            'order_type' => 'dine_in',
            'status' => 'new',
        ]);

        $this->actingAs($shop->user)
            ->get(route('restaurant.dashboard', $shop))
            ->assertOk()
            ->assertSee('class="status-choices"', false)
            ->assertSee('data-label="تفاصيل الوجبات"', false)
            ->assertSee('data-label="الحالة"', false)
            ->assertSee('value="preparing"', false)
            ->assertSee('value="cancelled"', false);

        $this->actingAs($shop->user)
            ->patchJson(route('restaurant.orders.status', $order), ['status' => 'preparing'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estimated_preparation_minutes');

        $response = $this->actingAs($shop->user)
            ->patchJson(route('restaurant.orders.status', $order), [
                'status' => 'preparing',
                'estimated_preparation_minutes' => 20,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'preparing');

        $this->assertStringContainsString('status-choice-preparing is-current', $response->json('html'));
        $this->assertStringContainsString('value="ready"', $response->json('html'));
        $this->assertDatabaseHas('front_orders', [
            'id' => $order->id,
            'status' => 'preparing',
            'estimated_preparation_minutes' => 20,
        ]);

        $this->actingAs($shop->user)
            ->patchJson(route('restaurant.orders.status', $order), ['status' => 'completed'])
            ->assertUnprocessable();
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
            ->assertDontSee('قائمة الطعام')
            ->assertSee('اختر حجم الوجبة')
            ->assertSee('تجهيز خلال نحو 15 دقيقة')
            ->assertSee('تتبّع طلبك')
            ->assertSee('تصفح جميع المحلات')
            ->assertSee('class="ozman-directory-link"', false)
            ->assertSee('href="'.route('front.home').'"', false)
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
            ->assertSee('Browse all stores')
            ->assertSee('data-public-locale="ar"', false)
            ->assertSee('data-public-locale="he"', false)
            ->assertSee('data-public-locale="en"', false);
    }

    public function test_restaurant_directory_link_uses_the_main_ozman_logo(): void
    {
        $ozmanOwner = User::create([
            'name' => 'Ozman owner',
            'email' => 'ozman-directory-logo@example.com',
            'password' => 'password',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $logoPath = 'storage/shops/logos/ozman-main-logo.png';
        Shop::create([
            'user_id' => $ozmanOwner->id,
            'name' => 'Ozman',
            'slug' => 'ozman',
            'catalog_type' => 'general',
            'logo' => $logoPath,
            'is_active' => true,
        ]);
        [$restaurant] = $this->restaurant('directory-main-logo');

        $this->withSession(['locale' => 'ar'])
            ->get(route('restaurant.menu', $restaurant))
            ->assertOk()
            ->assertSee('تصفح جميع المحلات')
            ->assertSee(asset($logoPath), false);
    }

    public function test_restaurant_menu_shows_only_configured_social_platforms(): void
    {
        [$restaurant] = $this->restaurant('social-platforms');
        $restaurant->social()->create([
            'facebook' => 'https://facebook.com/bankai',
            'instagram' => '@bankai.sushi',
            'tiktok' => 'bankai.sushi',
            'whatsapp' => '0591234567',
        ]);

        $this->withSession(['locale' => 'ar'])
            ->get(route('restaurant.menu', $restaurant))
            ->assertOk()
            ->assertSee('class="restaurant-social-links"', false)
            ->assertSee('is-facebook', false)
            ->assertSee('https://facebook.com/bankai', false)
            ->assertSee('https://instagram.com/bankai.sushi', false)
            ->assertSee('https://tiktok.com/@bankai.sushi', false)
            ->assertSee('https://wa.me/0591234567', false)
            ->assertDontSee('href="https://youtube.com/', false)
            ->assertDontSee('href="https://snapchat.com/', false);
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

    public function test_restaurant_can_create_driver_assign_delivery_and_driver_can_finish_it(): void
    {
        [$shop] = $this->restaurant('delivery-driver');
        $this->actingAs($shop->user)
            ->post(route('restaurant.drivers.store', $shop), [
                'name' => 'Delivery Driver',
                'email' => 'delivery-driver@example.test',
                'phone' => '0591234567',
                'password' => 'driver-password',
                'password_confirmation' => 'driver-password',
            ])->assertRedirect();

        $driver = $shop->restaurantDrivers()->with('user')->firstOrFail();
        $order = FrontOrder::create([
            'shop_id' => $shop->id,
            'order_number' => 'RST-DELIVERY-DRIVER',
            'customer_name' => 'Delivery customer',
            'customer_phone' => '0599876543',
            'customer_address' => 'Nablus',
            'order_channel' => 'restaurant',
            'order_type' => 'delivery',
            'status' => 'ready',
            'total' => 50,
        ]);

        $webPush = \Mockery::mock(\App\Services\WebPushService::class);
        $webPush->shouldReceive('sendToDriver')->once()->withArgs(fn ($assignedDriver, $title, $body, $url) =>
            $assignedDriver->is($driver)
            && str_contains($title, $shop->name)
            && str_contains($body, $order->order_number)
            && str_contains($url, route('driver.dashboard'))
        )->andReturn(1);
        $this->app->instance(\App\Services\WebPushService::class, $webPush);

        $this->actingAs($shop->user)
            ->patch(route('restaurant.orders.driver', $order), ['restaurant_driver_id' => $driver->id])
            ->assertRedirect();
        $this->assertSame($driver->id, $order->fresh()->restaurant_driver_id);
        $this->actingAs($shop->user)
            ->patchJson(route('restaurant.orders.status', $order), ['status' => 'completed'])
            ->assertUnprocessable();

        $this->actingAs($driver->user)
            ->get(route('driver.dashboard'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('استلمت الطلب وخرجت للتوصيل');
        $this->actingAs($driver->user)
            ->getJson(route('driver.orders.feed'))
            ->assertOk()
            ->assertJsonPath('stats.assigned', 1);

        $this->actingAs($driver->user)
            ->patch(route('driver.orders.status', $order), ['status' => 'out_for_delivery'])
            ->assertRedirect();
        $this->assertSame('out_for_delivery', $order->fresh()->status);
        $this->actingAs($shop->user)
            ->patchJson(route('restaurant.orders.driver', $order), ['restaurant_driver_id' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('restaurant_driver_id');
        $this->actingAs($driver->user)
            ->patch(route('driver.orders.status', $order), ['status' => 'completed'])
            ->assertRedirect();
        $this->assertNotNull($order->fresh()->delivered_at);
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_driver_web_push_subscription_is_scoped_to_driver_and_removed_on_logout(): void
    {
        [$shop] = $this->restaurant('driver-browser-push');
        $driverUser = User::create([
            'name' => 'Driver', 'email' => 'browser-driver@example.test',
            'phone' => '0591234567', 'password' => 'password',
            'role' => 'restaurant_driver', 'is_active' => true,
        ]);
        $shop->restaurantDrivers()->create(['user_id' => $driverUser->id, 'is_active' => true]);
        $payload = ['subscription' => [
            'endpoint' => 'https://push.example.test/driver-endpoint',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
            'contentEncoding' => 'aes128gcm',
        ]];

        $this->actingAs($shop->user)->postJson(route('driver.push.store'), $payload)->assertForbidden();
        $this->actingAs($driverUser)->postJson(route('driver.push.store'), $payload)
            ->assertOk()->assertJsonPath('registered', true);
        $this->assertDatabaseHas('web_push_subscriptions', ['user_id' => $driverUser->id, 'shop_id' => $shop->id]);
        $this->actingAs($driverUser)->post(route('logout'))->assertRedirect();
        $this->assertDatabaseMissing('web_push_subscriptions', ['user_id' => $driverUser->id]);
    }

    public function test_restaurant_cannot_assign_another_restaurants_driver(): void
    {
        [$shop] = $this->restaurant('assign-own-driver');
        [$otherShop] = $this->restaurant('assign-foreign-driver');
        $foreignUser = User::create([
            'name' => 'Foreign driver', 'email' => 'foreign-driver@example.test',
            'phone' => '0591234567', 'password' => 'password',
            'role' => 'restaurant_driver', 'is_active' => true,
        ]);
        $foreignDriver = $otherShop->restaurantDrivers()->create(['user_id' => $foreignUser->id, 'is_active' => true]);
        $order = FrontOrder::create([
            'shop_id' => $shop->id, 'order_number' => 'RST-FOREIGN-DRIVER',
            'customer_name' => 'Customer', 'order_channel' => 'restaurant',
            'order_type' => 'delivery', 'status' => 'ready',
        ]);

        $this->actingAs($shop->user)
            ->patchJson(route('restaurant.orders.driver', $order), ['restaurant_driver_id' => $foreignDriver->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('restaurant_driver_id');
        $this->assertNull($order->fresh()->restaurant_driver_id);
        $this->actingAs($foreignUser)
            ->patch(route('driver.orders.status', $order), ['status' => 'out_for_delivery'])
            ->assertForbidden();
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
