<?php

namespace Tests\Feature;

use App\Models\{FrontOrder, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_answers_from_the_report_without_customer_phones(): void
    {
        config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'claude-test']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => "## الخلاصة\n- **مبيعات** منيحة"]], 'stop_reason' => 'end_turn'])]);
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Bankai', 'slug' => 'bankai', 'catalog_type' => 'restaurant', 'is_active' => true]);
        FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'A-1', 'customer_name' => 'Ali', 'customer_phone' => '0599111222',
            'items' => [], 'subtotal' => 50, 'discount' => 0, 'total' => 50, 'status' => 'new'])->update(['status' => 'completed']);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->postJson('/admin/analytics/assistant', [
            'question' => 'شو الوضع؟', 'history' => [['role' => 'user', 'content' => 'مرحبا'], ['role' => 'assistant', 'content' => 'أهلا']],
        ])->assertOk()->assertJsonPath('answer', "## الخلاصة\n- **مبيعات** منيحة");

        Http::assertSent(function ($request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);
            return $request->hasHeader('x-api-key', 'test-key') && str_contains($body, 'Bankai')
                && ! str_contains($body, '0599111222') && str_contains($body, 'شو الوضع؟') && str_contains($body, 'مرحبا');
        });
    }

    public function test_assistant_is_admin_only_and_reports_missing_key(): void
    {
        config(['services.anthropic.key' => null]);
        $this->postJson('/admin/analytics/assistant')->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'shop_owner', 'is_active' => true]))->postJson('/admin/analytics/assistant')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->postJson('/admin/analytics/assistant', ['history' => [['role' => 'system', 'content' => 'x']]])->assertUnprocessable();
        $this->postJson('/admin/analytics/assistant')->assertStatus(503);
    }
}
