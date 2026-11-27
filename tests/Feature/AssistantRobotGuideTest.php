<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Assistant\CustomerAssistantTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fulla the robot can point at buttons in the app: only for the robot, only known spots. */
class AssistantRobotGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guide_tool_is_only_offered_to_the_robot(): void
    {
        $tools = app(CustomerAssistantTools::class);

        $this->assertNotContains('guide_to', collect($tools->definitions(null))->pluck('name')->all());
        $this->assertContains('guide_to', collect($tools->definitions(null, true))->pluck('name')->all());
    }

    public function test_steps_keep_known_spots_and_short_lines(): void
    {
        $lina = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $cards = [];

        $result = app(CustomerAssistantTools::class)->run('guide_to', ['steps' => [
            ['spot' => 'nav.cart', 'say' => 'اكبس هون عشان تفتح السلة'],
            ['spot' => 'somewhere.else', 'say' => 'x'],
            ['spot' => 'cart.checkout', 'say' => str_repeat('ا', 200)],
        ]], $lina, $cards);

        $this->assertSame(2, $result['shown']);
        $guide = CustomerAssistantTools::cardsFor($cards)['guide'];
        $this->assertSame(['nav.cart', 'cart.checkout'], array_column($guide, 'spot'));
        $this->assertSame(90, mb_strlen($guide[1]['say']));
    }

    public function test_unknown_spots_only_give_the_model_an_error(): void
    {
        $cards = [];
        $result = app(CustomerAssistantTools::class)->run('guide_to', ['steps' => [['spot' => 'admin.delete', 'say' => 'x']]], null, $cards);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame([], $cards);
        $this->assertSame([], CustomerAssistantTools::cardsFor([])['guide']);
    }
}
