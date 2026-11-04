<?php

namespace App\Services;

use App\Models\PromoterPost;
use App\Models\PromoterProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Deletes stories (and their files) some days after they expired; their shop visits stay on the page total. */
class PromoterStoryCleanup
{
    public const KEEP_DAYS = 3;

    public function run(): int
    {
        $deleted = 0;
        PromoterPost::query()->where('kind', 'story')->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDays(self::KEEP_DAYS))
            ->orderBy('id')->chunkById(100, function ($posts) use (&$deleted) {
                foreach ($posts as $post) {
                    $paths = array_values(array_filter([$post->media, $post->poster]));
                    DB::transaction(function () use ($post) {
                        if ($post->shop_clicks > 0) {
                            PromoterProfile::query()->whereKey($post->promoter_profile_id)->increment('archived_shop_clicks', $post->shop_clicks);
                        }
                        $post->delete();
                    });
                    // Files go only after the row is gone, so a failed delete never leaves a story without its media.
                    if ($paths) {
                        Storage::disk('local')->delete($paths);
                    }
                    $deleted++;
                }
            });

        return $deleted;
    }
}
