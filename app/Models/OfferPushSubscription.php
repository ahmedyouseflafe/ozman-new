<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferPushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'source_shop_id',
        'endpoint_hash',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'source_shop_id');
    }
}
