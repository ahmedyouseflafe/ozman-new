<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class AssistantMessage extends Model
{
    protected $fillable = ['assistant_conversation_id', 'role', 'content', 'has_image', 'tools', 'attachments', 'input_tokens', 'output_tokens'];

    protected $casts = ['has_image' => 'boolean', 'tools' => 'array', 'attachments' => 'array'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'assistant_conversation_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AssistantAction::class);
    }
}
