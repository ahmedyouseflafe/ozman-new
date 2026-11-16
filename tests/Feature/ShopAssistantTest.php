<?php

namespace Tests\Feature;

use App\Models\{ApiToken, AssistantAction, Category, FrontOrder, Product, Shop, User};
use App\Services\Assistant\ShopAssistantTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The shop owner's AI assistant: tools limited to the shop, proposals that run only after confirming, limits and access. */
class ShopAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.anthropic.key' => 'test-key', 'services.anthropic.shop_assistant.enabled' => true, 'services.anthropic.shop_assistant.monthly_limit' => 300]);
    }

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    private function shop(string $type = 'sweets', ?User $owner = null): Shop
    {
        $owner ??= User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);

        return Shop::create(['user_id' => $owner->id, 'name' => 'Demo '.$type, 'slug' => $type.'-'.uniqid(), 'catalog_type' => $type, 'is_active' => true]);
    }

    private function product(Shop $shop, array $extra = []): Product
    {
        $category = Category::firstOrCreate(['shop_id' => $shop->id, 'slug' => 'main-'.$shop->id], ['name' => 'Main', 'is_active' => true]);

        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'كنافة', 'slug' => 'k-'.uniqid(), 'price' => 30, 'is_active' => true, ...$extra]);
    }

    private function toolUse(string $name, array $input, string $id = 'tu_1'): array
    {
        return ['content' => [['type' => 'tool_use', 'id' => $id, 'name' => $name, 'input' => $input]], 'stop_reason' => 'tool_use', 'usage' => ['input_tokens' => 100, 'output_tokens' => 20]];
    }

    private function text(string $text): array
    {
        return ['content' => [['type' => 'text', 'text' => $text]], 'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 150, 'output_tokens' => 40]];
    }

    public function test_the_owner_asks_and_the_assistant_answers_from_the_shop_tools(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner);
        $this->product($shop);
        $other = $this->shop('sweets');
        $this->product($other, ['name' => 'سر المحل الثاني']);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->toolUse('search_products', []))
            ->push($this->text('عندك **1** منتج: كنافة بـ30 ₪.'))
            ->push($this->text('تمام'))]);
        $h = $this->auth($owner);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/assistant", $h)->assertOk()->assertJsonPath('messages', [])->assertJsonPath('usage.used', 0);
        $reply = $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'شو عندي منتجات؟'], $h)->assertOk()
            ->assertJsonPath('message.role', 'assistant')->assertJsonPath('usage.used', 1)->json();
        $this->assertStringContainsString('كنافة', $reply['message']['content']);

        $requests = Http::recorded();
        $this->assertCount(2, $requests);
        $first = $requests[0][0]->data();
        $this->assertSame('test-key', $requests[0][0]->header('x-api-key')[0]);
        $this->assertContains('propose_product_change', collect($first['tools'])->pluck('name'));
        $this->assertStringContainsString($shop->name, $first['system']);
        // The tool result went back to Claude and holds this shop's product only.
        $toolResult = json_encode($requests[1][0]->data()['messages'][2]['content'][0], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('كنافة', $toolResult);
        $this->assertStringNotContainsString('سر المحل الثاني', $toolResult);

        $this->getJson("/api/v1/merchant/shops/{$shop->id}/assistant", $h)->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.role', 'user')->assertJsonPath('conversation_id', $reply['conversation_id']);
        $this->assertDatabaseHas('assistant_messages', ['role' => 'assistant', 'input_tokens' => 250, 'output_tokens' => 60]);

        // The next question carries the earlier turns.
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'وكمان؟', 'conversation_id' => $reply['conversation_id']], $h)->assertOk();
        Http::assertSent(fn (HttpRequest $r) => count($r->data()['messages']) === 3 && $r->data()['messages'][0]['content'] === 'شو عندي منتجات؟');
    }

    public function test_a_proposed_price_change_runs_only_after_the_owner_confirms(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner);
        $product = $this->product($shop);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->toolUse('propose_product_change', ['product_id' => $product->id, 'price' => 35, 'discount_price' => 32]))
            ->push($this->text('جهّزتلك تغيير السعر، اكبس أكّد.'))]);
        $h = $this->auth($owner);

        $message = $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'غيّر سعر الكنافة لـ35 وخصم 32'], $h)->assertOk()
            ->assertJsonPath('message.actions.0.status', 'pending')->json('message');
        $this->assertSame('30.00', $product->fresh()->price);
        $action = $message['actions'][0];
        $this->assertStringContainsString('35', $action['summary']);

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->shop('sweets', $stranger);
        $this->postJson("/api/v1/merchant/assistant-actions/{$action['id']}/confirm", [], $this->auth($stranger))->assertNotFound();

        $this->postJson("/api/v1/merchant/assistant-actions/{$action['id']}/confirm", [], $h)->assertOk()->assertJsonPath('action.status', 'done');
        $this->assertSame('35.00', $product->fresh()->price);
        $this->assertSame('32.00', $product->fresh()->discount_price);
        $this->postJson("/api/v1/merchant/assistant-actions/{$action['id']}/confirm", [], $h)->assertStatus(422);

        // A proposal can be cancelled, and an old one expires instead of acting on stale numbers.
        $cancel = AssistantAction::create(['assistant_conversation_id' => AssistantAction::find($action['id'])->assistant_conversation_id, 'shop_id' => $shop->id,
            'user_id' => $owner->id, 'type' => 'accepting_orders', 'payload' => ['open' => false], 'summary' => 'x']);
        $this->postJson("/api/v1/merchant/assistant-actions/{$cancel->id}/cancel", [], $h)->assertOk()->assertJsonPath('action.status', 'cancelled');
        $this->postJson("/api/v1/merchant/assistant-actions/{$cancel->id}/confirm", [], $h)->assertStatus(422);
        $old = $cancel->replicate()->fill(['status' => 'pending']);
        $old->save();
        $old->forceFill(['created_at' => now()->subHours(2)])->save();
        $this->postJson("/api/v1/merchant/assistant-actions/{$old->id}/confirm", [], $h)->assertStatus(422)->assertJsonPath('action.status', 'expired');
        $this->assertTrue((bool) ($shop->fresh()->is_accepting_orders ?? true));
    }

    public function test_bad_proposals_go_back_to_the_model_as_errors(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner);
        $theirs = $this->product($this->shop('sweets'));
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->toolUse('propose_product_change', ['product_id' => $theirs->id, 'price' => 1]))
            ->push($this->text('ما لقيت المنتج.'))]);

        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'غيّر السعر'], $this->auth($owner))->assertOk()
            ->assertJsonPath('message.actions', []);
        $this->assertSame(0, AssistantAction::count());
        Http::assertSent(fn (HttpRequest $r) => str_contains(json_encode($r->data()['messages']), 'is_error') && ($r->data()['messages'][2]['content'][0]['is_error'] ?? false) === true);
    }

    public function test_an_order_status_change_uses_the_dashboard_rules(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner);
        $order = FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'SW-1001', 'customer_name' => 'لينا', 'customer_phone' => '0599111222',
            'total' => 60, 'items' => [['name' => 'كنافة', 'qty' => 2, 'price' => 30]], 'status' => 'new']);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->toolUse('list_orders', ['status' => 'new']))
            ->push($this->toolUse('propose_order_status', ['order_id' => $order->id, 'status' => 'ready'], 'tu_2'))
            ->push($this->text('جهّزت تغيير حالة طلب لينا لجاهز.'))]);
        $h = $this->auth($owner);

        $action = $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'طلب لينا جاهز'], $h)->assertOk()->json('message.actions.0');
        $orders = json_encode(Http::recorded()[1][0]->data()['messages'][2]['content'], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('لينا', $orders);
        $this->assertStringNotContainsString('0599111222', $orders);
        $this->assertSame('new', $order->fresh()->status);

        $this->postJson("/api/v1/merchant/assistant-actions/{$action['id']}/confirm", [], $h)->assertOk();
        $this->assertSame('ready', $order->fresh()->status);
    }

    public function test_limits_access_and_switch(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('restaurant', $owner);
        $h = $this->auth($owner);
        Http::fake(['api.anthropic.com/*' => Http::sequence()->push($this->text('أهلا'))->push(['error' => ['message' => 'overloaded']], 529)]);

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/assistant", $this->auth($stranger))->assertForbidden();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'hi'], $this->auth($customer))->assertForbidden();
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", [], $h)->assertStatus(422);

        config(['services.anthropic.shop_assistant.monthly_limit' => 1]);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'hi'], $h)->assertOk();
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'hi'], $h)->assertStatus(429);

        config(['services.anthropic.shop_assistant.enabled' => false]);
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/assistant", $h)->assertJsonPath('enabled', false);
        config(['services.anthropic.shop_assistant.monthly_limit' => 300]);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'hi'], $h)->assertStatus(503);

        // A failing API call stores nothing, so the conversation stays consistent.
        config(['services.anthropic.shop_assistant.enabled' => true]);
        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'hi again'], $h)->assertStatus(502);
        $this->assertDatabaseMissing('assistant_messages', ['content' => 'hi again']);

        $fresh = $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant/reset", [], $h)->assertOk()->json('conversation_id');
        $this->getJson("/api/v1/merchant/shops/{$shop->id}/assistant", $h)->assertJsonPath('conversation_id', $fresh)->assertJsonPath('messages', []);
    }

    public function test_the_dashboard_page_and_its_endpoints(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('cosmetics', $owner);
        $this->actingAs($owner)->get("/shops/{$shop->id}/assistant")->assertOk()->assertSee('المساعد الذكي');
        Http::fake(['api.anthropic.com/*' => Http::response($this->text('مواعيدك اليوم فاضية.'))]);
        $this->actingAs($owner)->postJson("/shops/{$shop->id}/assistant/messages", ['message' => 'شو مواعيد اليوم؟'])->assertOk()
            ->assertJsonPath('message.content', 'مواعيدك اليوم فاضية.');
        Http::assertSent(fn (HttpRequest $r) => collect($r->data()['tools'])->pluck('name')->contains('salon_appointments'));

        $otherShop = $this->shop('sweets');
        $this->actingAs($owner)->get("/shops/{$otherShop->id}/assistant")->assertForbidden();
    }

    public function test_customer_tools_never_return_phone_numbers(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner);
        foreach ([['لينا', '0599111222', 40], ['لينا', '0599111222', 60], ['سامي', '0599333444', 20]] as $i => [$name, $phone, $total]) {
            FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'SW-'.$i, 'customer_name' => $name, 'customer_phone' => $phone,
                'total' => $total, 'items' => [], 'status' => 'completed', 'sale_confirmed_at' => now()]);
        }
        $proposed = [];
        $conversation = \App\Models\AssistantConversation::create(['shop_id' => $shop->id, 'user_id' => $owner->id]);
        $result = app(ShopAssistantTools::class)->run('customers', ['sort' => 'top'], $shop, $owner, $conversation, $proposed);

        $this->assertSame(['name' => 'لينا', 'orders' => 2, 'spent' => 100.0], array_intersect_key($result['customers'][0], array_flip(['name', 'orders', 'spent'])));
        $this->assertStringNotContainsString('0599', json_encode($result));
    }

    public function test_every_read_tool_runs(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('cosmetics', $owner);
        $sold = $this->product($shop, ['name' => 'أحمر شفايف']);
        $this->product($shop, ['name' => 'كريم', 'views' => 9]);
        FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'C-1', 'customer_name' => 'هبة', 'total' => 60, 'status' => 'completed',
            'items' => [['product_id' => $sold->id, 'name' => 'أحمر شفايف', 'qty' => 2, 'price' => 30]], 'sale_confirmed_at' => now()]);
        \App\Models\SalonAppointment::create(['shop_id' => $shop->id, 'service' => 'مكياج', 'appointment_at' => now()->addHour(), 'slot_key' => 'k1',
            'customer_name' => 'رنا', 'customer_phone' => '0599000000', 'status' => 'new']);
        $tools = app(ShopAssistantTools::class);
        $conversation = \App\Models\AssistantConversation::create(['shop_id' => $shop->id, 'user_id' => $owner->id]);
        $p = [];
        $run = fn ($name, $input = []) => $tools->run($name, $input, $shop, $owner, $conversation, $p);

        $this->assertSame(2, $run('shop_info')['products']);
        $this->assertArrayNotHasKey('error', $run('sales_report', ['from' => now()->subDays(7)->toDateString()]));
        $this->assertSame('أحمر شفايف', $run('product_sales')['products'][0]['name']);
        $this->assertSame('كريم', $run('product_sales', ['order' => 'worst'])['products'][0]['name']);
        $this->assertSame(2, $run('list_categories')['categories'][0]['products']);
        $this->assertSame('رنا', $run('salon_appointments')['appointments'][0]['customer']);
        $this->assertStringNotContainsString('0599', json_encode($run('salon_appointments')));
        $this->assertSame(1, $run('search_products', ['query' => 'كريم'])['count']);
        $this->assertSame(1, $run('list_orders', ['search' => 'هبة'])['count']);
        $this->assertArrayHasKey('error', $run('sales_report', ['from' => 'yesterday']));
        $this->assertArrayHasKey('error', $run('propose_accepting_orders', ['open' => true]));
    }

    public function test_a_tool_called_without_arguments_goes_back_as_an_object(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = $this->shop('sweets', $owner);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push($this->toolUse('sales_report', []))
            ->push($this->text('ما في مبيعات هالشهر.'))]);

        $this->postJson("/api/v1/merchant/shops/{$shop->id}/assistant", ['message' => 'كم بعت هالشهر؟'], $this->auth($owner))->assertOk();
        $second = Http::recorded()[1][0];
        $this->assertStringContainsString('"input":{}', $second->body());
        $this->assertStringNotContainsString('"input":[]', $second->body());
    }
}
