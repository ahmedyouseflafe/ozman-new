<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorMarketer;
use App\Models\EmployeePermission;
use App\Models\FrontOrder;
use App\Models\RaffleCard;
use App\Models\Shop;
use App\Models\User;
use App\Models\VisitorRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MerchantOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_creates_a_separate_shop_owner_account_linked_to_him(): void
    {
        $distributorUser = User::create([
            'name' => 'Main distributor',
            'email' => 'main-distributor@example.com',
            'password' => 'secret123',
            'role' => 'distributor',
            'is_active' => true,
        ]);

        $baseShop = Shop::create([
            'user_id' => $distributorUser->id,
            'name' => 'Distributor base shop',
            'slug' => 'distributor-base-shop',
            'is_active' => true,
        ]);

        $distributor = Distributor::create([
            'shop_id' => $baseShop->id,
            'user_id' => $distributorUser->id,
            'name' => 'Main distributor',
            'is_active' => true,
        ]);
        EmployeePermission::create([
            'user_id' => $distributorUser->id,
            'permission' => 'shops.create',
        ]);

        $response = $this->actingAs($distributorUser)->post(route('shops.store'), [
            'name' => 'New linked merchant',
            'owner_email' => 'new-merchant@example.com',
            'owner_password' => 'secret123',
            'owner_password_confirmation' => 'secret123',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('shops'));

        $merchant = User::query()->where('email', 'new-merchant@example.com')->firstOrFail();
        $shop = Shop::query()->where('name', 'New linked merchant')->firstOrFail();

        $this->assertTrue($merchant->isShopOwner());
        $this->assertNotSame($distributorUser->id, $merchant->id);
        $this->assertSame($merchant->id, $shop->user_id);
        $this->assertSame($distributor->id, $shop->distributor_id);
    }

    public function test_marketer_created_shop_orders_reach_distributor_and_credit_marketer_commission(): void
    {
        $distributorUser = User::create([
            'name' => 'Commission distributor',
            'email' => 'commission-distributor@example.com',
            'password' => 'secret123',
            'role' => 'distributor',
            'is_active' => true,
        ]);
        $baseShop = Shop::create([
            'user_id' => $distributorUser->id,
            'name' => 'Commission base shop',
            'slug' => 'commission-base-shop',
            'is_active' => true,
        ]);
        $distributor = Distributor::create([
            'shop_id' => $baseShop->id,
            'user_id' => $distributorUser->id,
            'name' => 'Commission distributor',
            'is_active' => true,
        ]);

        $marketerUser = User::create([
            'name' => 'Commission marketer',
            'email' => 'commission-marketer@example.com',
            'password' => 'secret123',
            'role' => 'marketer',
            'is_active' => true,
        ]);
        $marketer = DistributorMarketer::create([
            'distributor_id' => $distributor->id,
            'user_id' => $marketerUser->id,
            'name' => 'Commission marketer',
            'tracking_code' => 'commission-marketer',
            'commission_rate' => 7.5,
            'is_active' => true,
        ]);
        EmployeePermission::create([
            'user_id' => $marketerUser->id,
            'permission' => 'shops.marketer_create',
        ]);

        $this->actingAs($marketerUser)->post(route('shops.store'), [
            'name' => 'Marketer linked merchant',
            'owner_email' => 'marketer-linked-merchant@example.com',
            'owner_password' => 'secret123',
            'owner_password_confirmation' => 'secret123',
            'is_active' => '1',
        ])->assertRedirect(route('shops'));

        $merchant = User::query()->where('email', 'marketer-linked-merchant@example.com')->firstOrFail();
        $merchantShop = Shop::query()->where('name', 'Marketer linked merchant')->firstOrFail();

        $this->assertSame($distributor->id, $merchantShop->distributor_id);
        $this->assertSame($marketer->id, $merchantShop->distributor_marketer_id);

        $this->actingAs($merchant)->postJson(route('front-orders.store'), [
            'customer_name' => 'Marketer linked merchant',
            'items' => [['name' => 'طلب عمولة', 'price' => '200', 'qty' => 1]],
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'order_channel' => 'whatsapp',
            'visitor_type' => 'merchant',
        ])->assertOk();

        $order = FrontOrder::query()->latest('id')->firstOrFail();
        $this->assertSame($distributor->id, $order->distributor_id);
        $this->assertSame($marketer->id, $order->distributor_marketer_id);
        $this->assertSame('marketer', $order->marketing_source);
        $this->assertSame('7.50', $order->marketer_commission_rate);
        $this->assertSame('15.00', $order->marketer_commission_amount);
        $this->assertTrue($marketer->frontOrders()->whereKey($order->id)->exists());
    }

    public function test_shop_owner_can_login_from_the_merchant_screen(): void
    {
        [$merchant, $merchantShop] = $this->merchantLinkedToDistributor();

        $response = $this->post(route('merchant.login.store'), [
            'email' => $merchant->email,
            'password' => 'secret123',
            'redirect' => 'https://malicious.example/steal',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($merchant);
        $this->assertSame($merchantShop->id, session('merchant_shop_id'));
    }

    public function test_authenticated_shop_owner_can_check_raffle_card_without_entering_customer_data_again(): void
    {
        [$merchant, $merchantShop] = $this->merchantLinkedToDistributor('raffle-owner');
        $merchantShop->update(['address' => 'عنوان المتجر الثابت']);
        $card = RaffleCard::create([
            'card_number' => '654321',
            'prize_title' => 'هدية اختبار',
            'is_active' => true,
        ]);

        $this->actingAs($merchant)
            ->postJson(route('raffle.check'), ['card_number' => '654321'])
            ->assertOk();

        $card->refresh();
        $this->assertNotNull($card->used_at);
        $this->assertSame($merchantShop->name, $card->used_customer_name);
        $this->assertSame($merchantShop->phone, $card->used_customer_phone);
        $this->assertSame('عنوان المتجر الثابت', $card->used_customer_payload['address']);
    }

    public function test_approved_qr_merchant_can_check_raffle_card_without_customer_form(): void
    {
        $registration = VisitorRegistration::create([
            'type' => 'merchant',
            'status' => 'approved',
            'public_token' => str_repeat('a', 64),
            'name' => 'Approved merchant',
            'shop_name' => 'Approved QR shop',
            'phone' => '0591234567',
            'residence_address' => 'Merchant residence',
            'business_location' => 'Merchant shop location',
            'latitude' => 31.5,
            'longitude' => 34.4,
            'map_link' => 'https://www.google.com/maps?q=31.5,34.4',
            'approved_at' => now(),
        ]);
        $card = RaffleCard::create([
            'card_number' => '654322',
            'prize_title' => 'Approved merchant prize',
            'is_active' => true,
        ]);

        $this->postJson(route('raffle.check'), [
            'card_number' => $card->card_number,
            'merchant_registration_token' => $registration->public_token,
        ])->assertOk();

        $card->refresh();
        $this->assertSame('Approved QR shop', $card->used_customer_name);
        $this->assertSame('0591234567', $card->used_customer_phone);
        $this->assertSame('Merchant shop location', $card->used_customer_payload['address']);
    }

    public function test_distributor_and_marketer_qr_visitors_are_auto_approved_and_published(): void
    {
        [, , $distributor] = $this->merchantLinkedToDistributor('auto-approve');
        $marketer = DistributorMarketer::create([
            'distributor_id' => $distributor->id,
            'name' => 'Auto approval marketer',
            'tracking_code' => 'auto-approval-marketer',
            'is_active' => true,
        ]);

        $referralUrls = [
            [route('front.distributor', $distributor), null, '0591234567'],
            [route('front.marketer', $marketer), $marketer->id, '0591234568'],
        ];

        foreach ($referralUrls as $index => [$referralUrl, $marketerId, $phone]) {
            $this->get($referralUrl)->assertOk();
            $response = $this->postJson(route('visitor-registrations.store'), [
                'type' => 'merchant',
                'name' => "QR Merchant {$index}",
                'phone' => $phone,
                'shop_name' => "Published QR Shop {$index}",
                'tax_file' => "TAX-{$index}",
                'business_location' => "Business location {$index}",
                'residence_address' => "Residence {$index}",
                'latitude' => 31.5 + ($index / 10),
                'longitude' => 34.4 + ($index / 10),
                'map_link' => 'https://www.google.com/maps?q=31.5,34.4',
            ])->assertCreated()->assertJsonPath('status', 'approved');

            $shop = Shop::query()->where('name', "Published QR Shop {$index}")->firstOrFail();
            $this->assertTrue($shop->is_active);
            $this->assertTrue($shop->show_ozman_products);
            $this->assertSame($distributor->id, $shop->distributor_id);
            $this->assertSame($marketerId, $shop->distributor_marketer_id);
            $this->assertSame($shop->id, $response->json('shop_id'));

            $centerIds = collect($this->get(route('home'))->viewData('frontData')['centersData'])->pluck('id');
            $this->assertTrue($centerIds->contains($shop->id));
        }
    }

    public function test_authenticated_merchant_order_ignores_spoofed_distributor_and_shop(): void
    {
        [$merchant, $merchantShop, $linkedDistributor] = $this->merchantLinkedToDistributor();
        $merchantShop->update([
            'address' => 'عنوان المتجر المحفوظ',
            'latitude' => 32.2211,
            'longitude' => 35.2544,
        ]);
        [, $otherShop, $otherDistributor] = $this->merchantLinkedToDistributor('other');

        $response = $this->actingAs($merchant)->postJson(route('front-orders.store'), [
            'shop_id' => $otherShop->id,
            'distributor_id' => $otherDistributor->id,
            'marketing_source' => 'distributor',
            'customer_name' => 'متجر الاختبار',
            'customer_phone' => '0590000000',
            'items' => [['name' => 'كرتونة', 'price' => '10', 'qty' => 2]],
            'subtotal' => 20,
            'discount' => 0,
            'total' => 20,
            'order_channel' => 'whatsapp',
            'visitor_type' => 'customer',
        ]);

        $response->assertOk();

        $order = FrontOrder::query()->latest('id')->firstOrFail();
        $this->assertSame($merchantShop->id, $order->shop_id);
        $this->assertSame($linkedDistributor->id, $order->distributor_id);
        $this->assertSame('merchant_account', $order->marketing_source);
        $this->assertSame($merchantShop->name, $order->customer_name);
        $this->assertSame($merchantShop->phone, $order->customer_phone);
        $this->assertSame('عنوان المتجر المحفوظ', $order->customer_address);
        $this->assertEqualsWithDelta(32.2211, (float) $order->latitude, 0.0000001);
        $this->assertEqualsWithDelta(35.2544, (float) $order->longitude, 0.0000001);
        $this->assertNotSame($otherDistributor->id, $order->distributor_id);
    }

    public function test_marketer_qr_login_links_shop_and_attributes_orders_and_commission(): void
    {
        [$merchant, $merchantShop, $distributor] = $this->merchantLinkedToDistributor('qr-marketer');
        $merchantShop->update([
            'distributor_id' => null,
            'distributor_marketer_id' => null,
        ]);

        $marketerUser = User::create([
            'name' => 'QR Marketer',
            'email' => 'qr-marketer-user@example.com',
            'password' => 'secret123',
            'role' => 'marketer',
            'is_active' => true,
        ]);
        $marketer = DistributorMarketer::create([
            'distributor_id' => $distributor->id,
            'user_id' => $marketerUser->id,
            'name' => 'QR Marketer',
            'tracking_code' => 'qr-marketer-code',
            'commission_rate' => 8,
            'is_active' => true,
        ]);
        $redirect = route('front.marketer', ['marketer' => $marketer->tracking_code], false);
        $qrLoginUrl = URL::signedRoute('merchant.login', [
            'referrer_type' => 'marketer',
            'referrer' => $marketer->tracking_code,
            'redirect' => $redirect,
        ]);

        $this->get($qrLoginUrl)
            ->assertOk()
            ->assertSee('دخول صاحب المتجر');

        $this->post(route('merchant.login.store'), [
            'email' => $merchant->email,
            'password' => 'secret123',
            'redirect' => $redirect,
        ])->assertRedirect($redirect);

        $merchantShop->refresh();
        $this->assertSame($distributor->id, $merchantShop->distributor_id);
        $this->assertSame($marketer->id, $merchantShop->distributor_marketer_id);
        $this->assertContains($merchantShop->id, $marketerUser->accessibleShopIds());

        $this->postJson(route('front-orders.store'), [
            'customer_name' => 'QR merchant',
            'items' => [['name' => 'طلب QR', 'price' => '250', 'qty' => 1]],
            'subtotal' => 250,
            'discount' => 0,
            'total' => 250,
            'order_channel' => 'whatsapp',
            'visitor_type' => 'merchant',
        ])->assertOk();

        $order = FrontOrder::query()->latest('id')->firstOrFail();
        $this->assertSame($distributor->id, $order->distributor_id);
        $this->assertSame($marketer->id, $order->distributor_marketer_id);
        $this->assertSame('20.00', $order->marketer_commission_amount);
    }

    public function test_distributor_qr_login_links_shop_directly_to_distributor(): void
    {
        [$merchant, $merchantShop, $distributor] = $this->merchantLinkedToDistributor('qr-distributor');
        $merchantShop->update([
            'distributor_id' => null,
            'distributor_marketer_id' => null,
        ]);
        $redirect = route('front.distributor', $distributor, false);
        $qrLoginUrl = URL::signedRoute('merchant.login', [
            'referrer_type' => 'distributor',
            'referrer' => $distributor->id,
            'redirect' => $redirect,
        ]);

        $this->get($qrLoginUrl)->assertOk();
        $this->post(route('merchant.login.store'), [
            'email' => $merchant->email,
            'password' => 'secret123',
            'redirect' => $redirect,
        ])->assertRedirect($redirect);

        $merchantShop->refresh();
        $this->assertSame($distributor->id, $merchantShop->distributor_id);
        $this->assertNull($merchantShop->distributor_marketer_id);
        $this->assertContains($merchantShop->id, $distributor->user->accessibleShopIds());
    }

    public function test_unsigned_merchant_referral_is_rejected(): void
    {
        $this->get(route('merchant.login', [
            'referrer_type' => 'distributor',
            'referrer' => 999,
        ]))->assertForbidden();
    }

    public function test_proxy_safe_relative_qr_signature_is_accepted(): void
    {
        [, , $distributor] = $this->merchantLinkedToDistributor('relative-qr');
        $redirect = route('front.distributor', $distributor, false);
        $relativeQrUrl = URL::signedRoute('merchant.login', [
            'referrer_type' => 'distributor',
            'referrer' => $distributor->id,
            'redirect' => $redirect,
        ], absolute: false);

        $this->get('https://ozman.online' . $relativeQrUrl)
            ->assertOk()
            ->assertSessionHas('merchant_referral', [
                'distributor_id' => $distributor->id,
                'distributor_marketer_id' => null,
            ]);
    }

    public function test_new_merchant_can_register_from_marketer_qr_and_first_order_credits_commission(): void
    {
        Storage::fake('public');

        [, , $distributor] = $this->merchantLinkedToDistributor('register-qr');
        $marketerUser = User::create([
            'name' => 'Register QR Marketer',
            'email' => 'register-qr-marketer@example.com',
            'password' => 'secret123',
            'role' => 'marketer',
            'is_active' => true,
        ]);
        $marketer = DistributorMarketer::create([
            'distributor_id' => $distributor->id,
            'user_id' => $marketerUser->id,
            'name' => 'Register QR Marketer',
            'tracking_code' => 'register-qr-code',
            'commission_rate' => 6,
            'is_active' => true,
        ]);
        $redirect = route('front.marketer', ['marketer' => $marketer->tracking_code], false);
        $qrLoginUrl = URL::signedRoute('merchant.login', [
            'referrer_type' => 'marketer',
            'referrer' => $marketer->tracking_code,
            'redirect' => $redirect,
        ]);

        $this->get($qrLoginUrl)
            ->assertOk()
            ->assertSee('إنشاء متجر جديد');
        $this->get(route('merchant.register', ['redirect' => $redirect]))
            ->assertOk()
            ->assertSee('إنشاء حساب متجر جديد');

        $this->post(route('merchant.register.store'), [
            'owner_name' => 'صاحب متجر جديد',
            'shop_name' => 'متجر QR الجديد',
            'email' => 'new-qr-shop@example.com',
            'phone' => '0591234567',
            'whatsapp' => '0591234567',
            'address' => 'نابلس',
            'latitude' => '32.2211000',
            'longitude' => '35.2544000',
            'logo' => UploadedFile::fake()->image('shop-logo.png', 300, 300),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'redirect' => $redirect,
        ])->assertRedirect($redirect);

        $owner = User::query()->where('email', 'new-qr-shop@example.com')->firstOrFail();
        $shop = Shop::query()->where('user_id', $owner->id)->firstOrFail();
        $this->assertAuthenticatedAs($owner);
        $this->assertTrue($owner->isShopOwner());
        $this->assertSame($distributor->id, $shop->distributor_id);
        $this->assertSame($marketer->id, $shop->distributor_marketer_id);
        $this->assertTrue($shop->show_ozman_products);
        $this->assertEqualsWithDelta(32.2211, (float) $shop->latitude, 0.0000001);
        $this->assertEqualsWithDelta(35.2544, (float) $shop->longitude, 0.0000001);
        $this->assertNotNull($shop->logo);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $shop->logo));
        $this->assertContains($shop->id, $marketerUser->accessibleShopIds());
        $this->assertContains($shop->id, $distributor->user->accessibleShopIds());

        $this->postJson(route('front-orders.store'), [
            'customer_name' => $shop->name,
            'items' => [['name' => 'أول طلب', 'price' => '300', 'qty' => 1]],
            'subtotal' => 300,
            'discount' => 0,
            'total' => 300,
            'order_channel' => 'whatsapp',
            'visitor_type' => 'merchant',
        ])->assertOk();

        $order = FrontOrder::query()->latest('id')->firstOrFail();
        $this->assertSame($distributor->id, $order->distributor_id);
        $this->assertSame($marketer->id, $order->distributor_marketer_id);
        $this->assertSame('18.00', $order->marketer_commission_amount);
    }

    public function test_qr_registration_reclaims_email_left_by_deleted_shop_owner_account(): void
    {
        [, , $distributor] = $this->merchantLinkedToDistributor('orphan-email');
        $orphan = User::create([
            'name' => 'حساب متجر محذوف',
            'email' => 'deleted-shop@example.com',
            'password' => 'old-password',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $this->assertTrue($orphan->shops()->doesntExist());

        $qrLoginUrl = URL::signedRoute('merchant.login', [
            'referrer_type' => 'distributor',
            'referrer' => $distributor->id,
            'redirect' => route('home', absolute: false),
        ]);
        $this->get($qrLoginUrl)->assertOk();

        $this->post(route('merchant.register.store'), [
            'owner_name' => 'صاحب المتجر الجديد',
            'shop_name' => 'المتجر الجديد',
            'email' => ' DELETED-SHOP@example.com ',
            'phone' => '0591234567',
            'latitude' => '32.2211000',
            'longitude' => '35.2544000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $replacement = User::query()->where('email', 'deleted-shop@example.com')->firstOrFail();
        $this->assertNotSame($orphan->id, $replacement->id);
        $this->assertTrue($replacement->shops()->exists());
        $this->assertSame(1, User::query()->where('email', 'deleted-shop@example.com')->count());
    }

    public function test_merchant_self_registration_requires_qr_referral(): void
    {
        $this->get(route('merchant.register'))
            ->assertRedirect(route('merchant.login'));

        $this->post(route('merchant.register.store'), [
            'owner_name' => 'No referral',
            'shop_name' => 'No referral shop',
            'email' => 'no-referral@example.com',
            'phone' => '0590000000',
            'latitude' => '32.2211000',
            'longitude' => '35.2544000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'no-referral@example.com']);
    }

    private function merchantLinkedToDistributor(string $suffix = 'main'): array
    {
        $distributorUser = User::create([
            'name' => "Distributor {$suffix}",
            'email' => "distributor-{$suffix}@example.com",
            'password' => 'secret123',
            'role' => 'distributor',
            'is_active' => true,
        ]);

        $baseShop = Shop::create([
            'user_id' => $distributorUser->id,
            'name' => "Base shop {$suffix}",
            'slug' => "base-shop-{$suffix}",
            'is_active' => true,
        ]);

        $distributor = Distributor::create([
            'shop_id' => $baseShop->id,
            'user_id' => $distributorUser->id,
            'name' => "Distributor {$suffix}",
            'whatsapp' => '970599000000',
            'is_active' => true,
        ]);

        $merchant = User::create([
            'name' => "Merchant {$suffix}",
            'email' => "merchant-{$suffix}@example.com",
            'password' => 'secret123',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        $merchantShop = Shop::create([
            'user_id' => $merchant->id,
            'distributor_id' => $distributor->id,
            'name' => "Merchant shop {$suffix}",
            'slug' => "merchant-shop-{$suffix}",
            'phone' => '0590000000',
            'is_active' => true,
        ]);

        return [$merchant, $merchantShop, $distributor];
    }
}
