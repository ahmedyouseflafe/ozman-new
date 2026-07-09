<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributorMarketer extends Model
{
    protected $fillable = [
        'distributor_id',
        'user_id',
        'name',
        'tracking_code',
        'phone',
        'whatsapp',
        'email',
        'commission_rate',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function frontOrders(): HasMany
    {
        return $this->hasMany(FrontOrder::class);
    }
}
