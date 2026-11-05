<?php

namespace Tests\Feature;

use App\Models\PromoterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Who can open what on the community side, and where each role lands after login and logout. */
class CommunityRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $mk = fn ($role) => User::factory()->create(['role' => $role, 'is_active' => true]);
        $this->users = ['customer' => $mk('customer'), 'promoter' => $mk('promoter'), 'pending' => $mk('promoter'), 'admin' => $mk('super_admin'), 'staff' => $mk('marketer')];
        PromoterProfile::create(['user_id' => $this->users['promoter']->id, 'slug' => 'live-page', 'display_name' => 'Live', 'is_active' => true]);
        PromoterProfile::create(['user_id' => $this->users['pending']->id, 'slug' => 'pending-page', 'display_name' => 'Pending', 'is_active' => false, 'approved_at' => null]);
    }

    private function as(string $who): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return $who === 'guest' ? $this : $this->actingAs($this->users[$who]);
    }

    public function test_each_role_reaches_only_its_own_areas(): void
    {
        $profile = $this->users['promoter']->promoterProfile;
        $expect = [
            // url => [guest, customer, promoter, pending, admin, staff]
            '/community/account' => ['login', 200, 200, 200, '/promoter/accounts', '/dashboard'],
            '/promoter' => ['login', 'account', '/promoter/profiles/'.$profile->id, 'account', '/promoter/accounts', 403],
            '/promoter/accounts' => ['login', 'account', 403, 'account', 200, 403],
            '/free-market/new' => ['login', 200, 200, 200, '/free-market', '/free-market'],
            '/free-market/admin' => ['login', 403, 403, 403, 200, 403],
            '/dashboard' => ['/login', 'account', '/promoter', '/promoter', 200, null],
        ];
        $roles = ['guest', 'customer', 'promoter', 'pending', 'admin', 'staff'];
        foreach ($expect as $url => $row) {
            foreach ($roles as $i => $role) {
                if ($row[$i] === null) {
                    continue;
                }
                $response = $this->as($role)->get($url);
                $want = ['login' => '/community/login', 'account' => '/community/account'][$row[$i]] ?? $row[$i];
                if (is_int($want)) {
                    $this->assertSame($want, $response->status(), "$role $url");
                } else {
                    $this->assertTrue($response->isRedirect(), "$role $url should redirect, got ".$response->status());
                    $this->assertSame($want, parse_url($response->headers->get('Location'), PHP_URL_PATH), "$role $url");
                }
            }
        }
    }

    public function test_login_pages_send_each_role_to_the_right_place(): void
    {
        $this->as('guest')->post(route('community.login.store'), ['login' => $this->users['customer']->email, 'password' => 'password'])->assertRedirect(route('community.account'));
        $this->as('guest')->post(route('community.login.store'), ['login' => $this->users['pending']->email, 'password' => 'password'])->assertRedirect(route('community.account'));
        $this->as('guest')->post(route('community.login.store'), ['login' => $this->users['promoter']->email, 'password' => 'password'])->assertRedirect(route('promoter.dashboard'));
        $this->as('guest')->post(route('community.login.store'), ['login' => $this->users['admin']->email, 'password' => 'password'])->assertRedirect(route('promoter.dashboard'));

        // Staff use the dashboard login; the community page explains that and signs nobody in.
        $this->as('guest')->from(route('community.login'))->post(route('community.login.store'), ['login' => $this->users['staff']->email, 'password' => 'password'])
            ->assertRedirect(route('community.login'))->assertSessionHasErrors('login')->assertSessionHas('staff_login');
        $this->assertGuest();

        // A customer on the dashboard login lands in their account instead of being signed out.
        $this->as('guest')->post(route('login.store'), ['email' => $this->users['customer']->email, 'password' => 'password'])->assertRedirect(route('community.account'));
        $this->assertAuthenticatedAs($this->users['customer']);
        $this->get(route('login'))->assertRedirect(route('community.account'));
    }

    public function test_logout_returns_community_members_to_the_community(): void
    {
        $this->as('customer')->post(route('logout'))->assertRedirect(route('promoters.index'));
        $this->as('promoter')->post(route('logout'))->assertRedirect(route('promoters.index'));
        $this->as('admin')->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_account_hub_shows_the_right_things_for_each_role(): void
    {
        $this->as('customer')->get(route('community.account'))->assertOk()->assertSee('🛍 عميل', false)
            ->assertSee(route('community.become-promoter'), false)->assertDontSee('مساحتك كمروّج');
        $this->as('promoter')->get(route('community.account'))->assertOk()->assertSee('مساحتك كمروّج')->assertSee('انشر حكاية')
            ->assertDontSee(route('community.become-promoter'), false);
        $this->as('pending')->get(route('community.account'))->assertOk()->assertSee('مروّج قيد المراجعة')->assertDontSee('مساحتك كمروّج');

        // Header: admins get management shortcuts, not buying and selling.
        $this->as('admin')->get(route('promoters.index'))->assertOk()->assertSee(route('market.admin'), false)->assertDontSee('aria-label="بيع منتج"', false);
        $this->as('customer')->get(route('promoters.index'))->assertSee('aria-label="بيع منتج"', false);
        $this->as('promoter')->get(route('promoters.index'))->assertSee('aria-label="انشر حكاية"', false);
    }

    public function test_a_customer_can_apply_to_become_a_promoter(): void
    {
        $customer = $this->users['customer'];
        $this->as('customer')->post(route('community.become-promoter'), ['username' => 'admin'])->assertSessionHasErrors('username');
        $this->as('customer')->post(route('community.become-promoter'), ['username' => 'live.page'])->assertSessionHasErrors('username');
        $this->as('customer')->post(route('community.become-promoter'), ['username' => 'new_creator', 'bio' => 'بحكي عن الأكل'])->assertRedirect(route('community.account'));

        $customer->refresh();
        $this->assertSame('promoter', $customer->role);
        $this->assertTrue($customer->promoterProfile->isPending());
        $this->assertSame('new-creator', $customer->promoterProfile->slug);
        $this->as('promoter')->post(route('community.become-promoter'), ['username' => 'again'])->assertForbidden();
    }
}
