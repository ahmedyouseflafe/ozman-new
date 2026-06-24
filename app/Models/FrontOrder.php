<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrontOrder extends Model
{
    protected $fillable = [
        'shop_id',
        'reward_wheel_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_whatsapp',
        'customer_address',
        'latitude',
        'longitude',
        'map_link',
        'items',
        'subtotal',
        'discount',
        'total',
        'order_channel',
        'payment_method',
        'payment_status',
        'status',
        'reward_label',
        'reward_discount_value',
        'reward_discount_type',
        'reward_gift_image',
        'reward_color',
        'reward_won_at',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'reward_discount_value' => 'decimal:2',
        'reward_won_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function rewardWheel(): BelongsTo
    {
        return $this->belongsTo(RewardWheel::class);
    }

    public function channelLabel(): string
    {
        return $this->order_channel === 'instant_payment' ? 'دفع فوري' : 'طلب واتساب';
    }
}
