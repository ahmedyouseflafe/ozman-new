<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantDeliveryOffer extends Model
{
    protected $fillable = [
        'front_order_id',
        'restaurant_driver_id',
        'status',
        'offered_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'offered_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FrontOrder::class, 'front_order_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(RestaurantDriver::class, 'restaurant_driver_id');
    }
}
