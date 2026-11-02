<?php

namespace Tests\Feature;

use App\Models\PromoterProfile;
use App\Models\User;
use App\Services\GuestFollows;
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
        $profile = $this->profile(); $this->withCredentials();
        $a = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $b = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $url = route('promoters.follow.store', $profile);
        $this->actingAs($a)->putJson($url, ['user_id' => $b->id])->assertOk()->assertJson(['following' => true, 'followers_count' => 1]);
        $this->putJson($url)->assertOk()->assertJsonPath('followers_count', 1);
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $a->id, 'promoter_profile_id' => $profile->id]);
        $this->get($profile->publicUrl())->assertOk()->assertSee('aria-pressed="true"', false)->assertSee('id="promoterFollowerCount">1', false);
        $this->actingAs($b)->putJson($url)->assertJsonPath('followers_count', 2);
        $this->actingAs($a)->deleteJson($url, ['user_id' => $b->id])->assertJson(['following' => false, 'followers_count' => 1]);
        $this->deleteJson($url)->assertJsonPath('followers_count', 1);
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $b->id]);
        $this->actingAs($profile->user)->get(route('promoter.manage', $profile))->assertOk()->assertSee('وصول صفحتك')->assertSee('<strong>1</strong>', false);
        $this->get('/promoters')->assertOk()->assertSee('<b>1</b> متابع', false);
    }

    public function test_self_follow_disabled_users_and_unpublished_profiles_are_rejected(): void
    {
        $profile = $this->profile(); $this->withCredentials();
        $url = route('promoters.follow.store', $profile);
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
        $profile = $this->profile(); $this->withCredentials();
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
        $profile = $this->profile(); $this->withCredentials();
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
        $profile = $this->profile(); $this->withCredentials();
        $url = route('promoters.follow.register', $profile);
        $this->postJson($url, ['name' => 'Visitor', 'email' => $profile->user->email, 'password' => 'short', 'password_confirmation' => 'different'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertGuest();
        $this->assertDatabaseCount('promoter_followers', 0);
    }

    public function test_follow_survives_profile_rename_and_is_removed_when_account_is_deleted(): void
    {
        $profile = $this->profile(); $this->withCredentials();
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->putJson(route('promoters.follow.store', $profile))->assertOk();
        $profile->update(['slug' => 'new-name']);
        $this->get($profile->publicUrl())->assertOk()->assertSee('aria-pressed="true"', false);
        $user->delete();
        $this->assertDatabaseCount('promoter_followers', 0);
    }

    public function test_owner_identity_handles_string_ids_and_composer_is_private(): void
    {
        $profile = $this->profile(); $this->withCredentials();
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

    public function test_guest_follows_instantly_with_a_device_cookie_and_counts_with_accounts(): void
    {
        $profile = $this->profile(); $this->withCredentials();
        $url = route('promoters.follow.store', $profile);
        $this->get($profile->publicUrl())->assertOk()->assertSee('id="promoterFollowForm"', false)->assertSee('aria-pressed="false"', false);

        $first = $this->putJson($url)->assertOk()->assertJson(['following' => true, 'followers_count' => 1, 'guest' => true, 'guest_prompt' => true])
            ->assertCookie(GuestFollows::COOKIE);
        $token = $first->getCookie(GuestFollows::COOKIE)->getValue();
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $token);
        $this->assertTrue($first->getCookie(GuestFollows::COOKIE)->isHttpOnly());
        $this->assertDatabaseMissing('promoter_guest_followers', ['guest_token_hash' => $token]);
        $this->assertDatabaseHas('promoter_guest_followers', ['promoter_profile_id' => $profile->id, 'guest_token_hash' => hash('sha256', $token)]);

        // Same device again: no duplicate row and no second account invitation.
        $this->withCookie(GuestFollows::COOKIE, $token)->putJson($url)->assertOk()->assertJson(['followers_count' => 1, 'guest_prompt' => false]);
        $this->assertDatabaseCount('promoter_guest_followers', 1);
        $this->withCookie(GuestFollows::COOKIE, $token)->get($profile->publicUrl())->assertSee('aria-pressed="true"', false);

        $this->actingAs(User::factory()->create(['role' => 'customer', 'is_active' => true]))->putJson($url)->assertJsonPath('followers_count', 2);
        $this->app['auth']->forgetGuards();
        $this->get('/promoters')->assertSee('<b>2</b> متابع', false);
        $this->withCookie(GuestFollows::COOKIE, $token)->deleteJson($url)->assertOk()->assertJson(['following' => false, 'followers_count' => 1]);
        $this->assertDatabaseCount('promoter_guest_followers', 0);
        // Unfollowing again is a no-op.
        $this->deleteJson($url)->assertOk()->assertJsonPath('followers_count', 1);
    }

    public function test_guest_follows_merge_into_the_account_on_register_and_login_without_duplicates(): void
    {
        $profile = $this->profile(); $this->withCredentials();
        $token = str_repeat('a', 64);
        $this->withCookie(GuestFollows::COOKIE, $token)->putJson(route('promoters.follow.store', $profile))->assertOk();

        $this->withCookie(GuestFollows::COOKIE, $token)->post(route('community.register'), [
            'name' => 'Guest', 'email' => 'guest@example.test', 'password' => 'eight888', 'password_confirmation' => 'eight888',
        ])->assertRedirect(route('community.account'))->assertCookieExpired(GuestFollows::COOKIE);
        $user = User::where('email', 'guest@example.test')->sole();
        $this->assertDatabaseHas('promoter_followers', ['user_id' => $user->id, 'promoter_profile_id' => $profile->id]);
        $this->assertDatabaseCount('promoter_guest_followers', 0);

        // Logging in on another device that also followed as a guest merges without a duplicate row.
        $this->post(route('logout'));
        $other = str_repeat('b', 64);
        $this->withCookie(GuestFollows::COOKIE, $other)->putJson(route('promoters.follow.store', $profile))->assertOk();
        $user->update(['password' => Hash::make('correct-password')]);
        $this->withCookie(GuestFollows::COOKIE, $other)->post(route('community.login.store'), ['email' => $user->email, 'password' => 'correct-password'])->assertRedirect();
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('promoter_followers')->where('user_id', $user->id)->count());
        $this->assertDatabaseCount('promoter_guest_followers', 0);
    }

    public function test_create_account_link_opens_register_and_returns_to_the_promoter(): void
    {
        $profile = $this->profile(); $this->withCredentials();
        $this->get(route('community.login', ['tab' => 'register', 'return' => '/promoters/sara']))->assertOk()
            ->assertSee('register-first')->assertSee('id="register"', false);
        $this->post(route('community.register'), ['name' => 'Back', 'email' => 'back@example.test', 'password' => 'eight888', 'password_confirmation' => 'eight888'])
            ->assertRedirect($profile->publicUrl());

        $this->post(route('logout'));
        $this->get(route('community.login', ['return' => '//evil.example/promoters/x']));
        $this->post(route('community.register'), ['name' => 'Safe', 'email' => 'safe@example.test', 'password' => 'eight888', 'password_confirmation' => 'eight888'])
            ->assertRedirect(route('community.account'));
    }

    public function test_guests_on_customer_pages_go_to_customer_login_and_come_back(): void
    {
        $this->get(route('community.account'))->assertRedirect(route('community.login'));
        $this->get('/dashboard')->assertRedirect('/login');
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true, 'password' => Hash::make('correct-password')]);
        // The intended page wins over the default account page after login.
        $this->get(route('community.push.key'))->assertRedirect(route('community.login'));
        $this->post(route('community.login.store'), ['email' => $user->email, 'password' => 'correct-password'])->assertRedirect(route('community.push.key'));
    }

    public function test_both_signup_forms_share_the_eight_character_rule(): void
    {
        $profile = $this->profile(); $this->withCredentials();
        foreach ([route('community.register'), route('promoters.follow.register', $profile)] as $i => $url) {
            $this->postJson($url, ['name' => 'Short', 'email' => "short$i@example.test", 'password' => 'seven77', 'password_confirmation' => 'seven77'])
                ->assertUnprocessable()->assertJsonValidationErrors('password');
            $long = str_repeat('x', 129);
            $this->postJson($url, ['name' => 'Long', 'email' => "long$i@example.test", 'password' => $long, 'password_confirmation' => $long])
                ->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->post(route('promoters.follow.register', $profile), ['name' => 'Ok', 'email' => 'ok@example.test', 'password' => 'eight888', 'password_confirmation' => 'eight888'])
            ->assertRedirect($profile->publicUrl());
        $this->assertDatabaseHas('users', ['email' => 'ok@example.test']);
    }

    public function test_follow_route_is_rate_limited(): void
    {
        $url = route('promoters.follow.store', $this->profile());
        for ($i = 0; $i < 20; $i++) {
            $this->putJson($url)->assertOk();
        }
        $this->putJson($url)->assertTooManyRequests();
    }

    public function test_inline_composer_publishes_story_with_expiration_and_enforces_ownership(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $profile = $this->profile(); $this->withCredentials();
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
