<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** A mobile-app login. The plain token is shown once; only its hash is kept. */
class ApiToken extends Model
{
    public const LIFETIME_DAYS = 180;

    protected $fillable = ['user_id', 'name', 'token_hash', 'last_used_at', 'expires_at'];

    protected $casts = ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];

    protected $hidden = ['token_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{0: self, 1: string} the stored token and the plain text to give the app */
    public static function issue(User $user, string $name = 'mobile'): array
    {
        $plain = Str::random(64);
        $token = self::create([
            'user_id' => $user->id,
            'name' => mb_substr($name, 0, 120),
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);

        return [$token, $plain];
    }

    public static function findValid(?string $plain): ?self
    {
        if (! is_string($plain) || strlen($plain) < 40 || strlen($plain) > 200) {
            return null;
        }
        $token = self::with('user')->where('token_hash', hash('sha256', $plain))->first();
        if (! $token || ($token->expires_at && $token->expires_at->isPast()) || ! $token->user?->is_active) {
            return null;
        }

        return $token;
    }
}
