<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class AssistantConversation extends Model
{
    protected $fillable = ['kind', 'user_id', 'shop_id', 'title'];

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AssistantAction::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
