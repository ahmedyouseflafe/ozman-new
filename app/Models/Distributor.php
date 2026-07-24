<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Distributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'agent_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function marketers()
    {
        return $this->hasMany(DistributorMarketer::class);
    }

    public function linkedShops()
    {
        return $this->hasMany(Shop::class);
    }
}
