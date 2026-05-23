<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
        'image',
        'phone',
        'whatsapp',
        'email',
        'address',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // العلاقات

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
