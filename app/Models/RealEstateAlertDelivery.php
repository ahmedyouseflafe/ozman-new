<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstateAlertDelivery extends Model
{
    protected $fillable = ['real_estate_alert_id', 'real_estate_property_id', 'fingerprint', 'channel', 'status', 'attempts', 'provider_reference', 'error_message', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(RealEstateAlert::class, 'real_estate_alert_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(RealEstateProperty::class, 'real_estate_property_id');
    }
}
