<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoterGift extends Model
{
    protected $fillable = ['name', 'icon', 'points', 'sort_order', 'is_active'];

    protected $casts = ['points' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean'];

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('points');
    }
}
