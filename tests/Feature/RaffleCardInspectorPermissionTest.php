<?php

namespace Tests\Feature;

use App\Models\EmployeePermission;
use App\Models\RaffleCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaffleCardInspectorPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_permission_can_check_one_card_but_cannot_open_management_page(): void
    {
        $employee = User::create([
            'name' => 'Card Inspector',
            'email' => 'card-inspector@example.com',
            'password' => 'secret123',
            'role' => 'employee',
            'is_active' => true,
        ]);
        EmployeePermission::create([
            'user_id' => $employee->id,
            'permission' => 'raffle_cards.inspect',
        ]);
        RaffleCard::create([
            'card_number' => '123456',
            'prize_title' => 'هدية الفحص',
            'is_active' => true,
            'used_at' => now(),
            'used_customer_name' => 'أحمد الفائز',
        ]);

        $this->actingAs($employee)
            ->get(route('raffle-cards.inspector', ['card_number' => '123456']))
            ->assertOk()
            ->assertSee('البطاقة رابحة')
            ->assertSee('هدية الفحص')
            ->assertSee('اسم الفائز')
            ->assertSee('أحمد الفائز');

        $this->actingAs($employee)
            ->get(route('raffle-cards.index'))
            ->assertForbidden();
    }

    public function test_inspector_shows_non_winning_result_without_creating_a_raffle_entry(): void
    {
        $employee = User::create([
            'name' => 'Card Inspector Two',
            'email' => 'card-inspector-two@example.com',
            'password' => 'secret123',
            'role' => 'employee',
            'is_active' => true,
        ]);
        EmployeePermission::create(['user_id' => $employee->id, 'permission' => 'raffle_cards.inspect']);

        $this->actingAs($employee)
            ->get(route('raffle-cards.inspector', ['card_number' => '999999']))
            ->assertOk()
            ->assertSee('الرقم ليس بطاقة رابحة مسجلة');

        $this->assertDatabaseCount('raffle_entries', 0);
    }
}
