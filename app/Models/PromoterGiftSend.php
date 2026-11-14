<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoterGiftSend extends Model
{
    protected $fillable = ['promoter_profile_id', 'user_id', 'promoter_gift_id', 'promoter_post_id', 'gift_name', 'gift_icon', 'points'];

    protected $casts = ['points' => 'integer'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PromoterProfile::class, 'promoter_profile_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Same as sender(); the promoter's page loads gifts with "user". */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(PromoterGift::class, 'promoter_gift_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(PromoterPost::class, 'promoter_post_id');
    }
}
