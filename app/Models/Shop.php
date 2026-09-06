<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $attributes = [
        'catalog_type' => 'general',
        'is_active' => true,
        'show_ozman_products' => true,
    ];

    public function catalogDefinition(): array
    {
        return config('catalog_types.'.($this->catalog_type ?: 'general'), config('catalog_types.general', []));
    }

    public function requiresActiveDistributor(): bool
    {
        return (bool) ($this->catalogDefinition()['requires_distributor'] ?? true);
    }

    public function activeDistributionPartner(): ?Distributor
    {
        $marketer = $this->distributorMarketer;
        if ($marketer?->is_active && $marketer->distributor?->is_active) {
            return $marketer->distributor;
        }

        return $this->distributor?->is_active ? $this->distributor : null;
    }

    public function dashboardRouteName(): string
    {
        return match ($this->catalog_type) {
            'real_estate' => 'real-estate.dashboard',
            'restaurant' => 'restaurant.dashboard',
            default => 'shops.show',
        };
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

    public function realEstateProperties(): HasMany
    {
        return $this->hasMany(RealEstateProperty::class);
    }

    public function realEstateLeads(): HasMany
    {
        return $this->hasMany(RealEstateLead::class);
    }

    public function realEstateAlerts(): HasMany
    {
        return $this->hasMany(RealEstateAlert::class);
    }

    public function publicRouteName(): string
    {
        return match ($this->catalog_type) {
            'restaurant' => 'restaurant.menu',
            'electronics' => 'electronics.store',
            'real_estate' => 'real-estate.company',
            default => 'front.shop.slug',
        };
    }

    public function publicUrl(): string
    {
        return route($this->publicRouteName(), $this);
    }
}
