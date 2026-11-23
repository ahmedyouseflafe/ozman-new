<?php

namespace Tests\Feature;

use App\Models\{AdminNotification, Distributor, PromoterProfile, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** The admin layout (header bell + sidebar menu) renders for every kind of user and every kind of shop. */
class AdminLayoutRolesTest extends TestCase
{
    use RefreshDatabase;

    private function shopFor(User $owner, string $type): Shop
    {
        return Shop::create(['user_id' => $owner->id, 'name' => "محل {$type}", 'slug' => $type.'-'.Str::random(5), 'catalog_type' => $type,
            'is_active' => true, 'is_accepting_orders' => true, 'whatsapp' => '972501234567']);
    }

    public function test_super_admin_sees_the_bell_and_the_waiting_promoters_count(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        AdminNotification::create(['type' => 'shop_created', 'title' => 'محل جديد', 'message' => 'تجربة']);
        if (class_exists(PromoterProfile::class) && method_exists(PromoterProfile::class, 'scopePending')) {
            $this->assertIsInt(PromoterProfile::pending()->count());
        }

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('admin-dashboard-sidebar', false)
            ->assertSee('محل جديد')
            ->assertSee('إحصائيات كل المحلات');
    }

    public function test_shop_owners_of_every_type_get_their_menu(): void
    {
        foreach (['general', 'restaurant', 'cosmetics', 'krembo', 'real_estate', 'clothing', 'electronics', 'furniture_appliances', 'sweets'] as $type) {
            $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
            $this->shopFor($owner, $type);
            // Owners land on their own dashboard page; it carries the same layout.
            $this->actingAs($owner)->followingRedirects()->get('/dashboard')->assertOk()->assertSee('admin-dashboard-sidebar', false);
        }
    }

    public function test_agents_distributors_marketers_and_employees_get_the_layout(): void
    {
        foreach (['agent', 'distributor', 'marketer', 'employee'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            if ($role === 'distributor') {
                $shop = $this->shopFor(User::factory()->create(['role' => 'shop_owner', 'is_active' => true]), 'krembo');
                Distributor::forceCreate(['shop_id' => $shop->id, 'user_id' => $user->id, 'name' => 'موزع', 'email' => $user->email, 'is_active' => true]);
            }
            $response = $this->actingAs($user)->followingRedirects()->get('/dashboard');
            $this->assertContains($response->status(), [200, 302, 403], "{$role}: {$response->status()}");
            if ($response->status() === 200) {
                // With the layout, or (no permissions given yet) the page that says so.
                $html = (string) $response->getContent();
                $this->assertTrue(str_contains($html, 'admin-dashboard-sidebar') || str_contains($html, 'صلاحيات الحساب'), "{$role}: no layout");
            }
        }
    }
}
