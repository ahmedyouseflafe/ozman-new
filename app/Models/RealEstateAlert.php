<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstateAlert extends Model
{
    protected $fillable = [
        'shop_id', 'name', 'email', 'phone', 'channel', 'filters', 'locale',
        'is_active', 'last_notified_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_active' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
