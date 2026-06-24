<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasLocalizedText;

class Product extends Model
{
    use HasFactory, HasLocalizedText;

    protected $fillable = [
        'shop_id',
        'category_id',
        'agent_id',
        'name',
        'name_translations',
        'slug',
        'description',
        'description_translations',
        'price',
        'discount_price',
        'merchant_price',
        'package_price',
        'pallet_price',
        'carton_price',
        'quantity',
        'sku',
        'barcode',
        'main_image',
        'video',
        'views',
        'rating',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'merchant_price' => 'decimal:2',
        'package_price' => 'decimal:2',
        'pallet_price' => 'decimal:2',
        'carton_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'name_translations' => 'array',
        'description_translations' => 'array',
    ];

    // العلاقات

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function campaigns()
    {
        return $this->hasMany(ProductCampaign::class);
    }
}
