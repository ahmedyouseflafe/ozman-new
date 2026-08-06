<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MainScreen extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'media',
        'placement',
        'duration',
        'is_active',
        'video_status',
        'video_poster',
        'video_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePubliclyReady($query)
    {
        return $query->where(fn($query) => $query
            ->where('type', '!=', 'video')
            ->orWhereNull('video_status')
            ->orWhere('video_status', 'ready'));
    }
}
