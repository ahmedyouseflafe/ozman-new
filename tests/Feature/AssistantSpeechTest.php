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

    public function test_without_azure_the_gemini_voice_reads_it_with_the_local_accent_as_wav(): void
    {
        Storage::fake('local');
        config(['services.azure_speech.key' => null, 'services.gemini_tts.key' => 'g-key', 'services.gemini_tts.model' => 'gemini-tts-test', 'services.gemini_tts.voice' => 'Aoede']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['inlineData' => [
                'mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => base64_encode(str_repeat("\x01\x00", 600)),
            ]]]]]]])
            ->push(['error' => ['message' => 'quota']], 429)]);
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $audio = $this->post('/api/v1/assistant/speech', ['text' => 'شو بدك اليوم؟', 'lang' => 'ar'], $this->auth($user))
            ->assertOk()->assertHeader('Content-Type', 'audio/wav')->getContent();
        $this->assertSame('RIFF', substr($audio, 0, 4));
        $this->assertSame(24000, unpack('V', substr($audio, 24, 4))[1]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'models/gemini-tts-test:generateContent') && $request->hasHeader('x-goog-api-key', 'g-key')
            && str_contains($request->body(), 'Palestinian') && str_contains($request->body(), 'TRANSCRIPT') && str_contains($request->body(), 'Aoede') && str_contains($request->body(), 'AUDIO'));

        // A refusal (e.g. the free quota is used up) falls back to the phone's voice.
        $this->postJson('/api/v1/assistant/speech', ['text' => 'جملة ثانية', 'lang' => 'ar'], $this->auth($user))->assertStatus(503)->assertJsonPath('spoken', 'جملة ثانية');
    }

    public function test_a_finished_wav_from_gemini_is_not_wrapped_twice(): void
    {
        Storage::fake('local');
        config(['services.azure_speech.key' => null, 'services.gemini_tts.key' => 'g-key']);
        $wav = Speech::wav(str_repeat("\x01\x00", 600), 24000);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['inlineData' => [
            'mimeType' => 'audio/wav', 'data' => base64_encode($wav),
        ]]]]]]])]);
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $audio = $this->post('/api/v1/assistant/speech', ['text' => 'أهلاً فيك', 'lang' => 'ar'], $this->auth($user))->assertOk()->getContent();
        $this->assertSame($wav, $audio);
        $this->assertSame(1, substr_count($audio, 'WAVE'));
    }

    public function test_her_name_is_read_fulla_with_its_vowels(): void
    {
        $this->assertSame('أهلاً! أنا فُلّة، وفُلّة بتساعدك.', Speech::plain('أهلاً! أنا فلة، وفلّة بتساعدك.'));
        $this->assertSame('الفلة وفلتة', Speech::plain('الفلة وفلتة'));
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
