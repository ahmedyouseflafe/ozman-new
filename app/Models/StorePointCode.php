<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A one-time QR the shop shows at the till: scanning it gives the customer points for an in-shop purchase. */
class StorePointCode extends Model
{
    protected $fillable = ['shop_id', 'created_by', 'amount', 'points', 'token', 'expires_at', 'claimed_by', 'claimed_at', 'loyalty_point_entry_id', 'cancelled_at'];

    protected $casts = ['amount' => 'decimal:2', 'points' => 'integer', 'expires_at' => 'datetime', 'claimed_at' => 'datetime', 'cancelled_at' => 'datetime'];

    protected $hidden = ['token'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function claimer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function status(): string
    {
        return match (true) {
            $this->claimed_at !== null => 'claimed',
            $this->cancelled_at !== null => 'cancelled',
            $this->expires_at->isPast() => 'expired',
            default => 'open',
        };
    }

    public function url(): string
    {
        return route('store-points.claim', $this->token);
    }
}
