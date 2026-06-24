<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class TranslationController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
            'targets' => ['required', 'array', 'min:1', 'max:2'],
            'targets.*' => ['required', Rule::in(['en', 'he'])],
        ]);

        $translations = [];

        foreach (array_unique($validated['targets']) as $target) {
            $translations[$target] = $this->translateFromArabic($validated['text'], $target);
        }

        return response()->json([
            'translations' => array_filter($translations),
        ]);
    }

    private function translateFromArabic(string $text, string $target): ?string
    {
        $text = trim($text);

        if ($glossaryTranslation = $this->glossaryTranslation($text, $target)) {
            return $glossaryTranslation;
        }

        if ($googleTranslation = $this->googleTranslate($text, $target)) {
            return $this->cleanTranslation($googleTranslation, $text);
        }

        if ($memoryTranslation = $this->myMemoryTranslate($text, $target)) {
            return $this->cleanTranslation($memoryTranslation, $text);
        }

        return null;
    }

    private function glossaryTranslation(string $text, string $target): ?string
    {
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim($text)));

        $phrases = [
            'مشروبات' => ['en' => 'Beverages', 'he' => 'משקאות'],
            'مشروبات غازية' => ['en' => 'Soft drinks', 'he' => 'משקאות מוגזים'],
            'مشروبات طاقة' => ['en' => 'Energy drinks', 'he' => 'משקאות אנרגיה'],
            'مشروبات كولا' => ['en' => 'Cola drinks', 'he' => 'משקאות קולה'],
            'عصائر' => ['en' => 'Juices', 'he' => 'מיצים'],
            'مياه' => ['en' => 'Water', 'he' => 'מים'],
            'ملابس' => ['en' => 'Clothing', 'he' => 'ביגוד'],
            'احذية' => ['en' => 'Shoes', 'he' => 'נעליים'],
            'أحذية' => ['en' => 'Shoes', 'he' => 'נעליים'],
            'مواد غذائية' => ['en' => 'Groceries', 'he' => 'מוצרי מזון'],
            'حلويات' => ['en' => 'Sweets', 'he' => 'ממתקים'],
            'منظفات' => ['en' => 'Cleaning products', 'he' => 'מוצרי ניקוי'],
        ];

        return $phrases[$normalized][$target] ?? null;
    }

    private function googleTranslate(string $text, string $target): ?string
    {
        $googleTarget = $target === 'he' ? 'iw' : $target;

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://translate.googleapis.com/translate_a/single', [
                    'client' => 'gtx',
                    'sl' => 'ar',
                    'tl' => $googleTarget,
                    'dt' => 't',
                    'q' => $text,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $translated = collect($response->json(0))
                ->pluck(0)
                ->filter(fn ($part) => is_string($part) && trim($part) !== '')
                ->implode('');

            return $translated !== '' ? $translated : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function myMemoryTranslate(string $text, string $target): ?string
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://api.mymemory.translated.net/get', [
                    'q' => $text,
                    'langpair' => "ar|{$target}",
                ]);

            if (! $response->ok()) {
                return null;
            }

            $translated = $response->json('responseData.translatedText');

            if (! is_string($translated) || trim($translated) === '') {
                return null;
            }

            return html_entity_decode(trim($translated), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanTranslation(string $translation, string $source): string
    {
        $translation = html_entity_decode(trim($translation), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! preg_match('/[.!?؟،؛]$/u', trim($source))) {
            $translation = preg_replace('/[.!?]+$/u', '', $translation);
        }

        return trim(preg_replace('/\s+/u', ' ', $translation));
    }
}
