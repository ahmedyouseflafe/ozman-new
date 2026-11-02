<?php

namespace App\Services;

use App\Models\PromoterProfile;

/**
 * A promoter's level shows shops how much reach a page has.
 * Score = gift points received + 10 per follower + 1 per page visit.
 */
class PromoterLevel
{
    public const FOLLOWER_WEIGHT = 10;

    /** Ordered from lowest; `from` is the minimum score. */
    public const LEVELS = [
        ['key' => 'bronze', 'name' => 'برونزي', 'icon' => '🥉', 'from' => 0],
        ['key' => 'silver', 'name' => 'فضي', 'icon' => '🥈', 'from' => 500],
        ['key' => 'gold', 'name' => 'ذهبي', 'icon' => '🥇', 'from' => 2000],
        ['key' => 'diamond', 'name' => 'ماسي', 'icon' => '💎', 'from' => 10000],
    ];

    public function score(PromoterProfile $profile): int
    {
        return (int) $profile->gift_points
            + self::FOLLOWER_WEIGHT * $profile->followerTotal()
            + (int) $profile->page_views;
    }

    /** @return array{key:string,name:string,icon:string,from:int,score:int,next:?array,progress:int} */
    public function for(PromoterProfile $profile): array
    {
        return $this->forScore($this->score($profile));
    }

    public function forScore(int $score): array
    {
        $current = self::LEVELS[0];
        $next = null;
        foreach (self::LEVELS as $index => $level) {
            if ($score >= $level['from']) {
                $current = $level;
                $next = self::LEVELS[$index + 1] ?? null;
            }
        }
        $progress = $next
            ? (int) floor(100 * ($score - $current['from']) / max($next['from'] - $current['from'], 1))
            : 100;

        return [...$current, 'score' => $score, 'next' => $next, 'progress' => min(max($progress, 0), 100)];
    }

    /** SQL for ordering a profile list by the same score (no N+1). */
    public static function scoreSql(string $table = 'promoter_profiles'): string
    {
        $weight = self::FOLLOWER_WEIGHT;

        return "({$table}.gift_points + {$table}.page_views + {$weight} * ("
            ."(select count(*) from promoter_followers where promoter_followers.promoter_profile_id = {$table}.id)"
            ." + (select count(*) from promoter_guest_followers where promoter_guest_followers.promoter_profile_id = {$table}.id)))";
    }
}
