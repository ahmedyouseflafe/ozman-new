<?php

namespace Tests\Feature;

use App\Models\{ApiToken, AssistantConversation, Category, FrontOrder, Product, Shop, User};
use App\Services\Assistant\CustomerAssistantTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The customers' shopping assistant: public catalogue search, the customer's own orders only, cards, limits. */
class CustomerAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.anthropic.key' => 'test-key', 'services.anthropic.customer_assistant.enabled' => true,
            'services.anthropic.customer_assistant.daily_limit' => 40, 'services.anthropic.customer_assistant.site_daily_limit' => 3000]);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function shop(string $type, array $extra = []): Shop
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        return Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true, ...$extra]);
    }

    private function product(Shop $shop, string $name, float $price, array $extra = []): Product
    {
        $category = Category::firstOrCreate(['shop_id' => $shop->id, 'slug' => 'c-'.$shop->id], ['name' => 'Main', 'is_active' => true]);

        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => $name, 'slug' => 'p-'.uniqid(), 'price' => $price, 'is_active' => true, ...$extra]);
    }

    private function toolUse(string $name, array $input, string $id = 'tu_1'): array
    {
        return ['content' => [['type' => 'tool_use', 'id' => $id, 'name' => $name, 'input' => $input]], 'stop_reason' => 'tool_use', 'usage' => ['input_tokens' => 50, 'output_tokens' => 10]];
    }

    private function text(string $text): array
    {
        return ['content' => [['type' => 'text', 'text' => $text]], 'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 80, 'output_tokens' => 30]];
    }

    private function tools(?User $user, string $name, array $input = [], ?array &$cards = null): array
    {
        $cards ??= [];

        return app(CustomerAssistantTools::class)->run($name, $input, $user, $cards);
    }

    public function test_the_customer_gets_an_answer_with_product_cards(): void
    {
        $restaurant = $this->shop('restaurant', ['open_time' => '00:00', 'close_time' => '23:59']);
        $burger = $this->product($restaurant, 'برغر لحم', 35);
        $this->product($restaurant, 'سلطة', 15);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->toolUse('search_products', ['query' => 'برغر', 'max_price' => 40]))
            ->push($this->toolUse('show_products', ['product_ids' => [$burger->id, 999999]], 'tu_2'))
            ->push($this->text('لقيتلك **برغر لحم** بـ35 ₪.'))]);
        $h = $this->auth($customer);

        $this->getJson('/api/v1/assistant', $h)->assertOk()->assertJsonPath('messages', [])->assertJsonPath('usage.used', 0)->assertJsonPath('enabled', true);
        $reply = $this->postJson('/api/v1/assistant', ['message' => 'بدي برغر تحت 40'], $h)->assertOk()
            ->assertJsonPath('message.products.0.id', $burger->id)->assertJsonPath('message.products.0.shop.name', $restaurant->name)
            ->assertJsonCount(1, 'message.products')->assertJsonPath('usage.used', 1)->json();

        $search = json_encode(Http::recorded()[1][0]->data()['messages'][2]['content'][0], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('برغر لحم', $search);
        $this->assertStringNotContainsString('سلطة', $search);
        $this->assertSame([], collect(Http::recorded()[0][0]->data()['tools'])->pluck('name')->intersect(['propose_product_change'])->all());

        // History comes back with the cards.
        $this->getJson('/api/v1/assistant', $h)->assertJsonCount(2, 'messages')->assertJsonPath('messages.1.products.0.id', $burger->id)
            ->assertJsonPath('conversation_id', $reply['conversation_id']);
    }

    public function test_search_covers_only_active_products_in_active_shops_and_respects_filters(): void
    {
        $open = $this->shop('clothing');
        $closed = $this->shop('clothing', ['is_active' => false]);
        $dress = $this->product($open, 'فستان صيفي', 159, ['discount_price' => 129]);
        $this->product($open, 'فستان سهرة', 400);
        $this->product($open, 'فستان مخفي', 50, ['is_active' => false]);
        $this->product($closed, 'فستان محل مسكر', 60);

        $result = $this->tools(null, 'search_products', ['query' => 'فستان', 'max_price' => 150]);
        $this->assertSame([$dress->id], array_column($result['products'], 'id'));
        $this->assertSame(129.0, $result['products'][0]['discount_price']);
        $this->assertSame(1, $this->tools(null, 'search_products', ['query' => 'فستان', 'on_offer' => true])['count']);
        $this->assertSame(2, $this->tools(null, 'search_products', ['shop_type' => 'clothing'])['count']);
        $this->assertSame(0, $this->tools(null, 'search_products', ['query' => 'xyz'])['count']);
        $this->assertArrayHasKey('error', $this->tools(null, 'product_details', ['product_id' => 999999]));
        $this->assertSame('فستان صيفي', $this->tools(null, 'product_details', ['product_id' => $dress->id])['name']);
        $this->assertSame([$open->id], array_column($this->tools(null, 'search_shops', ['shop_type' => 'clothing'])['shops'], 'id'));
    }

    public function test_the_customer_sees_only_their_own_orders_and_must_be_signed_in(): void
    {
        $shop = $this->shop('sweets', ['whatsapp' => '+972 599-111-222']);
        $me = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $me->id, 'order_number' => 'SW-MINE', 'customer_name' => 'Me', 'total' => 40, 'status' => 'preparing', 'items' => [['name' => 'كنافة', 'qty' => 2]]]);
        FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $other->id, 'order_number' => 'SW-THEIRS', 'customer_name' => 'Other', 'customer_phone' => '0599999999', 'total' => 90, 'status' => 'new', 'items' => []]);

        $orders = $this->tools($me, 'my_orders');
        $this->assertSame(['SW-MINE'], array_column($orders['orders'], 'number'));
        $this->assertSame('972599111222', $orders['orders'][0]['shop_whatsapp']);
        $this->assertStringNotContainsString('0599999999', json_encode($orders));
        $this->assertArrayHasKey('error', $this->tools(null, 'my_orders'));
        $this->assertArrayHasKey('error', $this->tools(null, 'my_points'));
    }

    public function test_opening_from_a_shop_page_tells_the_assistant_which_shop(): void
    {
        $shop = $this->shop('cosmetics');
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        Http::fake(['api.anthropic.com/*' => Http::response($this->text('المحل مفتوح.'))]);

        $this->getJson('/api/v1/assistant?shop_id='.$shop->id, $this->auth($customer))->assertJsonPath('suggestions.0', 'شو بتنصحني من هالمحل؟');
        $this->postJson('/api/v1/assistant', ['message' => 'مفتوحين؟', 'shop_id' => $shop->id], $this->auth($customer))->assertOk();
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->data()['system'], "\"{$shop->name}\" (id {$shop->id}"));
        $this->assertSame($shop->id, AssistantConversation::where('kind', 'customer')->first()->shop_id);
    }

    public function test_limits_switch_and_access(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $h = $this->auth($customer);
        Http::fake(['api.anthropic.com/*' => Http::sequence()->push($this->text('أهلا'))->push(['error' => ['message' => 'overloaded']], 529)]);

        $this->getJson('/api/v1/assistant')->assertUnauthorized();
        $this->postJson('/api/v1/assistant', [], $h)->assertStatus(422);

        config(['services.anthropic.customer_assistant.daily_limit' => 1]);
        $this->postJson('/api/v1/assistant', ['message' => 'هلا'], $h)->assertOk();
        $this->postJson('/api/v1/assistant', ['message' => 'هلا'], $h)->assertStatus(429);

        config(['services.anthropic.customer_assistant.daily_limit' => 40, 'services.anthropic.customer_assistant.site_daily_limit' => 1]);
        $this->postJson('/api/v1/assistant', ['message' => 'هلا'], $h)->assertStatus(429);

        config(['services.anthropic.customer_assistant.site_daily_limit' => 3000]);
        $this->postJson('/api/v1/assistant', ['message' => 'again'], $h)->assertStatus(502);
        $this->assertDatabaseMissing('assistant_messages', ['content' => 'again']);

        config(['services.anthropic.customer_assistant.enabled' => false]);
        $this->postJson('/api/v1/assistant', ['message' => 'هلا'], $h)->assertStatus(503);

        $fresh = $this->postJson('/api/v1/assistant/reset', [], $h)->assertOk()->json('conversation_id');
        $this->getJson('/api/v1/assistant', $h)->assertJsonPath('conversation_id', $fresh)->assertJsonPath('messages', []);

        // A customer's conversation never shows up for a shop owner's assistant, and the other way round.
        $this->assertSame(0, AssistantConversation::where('kind', 'shop_owner')->count());
    }

    public function test_the_website_chat_endpoints_need_a_signed_in_user(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        Http::fake(['api.anthropic.com/*' => Http::response($this->text('أهلاً!'))]);

        $this->getJson('/assistant/state')->assertUnauthorized();
        $this->actingAs($customer)->getJson('/assistant/state')->assertOk()->assertJsonPath('enabled', true);
        $this->actingAs($customer)->postJson('/assistant/messages', ['message' => 'هلا'])->assertOk()->assertJsonPath('message.content', 'أهلاً!');
    }

    public function test_storefronts_show_the_assistant_button_and_guests_are_asked_to_sign_in(): void
    {
        $shop = $this->shop('sweets');
        $this->product($shop, 'كنافة', 30);

        $this->get(route('sweets.store', $shop))->assertOk()->assertSee('id="ozSaFab"', false)->assertSee('const shopId = '.$shop->id.';', false)
            ->assertSee(json_encode(route('customer-assistant.state')), false);
        config(['services.anthropic.customer_assistant.enabled' => false]);
        $this->get(route('sweets.store', $shop))->assertOk()->assertDontSee('id="ozSaFab"', false);
    }

    public function test_a_reply_is_written_even_when_the_model_stops_after_showing_cards(): void
    {
        $shop = $this->shop('sweets');
        $kunafa = $this->product($shop, 'كنافة', 30);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->toolUse('show_products', ['product_ids' => [$kunafa->id]]))
            ->push(['content' => [], 'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 10, 'output_tokens' => 1]])
            ->push($this->text('لقيتلك كنافة بـ30 ₪ 👇'))]);

        $this->postJson('/api/v1/assistant', ['message' => 'حلويات'], $this->auth($customer))->assertOk()
            ->assertJsonPath('message.content', 'لقيتلك كنافة بـ30 ₪ 👇')->assertJsonPath('message.products.0.id', $kunafa->id);
        $retry = Http::recorded()[2][0]->data();
        $this->assertSame(['type' => 'none'], $retry['tool_choice']);
        $this->assertSame('text', end($retry['messages'])['content'][1]['type']);
    }
}
