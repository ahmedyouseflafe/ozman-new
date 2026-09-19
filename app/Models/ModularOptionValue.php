<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModularOptionValue extends Model
{
    use HasLocalizedText;

    protected $fillable = [
        'modular_option_group_id', 'key', 'name', 'name_translations', 'description',
        'description_translations', 'is_default', 'is_active', 'position',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'description_translations' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ModularOptionGroup::class, 'modular_option_group_id');
    }
}
