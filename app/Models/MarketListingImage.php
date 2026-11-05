<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketListingImage extends Model
{
    protected $fillable = ['market_listing_id', 'path', 'sort_order'];

    public function listing()
    {
        return $this->belongsTo(MarketListing::class, 'market_listing_id');
    }
}
