<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopStory extends Model
{
    protected $fillable = ['shop_id', 'media', 'type', 'caption', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function shop() { return $this->belongsTo(Shop::class); }
}
