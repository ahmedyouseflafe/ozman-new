<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ModularCategory extends Model
{
    use HasLocalizedText;

    protected $fillable = [
        'shop_id', 'name', 'name_translations', 'slug', 'description',
        'description_translations', 'icon_key', 'cover_image', 'position', 'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'description_translations' => 'array',
        'is_active' => 'boolean',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ModularService::class)->orderBy('position')->orderBy('id');
    }

    public function publicUrl(): string
    {
        return route('real-estate.services.category', [$this->shop, $this]);
    }

    public function imageUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return str_starts_with($this->cover_image, 'images/')
            ? asset($this->cover_image)
            : Storage::url($this->cover_image);
    }
}
