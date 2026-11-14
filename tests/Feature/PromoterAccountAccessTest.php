<?php

namespace Tests\Feature;

use App\Models\LoyaltyPointEntry;
use App\Models\PromoterGift;
use App\Models\PromoterGiftSend;
use App\Models\PromoterProfile;
use App\Models\User;
use App\Services\LoyaltyPoints;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromoterAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    private function profile(string $slug = 'rima', array $user = []): PromoterProfile
    {
        return PromoterProfile::create([
            'user_id' => User::factory()->create(['role' => 'promoter', 'is_active' => true, 'name' => 'Rima Ali', ...$user])->id,
            'slug' => $slug, 'display_name' => Str::title($slug), 'is_active' => true,
        ]);
    }

    public function test_guests_on_promoter_pages_go_to_the_community_login_and_come_back(): void
    {
        $profile = $this->profile();

        $this->get('/promoter')->assertRedirect(route('community.login'));
        $this->get(route('promoter.manage', $profile))->assertRedirect(route('community.login'));
        $this->get(route('promoters.index'))->assertSee(route('community.login', ['return' => '/promoter']), false);

        $this->post(route('community.login.store'), ['email' => $profile->user->email, 'password' => 'password'])
            ->assertRedirect(route('promoter.manage', $profile));
    }

    public function test_promoter_and_customer_can_change_their_password(): void
    {
        $profile = $this->profile();
        $promoter = $profile->user;

        $this->actingAs($promoter)->get(route('promoter.manage', $profile))->assertOk()->assertSee(route('community.password.change'), false)
            ->assertSee('id="giftHistory"', false)->assertSee('id="followersList"', false);

        $this->actingAs($promoter)->from(route('promoter.manage', $profile))->put(route('community.password.change'), [
            'current_password' => 'wrong', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $promoter->fresh()->password));

        $this->actingAs($promoter)->put(route('community.password.change'), [
            'current_password' => 'password', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1',
        ])->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertTrue(Hash::check('new-secret-1', $promoter->fresh()->password));

        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($customer)->get(route('community.account'))->assertOk()->assertSee(route('community.password.change'), false);

        // Dashboard staff don't use this form.
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->actingAs($owner)->put(route('community.password.change'), [
            'current_password' => 'password', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1',
        ])->assertForbidden();
    }

    public function test_forgot_password_sends_a_link_and_resets(): void
    {
        Notification::fake();
        $profile = $this->profile();
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        $this->get(route('community.login'))->assertSee(route('community.password.request'), false);
        $this->get(route('community.password.request'))->assertOk();

        $this->post(route('community.password.email'), ['email' => $profile->user->email])->assertSessionHas('status');
        $this->post(route('community.password.email'), ['email' => $owner->email])->assertSessionHas('status');
        $this->post(route('community.password.email'), ['email' => 'nobody@example.com'])->assertSessionHas('status');
        Notification::assertSentTo($profile->user, ResetPassword::class);
        Notification::assertSentTo($owner, ResetPassword::class);

        $token = null;
        Notification::assertSentTo($profile->user, ResetPassword::class, function (ResetPassword $n) use (&$token, $profile) {
            $token = $n->token;
            $mail = $n->toMail($profile->user);
            $this->assertStringContainsString('/community/reset-password/', $mail->actionUrl);
            $this->assertSame('تعيين كلمة مرور جديدة · أوزمان', $mail->subject);

            return true;
        });

        $this->get(route('community.password.reset', ['token' => $token, 'email' => $profile->user->email]))->assertOk()->assertSee($token, false);
        $this->post(route('community.password.store'), ['token' => 'bad', 'email' => $profile->user->email, 'password' => 'brand-new-9', 'password_confirmation' => 'brand-new-9'])
            ->assertSessionHasErrors('email');
        $this->post(route('community.password.store'), ['token' => $token, 'email' => $profile->user->email, 'password' => 'brand-new-9', 'password_confirmation' => 'brand-new-9'])
            ->assertRedirect(route('community.login'));
        $this->assertTrue(Hash::check('brand-new-9', $profile->user->fresh()->password));
    }

    public function test_shop_owners_reset_by_email_and_return_to_the_dashboard_login_but_admins_cannot(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->get(route('login'))->assertOk()->assertSee(route('community.password.request'), false);
        $this->post(route('community.password.email'), ['email' => $owner->email])->assertSessionHas('status');
        $this->post(route('community.password.email'), ['email' => $admin->email])->assertSessionHas('status');
        Notification::assertNotSentTo($admin, ResetPassword::class);

        $token = null;
        Notification::assertSentTo($owner, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->post(route('community.password.store'), ['token' => $token, 'email' => $owner->email, 'password' => 'owner-new-9', 'password_confirmation' => 'owner-new-9'])
            ->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('owner-new-9', $owner->fresh()->password));
    }

    public function test_admin_can_change_a_promoter_login_email(): void
    {
        $profile = $this->profile();
        $other = $this->profile('sara');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('promoter.accounts'))->assertOk()->assertSee('name="email"', false);
        $this->actingAs($admin)->patch(route('promoter.accounts.update', $profile), ['is_active' => 1, 'email' => $other->user->email])
            ->assertSessionHasErrors('email');
        $this->actingAs($admin)->patch(route('promoter.accounts.update', $profile), ['is_active' => 1, 'email' => 'rima.new@example.com'])
            ->assertSessionHasNoErrors();
        $this->assertSame('rima.new@example.com', $profile->user->fresh()->email);

        // The old form without an email field still works.
        $this->actingAs($admin)->patch(route('promoter.accounts.update', $profile), ['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertSame('rima.new@example.com', $profile->user->fresh()->email);
    }

    public function test_promoters_collect_points_and_can_gift_others_but_not_themselves(): void
    {
        $rima = $this->profile();
        $sara = $this->profile('sara');
        $loyalty = app(LoyaltyPoints::class);
        $this->assertTrue($loyalty->eligibleCustomer($rima->user));
        LoyaltyPointEntry::create(['user_id' => $rima->user_id, 'points' => 100, 'type' => LoyaltyPointEntry::TYPE_MANUAL, 'status' => LoyaltyPointEntry::CONFIRMED, 'note' => 'test']);
        $rose = PromoterGift::where('name', 'وردة')->value('id');

        $this->actingAs($rima->user)->postJson(route('promoters.gifts.store', $rima), ['gift_id' => $rose])->assertForbidden();
        $this->actingAs($rima->user)->postJson(route('promoters.gifts.store', $sara), ['gift_id' => $rose])->assertOk()->assertJsonPath('balance', 90);
        $this->assertSame(10, $sara->fresh()->gift_points);
    }

    public function test_expired_stories_are_deleted_with_files_and_keep_their_shop_visits(): void
    {
        Storage::fake('local');
        $profile = $this->profile();
        $make = fn (string $kind, $expires, int $clicks) => tap($profile->posts()->create(['kind' => $kind, 'type' => 'image', 'is_active' => true,
            'media' => 'promoters/posts/'.Str::random(6).'.jpg', 'expires_at' => $expires, 'shop_clicks' => $clicks]), fn ($p) => Storage::disk('local')->put($p->media, 'x'));
        $old = $make('story', now()->subDays(5), 4);
        $recent = $make('story', now()->subDay(), 1);
        $post = $make('post', null, 2);
        PromoterGiftSend::create(['promoter_profile_id' => $profile->id, 'promoter_post_id' => $old->id, 'gift_name' => 'x', 'gift_icon' => '🎁', 'points' => 10]);

        $this->artisan('promoters:prune-stories')->assertSuccessful();

        $this->assertModelMissing($old);
        Storage::disk('local')->assertMissing($old->media);
        $this->assertModelExists($recent);
        $this->assertModelExists($post);
        Storage::disk('local')->assertExists($recent->media);
        $this->assertSame(4, $profile->fresh()->archived_shop_clicks);
        $this->assertNull(PromoterGiftSend::sole()->promoter_post_id);

        $this->get($profile->publicUrl())->assertOk()->assertSee('<strong>7</strong>', false);
    }
}
