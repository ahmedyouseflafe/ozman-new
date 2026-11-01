<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointEntry extends Model
{
    public const TYPE_EARN = 'earn';
    public const TYPE_REVERSE = 'reverse';
    public const TYPE_MANUAL = 'manual_adjust';
    public const TYPE_REDEEM = 'redeem';

    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';

    protected $fillable = ['user_id', 'front_order_id', 'shop_id', 'points', 'type', 'status', 'base_amount', 'note', 'created_by'];

    protected $casts = ['points' => 'integer', 'base_amount' => 'decimal:2'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FrontOrder::class, 'front_order_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_EARN => __('نقاط مشتريات'),
            self::TYPE_REVERSE => __('سحب نقاط طلب ملغي'),
            self::TYPE_MANUAL => __('تعديل من الإدارة'),
            self::TYPE_REDEEM => __('استبدال نقاط'),
            default => $this->type,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING => __('معلّقة'),
            self::CONFIRMED => __('مؤكدة'),
            self::CANCELLED => __('ملغاة'),
            default => $this->status,
        };
    }
}
