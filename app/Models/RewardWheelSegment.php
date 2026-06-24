<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardWheelSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reward_wheel_id',
        'label',
        'discount_value',
        'discount_type',
        'gift_image',
        'win_quota',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'win_quota' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function wheel(): BelongsTo
    {
        return $this->belongsTo(RewardWheel::class, 'reward_wheel_id');
    }
}
