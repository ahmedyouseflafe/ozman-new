<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = ['shop_id', 'name', 'code', 'capacity', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function orders() { return $this->hasMany(FrontOrder::class); }
}
