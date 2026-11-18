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
}
