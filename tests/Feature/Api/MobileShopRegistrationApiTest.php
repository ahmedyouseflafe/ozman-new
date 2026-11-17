<?php

namespace Tests\Feature\Api;

use App\Models\{Distributor, DistributorMarketer, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** A new shop signs up in the app through a distributor's or marketer's referral QR, with the website's rules. */
class MobileShopRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    private function referral(): array
    {
        $company = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Krembo', 'slug' => 'krembo-'.uniqid(), 'catalog_type' => 'krembo', 'is_active' => true]);
        $distributor = Distributor::create(['shop_id' => $company->id, 'name' => 'موزع الشمال', 'is_active' => true]);
        $marketer = DistributorMarketer::create(['distributor_id' => $distributor->id, 'name' => 'سارة', 'tracking_code' => 'sara-qr', 'is_active' => true]);

        return [$distributor, $marketer];
    }

    private function form(array $extra = []): array
    {
        return ['owner_name' => 'أبو سليم', 'shop_name' => 'حلويات أبو سليم', 'email' => 'Abu.Saleem@Example.com', 'phone' => '0599123456',
            'latitude' => 32.22, 'longitude' => 35.26, 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1', ...$extra];
    }

    public function test_a_shop_signs_up_with_a_marketer_qr_and_is_signed_in(): void
    {
        Storage::fake('public');
        [$distributor, $marketer] = $this->referral();

        $this->getJson('/api/v1/auth/shop-referral?code='.urlencode(url('/marketer-stores/sara-qr')))->assertOk()
            ->assertJsonPath('referral.marketer', 'سارة')->assertJsonPath('referral.distributor', 'موزع الشمال');
        $this->getJson('/api/v1/auth/shop-referral?code=nope')->assertNotFound();
        $this->postJson('/api/v1/auth/register-shop', $this->form(['referral_code' => 'nope']))->assertStatus(422)->assertJsonValidationErrors('referral_code');
        $this->postJson('/api/v1/auth/register-shop', $this->form(['referral_code' => 'sara-qr', 'password_confirmation' => 'x']))->assertStatus(422);

        $json = $this->post('/api/v1/auth/register-shop', $this->form(['referral_code' => 'sara-qr', 'logo' => UploadedFile::fake()->image('logo.png', 200, 200)]),
            ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('user.role', 'shop_owner')->assertJsonPath('shop.name', 'حلويات أبو سليم')->json();
        $shop = Shop::find($json['shop']['id']);
        $this->assertSame([$distributor->id, $marketer->id, 'general', true], [$shop->distributor_id, $shop->distributor_marketer_id, $shop->catalog_type, (bool) $shop->show_ozman_products]);
        $this->assertSame('abu.saleem@example.com', $shop->user->email);
        $this->getJson('/api/v1/merchant/shops', ['Authorization' => 'Bearer '.$json['token']])->assertOk()->assertJsonPath('data.0.id', $shop->id);

        // The same email can't sign up twice.
        $this->postJson('/api/v1/auth/register-shop', $this->form(['referral_code' => 'sara-qr']))->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_a_distributor_qr_works_and_inactive_referrals_are_refused(): void
    {
        [$distributor, $marketer] = $this->referral();
        $this->postJson('/api/v1/auth/register-shop', $this->form(['referral_code' => url('/distributor-stores/'.$distributor->id)]))->assertCreated()
            ->assertJsonPath('shop.name', 'حلويات أبو سليم');
        $this->assertNull(Shop::where('name', 'حلويات أبو سليم')->first()->distributor_marketer_id);

        $marketer->update(['is_active' => false]);
        $this->getJson('/api/v1/auth/shop-referral?code=sara-qr')->assertNotFound();
        $this->postJson('/api/v1/auth/register-shop', $this->form(['email' => 'other@example.com', 'referral_code' => 'sara-qr']))->assertStatus(422);
    }
}
