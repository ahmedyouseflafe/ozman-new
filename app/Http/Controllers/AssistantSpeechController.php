<?php

namespace App\Http\Controllers;

use App\Services\Assistant\Speech;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Reads an assistant reply aloud in a natural voice (website and app). 503 means "use the device's own voice". */
class AssistantSpeechController extends Controller
{
    public function __invoke(Request $request, Speech $speech): Response
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:4000'],
            'lang' => ['nullable', 'string', 'in:ar,he,en'],
        ]);
        if (! $speech->ready()) {
            return response()->json(['message' => 'Natural voice is not set up.'], 503);
        }
        try {
            $path = $speech->audio($data['text'], $data['lang'] ?? app()->getLocale());
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Natural voice is not available right now.'], 503);
        }

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
