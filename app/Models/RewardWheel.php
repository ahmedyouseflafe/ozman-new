<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardWheel extends Model
{
    use HasFactory;

    public const CUSTOMER_SIGNUP_DISCOUNTS = 'customer_signup_discounts';
    public const MARKETER_DASHBOARD_WHEEL = 'marketer_dashboard_wheel';
    public const MARKETER_DIRECT_WHEEL = 'marketer_direct_wheel';
    public const TYPE_CUSTOMER_SIGNUP = 'customer_signup';
    public const TYPE_PURCHASE_AMOUNT = 'purchase_amount';
    public const TYPE_MARKETER_DASHBOARD = 'marketer_dashboard';
    public const TYPE_MARKETER_DIRECT = 'marketer_direct';

    protected $fillable = [
        'key',
        'wheel_type',
        'title',
        'min_order_total',
        'max_order_total',
        'is_active',
        'spin_cycle',
    ];

    protected $casts = [
        'min_order_total' => 'decimal:2',
        'max_order_total' => 'decimal:2',
        'is_active' => 'boolean',
        'spin_cycle' => 'array',
    ];

    public function segments(): HasMany
    {
        return $this->hasMany(RewardWheelSegment::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(RewardWheelQuestion::class)->orderBy('sort_order');
    }
}
