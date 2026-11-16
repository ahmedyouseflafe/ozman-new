<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A change the assistant proposed. Nothing happens until the user presses "confirm". */
class AssistantAction extends Model
{
    public const PENDING = 'pending';
    public const DONE = 'done';
    public const CANCELLED = 'cancelled';
    public const FAILED = 'failed';
    public const EXPIRED = 'expired';

    /** A proposal older than this must be asked for again, so it never acts on stale numbers. */
    public const TTL_MINUTES = 60;

    protected $fillable = ['assistant_conversation_id', 'assistant_message_id', 'shop_id', 'user_id', 'type', 'payload', 'summary', 'status', 'result', 'decided_at'];

    protected $casts = ['payload' => 'array', 'decided_at' => 'datetime'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'assistant_conversation_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function isStale(): bool
    {
        return $this->created_at && $this->created_at->lt(now()->subMinutes(self::TTL_MINUTES));
    }
}
