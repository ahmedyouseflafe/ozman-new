<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\ShopStory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopStoriesTest extends TestCase
{
    use RefreshDatabase;

    private function shop(User $owner, string $slug): Shop
    {
        return Shop::create(['user_id' => $owner->id, 'name' => $slug, 'slug' => $slug, 'is_active' => true]);
    }

    public function test_owner_can_publish_but_cannot_publish_or_delete_for_another_shop(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop($owner, 'mine');
        $foreign = $this->shop($other, 'other');
        $this->actingAs($owner)->get(route('shop-stories.index'))->assertOk()->assertSee('نشر الستوري');
        $this->actingAs($owner)->post(route('shop-stories.store'), [
            'shop_id' => $shop->id, 'caption' => 'New offer',
            'media' => UploadedFile::fake()->create('story.mp4', 10, 'video/mp4'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $story = ShopStory::firstOrFail();
        $this->assertTrue($story->expires_at->isFuture());
        Storage::disk('local')->assertExists($story->media);
        $this->actingAs($other)->delete(route('shop-stories.destroy', $story))->assertNotFound();
        $this->actingAs($owner)->post(route('shop-stories.store'), [
            'shop_id' => $foreign->id, 'media' => UploadedFile::fake()->create('story.mp4', 10, 'video/mp4'),
        ])->assertNotFound();
        $this->actingAs($owner)->delete(route('shop-stories.destroy', $story))->assertRedirect();
        Storage::disk('local')->assertMissing($story->media);
    }

    public function test_expired_stories_and_inactive_shops_are_not_public(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop($owner, 'stories');
        $story = ShopStory::create(['shop_id' => $shop->id, 'media' => 'shop-stories/test.mp4', 'type' => 'video', 'expires_at' => now()->addHour()]);
        $this->getJson(route('shop-stories.feed'))->assertOk()->assertJsonCount(1)->assertJsonPath('0.stories.0.id', $story->id);
        $shop->update(['is_active' => false]);
        $this->getJson(route('shop-stories.feed'))->assertExactJson([]);
        $this->get(route('shop-stories.media', $story))->assertNotFound();
        $shop->update(['is_active' => true]);
        $this->travel(25)->hours();
        $this->getJson(route('shop-stories.feed'))->assertExactJson([]);
        $this->get(route('shop-stories.media', $story))->assertNotFound();
    }

    public function test_disallowed_uploads_are_rejected(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop($owner, 'uploads');
        $this->actingAs($owner)->post(route('shop-stories.store'), [
            'shop_id' => $shop->id, 'media' => UploadedFile::fake()->create('page.html', 1, 'text/html'),
        ])->assertSessionHasErrors('media');
        $this->assertDatabaseCount('shop_stories', 0);
    }

    public function test_active_restaurant_story_turns_the_menu_logo_into_a_story_button(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop($owner, 'story-restaurant');
        $shop->update([
            'catalog_type' => 'restaurant',
            'logo' => 'images/logo.jpg',
        ]);
        ShopStory::create([
            'shop_id' => $shop->id,
            'media' => 'shop-stories/menu-story.jpg',
            'type' => 'image',
            'expires_at' => now()->addHour(),
        ]);

        $this->get(route('restaurant.menu', $shop))
            ->assertOk()
            ->assertSee('data-shop-story-trigger', false)
            ->assertSee('data-story-shop-id="'.$shop->id.'"', false)
            ->assertSee('class="restaurant-story-avatar has-shop-story"', false)
            ->assertSee('data-show-list="0"', false);
    }

    public function test_story_views_are_unique_and_the_owner_preview_is_not_counted(): void
    {
        $owner = User::factory()->create([
            'name' => 'Story Owner',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $viewer = User::factory()->create([
            'name' => 'Story Customer',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);
        $shop = $this->shop($owner, 'viewed-story');
        $story = ShopStory::create([
            'shop_id' => $shop->id,
            'media' => 'shop-stories/viewed.jpg',
            'type' => 'image',
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($owner)
            ->postJson(route('shop-stories.view', $story))
            ->assertOk()
            ->assertJsonPath('recorded', false);

        $this->actingAs($viewer)
            ->withHeader('User-Agent', 'OzmanApp/1.0 Android')
            ->postJson(route('shop-stories.view', $story))
            ->assertOk()
            ->assertJsonPath('recorded', true);
        $this->postJson(route('shop-stories.view', $story))->assertOk();

        $this->assertDatabaseCount('shop_story_views', 1);
        $this->assertDatabaseHas('shop_story_views', [
            'shop_story_id' => $story->id,
            'user_id' => $viewer->id,
            'viewer_name' => 'Story Customer',
            'source' => 'app',
        ]);

        $this->actingAs($owner)
            ->get(route('shop-stories.index'))
            ->assertOk()
            ->assertSee('>1</strong>', false)
            ->assertSee('مشاهدة فريدة')
            ->assertSee('Story Customer');
    }
}
