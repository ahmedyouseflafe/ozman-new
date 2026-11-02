<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoterPost extends Model
{
    protected $fillable = ['promoter_profile_id', 'shop_id', 'kind', 'type', 'media', 'poster', 'caption', 'is_active', 'expires_at', 'shop_clicks'];

    protected $casts = ['is_active' => 'boolean', 'expires_at' => 'datetime', 'shop_clicks' => 'integer'];

    public function profile()
    {
        return $this->belongsTo(PromoterProfile::class, 'promoter_profile_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Tracked link to the post's shop; counts the visit, then redirects. */
    public function shopVisitUrl(): ?string
    {
        return $this->shop?->is_active ? route('promoters.shop-visit', $this) : null;
    }

    public function isVisible(): bool
    {
        return $this->is_active && (! $this->expires_at || $this->expires_at->isFuture()) && $this->profile?->isPublished();
    }
}
