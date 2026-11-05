<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An order a signed-in member placed on someone's market listing. */
class MarketOrder extends Model
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $fillable = ['order_number', 'market_listing_id', 'seller_id', 'buyer_id', 'listing_title', 'unit_price', 'quantity', 'total',
        'buyer_name', 'buyer_phone', 'city', 'address', 'note', 'status', 'stock_taken'];

    protected $casts = ['unit_price' => 'decimal:2', 'total' => 'decimal:2', 'quantity' => 'integer', 'stock_taken' => 'boolean'];

    public function listing()
    {
        return $this->belongsTo(MarketListing::class, 'market_listing_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::PENDING, self::ACCEPTED], true);
    }

    public function statusLabel(): string
    {
        return [self::PENDING => __('بانتظار البائع'), self::ACCEPTED => __('قبله البائع'), self::COMPLETED => __('تم التسليم'),
            self::REJECTED => __('رفضه البائع'), self::CANCELLED => __('ملغي')][$this->status] ?? $this->status;
    }

    public function formattedTotal(): string
    {
        return rtrim(rtrim(number_format((float) $this->total, 2), '0'), '.').' ₪';
    }
}
