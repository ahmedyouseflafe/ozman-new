<?php

namespace Tests\Feature;

use App\Models\PromoterPost;
use App\Models\PromoterProfile;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromoterPageTest extends TestCase
{
    use RefreshDatabase;

    private function profile(string $slug = 'sara'): PromoterProfile
    {
        return PromoterProfile::create(['user_id' => User::factory()->create(['role' => 'promoter', 'is_active' => true])->id, 'display_name' => 'Sara '.$slug, 'slug' => $slug, 'is_active' => true]);
    }

    private function content(PromoterProfile $profile, array $attributes = []): PromoterPost
    {
        Storage::disk('local')->put('promoters/posts/test.jpg', 'image-content');

        return $profile->posts()->create(array_merge(['kind' => 'post', 'type' => 'image', 'media' => 'promoters/posts/test.jpg', 'caption' => 'Public caption', 'is_active' => true], $attributes));
    }

    public function test_admin_creates_independent_accounts_and_promoter_login_reaches_own_studio(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/promoter/accounts')->assertOk();
        $this->post('/promoter/accounts', ['display_name' => 'Sara', 'slug' => 'sara', 'email' => 'sara@example.test', 'password' => 'secret-testing-2026'])->assertRedirect();
        $profile = PromoterProfile::firstOrFail();
        $this->assertSame('promoter', $profile->user->role);
        $this->assertTrue(Hash::check('secret-testing-2026', $profile->user->password));
        $this->get(route('promoter.manage', $profile))->assertOk();
        Auth::logout();
        $this->post('/login', ['email' => 'sara@example.test', 'password' => 'secret-testing-2026'])->assertRedirect(route('promoter.dashboard'));
        $this->get('/promoter')->assertRedirect(route('promoter.manage', $profile));
        $this->get('/dashboard')->assertRedirect(route('promoter.dashboard'));
        $this->get('/products')->assertForbidden();
        $this->assertSame([], $profile->user->accessibleShopIds());
    }

    public function test_profiles_and_posts_are_isolated_and_other_roles_cannot_manage_them(): void
    {
        Storage::fake('local');
        $a = $this->profile();
        $b = $this->profile('other');
        $post = $this->content($b);
        $this->actingAs($a->user)->get(route('promoter.manage', $a))->assertOk();
        $this->get('/promoter/accounts')->assertForbidden();
        $this->get(route('promoter.manage', $b))->assertForbidden();
        $this->put(route('promoter.profile.update', $b), ['display_name' => 'stolen'])->assertForbidden();
        $this->post(route('promoter.posts.store', $b))->assertForbidden();
        $this->put(route('promoter.posts.update', $post), ['is_active' => 0])->assertForbidden();
        $this->delete(route('promoter.posts.destroy', $post))->assertForbidden();
        foreach (['marketer', 'shop_owner', 'customer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));
            $this->get('/promoter')->assertForbidden();
            $this->post('/promoter/accounts')->assertForbidden();
        }
        $this->assertDatabaseCount('promoter_posts', 1);
    }

    public function test_uploads_stories_and_posts_without_publishing_to_shop_displays(): void
    {
        Storage::fake('local');
        $profile = $this->profile();
        $shop = Shop::create(['name' => 'Local shop', 'slug' => 'local-shop', 'is_active' => true, 'user_id' => User::factory()->create(['role' => 'shop_owner'])->id]);
        $this->actingAs($profile->user)->post(route('promoter.posts.store', $profile), ['kind' => 'story', 'caption' => 'Today story', 'shop_id' => $shop->id, 'media' => UploadedFile::fake()->image('photo.jpg')])->assertRedirect();
        $story = PromoterPost::firstOrFail();
        $this->assertTrue($story->expires_at->equalTo($story->created_at->copy()->addDay()));
        Storage::disk('local')->assertExists($story->media);
        $this->post(route('promoter.posts.store', $profile), ['kind' => 'post', 'caption' => 'Permanent', 'media' => UploadedFile::fake()->image('photo.png')])->assertRedirect();
        $this->assertNull(PromoterPost::latest('id')->first()->expires_at);
        $this->assertDatabaseCount('main_screens', 0);
        $this->assertDatabaseCount('advertisements', 0);
        $this->assertDatabaseCount('shop_stories', 0);
        $this->postJson(route('promoter.posts.store', $profile), ['kind' => 'post', 'media' => UploadedFile::fake()->createWithContent('bad.html', '<script>alert(1)</script>')])->assertUnprocessable();
        $this->postJson(route('promoter.posts.store', $profile), ['kind' => 'post', 'media' => UploadedFile::fake()->image('big.jpg')->size(20481)])->assertUnprocessable();
        $shop->update(['is_active' => false]);
        $this->postJson(route('promoter.posts.store', $profile), ['kind' => 'post', 'shop_id' => $shop->id, 'media' => UploadedFile::fake()->image('photo.jpg')])->assertUnprocessable();
        $this->assertDatabaseCount('promoter_posts', 2);
    }

    public function test_expired_hidden_and_disabled_content_cannot_be_retrieved_publicly(): void
    {
        Storage::fake('local');
        $profile = $this->profile();
        $live = $this->content($profile, ['caption' => 'Visible live']);
        $hidden = $this->content($profile, ['caption' => 'Hidden secret', 'is_active' => false]);
        $expired = $this->content($profile, ['kind' => 'story', 'caption' => 'Expired secret', 'expires_at' => now()->subSecond()]);
        $story = $this->content($profile, ['kind' => 'story', 'caption' => 'Story live', 'expires_at' => now()->addDay()]);
        $video = $this->content($profile, ['type' => 'video', 'caption' => 'Video live']);
        $this->get($profile->publicUrl())->assertOk()->assertSee('Visible live')->assertSee('Video live')->assertSee('Story live')->assertDontSee('Hidden secret')->assertDontSee('Expired secret');
        $this->get(route('promoters.media', $live))->assertOk();
        $this->get(route('promoters.media', $hidden))->assertNotFound();
        $this->get(route('promoters.media', $expired))->assertNotFound();
        $this->travel(25)->hours();
        $this->get(route('promoters.media', $story))->assertNotFound();
        $this->get($profile->publicUrl())->assertDontSee('Story live');
        $this->actingAs($profile->user)->get(route('promoters.media', $expired))->assertOk();
        $profile->update(['is_active' => false]);
        Auth::logout();
        $this->get($profile->publicUrl())->assertNotFound();
        $this->get(route('promoters.media', $live))->assertNotFound();
        $this->get('/promoters')->assertOk()->assertDontSee($profile->display_name);
        $this->actingAs($profile->user)->get('/promoter')->assertForbidden();
    }

    public function test_editing_profile_and_deleting_content_updates_storage_and_visibility(): void
    {
        Storage::fake('local');
        $profile = $this->profile();
        $post = $this->content($profile);
        $this->actingAs($profile->user)->put(route('promoter.profile.update', $profile), ['display_name' => 'Updated', 'bio' => 'My story', 'avatar' => UploadedFile::fake()->image('avatar.jpg'), 'is_active' => false])->assertRedirect();
        $profile->refresh();
        $old = $profile->avatar;
        $this->assertTrue($profile->is_active);
        Storage::disk('local')->assertExists($old);
        $this->put(route('promoter.profile.update', $profile), ['display_name' => 'Updated', 'avatar' => UploadedFile::fake()->image('new.jpg')])->assertRedirect();
        Storage::disk('local')->assertMissing($old);
        $this->put(route('promoter.posts.update', $post), ['caption' => 'Updated caption', 'is_active' => 0, 'promoter_profile_id' => 999])->assertRedirect();
        $post->refresh();
        $this->assertFalse($post->is_active);
        $this->assertSame($profile->id, $post->promoter_profile_id);
        $this->delete(route('promoter.posts.destroy', $post))->assertRedirect();
        Storage::disk('local')->assertMissing($post->media);
        $this->assertDatabaseCount('promoter_posts', 0);
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))->patch(route('promoter.accounts.update', $profile), ['is_active' => 0])->assertRedirect();
        $this->assertFalse($profile->fresh()->user->is_active);
        Auth::logout();
        $this->get($profile->publicUrl())->assertNotFound();
    }

    public function test_video_upload_and_range_requests_and_escaped_profile_metadata(): void
    {
        Storage::fake('local');
        $profile = $this->profile();
        // A minimal ISO BMFF header exercises actual server-side MIME detection.
        $bytes = base64_decode('AAAAHGZ0eXBtcDQyAAAAAG1wNDJpc29tYXZjMQAAF7xtb292AAAAeG12aGQBAAAAAAAAAOG8K6UAAAAA4bwrpQAAAlgAAAAAAAAiLAABAAABAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAEAAAAA=');
        Storage::disk('local')->put('fixture.mp4', $bytes);
        $file = new UploadedFile(Storage::disk('local')->path('fixture.mp4'), 'movie.mp4', null, null, true);
        $this->actingAs($profile->user)->post(route('promoter.posts.store', $profile), ['kind' => 'post', 'caption' => 'Video', 'media' => $file])->assertRedirect()->assertSessionHasNoErrors();
        $post = PromoterPost::firstOrFail();
        $this->assertSame('video', $post->type);
        Auth::logout();
        $this->get(route('promoters.media', $post), ['Range' => 'bytes=0-7'])->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-7/'.strlen($bytes));
        $profile->update(['display_name' => '</title><script>alert(1)</script>', 'bio' => '" onload="alert(2)']);
        $this->get($profile->publicUrl())->assertOk()->assertDontSee('</title><script>alert(1)</script>',false)->assertDontSee('content="" onload=',false);
    }
}
