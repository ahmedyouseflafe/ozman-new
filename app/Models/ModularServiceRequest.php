<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModularServiceRequest extends Model
{
    protected $fillable = [
        'shop_id', 'modular_service_id', 'customer_name', 'customer_phone', 'city',
        'notes', 'selections', 'locale', 'status', 'whatsapp_opened_at',
    ];

    protected $casts = [
        'selections' => 'array',
        'whatsapp_opened_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ModularService::class, 'modular_service_id');
    }
}
