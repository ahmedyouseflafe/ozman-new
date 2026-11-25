<?php

namespace Tests\Feature;

use App\Models\{Shop, StorePointCode, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The dashboard's till page for in-store points: the same one-time QR as the app. */
class StorePointsTillPageTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $name = 'Grill'): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => $name, 'slug' => 'shop-'.uniqid(), 'catalog_type' => 'restaurant', 'is_active' => true]);

        return [$shop, $owner];
    }

    public function test_the_owner_makes_a_code_watches_it_and_can_cancel_it(): void
    {
        [$shop, $owner] = $this->shop();

        $this->actingAs($owner)->get('/store-points')->assertOk()
            ->assertSee('نقاط للزبون بالمحل')->assertSee(route('store-points.make', $shop), false);

        $code = $this->actingAs($owner)->postJson(route('store-points.make', $shop), ['amount' => 250])
            ->assertCreated()->assertJsonPath('code.points', 20)->assertJsonPath('code.status', 'open')->json('code');
        $this->assertSame(count($code['qr']), strlen($code['qr'][0]));
        $this->assertSame($owner->id, StorePointCode::find($code['id'])->created_by);

        $this->actingAs($owner)->getJson(route('store-points.status', $code['id']))->assertOk()
            ->assertJsonPath('code.status', 'open')->assertJsonMissingPath('code.qr');
        $this->actingAs($owner)->deleteJson(route('store-points.cancel', $code['id']))->assertOk()->assertJsonPath('code.status', 'cancelled');

        // Too small for a point: the reason comes back for the page to show.
        $this->actingAs($owner)->postJson(route('store-points.make', $shop), ['amount' => 5])->assertStatus(422)->assertJsonValidationErrors('amount');
    }

    public function test_other_shops_and_customers_are_kept_out(): void
    {
        [$shop, $owner] = $this->shop();
        [$other, $otherOwner] = $this->shop('Other');
        $code = $this->actingAs($owner)->postJson(route('store-points.make', $shop), ['amount' => 100])->json('code');

        $this->actingAs($otherOwner)->postJson(route('store-points.make', $shop), ['amount' => 100])->assertForbidden();
        $this->actingAs($otherOwner)->getJson(route('store-points.status', $code['id']))->assertForbidden();
        $this->actingAs($otherOwner)->deleteJson(route('store-points.cancel', $code['id']))->assertForbidden();
        // Another shop's id in the address doesn't open it.
        $this->actingAs($otherOwner)->get('/store-points?shop_id='.$shop->id)->assertForbidden();

        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($customer)->get('/store-points')->assertRedirect();
        $this->actingAs($customer)->postJson(route('store-points.make', $shop), ['amount' => 100])->assertRedirect();
        $this->assertSame(1, StorePointCode::count());
    }
}
