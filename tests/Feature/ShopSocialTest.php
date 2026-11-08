<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopSocialTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Shop $shop;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.zernio.key' => 'zk_test', 'services.anthropic.key' => 'ak_test', 'services.anthropic.model' => 'claude-haiku-5-5']);
        $this->owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->shop = Shop::create(['user_id' => $this->owner->id, 'name' => 'Bankai sushi', 'slug' => 'bankai-sushi', 'catalog_type' => 'restaurant', 'is_active' => true]);
        $category = Category::create(['shop_id' => $this->shop->id, 'name' => 'رولات', 'slug' => 'rolls', 'is_active' => true]);
        $this->product = Product::create(['shop_id' => $this->shop->id, 'category_id' => $category->id, 'name' => 'ريد دراغون رول', 'slug' => 'red-dragon',
            'description' => 'تونا حمراء وأفوكادو', 'price' => 53, 'quantity' => 10, 'is_active' => true, 'main_image' => 'storage/products/red.jpg']);
    }

    private function fakeZernio(array $accounts = null): void
    {
        $accounts ??= [['_id' => 'acc_ig', 'platform' => 'instagram', 'username' => 'bankai', 'isActive' => true, 'profileId' => 'prof_1'],
            ['_id' => 'acc_fb', 'platform' => 'facebook', 'displayName' => 'Bankai', 'isActive' => true, 'profileId' => 'prof_1']];
        Http::fake([
            'zernio.com/api/v1/profiles' => Http::response(['profile' => ['_id' => 'prof_1']], 201),
            'zernio.com/api/v1/connect/*' => Http::response(['authUrl' => 'https://www.instagram.com/oauth/authorize?x=1']),
            'zernio.com/api/v1/accounts*' => Http::response(['accounts' => $accounts, 'hasAnalyticsAccess' => false]),
            'zernio.com/api/v1/analytics/best-time*' => Http::response(['slots' => [['day_of_week' => 4, 'hour' => 20, 'avg_engagement' => 9.5, 'post_count' => 6]]]),
            'zernio.com/api/v1/posts' => Http::response(['post' => ['_id' => 'post_9', 'status' => 'scheduled']], 201),
            'zernio.com/api/v1/posts/*' => Http::response(['message' => 'deleted']),
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => "ريد دراغون بستناك 🍣🔥\nاطلبها هلقيت!\n#سوشي #بانكاي"]]]),
        ]);
    }

    public function test_owner_connects_an_account_through_a_shop_profile(): void
    {
        $this->fakeZernio();
        $this->actingAs($this->owner)->get(route('social.connect', ['platform' => 'instagram', 'shop_id' => $this->shop->id]))
            ->assertRedirect('https://www.instagram.com/oauth/authorize?x=1');
        $this->assertSame('prof_1', $this->shop->fresh()->zernio_profile_id);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/v1/profiles') && $r['externalId'] === 'shop-'.$this->shop->id && $r->hasHeader('Authorization', 'Bearer zk_test'));
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/v1/connect/instagram') && str_contains(urldecode($r->url()), route('social.callback', ['shop_id' => $this->shop->id])));

        $this->actingAs($this->owner)->get(route('social.connect', ['platform' => 'myspace']))->assertNotFound();
        $this->actingAs($this->owner)->get(route('social.callback', ['shop_id' => $this->shop->id]))->assertSessionHas('status');
    }

    public function test_page_shows_accounts_best_times_and_composer(): void
    {
        $this->fakeZernio();
        $this->shop->forceFill(['zernio_profile_id' => 'prof_1'])->save();
        $this->actingAs($this->owner)->get(route('social.index'))->assertOk()
            ->assertSee('@bankai')->assertSee('اكتبلي المنشور')->assertSee('أفضل أوقات حسب تفاعل جمهورك')
            ->assertSee('ريد دراغون رول')->assertSee(route('social.connect', ['platform' => 'tiktok', 'shop_id' => $this->shop->id]), false);
    }

    public function test_claude_writes_a_caption_from_the_product(): void
    {
        $this->fakeZernio();
        $this->actingAs($this->owner)->postJson(route('social.caption'), ['product_id' => $this->product->id, 'language' => 'ar'])
            ->assertOk()->assertJsonPath('caption', "ريد دراغون بستناك 🍣🔥\nاطلبها هلقيت!\n#سوشي #بانكاي");
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'api.anthropic.com/v1/messages') && $r->hasHeader('x-api-key', 'ak_test')
            && $r['model'] === 'claude-haiku-5-5' && str_contains($r['messages'][0]['content'], 'ريد دراغون رول') && str_contains($r['messages'][0]['content'], '53.00 ₪'));
    }

    public function test_post_is_scheduled_on_the_shops_own_accounts_with_photo_and_order_link(): void
    {
        $this->fakeZernio();
        $this->shop->forceFill(['zernio_profile_id' => 'prof_1'])->save();
        $at = now('Asia/Jerusalem')->addDay()->setTime(20, 0)->format('Y-m-d\TH:i');
        $this->actingAs($this->owner)->post(route('social.posts.store'), [
            'caption' => 'ريد دراغون 🍣', 'product_id' => $this->product->id, 'add_link' => 1,
            'accounts' => ['acc_ig', 'acc_fb'], 'when' => 'schedule', 'scheduled_at' => $at,
        ])->assertRedirect(route('social.index', ['shop_id' => $this->shop->id]))->assertSessionHas('status');

        Http::assertSent(function (HttpRequest $r) {
            if (! str_ends_with($r->url(), '/v1/posts') || $r->method() !== 'POST') return false;
            return $r['platforms'][1] === ['platform' => 'facebook', 'accountId' => 'acc_fb']
                && $r['platforms'][0]['accountId'] === 'acc_ig' && str_contains($r['platforms'][0]['customContent'], 'الرابط بالبايو')
                && ! str_contains($r['platforms'][0]['customContent'], 'http')
                && $r['mediaItems'][0]['url'] === asset('storage/products/red.jpg')
                && str_contains($r['content'], route('share.product', $this->product))
                && str_ends_with($r['scheduledFor'], 'Z') && ! isset($r['publishNow']) && $r['timezone'] === 'Asia/Jerusalem';
        });
        $post = SocialPost::sole();
        $this->assertSame(['scheduled', 'post_9'], [$post->status, $post->zernio_post_id]);

        // Cancelling a scheduled post removes it from Zernio too.
        $this->actingAs($this->owner)->delete(route('social.posts.destroy', $post))->assertSessionHas('status');
        $this->assertSame('cancelled', $post->fresh()->status);
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v1/posts/post_9'));
    }

    public function test_foreign_accounts_and_photo_less_instagram_posts_are_refused(): void
    {
        $this->fakeZernio();
        $this->shop->forceFill(['zernio_profile_id' => 'prof_1'])->save();
        $this->actingAs($this->owner)->post(route('social.posts.store'), ['caption' => 'x', 'accounts' => ['acc_someone_else'], 'when' => 'now'])
            ->assertSessionHasErrors('accounts');
        $this->actingAs($this->owner)->post(route('social.posts.store'), ['caption' => 'x', 'accounts' => ['acc_ig'], 'when' => 'now'])
            ->assertSessionHasErrors('media');
        $this->actingAs($this->owner)->post(route('social.posts.store'), ['caption' => 'x', 'accounts' => ['acc_fb'], 'when' => 'schedule', 'scheduled_at' => '2020-01-01T10:00'])
            ->assertSessionHasErrors('scheduled_at');
        $this->assertDatabaseCount('social_posts', 0);
        Http::assertNotSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/v1/posts'));
    }

    public function test_other_shops_cannot_touch_this_shops_posts(): void
    {
        $this->fakeZernio();
        $other = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        Shop::create(['user_id' => $other->id, 'name' => 'Other', 'slug' => 'other', 'is_active' => true]);
        $post = SocialPost::create(['shop_id' => $this->shop->id, 'caption' => 'x', 'targets' => [], 'status' => 'scheduled', 'zernio_post_id' => 'post_1']);
        $this->assertContains($this->actingAs($other)->delete(route('social.posts.destroy', $post))->status(), [403, 404]);
        $this->assertContains($this->actingAs($other)->postJson(route('social.caption'), ['product_id' => $this->product->id, 'language' => 'ar'])->status(), [403, 422]);
        $this->assertSame('scheduled', $post->fresh()->status);
    }

    public function test_caption_survives_thinking_blocks_and_models_without_effort_control(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push(['type' => 'error', 'error' => ['message' => 'output_config: Extra inputs are not permitted']], 400)
            ->push(['content' => [['type' => 'thinking', 'thinking' => '...'], ['type' => 'text', 'text' => 'سوشي طازة 🍣']], 'stop_reason' => 'end_turn'])]);
        $this->actingAs($this->owner)->postJson(route('social.caption'), ['product_id' => $this->product->id, 'language' => 'ar'])
            ->assertOk()->assertJsonPath('caption', 'سوشي طازة 🍣');
        Http::assertSentCount(2);
        Http::assertSent(fn (HttpRequest $r) => $r['max_tokens'] >= 2000 && ($r['output_config']['effort'] ?? null) === 'low');
    }

    public function test_thinking_only_reply_reports_why_there_is_no_caption(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'thinking', 'thinking' => '...']], 'stop_reason' => 'max_tokens'])]);
        $this->actingAs($this->owner)->postJson(route('social.caption'), ['product_id' => $this->product->id, 'language' => 'ar'])
            ->assertStatus(503)->assertJsonFragment(['message' => 'الكاتب الذكي مش متاح هلقيت: Claude returned no text (stop reason: max_tokens).']);
    }

    public function test_own_photos_or_video_replace_the_menu_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->fakeZernio();
        $this->shop->forceFill(['zernio_profile_id' => 'prof_1'])->save();
        $photos = [\Illuminate\Http\UploadedFile::fake()->image('a.jpg'), \Illuminate\Http\UploadedFile::fake()->image('b.png')];
        $this->actingAs($this->owner)->post(route('social.posts.store'), ['caption' => 'سوشي', 'product_id' => $this->product->id,
            'media' => $photos, 'accounts' => ['acc_ig'], 'when' => 'now'])->assertSessionHasNoErrors();
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/v1/posts') && $r->method() === 'POST' && count($r['mediaItems']) === 2
            && $r['mediaItems'][0]['type'] === 'image' && str_contains($r['mediaItems'][0]['url'], '/storage/social/'.$this->shop->id.'/')
            && $r['publishNow'] === true);
        $this->assertCount(2, \Illuminate\Support\Facades\Storage::disk('public')->files('social/'.$this->shop->id));

        // A video goes alone; a video plus photos is refused.
        $video = \Illuminate\Http\UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4');
        $this->actingAs($this->owner)->post(route('social.posts.store'), ['caption' => 'x', 'media' => [$video, \Illuminate\Http\UploadedFile::fake()->image('c.jpg')],
            'accounts' => ['acc_ig'], 'when' => 'now'])->assertSessionHasErrors('media');
        $this->actingAs($this->owner)->post(route('social.posts.store'), ['caption' => 'x', 'media' => [\Illuminate\Http\UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')],
            'accounts' => ['acc_ig'], 'when' => 'now'])->assertSessionHasErrors('media.0');
    }

    public function test_publishing_posts_pick_up_their_final_status(): void
    {
        Http::fake([
            'zernio.com/api/v1/accounts*' => Http::response(['accounts' => []]),
            'zernio.com/api/v1/analytics/best-time*' => Http::response(['slots' => []]),
            'zernio.com/api/v1/posts/post_7' => Http::response(['post' => ['_id' => 'post_7', 'status' => 'published']]),
        ]);
        $this->shop->forceFill(['zernio_profile_id' => 'prof_1'])->save();
        $post = SocialPost::create(['shop_id' => $this->shop->id, 'caption' => 'x', 'targets' => [], 'status' => 'publishing', 'zernio_post_id' => 'post_7']);
        $this->actingAs($this->owner)->get(route('social.index'))->assertOk()->assertSee('انتشر');
        $this->assertSame('published', $post->fresh()->status);
    }
}
