<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RealEstateProperty extends Model
{
    use HasLocalizedText;

    public const PURPOSES = ['rent', 'sale'];

    public const STATUSES = ['draft', 'published', 'reserved', 'rented', 'sold', 'archived'];

    protected $fillable = [
        'shop_id', 'assigned_user_id', 'reference', 'slug', 'purpose', 'property_type',
        'title', 'title_translations', 'description', 'description_translations',
        'price', 'currency', 'city', 'neighborhood', 'address', 'latitude', 'longitude',
        'rooms', 'bedrooms', 'bathrooms', 'area', 'floor', 'building_floors',
        'furnished', 'is_new_project', 'has_elevator', 'has_balcony', 'has_garden',
        'has_storage', 'has_air_conditioning', 'parking_spaces', 'amenities', 'nearby_places',
        'video_url', 'virtual_tour_url', 'monthly_fees', 'status', 'is_featured',
        'published_at', 'available_from',
    ];

    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'amenities' => 'array',
        'nearby_places' => 'array',
        'price' => 'decimal:2',
        'area' => 'decimal:2',
        'rooms' => 'decimal:1',
        'furnished' => 'boolean',
        'is_new_project' => 'boolean',
        'has_elevator' => 'boolean',
        'has_balcony' => 'boolean',
        'has_garden' => 'boolean',
        'has_storage' => 'boolean',
        'has_air_conditioning' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'available_from' => 'date',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RealEstatePropertyImage::class, 'property_id')->orderBy('position');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(RealEstateLead::class, 'property_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function publicUrl(): string
    {
        return route('real-estate.property', [$this->shop, $this]);
    }
}
