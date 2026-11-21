<?php

namespace App\Services;

use App\Models\{FrontOrder, LoyaltyPointEntry, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Invite a friend": every customer has a short code. A friend who signs up with it, once their first order is
 * completed, earns Ozman points for both of them (points that work across Ozman, like the ones from admins).
 */
class Invites
{
    public const COOKIE = 'ozman_invite';

    public function points(): int
    {
        return max(0, (int) config('services.invites.points', 50));
    }

    public function enabled(): bool
    {
        return $this->points() > 0 && (bool) app(LoyaltyPoints::class)->settings()['enabled'];
    }

    /** The customer's own code, made the first time it's asked for. */
    public function codeFor(User $user): string
    {
        if ($user->invite_code) {
            return $user->invite_code;
        }
        do {
            $code = Str::upper(Str::random(6));
        } while (User::where('invite_code', $code)->exists() || preg_match('/[0O1IL]/', $code));
        $user->forceFill(['invite_code' => $code])->save();

        return $code;
    }

    public function link(User $user): string
    {
        return route('invite.open', $this->codeFor($user));
    }

    /** A new account remembers who invited it (never itself, and only a real customer's code). */
    public function attach(User $newUser, ?string $code): void
    {
        $code = Str::upper(trim((string) $code));
        if ($code === '' || $newUser->invited_by) {
            return;
        }
        $inviter = User::where('invite_code', $code)->first();
        if ($inviter && $inviter->id !== $newUser->id && $inviter->is_active) {
            $newUser->forceFill(['invited_by' => $inviter->id])->save();
        }
    }

    public function codeFromRequest(Request $request): ?string
    {
        return $request->input('invite_code') ?: $request->cookie(self::COOKIE);
    }

    /** Called when an order is completed: the first one of an invited customer rewards both, once. */
    public function rewardFirstOrder(FrontOrder $order): void
    {
        if (! $this->enabled() || ! $order->user_id || ! in_array($order->status, LoyaltyPoints::CONFIRM_STATUSES, true)) {
            return;
        }
        DB::transaction(function () use ($order) {
            $friend = User::query()->whereKey($order->user_id)->lockForUpdate()->first();
            if (! $friend || ! $friend->invited_by || $friend->invite_rewarded_at) {
                return;
            }
            $inviter = User::find($friend->invited_by);
            $friend->forceFill(['invite_rewarded_at' => now()])->save();
            $points = $this->points();
            LoyaltyPointEntry::create(['user_id' => $friend->id, 'front_order_id' => $order->id, 'points' => $points, 'type' => 'invite',
                'status' => LoyaltyPointEntry::CONFIRMED, 'note' => __('هدية التسجيل بدعوة')]);
            if ($inviter && $inviter->is_active) {
                LoyaltyPointEntry::create(['user_id' => $inviter->id, 'front_order_id' => $order->id, 'points' => $points, 'type' => 'invite',
                    'status' => LoyaltyPointEntry::CONFIRMED, 'note' => __('صاحبك :name طلب أول طلب', ['name' => $friend->name])]);
            }
        });
    }

    /** What the invite screen shows. */
    public function summary(User $user): array
    {
        return [
            'enabled' => $this->enabled(),
            'points' => $this->points(),
            'code' => $this->codeFor($user),
            'link' => $this->link($user),
            'joined' => User::where('invited_by', $user->id)->count(),
            'rewarded' => User::where('invited_by', $user->id)->whereNotNull('invite_rewarded_at')->count(),
        ];
    }
}
