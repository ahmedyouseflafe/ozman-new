<?php

namespace Tests\Feature;

use App\Models\{ApiToken, User};
use App\Services\Assistant\Speech;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Storage};
use Tests\TestCase;

class AssistantSpeechTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    public function test_a_reply_is_read_in_the_natural_voice_once_and_then_from_the_kept_file(): void
    {
        Storage::fake('local');
        config(['services.azure_speech.key' => 'test-key', 'services.azure_speech.region' => 'westeurope']);
        Http::fake(['westeurope.tts.speech.microsoft.com/*' => Http::response(str_repeat('M', 400), 200, ['Content-Type' => 'audio/mpeg'])]);
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $h = $this->auth($user);

        $this->post('/api/v1/assistant/speech', ['text' => "**كنافة** بـ 30 ₪ 😋\n- https://x.test/a", 'lang' => 'ar'], $h)
            ->assertOk()->assertHeader('Content-Type', 'audio/mpeg');
        $this->post('/api/v1/assistant/speech', ['text' => "**كنافة** بـ 30 ₪ 😋\n- https://x.test/a", 'lang' => 'ar'], $h)->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Ocp-Apim-Subscription-Key', 'test-key')
            && str_contains($request->body(), 'ar-JO-SanaNeural')
            && str_contains($request->body(), 'كنافة بـ 30 شيكل')
            && ! str_contains($request->body(), 'https') && ! str_contains($request->body(), '**'));
    }

    public function test_without_a_key_or_when_the_service_fails_the_apps_are_told_to_use_the_phone_voice(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->postJson('/api/v1/assistant/speech', ['text' => 'مرحبا'], $this->auth($user))->assertStatus(503);

        config(['services.azure_speech.key' => 'test-key']);
        Http::fake(['*' => Http::response('nope', 401)]);
        $this->postJson('/api/v1/assistant/speech', ['text' => 'مرحبا', 'lang' => 'he'], $this->auth($user))->assertStatus(503);
    }

    public function test_without_a_natural_voice_the_phone_gets_the_reply_with_diacritics_to_read(): void
    {
        config(['services.anthropic.key' => 'test-key', 'services.azure_speech.key' => null]);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push(['content' => [['type' => 'text', 'text' => 'شُو بِدَّك اليُوم؟']], 'stop_reason' => 'end_turn'])
            ->push(['content' => [['type' => 'text', 'text' => 'كَلام ثانِي خالِص']], 'stop_reason' => 'end_turn'])]);
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $h = $this->auth($user);

        $this->postJson('/api/v1/assistant/speech', ['text' => '**شو بدك اليوم؟** 😊', 'lang' => 'ar'], $h)
            ->assertStatus(503)->assertJsonPath('spoken', 'شُو بِدَّك اليُوم؟');
        // Asked again: from the kept copy, no second call.
        $this->postJson('/api/v1/assistant/speech', ['text' => 'شو بدك اليوم؟', 'lang' => 'ar'], $h)->assertJsonPath('spoken', 'شُو بِدَّك اليُوم؟');
        Http::assertSentCount(1);
        // When the writer changes the words, the plain text is read instead.
        $this->postJson('/api/v1/assistant/speech', ['text' => 'وين طلبي؟', 'lang' => 'ar'], $h)->assertJsonPath('spoken', 'وين طلبي؟');
        // Hebrew and English are read as they are.
        $this->postJson('/api/v1/assistant/speech', ['text' => 'שלום', 'lang' => 'he'], $h)->assertJsonPath('spoken', 'שלום');
        Http::assertSentCount(2);
    }

    public function test_the_website_route_needs_a_signed_in_user_and_xml_is_escaped(): void
    {
        $this->postJson('/assistant-speech', ['text' => 'مرحبا'])->assertUnauthorized();
        $this->assertSame('a <b> & c', Speech::plain('a <b> & c'));

        Storage::fake('local');
        config(['services.azure_speech.key' => 'k']);
        Http::fake(['*' => Http::response(str_repeat('M', 400), 200)]);
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $this->actingAs($owner)->post('/assistant-speech', ['text' => 'a <b> & c', 'lang' => 'en'])->assertOk();
        Http::assertSent(fn ($request) => str_contains($request->body(), 'a &lt;b&gt; &amp; c') && str_contains($request->body(), 'en-US-AvaNeural'));
    }
}
