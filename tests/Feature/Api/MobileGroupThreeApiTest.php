<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, Distributor, FrontOrder, ModularService, ModularServiceRequest, Product, ProductVariant, PushDevice, RealEstateLead, RealEstateProperty, RestaurantDriver, SalonAppointment, Shop, ShopOutlet, User};
use App\Services\{FirebaseMessagingService, WebPushService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/** Salon bookings, restaurant tables and drivers, electronics compare, service companies and krembo outlets in the app. */
class MobileGroupThreeApiTest extends TestCase
{
    use RefreshDatabase;

    private $firebase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firebase = Mockery::mock(FirebaseMessagingService::class);
        $this->firebase->shouldReceive('sendToTokens')->andReturn(1)->byDefault();
        $this->app->instance(FirebaseMessagingService::class, $this->firebase);
        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldIgnoreMissing();
        $this->app->instance(WebPushService::class, $webPush);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function shop(string $type, ?User $owner = null): Shop
    {
        $owner ??= User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        return Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type,
            'is_active' => true, 'is_accepting_orders' => true, 'phone' => '0599111222']);
    }

    public function test_a_customer_books_a_salon_and_the_owner_confirms(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $salon = $this->shop('cosmetics', $owner);
        PushDevice::create(['user_id' => $owner->id, 'token' => 'owner-phone', 'platform' => 'android']);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        PushDevice::create(['user_id' => $customer->id, 'token' => 'customer-phone', 'platform' => 'android']);
        $h = $this->auth($customer);
        $date = now()->addDays(2)->toDateString();

        $this->getJson("/api/v1/shops/{$salon->id}/booking")->assertOk()->assertJsonCount(5, 'times')->assertJsonPath('services.0', 'تسريحة شعر');
        $this->getJson("/api/v1/shops/{$this->shop('clothing')->id}/booking")->assertNotFound();
        $booking = ['service' => 'مكياج', 'date' => $date, 'time' => '12:00', 'name' => 'Lina', 'phone' => '0599123456'];
        $this->postJson("/api/v1/shops/{$salon->id}/booking", $booking, $h)->assertCreated();
        $this->postJson("/api/v1/shops/{$salon->id}/booking", $booking, $h)->assertStatus(422);
        $this->getJson("/api/v1/shops/{$salon->id}/booking/availability?date=$date")->assertJsonPath('booked_times', ['12:00']);
        $this->getJson('/api/v1/me/appointments', $h)->assertOk()->assertJsonPath('data.0.service', 'مكياج')->assertJsonPath('data.0.status', 'new')
            ->assertJsonMissingPath('data.0.customer_phone');

        $oh = $this->auth($owner);
        $this->getJson("/api/v1/merchant/shops/{$salon->id}/appointments", $h)->assertForbidden();
        $id = $this->getJson("/api/v1/merchant/shops/{$salon->id}/appointments", $oh)->assertOk()
            ->assertJsonPath('stats.new', 1)->assertJsonPath('data.0.customer_phone', '0599123456')->json('data.0.id');
        $this->patchJson("/api/v1/merchant/appointments/$id", ['status' => 'confirmed'], $oh)->assertOk()->assertJsonPath('appointment.status', 'confirmed');
        $this->assertSame('confirmed', SalonAppointment::find($id)->status);
    }

    public function test_restaurant_tables_drivers_and_tracking(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $restaurant = $this->shop('restaurant', $owner);
        $oh = $this->auth($owner);

        $table = $this->postJson("/api/v1/merchant/shops/{$restaurant->id}/tables", ['name' => 'طاولة 1', 'capacity' => 4], $oh)
            ->assertCreated()->json('table');
        $this->assertStringContainsString('/table/', $table['link']);
        $code = basename($table['link']);
        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/tables/$code")->assertOk()->assertJsonPath('table.name', 'طاولة 1');
        $this->getJson("/api/v1/restaurants/{$restaurant->slug}/tables/nope")->assertNotFound();
        $this->getJson("/api/v1/merchant/shops/{$restaurant->id}/tables", $oh)->assertJsonCount(1, 'data');

        $driverId = $this->postJson("/api/v1/merchant/shops/{$restaurant->id}/drivers", ['name' => 'Sami', 'email' => 'sami-driver@example.test',
            'phone' => '0599777888', 'password' => 'driver-pass-1', 'password_confirmation' => 'driver-pass-1'], $oh)->assertCreated()->json('driver.id');

        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $order = FrontOrder::create(['shop_id' => $restaurant->id, 'user_id' => $customer->id, 'order_number' => 'R-1001', 'order_type' => 'delivery', 'order_channel' => 'restaurant',
            'customer_name' => 'Lina', 'customer_phone' => '0599123456', 'customer_address' => 'Ramallah', 'items' => [], 'subtotal' => 30, 'total' => 30, 'status' => 'ready']);
        $this->getJson("/api/v1/merchant/orders/{$order->id}", $oh)->assertJsonPath('order.drivers.0.id', $driverId)->assertJsonPath('order.driver', null);
        $this->patchJson("/api/v1/merchant/orders/{$order->id}/driver", ['restaurant_driver_id' => $driverId], $oh)->assertOk()->assertJsonPath('order.driver.name', 'Sami');
        $order->update(['status' => 'out_for_delivery', 'estimated_delivery_at' => now()->addMinutes(20)]);
        $this->getJson("/api/v1/orders/{$order->id}", $this->auth($customer))->assertOk()
            ->assertJsonPath('order.driver.name', 'Sami')->assertJsonPath('order.driver.phone', '0599777888')
            ->assertJsonPath('order.estimated_delivery_at', fn ($at) => is_string($at));

        $this->patchJson("/api/v1/merchant/drivers/$driverId", ['is_active' => false], $oh)->assertOk()->assertJsonPath('driver.is_active', false);
        $this->assertFalse(RestaurantDriver::find($driverId)->user->is_active);
        $this->deleteJson("/api/v1/merchant/tables/{$table['id']}", [], $oh)->assertOk();
    }

    public function test_electronics_specs_and_compare(): void
    {
        $shop = $this->shop('electronics');
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Phones', 'slug' => 'phones-'.uniqid(), 'is_active' => true]);
        $make = function (string $name, string $brand, string $storage) use ($shop, $category) {
            $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name).uniqid(),
                'price' => 2000, 'is_active' => true, 'catalog_attributes' => ['brand' => $brand, 'screen_size' => '6.1"']]);
            $product->variants()->create(['storage' => $storage, 'price' => 2000, 'quantity' => 3, 'is_active' => true]);

            return $product;
        };
        $a = $make('Phone A', 'Apple', '128GB');
        $b = $make('Phone B', 'Samsung', '128GB');

        $this->getJson("/api/v1/products/{$a->id}")->assertOk()->assertJsonPath('product.comparable', true)
            ->assertJsonPath('product.specs.0', ['key' => 'brand', 'label' => 'الماركة', 'value' => 'Apple']);
        $rows = collect($this->getJson("/api/v1/shops/{$shop->id}/compare?ids={$a->id},{$b->id}")->assertOk()->json('rows'))->keyBy('key');
        $this->assertTrue($rows['brand']['different']);
        $this->assertFalse($rows['screen_size']['different']);
        $this->assertSame(['128GB', '128GB'], $rows['storage']['values']);
        $this->getJson("/api/v1/shops/{$shop->id}/compare?ids={$a->id}")->assertStatus(422);
    }

    public function test_service_company_requests_inquiries_and_owner_inbox(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $company = $this->shop('real_estate', $owner);
        $company->update(['whatsapp' => '0599000111']);
        $property = RealEstateProperty::create(['shop_id' => $company->id, 'slug' => 'flat-1', 'purpose' => 'rent', 'property_type' => 'apartment',
            'title' => 'شقة 3 غرف', 'price' => 2700, 'city' => 'رام الله', 'status' => 'published', 'published_at' => now()]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $h = $this->auth($customer);

        $json = $this->getJson("/api/v1/real-estate/companies/{$company->id}")->assertOk()->assertJsonPath('properties.0.title', 'شقة 3 غرف')->json();
        $this->assertNotEmpty($json['categories']);
        $serviceId = $json['categories'][0]['services'][0]['id'];
        $service = $this->getJson("/api/v1/real-estate/services/$serviceId")->assertOk()->json('service');
        $options = collect($service['groups'])->mapWithKeys(fn ($g) => [$g['id'] => $g['values'][0]['id']])->all();
        $this->postJson("/api/v1/real-estate/services/$serviceId/request", ['customer_name' => 'Lina', 'customer_phone' => '0599123456', 'options' => $options], $h)
            ->assertCreated()->assertJsonPath('whatsapp_url', fn ($url) => str_starts_with($url, 'https://wa.me/972599000111'));
        $this->assertSame(1, ModularServiceRequest::count());

        $this->getJson('/api/v1/real-estate/properties?purpose=rent')->assertOk()->assertJsonPath('data.0.id', $property->id);
        $this->getJson("/api/v1/real-estate/properties/{$property->id}")->assertOk()->assertJsonPath('property.purpose_label', 'للإيجار');
        $this->postJson("/api/v1/real-estate/properties/{$property->id}/inquiry", ['name' => 'Lina', 'phone' => '0599123456', 'message' => 'متاحة؟'], $h)->assertCreated();
        $this->assertSame(1, RealEstateLead::count());

        $oh = $this->auth($owner);
        $inbox = $this->getJson("/api/v1/merchant/shops/{$company->id}/inbox", $oh)->assertOk()->json();
        $this->assertCount(1, $inbox['requests']);
        $this->patchJson("/api/v1/merchant/service-requests/{$inbox['requests'][0]['id']}", ['status' => 'contacted'], $oh)->assertOk()->assertJsonPath('status', 'contacted');
        $this->patchJson("/api/v1/merchant/leads/{$inbox['leads'][0]['id']}", ['status' => 'viewing'], $oh)->assertOk()->assertJsonPath('status', 'viewing');
        $this->getJson("/api/v1/merchant/shops/{$company->id}/inbox", $h)->assertForbidden();
    }

    public function test_krembo_network_lists_outlets_by_region(): void
    {
        $company = $this->shop('krembo');
        ShopOutlet::create(['shop_id' => $company->id, 'name' => 'سوبرماركت النور', 'region' => 'نابلس', 'address' => 'شارع فيصل', 'is_active' => true]);
        Distributor::create(['shop_id' => $company->id, 'name' => 'موزع الشمال', 'is_active' => true]);

        $this->getJson("/api/v1/shops/{$company->id}/network")->assertOk()
            ->assertJsonPath('regions.0.region', 'نابلس')->assertJsonPath('regions.0.outlets.0.name', 'سوبرماركت النور')
            ->assertJsonPath('distributors.0.name', 'موزع الشمال');
        $this->getJson("/api/v1/shops/{$this->shop('sweets')->id}/network")->assertNotFound();
    }
}
