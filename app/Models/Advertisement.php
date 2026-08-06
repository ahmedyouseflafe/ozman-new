<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'title',
        'description',
        'type',
        'media',
        'duration',
        'sort_order',
        'is_active',
        'video_status',
        'video_poster',
        'video_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // العلاقات

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopePubliclyReady($query)
    {
        return $query->where(fn($query) => $query
            ->where('type', '!=', 'video')
            ->orWhereNull('video_status')
            ->orWhere('video_status', 'ready'));
    }
}
