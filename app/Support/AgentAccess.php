<?php

namespace App\Support;

use App\Models\Agent;
use App\Models\User;

/** The agent records a user acts as (matched by their user id, or by their email). */
class AgentAccess
{
    /** @return list<int> */
    public static function idsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return Agent::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
