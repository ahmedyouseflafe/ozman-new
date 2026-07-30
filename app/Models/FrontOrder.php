<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrontOrder extends Model
{
    protected $fillable = [
        'shop_id',
        'restaurant_table_id',
        'distributor_id',
        'distributor_marketer_id',
        'marketing_source',
        'marketer_commission_rate',
        'marketer_commission_amount',
        'reward_wheel_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_whatsapp',
        'customer_address',
        'customer_notes',
        'latitude',
        'longitude',
        'map_link',
        'items',
        'subtotal',
        'discount',
        'total',
        'order_channel',
        'order_type',
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
        'marketer_commission_rate' => 'decimal:2',
        'marketer_commission_amount' => 'decimal:2',
        'reward_discount_value' => 'decimal:2',
        'reward_won_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function distributorMarketer(): BelongsTo
    {
        return $this->belongsTo(DistributorMarketer::class);
    }

    public function rewardWheel(): BelongsTo
    {
        return $this->belongsTo(RewardWheel::class);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? self::statusOptions()['new'];
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            'delivered' => 'green',
            'not_delivered' => 'yellow',
            default => '',
        };
    }

    public static function statusOptions(): array
    {
        return [
            'new' => 'جديد',
            'preparing' => 'قيد التحضير',
            'ready' => 'جاهز',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'not_delivered' => 'لم يتم التسليم',
            'delivered' => 'تم التسليم',
        ];
    }

    public function channelLabel(): string
    {
        return $this->order_channel === 'instant_payment' ? 'دفع فوري' : 'طلب واتساب';
    }
}
