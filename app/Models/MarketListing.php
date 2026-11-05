<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A product a community member offers in "المتجر الحر". */
class MarketListing extends Model
{
    public const ACTIVE = 'active';
    public const HIDDEN = 'hidden';
    public const SOLD = 'sold';
    public const REMOVED = 'removed';

    protected $fillable = ['user_id', 'title', 'description', 'price', 'category', 'condition', 'city', 'quantity', 'status'];

    protected $casts = ['price' => 'decimal:2', 'quantity' => 'integer', 'views' => 'integer'];

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images()
    {
        return $this->hasMany(MarketListingImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function orders()
    {
        return $this->hasMany(MarketOrder::class);
    }

    /** Shown in the market: active, in stock, and the seller's account still works. */
    public function scopeVisible($query)
    {
        return $query->where('status', self::ACTIVE)->where('quantity', '>', 0)
            ->whereHas('seller', fn ($q) => $q->where('is_active', true));
    }

    public function isVisible(): bool
    {
        return $this->status === self::ACTIVE && $this->quantity > 0 && $this->seller?->is_active;
    }

    public function canBeManagedBy(?User $user): bool
    {
        return $user?->is_active && ((int) $user->id === (int) $this->user_id || $user->isSuperAdmin());
    }

    public function url(): string
    {
        return route('market.show', $this);
    }

    public function coverUrl(): ?string
    {
        $image = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $image ? route('market.image', $image) : null;
    }

    public function categoryLabel(): string
    {
        return __(config('market.categories.'.$this->category, $this->category));
    }

    public function conditionLabel(): string
    {
        return __(config('market.conditions.'.$this->condition, $this->condition));
    }

    public function statusLabel(): string
    {
        return [self::ACTIVE => __('معروض'), self::HIDDEN => __('مخفي'), self::SOLD => __('خلص'), self::REMOVED => __('أوقفته الإدارة')][$this->status] ?? $this->status;
    }

    public function formattedPrice(): string
    {
        return rtrim(rtrim(number_format((float) $this->price, 2), '0'), '.').' ₪';
    }
}
