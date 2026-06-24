<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasLocalizedText;

class Category extends Model
{
    use HasFactory, HasLocalizedText;

    protected $fillable = [
        'shop_id',
        'agent_id',
        'name',
        'name_translations',
        'slug',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'name_translations' => 'array',
    ];

    // العلاقات

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
