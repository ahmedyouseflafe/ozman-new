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
}
