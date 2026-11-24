<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One search that found nothing. No user is stored. */
class SearchMiss extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['term', 'shown', 'source', 'shop_id', 'catalog_type'];

    protected $casts = ['created_at' => 'datetime'];
}
