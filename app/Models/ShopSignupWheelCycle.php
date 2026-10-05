<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSignupWheelCycle extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['quotas' => 'array', 'remaining' => 'array'];
}
