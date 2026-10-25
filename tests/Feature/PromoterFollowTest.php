<?php

namespace Tests\Feature;

use App\Models\PromoterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PromoterFollowTest extends TestCase
{
    use RefreshDatabase;

    private function profile(): PromoterProfile
    {
        return PromoterProfile::create([
            'user_id' => User::factory()->create(['role' => 'promoter', 'is_active' => true])->id,
            'slug' => 'sara', 'display_name' => 'Sara', 'is_active' => true,
        ]);
    }

    public function test_follow_and_unfollow_are_idempotent_and_isolated_by_account(): void
    {
        $profile = $this->profile();
        $a = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $b = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $url = route('promoters.follow.store', $profile);
        $this->actingAs($a)->putJson($url, ['user_id' => $b->id])->assertOk()->assertJson(['following' => true, 'followers_count' => 1]);
        $this->putJson($url)->assertOk()->assertJsonPath('followers_count', 1);
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $a->id, 'promoter_profile_id' => $profile->id]);
        $this->get($profile->publicUrl())->assertOk()->assertSee('إلغاء المتابعة')->assertSee('id="promoterFollowerCount">1', false);
        $this->actingAs($b)->putJson($url)->assertJsonPath('followers_count', 2);
        $this->actingAs($a)->deleteJson($url, ['user_id' => $b->id])->assertJson(['following' => false, 'followers_count' => 1]);
        $this->deleteJson($url)->assertJsonPath('followers_count', 1);
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $b->id]);
        $this->actingAs($profile->user)->get(route('promoter.manage', $profile))->assertOk()->assertSee('متابعو حسابك:')->assertSee('<strong>1</strong>', false);
        $this->get('/promoters')->assertOk()->assertSee('1 متابع');
    }

    public function test_guests_self_follow_disabled_users_and_unpublished_profiles_are_rejected(): void
    {
        $profile = $this->profile();
        $url = route('promoters.follow.store', $profile);
        $this->get($profile->publicUrl())->assertOk()->assertSee(route('promoters.follow.account', $profile), false);
        $this->putJson($url)->assertUnauthorized();
        $this->deleteJson($url)->assertUnauthorized();
        $this->actingAs($profile->user)->putJson($url)->assertUnprocessable();
        $this->get($profile->publicUrl())->assertDontSee('id="promoterFollowForm"', false);
        $this->actingAs(User::factory()->create(['is_active' => false]))->putJson($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $profile->update(['is_active' => false]);
        $this->putJson($url)->assertNotFound();
        $this->get(route('promoters.follow.account', $profile))->assertNotFound();
        $profile->update(['is_active' => true]);
        $profile->user->update(['is_active' => false]);
        $this->putJson($url)->assertNotFound();
        $this->assertDatabaseCount('promoter_followers', 0);
    }

    public function test_new_visitor_can_create_customer_account_and_follow_without_privilege_escalation(): void
    {
        $profile = $this->profile();
        $this->get(route('promoters.follow.account', $profile))->assertOk()->assertSee('إنشاء حساب ومتابعة');
        $this->post(route('promoters.follow.register', $profile), [
            'name' => 'Visitor', 'email' => 'visitor@example.test',
            'password' => 'visitor-password', 'password_confirmation' => 'visitor-password',
            'role' => 'super_admin', 'is_active' => false,
        ])->assertRedirect($profile->publicUrl());
        $user = User::where('email', 'visitor@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('visitor-password', $user->password));
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $user->id]);
        $this->get('/promoter')->assertForbidden();
    }

    public function test_existing_account_login_requires_password_and_resumes_follow(): void
    {
        $profile = $this->profile();
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true, 'password' => Hash::make('correct-password')]);
        $url = route('promoters.follow.login', $profile);
        $this->post($url, ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('promoter_followers', 0);
        $this->post($url, ['email' => $user->email, 'password' => 'correct-password'])->assertRedirect($profile->publicUrl());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $user->id]);
    }

    public function test_duplicate_email_and_invalid_password_cannot_create_accounts(): void
    {
        $profile = $this->profile();
        $url = route('promoters.follow.register', $profile);
        $this->postJson($url, ['name' => 'Visitor', 'email' => $profile->user->email, 'password' => 'short', 'password_confirmation' => 'different'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertGuest();
        $this->assertDatabaseCount('promoter_followers', 0);
    }

    public function test_follow_survives_profile_rename_and_is_removed_when_account_is_deleted(): void
    {
        $profile = $this->profile();
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->putJson(route('promoters.follow.store', $profile))->assertOk();
        $profile->update(['slug' => 'new-name']);
        $this->get($profile->publicUrl())->assertOk()->assertSee('إلغاء المتابعة');
        $user->delete();
        $this->assertDatabaseCount('promoter_followers', 0);
    }

    public function test_owner_identity_handles_string_ids_and_composer_is_private(): void
    {
        $profile = $this->profile();
        $profile->user_id = (string) $profile->user_id;
        $this->assertTrue($profile->isOwnedBy($profile->user));
        $this->assertTrue($profile->canBeManagedBy($profile->user));
        $this->actingAs($profile->user);
        $this->assertStringNotContainsString('promoterFollowForm', view('promoters.follow-button', ['profile' => $profile, 'following' => false])->render());
        $response = $this->actingAs($profile->user)->get($profile->publicUrl());
        $response->assertOk()->assertSee('id="profileComposer"', false)->assertSee('data-compose="story"', false)->assertDontSee('id="promoterFollowForm"', false);
        if (getenv('OZMAN_PROFILE_PREVIEW')) {
            file_put_contents(storage_path('app/promoter-profile-preview.html'), $response->getContent());
        }
        $this->actingAs(User::factory()->create(['role' => 'customer', 'is_active' => true]))
            ->get($profile->publicUrl())->assertOk()->assertDontSee('id="profileComposer"', false)->assertDontSee('data-compose="story"', false)->assertSee('id="promoterFollowForm"', false);
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->get($profile->publicUrl())->assertOk()->assertSee('id="profileComposer"', false)->assertDontSee('id="promoterFollowForm"', false);
    }

    public function test_inline_composer_publishes_story_with_expiration_and_enforces_ownership(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $profile = $this->profile();
        $payload = ['kind' => 'story', 'caption' => 'New story', 'media' => \Illuminate\Http\UploadedFile::fake()->image('story.jpg')];
        $url = route('promoter.posts.store', $profile);
        $this->actingAs(User::factory()->create(['role' => 'promoter', 'is_active' => true]))->postJson($url, $payload)->assertForbidden();
        $this->actingAs($profile->user)->postJson($url, $payload)->assertCreated()->assertJson(['published' => true, 'url' => $profile->publicUrl()]);
        $post = $profile->posts()->sole();
        $this->assertSame('story', $post->kind);
        $this->assertTrue($post->expires_at->equalTo($post->created_at->copy()->addDay()));
        $this->postJson($url, ['kind' => 'story'])->assertUnprocessable()->assertJsonValidationErrors('media');
        $this->assertDatabaseCount('promoter_posts', 1);
    }
}
