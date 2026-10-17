<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class MainScreen extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_id',
        'needs_shop_assignment',
        'title',
        'type',
        'media',
        'placement',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'needs_shop_assignment' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeForPlatform(Builder $query): Builder
    {
        return $query->whereNull('shop_id')->where('needs_shop_assignment', false);
    }

}
