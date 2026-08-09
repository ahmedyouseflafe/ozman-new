<?php

namespace Tests\Feature;

use App\Models\EmployeePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveDashboardPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_dashboard_exposes_the_complete_permission_aware_sidebar(): void
    {
        $employee = User::create([
            'name' => 'Full Mobile Access',
            'email' => 'full-mobile-access@example.com',
            'password' => 'secret123',
            'role' => 'employee',
            'is_active' => true,
        ]);

        $permissions = collect(config('employee_permissions.groups', []))
            ->flatMap(fn (array $group) => array_keys($group['permissions'] ?? []))
            ->unique();

        foreach ($permissions as $permission) {
            EmployeePermission::create([
                'user_id' => $employee->id,
                'permission' => $permission,
            ]);
        }

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="admin-dashboard-sidebar"', false)
            ->assertSee('data-admin-menu-open', false)
            ->assertSee('كل الأقسام')
            ->assertSee(route('categories'))
            ->assertSee(route('ads'))
            ->assertSee(route('distributors'))
            ->assertSee(route('raffle-cards.index'));
    }
}
