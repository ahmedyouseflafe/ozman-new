<?php

namespace Tests\Feature;

use App\Models\PromoterProfile;
use App\Models\User;
use App\Notifications\PromoterApplicationDecided;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommunitySignupApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function signup(array $data = [])
    {
        return $this->post(route('community.register'), ['account_type' => 'customer', 'name' => 'Lina Haddad', 'email' => 'lina@example.test',
            'username' => 'lina.h', 'password' => 'eight888', ...$data]);
    }

    public function test_signup_page_looks_like_instagram_with_account_type_and_username(): void
    {
        $this->get(route('community.login', ['tab' => 'register']))->assertOk()
            ->assertSee('name="account_type"', false)->assertSee('name="username"', false)->assertSee('التسجيل');
        $this->get(route('community.login'))->assertOk()->assertSee('name="login"', false)->assertSee('الإيميل أو اسم المستخدم')
            ->assertSee(route('community.login', ['tab' => 'register', 'as' => 'promoter']));
    }

    public function test_customer_signs_up_with_username_and_logs_in_with_it(): void
    {
        $this->signup(['username' => '@Lina.H'])->assertRedirect(route('community.account'));
        $user = User::where('email', 'lina@example.test')->sole();
        $this->assertSame('customer', $user->role);
        $this->assertSame('lina.h', $user->username);

        $this->post(route('logout'));
        $this->post(route('community.login.store'), ['login' => '@lina.h', 'password' => 'eight888'])->assertRedirect(route('community.account'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));
        $this->post(route('community.login.store'), ['login' => 'lina@example.test', 'password' => 'eight888'])->assertRedirect(route('community.account'));
    }

    public function test_usernames_are_validated_and_unique(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        User::factory()->create(['role' => 'customer', 'username' => 'taken']);
        foreach (['ab', 'bad name', 'dots..double', '.start', 'admin', 'taken', 'عربي'] as $bad) {
            $this->signup(['username' => $bad, 'email' => uniqid().'@example.test'])->assertSessionHasErrors('username');
        }
        $this->assertSame(1, User::count());

        // Accounts made without one (follow dialog, old forms) still get a username.
        $this->post(route('community.register'), ['name' => 'Omar', 'email' => 'omar.s@example.test', 'password' => 'eight888'])->assertRedirect();
        $this->assertSame('omar.s', User::where('email', 'omar.s@example.test')->value('username'));
    }

    public function test_promoter_signup_waits_for_admin_approval(): void
    {
        Notification::fake();
        $this->signup(['account_type' => 'promoter', 'username' => 'lina_finds'])->assertRedirect(route('community.account'));
        $user = User::where('email', 'lina@example.test')->sole();
        $profile = $user->promoterProfile;
        $this->assertSame('promoter', $user->role);
        $this->assertTrue($profile->isPending());
        $this->assertSame('lina-finds', $profile->slug);

        // Hidden from the public, no dashboard yet, but the customer account works.
        $this->get(route('promoters.show', 'lina-finds'))->assertNotFound();
        $this->get(route('promoters.index'))->assertDontSee('Lina Haddad');
        $this->get(route('promoter.dashboard'))->assertRedirect(route('community.account'));
        $this->get(route('community.account'))->assertOk()->assertSee('طلبك كمروّج قيد المراجعة');

        // The same link can't be taken by someone else meanwhile.
        $this->post(route('logout'));
        $this->signup(['account_type' => 'promoter', 'username' => 'lina.finds', 'email' => 'other@example.test'])->assertSessionHasErrors('username');

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('promoter.accounts'))->assertOk()->assertSee('id="pendingApplications"', false)->assertSee('@lina_finds');
        $this->actingAs($admin)->post(route('promoter.accounts.approve', $profile))->assertRedirect();
        Notification::assertSentTo($user, PromoterApplicationDecided::class, fn ($n) => $n->approved);
        $this->assertTrue($profile->fresh()->isPublished());

        $this->app['auth']->forgetGuards();
        $this->actingAs($user->fresh())->get(route('promoter.dashboard'))->assertRedirect(route('promoter.manage', $profile));
        $this->get(route('promoters.show', 'lina-finds'))->assertOk();
    }

    public function test_rejected_promoter_keeps_a_customer_account_and_frees_the_link(): void
    {
        Notification::fake();
        $this->signup(['account_type' => 'promoter', 'username' => 'lina_finds']);
        $user = User::where('email', 'lina@example.test')->sole();
        $profile = $user->promoterProfile;
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('promoter.accounts.reject', $profile))->assertRedirect();
        Notification::assertSentTo($user, PromoterApplicationDecided::class, fn ($n) => ! $n->approved);
        $this->assertModelMissing($profile);
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertDatabaseMissing('promoter_profile_slugs', ['slug' => 'lina-finds']);

        // Only pending applications can be approved or rejected; admin only.
        $live = PromoterProfile::create(['user_id' => User::factory()->create(['role' => 'promoter', 'is_active' => true])->id, 'slug' => 'live', 'display_name' => 'Live', 'is_active' => true, 'approved_at' => now()]);
        $this->actingAs($admin)->post(route('promoter.accounts.reject', $live))->assertNotFound();
        $this->actingAs($user->fresh())->post(route('promoter.accounts.approve', $live))->assertForbidden();
    }
}
