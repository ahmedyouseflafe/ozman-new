<?php

namespace Tests\Feature;

use App\Models\{Category, Product, Shop, User};
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

    public function test_the_robot_opens_known_pages_and_real_products_only(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Dresses', 'slug' => 'dresses', 'catalog_type' => 'clothing', 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-dresses', 'is_active' => true]);
        $dress = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'فستان صيفي', 'slug' => 'dress', 'price' => 129, 'quantity' => 5, 'rating' => 0, 'is_active' => true]);
        $tools = app(CustomerAssistantTools::class);

        $this->assertContains('open_page', collect($tools->definitions(null, true))->pluck('name')->all());
        $this->assertNotContains('open_page', collect($tools->definitions(null))->pluck('name')->all());

        $cards = [];
        $tools->run('open_page', ['page' => 'product', 'id' => $dress->id], null, $cards);
        $this->assertSame(['page' => 'product', 'id' => $dress->id], CustomerAssistantTools::cardsFor($cards)['open']);

        $cards = [];
        $this->assertArrayHasKey('error', $tools->run('open_page', ['page' => 'product', 'id' => 999999], null, $cards));
        $this->assertArrayHasKey('error', $tools->run('open_page', ['page' => 'admin'], null, $cards));
        $this->assertNull(CustomerAssistantTools::cardsFor($cards)['open']);

        $tools->run('open_page', ['page' => 'basket', 'id' => 5], null, $cards);
        $this->assertSame(['page' => 'basket', 'id' => null], CustomerAssistantTools::cardsFor($cards)['open']);
    }

    public function test_for_the_robot_the_basket_is_added_at_once(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Dresses', 'slug' => 'dresses2', 'catalog_type' => 'clothing', 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-dresses2', 'is_active' => true]);
        $dress = Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'فستان', 'slug' => 'dress2', 'price' => 129, 'quantity' => 5, 'rating' => 0, 'is_active' => true]);
        $lina = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $tools = app(CustomerAssistantTools::class);

        $cards = [];
        $chat = $tools->run('propose_basket', ['items' => [['product_id' => $dress->id]]], $lina, $cards);
        $tools->robot = true;
        $robot = $tools->run('propose_basket', ['items' => [['product_id' => $dress->id]]], $lina, $cards);

        $this->assertStringContainsString('button', $chat['note']);
        $this->assertStringContainsString('right away', $robot['note']);
    }
}
