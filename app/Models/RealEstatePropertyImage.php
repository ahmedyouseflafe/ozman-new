<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstatePropertyImage extends Model
{
    protected $fillable = ['property_id', 'path', 'alt_text', 'position', 'is_cover'];

    protected $casts = ['is_cover' => 'boolean'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(RealEstateProperty::class, 'property_id');
    }
}
