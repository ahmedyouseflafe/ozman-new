<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopStoryView extends Model
{
    protected $fillable = [
        'shop_story_id',
        'user_id',
        'viewer_key',
        'viewer_name',
        'source',
        'viewed_at',
        'last_viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(ShopStory::class, 'shop_story_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
