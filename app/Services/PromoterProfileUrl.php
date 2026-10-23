<?php

namespace App\Services;

use App\Models\PromoterProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromoterProfileUrl
{
    public function sync(PromoterProfile $profile): void
    {
        DB::transaction(function () use ($profile) {
            $current = PromoterProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            DB::table('promoter_profile_slugs')->insertOrIgnore(['slug' => $current->slug, 'promoter_profile_id' => $current->id]);
            $base = Str::slug($current->display_name);
            if ($base === '') {
                $base = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($current->display_name)), '-');
            }
            $base = mb_substr($base ?: 'promoter-'.$current->id, 0, 65);
            for ($suffix = 1; ; $suffix++) {
                $candidate = $base.($suffix === 1 ? '' : '-'.$suffix);
                if (PromoterProfile::where('slug', $candidate)->where('id', '!=', $current->id)->exists()) {
                    continue;
                }
                // The unique registry also reserves old links, including concurrent claims.
                DB::table('promoter_profile_slugs')->insertOrIgnore(['slug' => $candidate, 'promoter_profile_id' => $current->id]);
                $owner = DB::table('promoter_profile_slugs')->where('slug', $candidate)->value('promoter_profile_id');
                if ((int) $owner !== (int) $current->id) {
                    continue;
                }
                $current->update(['slug' => $candidate]);
                $profile->slug = $candidate;
                break;
            }
        });
    }
}
