<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, RewardWheel, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Shop owners edit their reward wheels in the app with the dashboard's rules. */
class MobileRewardWheelsApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function shop(string $type, ?User $owner = null): Shop
    {
        $owner ??= User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        return Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true]);
    }

    private function segments(array $quotas): array
    {
        return collect($quotas)->map(fn ($quota, $i) => ['label' => 'خصم '.(5 * ($i + 1)).'%', 'discount_type' => 'percent',
            'discount_value' => 5 * ($i + 1), 'color' => '#00cfe8', 'win_quota' => $quota, 'is_active' => true])->all();
    }

    public function test_the_owner_creates_edits_and_deletes_a_purchase_wheel(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/wheels", $h)->assertOk()->assertJsonPath('purchase', [])->assertJsonPath('signup', null)
            ->assertJsonPath('segment_types.0.key', 'percent');

        // The active segments' quotas must add up to the wheel's total, like the dashboard.
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/wheels/purchase", ['title' => 'فوق 100', 'is_active' => true, 'min_order_total' => 100,
            'win_quota_total' => 10, 'segments' => $this->segments([3, 3])], $h)->assertStatus(422)->assertJsonValidationErrors('segments');

        $wheel = $this->postJson("/api/v1/merchant/shops/{$shop->id}/wheels/purchase", ['title' => 'فوق 100', 'is_active' => true, 'min_order_total' => 100,
            'win_quota_total' => 6, 'segments' => $this->segments([3, 3])], $h)->assertCreated()->assertJsonPath('wheel.segments.1.discount_value', 10)->json('wheel');
        $this->assertSame(1, RewardWheel::where('shop_id', $shop->id)->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)->count());

        // Same range again updates that wheel instead of making a second one.
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/wheels/purchase", ['title' => 'فوق 100 جديد', 'is_active' => true, 'min_order_total' => 100,
            'win_quota_total' => 4, 'segments' => $this->segments([2, 2])], $h)->assertOk()->assertJsonPath('wheel.id', $wheel['id']);

        $this->postJson("/api/v1/merchant/wheels/{$wheel['id']}", ['title' => 'من 50 لـ200', 'is_active' => false, 'min_order_total' => 50, 'max_order_total' => 200,
            'win_quota_total' => 5, 'segments' => $this->segments([5])], $h)->assertOk()->assertJsonPath('wheel.max_order_total', 200)
            ->assertJsonCount(1, 'wheel.segments')->assertJsonPath('wheel.is_active', false);

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->shop('sweets', $stranger);
        $this->postJson("/api/v1/merchant/wheels/{$wheel['id']}", ['title' => 'x'], $this->auth($stranger))->assertForbidden();
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/wheels", $this->auth($stranger))->assertForbidden();
        $this->deleteJson("/api/v1/merchant/wheels/{$wheel['id']}", [], $this->auth($stranger))->assertForbidden();

        $this->deleteJson("/api/v1/merchant/wheels/{$wheel['id']}", [], $h)->assertOk();
        $this->assertDatabaseMissing('reward_wheels', ['id' => $wheel['id']]);
    }

    public function test_restaurants_edit_their_signup_wheel_with_a_gift_image(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('restaurant', $owner);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/wheels", $h)->assertOk()->assertJsonPath('signup.id', null)
            ->assertJsonPath('signup.is_active', false)->assertJsonCount(2, 'signup.segments');

        $segments = $this->segments([2, 2]);
        $segments[1] = ['label' => 'بطاطا مجاناً', 'discount_type' => 'gift', 'color' => '#7000ff', 'win_quota' => 2, 'is_active' => true];
        // One active segment can't run a wheel.
        $this->post("/api/v1/merchant/shops/{$shop->id}/wheels/signup", ['title' => 'هدية تسجيلك', 'is_active' => '1', 'win_quota_total' => 2,
            'segments' => [$segments[0] + ['is_active' => '1'], ['is_active' => '0'] + $segments[1]]], $h)->assertStatus(422);

        $segments[1]['gift_image'] = UploadedFile::fake()->image('fries.png', 200, 200);
        $wheel = $this->post("/api/v1/merchant/shops/{$shop->id}/wheels/signup", ['title' => 'هدية تسجيلك', 'is_active' => '1', 'win_quota_total' => 4,
            'segments' => $segments], $h)->assertOk()->assertJsonPath('wheel.is_active', true)->assertJsonPath('wheel.segments.1.discount_value', null)->json('wheel');
        $image = $wheel['segments'][1]['existing_gift_image'];
        Storage::disk('public')->assertExists(substr($image, strlen('storage/')));

        // Keeping the uploaded gift image on the next save, and refusing someone else's file path.
        unset($segments[1]['gift_image']);
        $this->post("/api/v1/merchant/shops/{$shop->id}/wheels/signup", ['title' => 'هدية', 'is_active' => '1', 'win_quota_total' => 4,
            'segments' => [$segments[0], $segments[1] + ['existing_gift_image' => $image]]], $h)->assertOk()->assertJsonPath('wheel.segments.1.existing_gift_image', $image);
        $this->post("/api/v1/merchant/shops/{$shop->id}/wheels/signup", ['title' => 'هدية', 'is_active' => '1', 'win_quota_total' => 4,
            'segments' => [$segments[0], $segments[1] + ['existing_gift_image' => 'storage/other-shop.png']]], $h)->assertStatus(422);
        $this->getJson("/api/v1/shops/{$shop->id}/signup-reward", $h)->assertOk();

        // A sweets shop has no signup wheel.
        $sweets = $this->shop('sweets', $owner);
        $this->post("/api/v1/merchant/shops/{$sweets->id}/wheels/signup", ['title' => 'x'], $h)->assertForbidden();
    }
}
