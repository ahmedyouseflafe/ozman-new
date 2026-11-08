<?php

namespace App\Services\Social;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Writes a social media caption for a product with Claude. */
class CaptionWriter
{
    private const LANGUAGES = ['ar' => 'Arabic (friendly Levantine/Palestinian dialect)', 'he' => 'Hebrew', 'en' => 'English'];

    public function configured(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function write(Shop $shop, ?Product $product, string $language, ?string $idea = null): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not set.');
        }
        $lang = self::LANGUAGES[$language] ?? self::LANGUAGES['ar'];
        $facts = array_filter([
            'Shop' => $shop->name,
            'Product' => $product?->localized('name'),
            'Description' => $product ? mb_substr((string) $product->localized('description'), 0, 600) : null,
            'Price' => $product ? number_format((float) ($product->discount_price ?: $product->price), 2).' ₪' : null,
            'Owner idea' => $idea ? mb_substr($idea, 0, 500) : null,
        ]);

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(40)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 600,
            'system' => 'You write short, warm social media captions for small local shops (Instagram, Facebook, TikTok). '
                .'Use only the facts given; never invent prices, offers or claims. 2-4 short lines, a few fitting emojis, '
                .'a clear call to order, then 4-8 relevant hashtags on the last line. Do not add any link. Reply with the caption only.',
            'messages' => [[
                'role' => 'user',
                'content' => "Write the caption in {$lang}.\n".collect($facts)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n"),
            ]],
        ]);

        if ($response->failed()) {
            throw new RuntimeException((string) ($response->json('error.message') ?: 'Claude request failed ('.$response->status().')'));
        }
        $text = trim((string) collect($response->json('content', []))->where('type', 'text')->pluck('text')->implode("\n"));
        if ($text === '') {
            throw new RuntimeException('Claude returned an empty caption.');
        }

        return $text;
    }
}
