<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, LoyaltyPointEntry, MarketListing, MarketOrder, PromoterGift, PromoterPost, PromoterProfile, Shop, User};
use App\Services\{FirebaseMessagingService, WebPushService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Notification, Storage};
use Mockery;
use Tests\TestCase;

/** Free market and creators (promoters), as the app uses them. */
class MobileGroupTwoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $firebase = Mockery::mock(FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToTokens')->andReturn(1);
        $this->app->instance(FirebaseMessagingService::class, $firebase);
        $webPush = Mockery::mock(WebPushService::class);
        $webPush->shouldIgnoreMissing();
        $this->app->instance(WebPushService::class, $webPush);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function user(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_a_member_sells_and_another_buys_in_the_free_market(): void
    {
        $seller = $this->user();
        $buyer = $this->user('promoter');
        $sellerH = $this->auth($seller);
        $buyerH = $this->auth($buyer);

        $this->getJson('/api/v1/market/meta')->assertOk()->assertJsonStructure(['categories', 'conditions', 'cities', 'max_images']);
        $this->post('/api/v1/me/market/listings', ['title' => 'جاكيت', 'price' => '120', 'category' => 'clothing', 'condition' => 'used',
            'city' => 'نابلس', 'quantity' => 1], $sellerH)->assertStatus(422);
        $id = $this->post('/api/v1/me/market/listings', ['title' => 'جاكيت جلد', 'price' => '120', 'category' => 'clothing', 'condition' => 'used',
            'city' => 'نابلس', 'quantity' => 1, 'images' => [UploadedFile::fake()->image('a.jpg', 800, 600)]], $sellerH)
            ->assertCreated()->json('listing.id');

        $this->getJson('/api/v1/market?q=جاكيت')->assertOk()->assertJsonPath('data.0.id', $id);
        $this->getJson("/api/v1/market/$id", $buyerH)->assertOk()->assertJsonPath('listing.can_order', true)->assertJsonPath('listing.mine', false);
        $this->getJson("/api/v1/market/$id", $buyerH);
        $this->assertSame(1, (int) MarketListing::find($id)->views);

        $this->postJson("/api/v1/market/$id/order", ['quantity' => 1, 'buyer_name' => 'Lina', 'buyer_phone' => '0599000000', 'city' => 'رام الله', 'address' => 'الماصيون'], $sellerH)->assertForbidden();
        $orderId = $this->postJson("/api/v1/market/$id/order", ['quantity' => 1, 'buyer_name' => 'Lina', 'buyer_phone' => '0599000000', 'city' => 'رام الله', 'address' => 'الماصيون'], $buyerH)
            ->assertCreated()->json('order.id');

        // The seller sees who ordered; the buyer doesn't see the seller's phone until it's accepted.
        $seller->forceFill(['phone' => '0598111111'])->save();
        $this->getJson('/api/v1/me/market/sales', $sellerH)->assertOk()->assertJsonPath('data.0.buyer.phone', '0599000000');
        $this->getJson('/api/v1/me/market/purchases', $buyerH)->assertOk()->assertJsonPath('data.0.seller.phone', null);
        $this->patchJson("/api/v1/me/market/orders/$orderId", ['action' => 'accept'], $buyerH)->assertForbidden();
        $this->patchJson("/api/v1/me/market/orders/$orderId", ['action' => 'accept'], $sellerH)->assertOk()->assertJsonPath('order.status', MarketOrder::ACCEPTED);
        $this->getJson('/api/v1/me/market/purchases', $buyerH)->assertJsonPath('data.0.seller.phone', '0598111111');
        $this->assertSame(MarketListing::SOLD, MarketListing::find($id)->status);
        $this->patchJson("/api/v1/me/market/orders/$orderId", ['action' => 'buyer_cancel'], $buyerH)->assertStatus(422);
        $this->patchJson("/api/v1/me/market/orders/$orderId", ['action' => 'complete'], $sellerH)->assertOk();

        $this->getJson('/api/v1/me/market/listings', $sellerH)->assertOk()->assertJsonPath('data.0.status', MarketListing::SOLD);
        $this->post("/api/v1/me/market/listings/$id", ['title' => 'جاكيت جلد', 'price' => '100', 'category' => 'clothing', 'condition' => 'used',
            'city' => 'نابلس', 'quantity' => 2], $sellerH)->assertOk()->assertJsonPath('listing.price', fn ($p) => $p !== null);
        $this->assertSame(MarketListing::ACTIVE, MarketListing::find($id)->status);
        $this->patchJson("/api/v1/me/market/listings/$id/toggle", [], $buyerH)->assertForbidden();
        $this->patchJson("/api/v1/me/market/listings/$id/toggle", [], $sellerH)->assertOk()->assertJsonPath('status', MarketListing::HIDDEN);
        $this->getJson("/api/v1/market/$id", $buyerH)->assertNotFound();
        $this->deleteJson("/api/v1/me/market/listings/$id", [], $sellerH)->assertOk();

        $owner = $this->user('shop_owner');
        $this->getJson('/api/v1/me/market/listings', $this->auth($owner))->assertForbidden();
    }

    private function profile(string $slug = 'rima'): PromoterProfile
    {
        return PromoterProfile::create(['user_id' => $this->user('promoter')->id, 'slug' => $slug, 'display_name' => 'Rima', 'is_active' => true, 'approved_at' => now()]);
    }

    public function test_customers_browse_follow_and_gift_creators(): void
    {
        $profile = $this->profile();
        $shop = Shop::create(['user_id' => $this->user('shop_owner')->id, 'name' => 'Cafe', 'slug' => 'cafe', 'is_active' => true]);
        Storage::disk('local')->put('promoters/posts/a.jpg', 'x');
        $post = PromoterPost::create(['promoter_profile_id' => $profile->id, 'shop_id' => $shop->id, 'kind' => 'post', 'type' => 'image', 'media' => 'promoters/posts/a.jpg', 'is_active' => true]);
        PromoterProfile::create(['user_id' => $this->user('promoter')->id, 'slug' => 'pending', 'display_name' => 'Pending', 'is_active' => false]);
        $customer = $this->user();
        $h = $this->auth($customer);

        $this->getJson('/api/v1/promoters')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'rima')->assertJsonPath('stories.0.id', $post->id);
        $this->getJson('/api/v1/promoters/pending')->assertNotFound();
        $this->getJson('/api/v1/promoters/rima', $h)->assertOk()->assertJsonPath('profile.following', false)->assertJsonPath('posts.0.shop.id', $shop->id)
            ->assertJsonPath('shops.0.id', $shop->id)->assertJsonPath('gifting.can_gift', true);
        $this->getJson('/api/v1/promoters/rima', $h);
        $this->assertSame(1, (int) $profile->fresh()->page_views);

        $this->putJson("/api/v1/promoters/{$profile->id}/follow", [], $h)->assertOk()->assertJsonPath('followers', 1);
        $this->putJson("/api/v1/promoters/{$profile->id}/follow", [], $h)->assertOk()->assertJsonPath('followers', 1);
        $this->getJson('/api/v1/promoters/rima', $h)->assertJsonPath('profile.following', true);
        $this->deleteJson("/api/v1/promoters/{$profile->id}/follow", [], $h)->assertOk()->assertJsonPath('followers', 0);

        $gift = PromoterGift::available()->first() ?? PromoterGift::create(['name' => 'وردة', 'icon' => '🌹', 'points' => 10, 'is_active' => true]);
        $this->postJson("/api/v1/promoters/{$profile->id}/gifts", ['gift_id' => $gift->id], $h)->assertStatus(422);
        LoyaltyPointEntry::create(['user_id' => $customer->id, 'points' => 100, 'type' => LoyaltyPointEntry::TYPE_MANUAL, 'status' => LoyaltyPointEntry::CONFIRMED, 'note' => 'test']);
        $this->postJson("/api/v1/promoters/{$profile->id}/gifts", ['gift_id' => $gift->id, 'post_id' => $post->id], $h)->assertOk()->assertJsonPath('balance', 100 - $gift->points);
        $this->getJson('/api/v1/promoters/rima', $h)->assertJsonPath('supporters.0.points', $gift->points);

        $this->postJson("/api/v1/promoter-posts/{$post->id}/visit-shop", [], $h)->assertOk()->assertJsonPath('shop.id', $shop->id);
        $this->postJson("/api/v1/promoter-posts/{$post->id}/visit-shop", [], $h);
        $this->assertSame(1, (int) $post->fresh()->shop_clicks);
    }

    public function test_a_customer_applies_and_a_creator_runs_their_page(): void
    {
        $customer = $this->user();
        $h = $this->auth($customer);
        $this->getJson('/api/v1/me/promoter', $h)->assertForbidden();
        $this->postJson('/api/v1/me/promoter/apply', ['username' => 'lina.art', 'bio' => 'رسم'], $h)->assertOk()->assertJsonPath('user.role', 'promoter');
        $this->getJson('/api/v1/me/promoter', $h)->assertOk()->assertJsonPath('profile.pending', true);
        $this->post('/api/v1/me/promoter/posts', ['kind' => 'post', 'media' => UploadedFile::fake()->image('p.jpg')], $h)->assertStatus(422);

        $profile = $customer->fresh()->promoterProfile;
        $profile->update(['is_active' => true, 'approved_at' => now()]);
        $fan = $this->user();
        $profile->followers()->attach($fan->id);

        $postId = $this->post('/api/v1/me/promoter/posts', ['kind' => 'story', 'caption' => 'جديد', 'media' => UploadedFile::fake()->image('p.jpg')], $h)
            ->assertCreated()->assertJsonPath('post.kind', 'story')->json('post.id');
        $this->patchJson("/api/v1/me/promoter/posts/$postId", ['caption' => 'معدّل', 'is_active' => false], $h)->assertOk()->assertJsonPath('post.is_active', false);
        $this->patchJson("/api/v1/me/promoter/posts/$postId", ['caption' => 'x'], $this->auth($fan))->assertForbidden();
        $this->post('/api/v1/me/promoter/profile', ['display_name' => 'Lina Art', 'bio' => 'رسم وألوان'], $h)->assertOk()->assertJsonPath('profile.name', 'Lina Art');
        $this->getJson('/api/v1/me/promoter', $h)->assertOk()->assertJsonPath('posts.0.caption', 'معدّل')->assertJsonPath('profile.pending', false);
        $this->deleteJson("/api/v1/me/promoter/posts/$postId", [], $h)->assertOk();
        $this->assertSame(0, PromoterPost::count());
    }
}
