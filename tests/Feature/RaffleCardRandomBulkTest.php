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

    public function test_admin_can_download_winning_cards_pdf_for_selected_range(): void
    {
        $admin = $this->admin();

        foreach (['010000', '010005', '020000'] as $number) {
            RaffleCard::create([
                'card_number' => $number,
                'prize_title' => "هدية {$number}",
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('raffle-cards.export-pdf', [
            'from_number' => '010000',
            'to_number' => '010999',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('winning-cards-010000-010999.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pdf_export_rejects_reversed_range(): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('raffle-cards.index'))
            ->get(route('raffle-cards.export-pdf', [
                'from_number' => '900000',
                'to_number' => '100000',
            ]));

        $response->assertRedirect(route('raffle-cards.index'));
        $response->assertSessionHasErrors('to_number');
    }

    public function test_printable_cards_use_one_physical_page_per_sheet_without_trailing_break(): void
    {
        $response = $this->actingAs($this->admin())->post(route('raffle-cards.printable'), [
            'from_number' => '198501',
            'to_number' => '198509',
            'cards_per_page' => 8,
            'brand_text' => 'Ozman',
        ]);

        $response->assertOk();
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'class="sheet cards-8"'));
        $this->assertStringContainsString('height: 296mm;', $html);
        $this->assertStringContainsString('.sheet:last-child', $html);
        $this->assertStringContainsString('break-after: auto;', $html);
    }

    public function test_printable_page_supports_twenty_four_cards_on_a4(): void
    {
        $response = $this->actingAs($this->admin())->post(route('raffle-cards.printable'), [
            'from_number' => '300001',
            'to_number' => '300024',
            'cards_per_page' => 24,
            'brand_text' => 'Ozman',
        ]);

        $response->assertOk();
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'class="sheet cards-24"'));
        $this->assertSame(24, substr_count($html, 'class="ticket"'));
        $this->assertStringContainsString('grid-template-columns: repeat(4, 1fr);', $html);
        $this->assertStringContainsString('grid-template-rows: repeat(6, 1fr);', $html);
    }

    public function test_admin_can_bulk_delete_selected_winning_cards_only(): void
    {
        $admin = $this->admin();
        $selectedCards = collect(['310001', '310002'])->map(fn (string $number) => RaffleCard::create([
            'card_number' => $number,
            'prize_title' => 'هدية للحذف',
            'is_active' => true,
            'created_by' => $admin->id,
        ]));
        $remainingCard = RaffleCard::create([
            'card_number' => '310003',
            'prize_title' => 'هدية باقية',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('raffle-cards.bulk-destroy'), [
            'cards' => $selectedCards->pluck('id')->all(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        foreach ($selectedCards as $card) {
            $this->assertDatabaseMissing('raffle_cards', ['id' => $card->id]);
        }
        $this->assertDatabaseHas('raffle_cards', ['id' => $remainingCard->id]);
    }

    public function test_bulk_delete_requires_at_least_one_winning_card(): void
    {
        $response = $this->actingAs($this->admin())
            ->from(route('raffle-cards.index'))
            ->delete(route('raffle-cards.bulk-destroy'), ['cards' => []]);

        $response->assertRedirect(route('raffle-cards.index'));
        $response->assertSessionHasErrors('cards');
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
