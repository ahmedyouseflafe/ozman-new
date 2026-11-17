<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Category, Product, Shop, SocialPost, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Tests\TestCase;

/** The shop's social media in the app, through the same code as the dashboard's page. */
class MobileSocialApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    public function test_the_owner_connects_writes_publishes_and_cancels_from_the_app(): void
    {
        Storage::fake('public');
        config(['services.zernio.key' => 'zk_test', 'services.anthropic.key' => 'ak_test', 'services.anthropic.model' => 'claude-haiku-5-5']);
        Http::fake([
            'zernio.com/api/v1/profiles' => Http::response(['profile' => ['_id' => 'prof_1']], 201),
            'zernio.com/api/v1/connect/*' => Http::response(['authUrl' => 'https://www.facebook.com/dialog/oauth?x=1']),
            'zernio.com/api/v1/accounts*' => Http::response(['accounts' => [['_id' => 'acc_ig', 'platform' => 'instagram', 'username' => 'bankai', 'isActive' => true],
                ['_id' => 'acc_fb', 'platform' => 'facebook', 'displayName' => 'Bankai', 'isActive' => true]]]),
            'zernio.com/api/v1/analytics/best-time*' => Http::response(['slots' => []]),
            'zernio.com/api/v1/posts' => Http::response(['post' => ['_id' => 'post_9', 'status' => 'scheduled']], 201),
            'zernio.com/api/v1/posts/*' => Http::response(['message' => 'deleted']),
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'سوشي طازة 🍣']]]),
        ]);
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Bankai', 'slug' => 'bankai', 'catalog_type' => 'restaurant', 'is_active' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'رولات', 'slug' => 'rolls', 'is_active' => true]);
        $product = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'ريد دراغون', 'slug' => 'red', 'price' => 53, 'quantity' => 1,
            'is_active' => true, 'main_image' => 'storage/products/red.jpg']);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/social", $h)->assertOk()->assertJsonPath('configured', true)
            ->assertJsonPath('accounts', [])->assertJsonPath('products.0.name', 'ريد دراغون')->assertJsonPath('platforms', ['instagram', 'facebook', 'tiktok']);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/social/connect/myspace", [], $h)->assertNotFound();
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/social/connect/facebook", [], $h)->assertOk()
            ->assertJsonPath('url', 'https://www.facebook.com/dialog/oauth?x=1');
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/v1/connect/facebook') && str_contains(urldecode($r->url()), route('social.app-callback')));
        $this->get(route('social.app-callback'))->assertOk()->assertSee('تم ربط الحساب');
        $this->get(route('social.app-callback', ['error' => 'denied']))->assertOk()->assertSee('الربط ما كمل');

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/social", $h)->assertJsonCount(2, 'accounts')->assertJsonPath('accounts.0.username', 'bankai');
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/social/caption", ['language' => 'ar'], $h)->assertStatus(422);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/social/caption", ['product_id' => $product->id, 'language' => 'ar'], $h)
            ->assertOk()->assertJsonPath('caption', 'سوشي طازة 🍣');

        $this->post("/api/v1/merchant/shops/{$shop->id}/social/posts", ['caption' => 'x', 'accounts' => ['acc_ig'], 'when' => 'now'], $h)
            ->assertStatus(422)->assertJsonValidationErrors('media');
        $at = now('Asia/Jerusalem')->addDay()->setTime(20, 0)->format('Y-m-d\TH:i');
        $post = $this->post("/api/v1/merchant/shops/{$shop->id}/social/posts", ['caption' => 'سوشي طازة 🍣', 'add_link' => '1', 'accounts' => ['acc_ig', 'acc_fb'],
            'media' => [UploadedFile::fake()->image('a.jpg')], 'when' => 'schedule', 'scheduled_at' => $at], $h)
            ->assertCreated()->assertJsonPath('post.status', 'scheduled')->assertJsonCount(2, 'post.targets')->json('post');
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/social", $h)->assertJsonPath('posts.0.id', $post['id']);

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        Shop::create(['user_id' => $stranger->id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        $this->deleteJson("/api/v1/merchant/social-posts/{$post['id']}", [], $this->auth($stranger))->assertForbidden();
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/social", $this->auth($stranger))->assertForbidden();

        $this->deleteJson("/api/v1/merchant/social-posts/{$post['id']}", [], $h)->assertOk()->assertJsonPath('post.status', 'cancelled');
        $this->assertSame('cancelled', SocialPost::find($post['id'])->status);
    }
}
