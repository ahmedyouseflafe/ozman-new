<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModularServiceImage extends Model
{
    protected $fillable = ['modular_service_id', 'path', 'alt_text', 'position'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ModularService::class, 'modular_service_id');
    }
}
