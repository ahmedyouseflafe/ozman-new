<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'distributor_id',
        'distributor_marketer_id',
        'name',
        'slug',
        'catalog_type',
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
        'show_ozman_products',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_ozman_products' => 'boolean',
    ];

    public function catalogDefinition(): array
    {
        return config('catalog_types.' . ($this->catalog_type ?: 'general'), config('catalog_types.general', []));
    }

    // العلاقات

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
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

    public function restaurantTables()
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function distributors()
    {
        return $this->hasMany(Distributor::class);
    }

    public function distributorMarketer(): BelongsTo
    {
        return $this->belongsTo(DistributorMarketer::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }

    public function rewardWheels()
    {
        return $this->hasMany(RewardWheel::class);
    }
}
