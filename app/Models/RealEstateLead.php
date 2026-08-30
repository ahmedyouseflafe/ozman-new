<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstateLead extends Model
{
    protected $fillable = [
        'shop_id', 'property_id', 'assigned_user_id', 'name', 'phone', 'email',
        'message', 'source', 'status', 'viewing_at',
    ];

    protected $casts = ['viewing_at' => 'datetime'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(RealEstateProperty::class, 'property_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
