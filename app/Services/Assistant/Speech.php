<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\{Cache, Http, Storage};
use RuntimeException;
use Throwable;

/**
 * The assistants' voice: a natural (Azure neural) reading of a reply, as mp3. The same text is read once and kept,
 * so repeating a reply costs nothing.
 */
class Speech
{
    public const MAX_CHARS = 1500;

    /** Set while Google refuses the free voice (quota used up), so replies don't wait for a refusal every time. */
    public const GEMINI_RESTING = 'gemini-tts-resting';

    /**
     * A natural voice is set up: Google's Gemini voice first (it speaks the Palestinian dialect), Azure while Google
     * rests after its free quota is used up.
     */
    public function ready(): bool
    {
        return $this->provider() !== null;
    }

    public function provider(): ?string
    {
        return match (true) {
            filled(config('services.gemini_tts.key')) && ! Cache::has(self::GEMINI_RESTING) => 'gemini',
            filled(config('services.azure_speech.key')) => 'azure',
            default => null,
        };
    }

    /** What the assistant wrote, as it should sound: no markdown, links or emoji. */
    public static function plain(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/u', '$1', $text);
        $text = preg_replace('/^#{1,3}\s+/mu', '', $text);
        $text = preg_replace('/^\s*(?:[-•*]|\d+[.)])\s+/mu', '', $text);
        $text = preg_replace('~https?://\S+~u', '', $text);
        $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text);
        // Her name is said Fulla (فُلّة), not Falla: written with its vowels so every voice says it right.
        $text = preg_replace('/(?<![\x{0621}-\x{064A}])([وبل]?)فل[\x{064B}-\x{0652}]*ة(?![\x{0621}-\x{064A}])/u', '$1فُلّة', $text);
        // A price reads better as "49 شيكل" than as a symbol.
        $text = str_replace('₪', ' شيكل', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);

        return trim(mb_substr((string) $text, 0, self::MAX_CHARS));
    }

    /**
     * The reply written for the phone's own voice: Arabic gets full diacritics, so the reader says dialect words
     * right. Kept a month. Falls back to the plain text whenever the writing fails or changes the words.
     */
    public function spokenText(string $text, string $lang): string
    {
        $words = self::plain($text);
        if ($lang !== 'ar' || $words === '' || ! filled(config('services.anthropic.key'))) {
            return $words;
        }

        return Cache::remember('assistant-spoken:'.hash('sha256', $words), now()->addDays(30), function () use ($words) {
            try {
                $response = Http::withHeaders([
                    'x-api-key' => config('services.anthropic.key'),
                    'anthropic-version' => '2023-06-01',
                ])->acceptJson()->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model'),
                    'max_tokens' => 2000,
                    'system' => 'You add Arabic diacritics (tashkeel) so a text-to-speech voice pronounces the text the way a '
                        .'Palestinian speaker would say it. The text is in Levantine/Palestinian dialect mixed with standard Arabic. '
                        .'Put full diacritics on every Arabic word as it is pronounced in that dialect. Do not add, remove, '
                        .'reorder or translate any word, and keep numbers, Latin words and punctuation exactly as they are. '
                        .'Reply with the diacritized text only.',
                    'messages' => [['role' => 'user', 'content' => $words]],
                ]);
                $out = trim((string) collect($response->json('content', []))->where('type', 'text')->pluck('text')->implode(''));

                return $response->successful() && self::sameWords($words, $out) ? $out : $words;
            } catch (\Throwable $e) {
                report($e);

                return $words;
            }
        });
    }

    /** The diacritized text must have the same letters as the original (diacritics and spaces aside). */
    private static function sameWords(string $original, string $diacritized): bool
    {
        $bare = fn (string $s) => preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}\s]/u', '', $s);
        if ($diacritized === '') {
            return false;
        }
        similar_text($bare($original), $bare($diacritized), $percent);

        return $percent >= 97;
    }

    /** Path (on the local disk) of the audio for this text (mp3 or wav), made now if it isn't there yet. */
    public function audio(string $text, string $lang): string
    {
        $lang = in_array($lang, ['ar', 'he', 'en'], true) ? $lang : 'ar';
        $words = self::plain($text);
        if ($words === '') {
            throw new RuntimeException('Nothing to read.');
        }
        $provider = $this->provider() ?? throw new RuntimeException('No natural voice is set up.');
        try {
            return $this->audioFrom($provider, $text, $words, $lang);
        } catch (Throwable $e) {
            // Google refused (quota used up, or down): Azure reads it instead of the phone, when it's set up.
            if ($provider !== 'gemini' || ! filled(config('services.azure_speech.key'))) {
                throw $e;
            }
            report($e);

            return $this->audioFrom('azure', $text, $words, $lang);
        }
    }

    private function audioFrom(string $provider, string $text, string $words, string $lang): string
    {
        // Azure's Arabic voices guess the vowels of standard Arabic, so dialect words come out wrong. Given the reply
        // with its diacritics as a Palestinian says it, they read it closer to the local way. (Gemini is told the accent.)
        if ($provider === 'azure' && $lang === 'ar') {
            $words = $this->spokenText($text, 'ar');
        }
        $voice = $provider === 'azure'
            ? (string) config("services.azure_speech.voices.$lang")
            : (string) config('services.gemini_tts.model').'/'.config('services.gemini_tts.voice');
        $path = 'assistant-speech/'.hash('sha256', $provider.'|v3|'.$voice.'|'.$lang.'|'.$words).($provider === 'azure' ? '.mp3' : '.wav');
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            $disk->put($path, $provider === 'azure' ? $this->azure($words, $voice) : $this->gemini($words, $lang));
        }

        return $path;
    }

    private function azure(string $words, string $voice): string
    {
        $locale = substr($voice, 0, 5);
        $ssml = '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="'.$locale.'">'
            .'<voice name="'.htmlspecialchars($voice, ENT_XML1 | ENT_QUOTES).'">'
            .htmlspecialchars($words, ENT_XML1 | ENT_QUOTES)
            .'</voice></speak>';
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => (string) config('services.azure_speech.key'),
            'X-Microsoft-OutputFormat' => 'audio-24khz-48kbitrate-mono-mp3',
            'User-Agent' => 'Ozman',
        ])->withBody($ssml, 'application/ssml+xml')->timeout(20)
            ->post('https://'.config('services.azure_speech.region').'.tts.speech.microsoft.com/cognitiveservices/v1');
        if (! $response->successful() || strlen($response->body()) < 100) {
            throw new RuntimeException('Speech service answered '.$response->status());
        }

        return $response->body();
    }

    /** Google's Gemini voice: told how to sound (a warm young woman, the local accent), then the words. Returns WAV. */
    private function gemini(string $words, string $lang): string
    {
        // Google's layout for voice directions: notes for the "director", then only the transcript is spoken.
        // (A plain "Read this in a warm voice:" line was sometimes read out loud as part of the answer.)
        $accent = match ($lang) {
            'he' => 'Native Israeli Hebrew.',
            'en' => 'Clear, friendly English.',
            default => 'Palestinian Levantine Arabic (the way people in Palestine speak), not formal Arabic.',
        };
        $style = "# AUDIO PROFILE: Fulla, a young woman who helps shoppers on a marketplace app.\n"
            ."### DIRECTOR'S NOTES\nStyle: warm, friendly, lively and natural, smiling while she talks.\nAccent: {$accent}\n"
            ."Read ONLY the transcript below. Never say these notes out loud.\n#### TRANSCRIPT\n";
        $response = Http::withHeaders(['x-goog-api-key' => (string) config('services.gemini_tts.key')])->acceptJson()->timeout(30)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('services.gemini_tts.model').':generateContent', [
                'contents' => [['parts' => [['text' => $style.$words]]]],
                'generationConfig' => [
                    'responseModalities' => ['AUDIO'],
                    'speechConfig' => ['voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => (string) config('services.gemini_tts.voice')]]],
                ],
            ]);
        $pcm = base64_decode((string) $response->json('candidates.0.content.parts.0.inlineData.data'), true);
        if ($response->status() === 429) {
            self::restGemini((array) $response->json('error.details', []));
        }
        if (! $response->successful() || ! $pcm || strlen($pcm) < 200) {
            throw new RuntimeException('Gemini voice answered '.$response->status().': '.mb_substr((string) $response->json('error.message'), 0, 400));
        }
        // Newer models already send a finished WAV file; older ones send raw PCM that needs the header.
        if (str_starts_with($pcm, 'RIFF') && substr($pcm, 8, 4) === 'WAVE') {
            return $pcm;
        }
        $rate = 24000;
        if (preg_match('/rate=(\d+)/', (string) $response->json('candidates.0.content.parts.0.inlineData.mimeType'), $m)) {
            $rate = (int) $m[1];
        }

        return self::wav($pcm, $rate);
    }

    /**
     * Google said "quota used up": a daily quota comes back at midnight Pacific time (Google's reset), a per-minute
     * one after the delay Google gives (a minute if it gives none).
     */
    private static function restGemini(array $details): void
    {
        $daily = collect($details)->pluck('violations')->flatten(1)->contains(fn ($v) => str_contains((string) ($v['quotaId'] ?? ''), 'PerDay'));
        $delay = (int) collect($details)->pluck('retryDelay')->filter()->map(fn ($d) => (int) $d)->first();
        $until = $daily ? now('America/Los_Angeles')->addDay()->startOfDay() : now()->addSeconds(max(30, $delay ?: 60));
        Cache::put(self::GEMINI_RESTING, true, $until);
    }

    /** 16-bit mono PCM wrapped in a WAV header, so phones and browsers can play it. */
    public static function wav(string $pcm, int $rate): string
    {
        return 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
            .'data'.pack('V', strlen($pcm)).$pcm;
    }
}
