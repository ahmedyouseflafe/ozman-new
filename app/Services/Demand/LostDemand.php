<?php

namespace App\Services\Demand;

use App\Models\{Product, SearchMiss, Shop};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * "Lost demand": what customers searched for and didn't find, so shops know what to add.
 *
 * Every search that comes back empty (in the app, on the website, or when Fulla finds nothing) is kept
 * anonymously: the words, where they were typed (a shop or everywhere) and the kind of shop they belong to.
 * Spellings are folded together, so "فطائر جبنة" and "فطاير جبنه" count as one thing.
 */
class LostDemand
{
    public const SOURCES = ['app', 'app_shop', 'web', 'assistant'];

    /** Folds the ways the same words get typed: diacritics, alef/ya/ta marbuta forms, punctuation, "ال". */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي']);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = array_filter(preg_split('/\s+/u', $text) ?: [], fn ($word) => $word !== '');
        $words = array_map(fn ($word) => mb_strlen($word) >= 5 && str_starts_with($word, 'ال') ? mb_substr($word, 2) : $word, $words);

        return implode(' ', $words);
    }

    /** Keeps one empty search. Junk, very long phrases and the same search repeated by the same visitor are skipped. */
    public function record(string $typed, string $source, ?Shop $shop = null, ?string $catalogType = null): ?SearchMiss
    {
        $term = self::normalize($typed);
        $shown = trim(preg_replace('/\s+/u', ' ', $typed));
        if (! in_array($source, self::SOURCES, true) || mb_strlen($term) < 2 || mb_strlen($shown) > 60
            || substr_count($term, ' ') > 5 || ! preg_match('/\p{L}/u', $term)) {
            return null;
        }
        // Typing "ب", "بر", "برغ"… or searching the same thing twice shouldn't count twice. The visitor is only
        // part of this short-lived key; nothing about them is saved.
        $visitor = request()?->ip().'|'.request()?->userAgent();
        if (! Cache::add('search-miss:'.sha1($term.'|'.$source.'|'.($shop?->id).'|'.$visitor), 1, now()->addMinutes(30))) {
            return null;
        }

        return SearchMiss::create([
            'term' => mb_substr($term, 0, 100),
            'shown' => mb_substr($shown, 0, 100),
            'source' => $source,
            'shop_id' => $shop?->id,
            'catalog_type' => $shop?->catalog_type ?: ($catalogType ?: $this->guessType($term)),
        ]);
    }

    /**
     * The kind of shop a search belongs to, from the shops whose products share its words
     * ("فطاير جبنه" → the shops that sell "فطائر"). Null when nothing is close.
     */
    public function guessType(string $term): ?string
    {
        return Cache::remember('search-miss-type:'.sha1($term), now()->addDay(), function () use ($term) {
            $patterns = collect(explode(' ', $term))->filter(fn ($word) => mb_strlen($word) >= 3)
                ->flatMap(fn ($word) => self::spellings($word))->unique()->take(16)->values();
            if ($patterns->isEmpty()) {
                return null;
            }
            $row = Product::query()->join('shops', 'shops.id', '=', 'products.shop_id')
                ->where('products.is_active', true)->where('shops.is_active', true)->whereNotNull('shops.catalog_type')
                ->where(function ($query) use ($patterns) {
                    foreach ($patterns as $pattern) {
                        $query->orWhere('products.name', 'like', '%'.addcslashes($pattern, '%_\\').'%');
                    }
                })
                ->selectRaw('shops.catalog_type AS type, COUNT(*) AS hits')->groupBy('shops.catalog_type')
                ->orderByDesc('hits')->first();

            return $row?->type;
        });
    }

    /**
     * What a shop's customers looked for lately: inside the shop, and across Ozman in the shop's kind of shop.
     *
     * @return array{in_shop: Collection, in_type: Collection, days: int}
     */
    public function forShop(Shop $shop, int $days = 7, int $limit = 20): array
    {
        $since = now()->subDays($days);
        $inShop = $this->grouped(SearchMiss::where('shop_id', $shop->id)->where('created_at', '>=', $since), $limit);
        $inType = $shop->catalog_type
            ? $this->grouped(SearchMiss::whereNull('shop_id')->where('catalog_type', $shop->catalog_type)
                ->where('created_at', '>=', $since)->whereNotIn('term', $inShop->pluck('term')), $limit)
            : collect();
        $names = Product::where('shop_id', $shop->id)->pluck('name')->map(fn ($name) => self::normalize((string) $name));
        $mark = fn (Collection $rows) => $rows->map(fn ($row) => $row + ['added' => $this->covered($row['term'], $names)])->values();

        return ['in_shop' => $mark($inShop), 'in_type' => $mark($inType), 'days' => $days];
    }

    /** Across all of Ozman: the most wanted things nobody had. */
    public function overall(int $days = 30, int $limit = 50, ?string $catalogType = null): Collection
    {
        $query = SearchMiss::where('created_at', '>=', now()->subDays($days))
            ->when($catalogType, fn ($q) => $q->where('catalog_type', $catalogType));

        return $this->grouped($query, $limit, true);
    }

    /** How many searches found nothing, per kind of shop. */
    public function byType(int $days = 30): Collection
    {
        return SearchMiss::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('catalog_type, COUNT(*) AS searches')->groupBy('catalog_type')->orderByDesc('searches')
            ->get()->map(fn ($row) => ['type' => $row->catalog_type, 'searches' => (int) $row->searches]);
    }

    private function grouped($query, int $limit, bool $withType = false): Collection
    {
        $rows = (clone $query)->selectRaw('term, COUNT(*) AS searches, MAX(created_at) AS last_at, MAX(id) AS last_id')
            ->groupBy('term')->orderByDesc('searches')->orderByDesc('last_at')->limit($limit)->get();
        $latest = SearchMiss::whereIn('id', $rows->pluck('last_id'))->get(['id', 'shown', 'catalog_type'])->keyBy('id');

        return $rows->map(fn ($row) => [
            'term' => $row->term,
            'shown' => $latest[$row->last_id]->shown ?? $row->term,
            'searches' => (int) $row->searches,
            'last_at' => \Illuminate\Support\Carbon::parse($row->last_at)->toIso8601String(),
        ] + ($withType ? ['type' => $latest[$row->last_id]->catalog_type ?? null] : []))->values();
    }

    /** Does one of the shop's products now carry every word of the search? */
    private function covered(string $term, Collection $productNames): bool
    {
        $words = array_filter(explode(' ', $term), fn ($word) => mb_strlen($word) >= 2);

        return $words !== [] && $productNames->contains(fn ($name) => collect($words)->every(fn ($word) => str_contains($name, $word)));
    }

    /** The common ways a folded word is written in shop data (فطاير → فطائر, جبنه → جبنة). */
    private static function spellings(string $word): array
    {
        $forms = [$word];
        if (str_ends_with($word, 'ه')) {
            $forms[] = mb_substr($word, 0, -1).'ة';
        }
        if (str_ends_with($word, 'ي')) {
            $forms[] = mb_substr($word, 0, -1).'ى';
        }
        if (str_contains($word, 'اي')) {
            $forms[] = str_replace('اي', 'ائ', $word);
        }
        if (str_starts_with($word, 'ا')) {
            $forms[] = 'أ'.mb_substr($word, 1);
            $forms[] = 'إ'.mb_substr($word, 1);
        }

        return $forms;
    }
}
