<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleEntry extends Model
{
    use HasFactory;

    public const OUTCOME_WINNER = 'winner';
    public const OUTCOME_LIVE_DRAW = 'live_draw';
    public const OUTCOME_USED_WINNER = 'used_winner';

    protected $fillable = [
        'card_number',
        'raffle_card_id',
        'outcome',
        'customer_name',
        'customer_phone',
        'customer_whatsapp',
        'customer_payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'customer_payload' => 'array',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(RaffleCard::class, 'raffle_card_id');
    }
}
