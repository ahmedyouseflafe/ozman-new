<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOutlet extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'region',
        'address',
        'map_url',
        'latitude',
        'longitude',
        'phone',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    // Coordinates win over a pasted map link, then the written address.
    public function mapUrl(): ?string
    {
        if (is_numeric($this->latitude) && is_numeric($this->longitude)) {
            return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->latitude.','.$this->longitude);
        }

        if (filled($this->map_url)) {
            return $this->map_url;
        }

        $query = implode(', ', array_filter([$this->name, $this->address, $this->region]));

        return filled($this->address) ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query) : null;
    }
}
