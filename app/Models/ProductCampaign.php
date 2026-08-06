<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasLocalizedText;

class ProductCampaign extends Model
{
    use HasFactory, HasLocalizedText;

    protected $fillable = [
        'product_id',
        'title',
        'title_translations',
        'type',
        'media',
        'offer_type',
        'unit_key',
        'offer_quantity',
        'min_quantity',
        'max_quantity',
        'offer_price',
        'offer_note',
        'offer_note_translations',
        'starts_at',
        'ends_at',
        'is_active',
        'video_status',
        'video_poster',
        'video_error',
    ];

    protected $casts = [
        'offer_price' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'title_translations' => 'array',
        'offer_note_translations' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopePubliclyReady($query)
    {
        return $query->where(fn($query) => $query
            ->where('type', '!=', 'video')
            ->orWhereNull('video_status')
            ->orWhere('video_status', 'ready'));
    }
}
