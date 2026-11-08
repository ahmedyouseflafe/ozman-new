<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    protected $fillable = ['shop_id', 'product_id', 'user_id', 'caption', 'media_url', 'targets', 'scheduled_for', 'status', 'zernio_post_id', 'error'];

    protected $casts = ['targets' => 'array', 'scheduled_for' => 'datetime'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
