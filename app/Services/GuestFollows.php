<?php

namespace App\Services;

use App\Models\PromoterGuestFollower;
use App\Models\PromoterProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

// Lets a visitor follow promoters before having an account. The raw token lives
// only in the visitor's httpOnly cookie; the database keeps its SHA-256.
class GuestFollows
{
    public const COOKIE = 'ozman_guest_follow';

    private const MINUTES = 525600; // one year

    public function token(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && preg_match('/^[A-Za-z0-9]{64}$/', $token) ? $token : null;
    }

    public function newToken(): string
    {
        return Str::random(64);
    }

    public function cookie(Request $request, string $token): SymfonyCookie
    {
        return cookie(self::COOKIE, $token, self::MINUTES, '/', null, $request->isSecure(), true, false, 'lax');
    }

    public function isFollowing(Request $request, PromoterProfile $profile): bool
    {
        $token = $this->token($request);

        return $token !== null && $profile->guestFollowers()->where('guest_token_hash', $this->hash($token))->exists();
    }

    public function follow(PromoterProfile $profile, string $token): void
    {
        // The unique pair makes repeated clicks and retries a no-op.
        PromoterGuestFollower::query()->insertOrIgnore([
            'promoter_profile_id' => $profile->id, 'guest_token_hash' => $this->hash($token),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function unfollow(PromoterProfile $profile, string $token): void
    {
        $profile->guestFollowers()->where('guest_token_hash', $this->hash($token))->delete();
    }

    // Moves this device's guest follows to the account, without duplicates, then forgets the token.
    public function merge(Request $request, User $user): void
    {
        $token = $this->token($request);
        if ($token === null) {
            return;
        }

        $hash = $this->hash($token);
        DB::transaction(function () use ($hash, $user) {
            $profileIds = PromoterGuestFollower::where('guest_token_hash', $hash)->pluck('promoter_profile_id');
            $rows = PromoterProfile::whereKey($profileIds)->where('user_id', '!=', $user->id)->pluck('id')
                ->map(fn ($id) => ['promoter_profile_id' => $id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()])
                ->all();
            if ($rows) {
                DB::table('promoter_followers')->upsert($rows, ['promoter_profile_id', 'user_id'], ['updated_at']);
            }
            PromoterGuestFollower::where('guest_token_hash', $hash)->delete();
        });

        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
