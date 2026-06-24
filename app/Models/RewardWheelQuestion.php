<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardWheelQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'reward_wheel_id',
        'question',
        'answer',
        'options',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function wheel(): BelongsTo
    {
        return $this->belongsTo(RewardWheel::class, 'reward_wheel_id');
    }
}
