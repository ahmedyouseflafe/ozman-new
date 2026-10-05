<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSignupReward extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['segments' => 'array', 'selected_index' => 'integer', 'spun_at' => 'datetime', 'redeemed_at' => 'datetime'];
}
