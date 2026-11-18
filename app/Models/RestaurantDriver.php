<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantDriver extends Model
{
    protected $fillable = ['shop_id', 'user_id', 'is_active', 'latitude', 'longitude', 'location_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float', 'location_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FrontOrder::class);
    }

    public function deliveryOffers(): HasMany
    {
        return $this->hasMany(RestaurantDeliveryOffer::class);
    }
}
