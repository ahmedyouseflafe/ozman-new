<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopReview extends Model
{
    protected $fillable = ['shop_id', 'user_id', 'front_order_id', 'rating', 'comment', 'reply', 'replied_at', 'is_visible'];

    protected $casts = ['rating' => 'integer', 'is_visible' => 'boolean', 'replied_at' => 'datetime'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FrontOrder::class, 'front_order_id');
    }

    /** Average and count of the shop's visible reviews. @return array{average: ?float, count: int} */
    public static function summaryFor(int $shopId): array
    {
        $row = self::query()->where('shop_id', $shopId)->where('is_visible', true)->selectRaw('AVG(rating) AS avg, COUNT(*) AS cnt')->first();

        return ['average' => $row && $row->cnt ? round((float) $row->avg, 1) : null, 'count' => (int) ($row->cnt ?? 0)];
    }

    /** How many visible reviews gave each number of stars, 5 down to 1. @return array<string, int> */
    public static function starsFor(int $shopId): array
    {
        $counts = self::query()->where('shop_id', $shopId)->where('is_visible', true)->selectRaw('rating, COUNT(*) AS c')->groupBy('rating')->pluck('c', 'rating');

        return collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [(string) $s => (int) ($counts[$s] ?? 0)])->all();
    }

    /** The reviewer's name as other customers see it: first name and an initial. */
    public function publicName(): string
    {
        $name = trim((string) $this->user?->name);
        if ($name === '') {
            return __('زبون');
        }
        $parts = preg_split('/\s+/u', $name) ?: [];

        return $parts[0].(isset($parts[1]) ? ' '.mb_substr($parts[1], 0, 1).'.' : '');
    }
}
