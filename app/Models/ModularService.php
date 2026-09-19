<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ModularService extends Model
{
    use HasLocalizedText;

    protected $fillable = [
        'modular_category_id', 'name', 'name_translations', 'slug', 'short_description',
        'short_description_translations', 'description', 'description_translations',
        'cover_image', 'position', 'is_featured', 'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'short_description_translations' => 'array',
        'description_translations' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ModularCategory::class, 'modular_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ModularServiceImage::class)->orderBy('position')->orderBy('id');
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(ModularOptionGroup::class)->orderBy('position')->orderBy('id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ModularServiceRequest::class);
    }

    public function publicUrl(): string
    {
        return route('real-estate.services.show', [$this->category->shop, $this->category, $this]);
    }

    public function imageUrl(): ?string
    {
        $path = $this->cover_image ?: $this->images->first()?->path ?: $this->category?->cover_image;

        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'images/') ? asset($path) : Storage::url($path);
    }
}
