<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class TextToSpeechController extends Controller
{
    public function hebrew(Request $request): Response
    {
        return $this->googleTranslateTts($request, 'he');
    }

    public function arabic(Request $request): Response
    {
        return $this->googleTranslateTts($request, 'ar');
    }

    private function googleTranslateTts(Request $request, string $language): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        $response = Http::timeout(12)
            ->withUserAgent('Mozilla/5.0 Ozman')
            ->get('https://translate.google.com/translate_tts', [
                'ie' => 'UTF-8',
                'client' => 'tw-ob',
                'tl' => $language,
                'q' => $validated['text'],
            ]);

        abort_unless($response->ok() && $response->body() !== '', 502);

        return response($response->body(), 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
