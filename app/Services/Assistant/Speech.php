<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\{Cache, Http, Storage};
use RuntimeException;

/**
 * The assistants' voice: a natural (Azure neural) reading of a reply, as mp3. The same text is read once and kept,
 * so repeating a reply costs nothing.
 */
class Speech
{
    public const MAX_CHARS = 1500;

    public function ready(): bool
    {
        return filled(config('services.azure_speech.key'));
    }

    /** What the assistant wrote, as it should sound: no markdown, links or emoji. */
    public static function plain(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/u', '$1', $text);
        $text = preg_replace('/^#{1,3}\s+/mu', '', $text);
        $text = preg_replace('/^\s*(?:[-•*]|\d+[.)])\s+/mu', '', $text);
        $text = preg_replace('~https?://\S+~u', '', $text);
        $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text);
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

    /** Path (on the local disk) of the mp3 for this text, made now if it isn't there yet. */
    public function audio(string $text, string $lang): string
    {
        $lang = in_array($lang, ['ar', 'he', 'en'], true) ? $lang : 'ar';
        $voice = (string) config("services.azure_speech.voices.$lang");
        $words = self::plain($text);
        if ($words === '') {
            throw new RuntimeException('Nothing to read.');
        }
        $path = 'assistant-speech/'.hash('sha256', $voice.'|'.$words).'.mp3';
        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            return $path;
        }

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
        $disk->put($path, $response->body());

        return $path;
    }
}
