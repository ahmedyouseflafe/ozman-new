<?php

use App\Models\PromoterProfile;
use App\Services\PromoterProfileUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promoter_profile_slugs', function (Blueprint $table) {
            $table->string('slug', 80)->primary();
            $table->foreignId('promoter_profile_id')->constrained()->cascadeOnDelete();
        });
        // Reserve every existing link before updating any profile's canonical URL.
        PromoterProfile::orderBy('id')->chunkById(100, function ($profiles) {
            foreach ($profiles as $profile) {
                DB::table('promoter_profile_slugs')->insert(['slug' => $profile->slug, 'promoter_profile_id' => $profile->id]);
            }
        });
        PromoterProfile::orderBy('id')->chunkById(100, function ($profiles) {
            foreach ($profiles as $profile) {
                app(PromoterProfileUrl::class)->sync($profile);
            }
        });
    }

    public function down(): void
    {
        // Keep the latest canonical URLs; only historical redirects are removed.
        Schema::dropIfExists('promoter_profile_slugs');
    }
};
