<?php

namespace Tests\Feature;

use App\Models\RaffleCard;
use App\Models\RaffleEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaffleCardRandomBulkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_requested_number_of_random_winning_cards_in_range(): void
    {
        $admin = $this->admin();

        RaffleCard::create([
            'card_number' => '100000',
            'prize_title' => 'هدية قديمة',
            'is_active' => true,
        ]);
        RaffleEntry::create([
            'card_number' => '100001',
            'outcome' => RaffleEntry::OUTCOME_LIVE_DRAW,
        ]);

        $response = $this->actingAs($admin)->post(route('raffle-cards.random-bulk'), [
            'from_number' => '100000',
            'to_number' => '100020',
            'prize_count' => 8,
            'prize_title' => 'سماعة بلوتوث',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $createdCards = RaffleCard::query()
            ->where('prize_title', 'سماعة بلوتوث')
            ->get();

        $this->assertCount(8, $createdCards);
        $this->assertSame(8, $createdCards->pluck('card_number')->unique()->count());
        $this->assertFalse($createdCards->pluck('card_number')->contains('100000'));
        $this->assertFalse($createdCards->pluck('card_number')->contains('100001'));
        $this->assertTrue($createdCards->every(
            fn (RaffleCard $card) => (int) $card->card_number >= 100000
                && (int) $card->card_number <= 100020
                && $card->created_by === $admin->id
                && $card->is_active
        ));
    }

    public function test_bulk_creation_is_rejected_when_range_has_too_few_available_numbers(): void
    {
        $admin = $this->admin();

        foreach (['200000', '200001'] as $number) {
            RaffleCard::create([
                'card_number' => $number,
                'prize_title' => 'محجوز',
                'is_active' => true,
            ]);
        }
        RaffleEntry::create([
            'card_number' => '200002',
            'outcome' => RaffleEntry::OUTCOME_LIVE_DRAW,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('raffle-cards.index'))
            ->post(route('raffle-cards.random-bulk'), [
                'from_number' => '200000',
                'to_number' => '200002',
                'prize_count' => 1,
                'prize_title' => 'هدية جديدة',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('raffle-cards.index'));
        $response->assertSessionHasErrors('prize_count');
        $this->assertDatabaseMissing('raffle_cards', ['prize_title' => 'هدية جديدة']);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Raffle Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret123',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }
}
