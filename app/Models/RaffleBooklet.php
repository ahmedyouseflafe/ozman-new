<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleBooklet extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_card_number',
        'end_card_number',
        'cards_count',
        'winning_cards_count',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'cards_count' => 'integer',
        'winning_cards_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
