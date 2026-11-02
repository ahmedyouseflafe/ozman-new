<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoterProfile extends Model
{
    protected $fillable = ['user_id', 'slug', 'display_name', 'bio', 'avatar', 'is_active', 'gift_points', 'page_views'];

    protected $casts = ['is_active' => 'boolean', 'gift_points' => 'integer', 'page_views' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->hasMany(PromoterPost::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'promoter_followers')->withTimestamps();
    }

    public function guestFollowers()
    {
        return $this->hasMany(PromoterGuestFollower::class);
    }

    public function giftSends()
    {
        return $this->hasMany(PromoterGiftSend::class);
    }

    /** Level badge from gifts, followers and visits; see App\Services\PromoterLevel. */
    public function level(): array
    {
        return app(\App\Services\PromoterLevel::class)->for($this);
    }

    // Account followers plus device (guest) followers; uses withCount values when loaded.
    public function followerTotal(): int
    {
        return (int) ($this->followers_count ?? $this->followers()->count())
            + (int) ($this->guest_followers_count ?? $this->guestFollowers()->count());
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user && (int) $user->id === (int) $this->user_id;
    }

    public function canBeManagedBy(?User $user): bool
    {
        return $user?->is_active && ($user->isSuperAdmin() || ($user->isPromoter() && $this->isOwnedBy($user)));
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)->whereHas('user', fn ($q) => $q->where('is_active', true)->where('role', 'promoter'));
    }

    public function publicUrl(): string
    {
        return route('promoters.show', $this->slug);
    }

    public function isPublished(): bool
    {
        return $this->is_active && $this->user?->is_active && $this->user->isPromoter();
    }
}
