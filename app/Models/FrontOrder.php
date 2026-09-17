<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrontOrder extends Model
{
    protected $fillable = [
        'shop_id',
        'restaurant_table_id',
        'restaurant_driver_id',
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
        'estimated_preparation_minutes',
        'customer_push_token',
        'driver_assigned_at',
        'driver_latitude',
        'driver_longitude',
        'driver_location_accuracy_meters',
        'driver_location_at',
        'picked_up_at',
        'delivered_at',
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
        'estimated_preparation_minutes' => 'integer',
        'driver_assigned_at' => 'datetime',
        'driver_latitude' => 'decimal:7',
        'driver_longitude' => 'decimal:7',
        'driver_location_accuracy_meters' => 'integer',
        'driver_location_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $hidden = [
        'customer_push_token', 'driver_latitude', 'driver_longitude',
        'driver_location_accuracy_meters', 'driver_location_at',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function restaurantDriver(): BelongsTo
    {
        return $this->belongsTo(RestaurantDriver::class);
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
            'out_for_delivery' => 'خرج للتوصيل',
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
