<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopActivityEvent extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['data' => 'array', 'created_at' => 'datetime'];

    public function shop() { return $this->belongsTo(Shop::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }

    public const NOTIFICATIONS = ['sale_confirmed', 'order_created', 'order_cancelled', 'sale_reversed'];
}
