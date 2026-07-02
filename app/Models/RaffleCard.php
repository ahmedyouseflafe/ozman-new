<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_number',
        'prize_title',
        'prize_image',
        'is_active',
        'used_at',
        'used_customer_name',
        'used_customer_phone',
        'used_customer_whatsapp',
        'used_customer_payload',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'used_at' => 'datetime',
        'used_customer_payload' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
