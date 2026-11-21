<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasLocalizedText;

class Product extends Model
{
    use HasFactory, HasLocalizedText;

    protected static function booted(): void
    {
        // A product going on offer (a new discount, or a lower one) tells the shop's followers.
        static::saved(function (Product $product) {
            $now = (float) $product->discount_price;
            $before = $product->wasRecentlyCreated ? 0.0 : (float) $product->getOriginal('discount_price');
            if ($now > 0 && $now < (float) $product->price && ($product->wasRecentlyCreated || $product->wasChanged('discount_price'))
                && ($before <= 0 || $now < $before)) {
                try {
                    app(\App\Services\ShopFollows::class)->offerStarted($product);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }

    protected $fillable = [
        'shop_id',
        'category_id',
        'agent_id',
        'name',
        'name_translations',
        'slug',
        'description',
        'catalog_attributes',
        'description_translations',
        'price',
        'discount_price',
        'customer_package_price',
        'show_customer_package_price',
        'customer_carton_price',
        'customer_pallet_price',
        'show_customer_carton_price',
        'show_customer_pallet_price',
        'merchant_price',
        'package_price',
        'pallet_price',
        'carton_price',
        'show_package_price',
        'show_carton_price',
        'show_pallet_price',
        'quantity',
        'sku',
        'barcode',
        'main_image',
        'video',
        'model_3d',
        'model_3d_size',
        'views',
        'rating',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'model_3d_size' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'customer_package_price' => 'decimal:2',
        'show_customer_package_price' => 'boolean',
        'customer_carton_price' => 'decimal:2',
        'customer_pallet_price' => 'decimal:2',
        'show_customer_carton_price' => 'boolean',
        'show_customer_pallet_price' => 'boolean',
        'merchant_price' => 'decimal:2',
        'package_price' => 'decimal:2',
        'pallet_price' => 'decimal:2',
        'carton_price' => 'decimal:2',
        'show_package_price' => 'boolean',
        'show_carton_price' => 'boolean',
        'show_pallet_price' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'name_translations' => 'array',
        'description_translations' => 'array',
        'catalog_attributes' => 'array',
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

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
