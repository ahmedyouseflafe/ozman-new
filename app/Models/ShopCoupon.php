<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCoupon extends Model
{
    protected $fillable = ['shop_id', 'code', 'type', 'value', 'min_total', 'max_uses', 'per_customer', 'used_count', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = [
        'value' => 'float', 'min_total' => 'float', 'max_uses' => 'integer', 'per_customer' => 'integer', 'used_count' => 'integer',
        'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public static function normalize(string $code): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', trim($code)));
    }
}
