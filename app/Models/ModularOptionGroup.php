<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModularOptionGroup extends Model
{
    use HasLocalizedText;

    protected $fillable = [
        'modular_service_id', 'key', 'name', 'name_translations', 'help_text',
        'help_text_translations', 'is_required', 'position',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'help_text_translations' => 'array',
        'is_required' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ModularService::class, 'modular_service_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ModularOptionValue::class)->orderBy('position')->orderBy('id');
    }
}
