<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'phone',
        'whatsapp',
        'email',
        'address',
        'latitude',
        'longitude',
        'city',
        'country',
        'open_time',
        'close_time',
        'payment_method',
        'payment_provider',
        'payment_account_holder',
        'payment_account_number',
        'payment_iban',
        'payment_wallet_number',
        'payment_notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // العلاقات

    public function user()
    {
        return $this->belongsTo(User::class);
    }

   public function social()
{
    return $this->hasOne(ShopSocial::class);
}

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function distributors()
    {
        return $this->hasMany(Distributor::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }
}
