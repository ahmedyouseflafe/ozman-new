<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gift catalog managed by Ozman admins (name, emoji and points cost).
        Schema::create('promoter_gifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('icon', 16);
            $table->unsignedInteger('points');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Every gift a customer sends to a promoter. Name, icon and points are a snapshot,
        // so editing the catalog later never rewrites history.
        Schema::create('promoter_gift_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promoter_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promoter_gift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promoter_post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gift_name', 60);
            $table->string('gift_icon', 16);
            $table->unsignedInteger('points');
            $table->timestamps();

            $table->index(['promoter_profile_id', 'created_at']);
        });

        // Ties each "gift" ledger row (one per shop the points came from) to its gift.
        Schema::table('loyalty_point_entries', function (Blueprint $table) {
            $table->foreignId('promoter_gift_send_id')->nullable()->after('front_order_id')
                ->constrained('promoter_gift_sends')->nullOnDelete();
            $table->index(['user_id', 'shop_id', 'status']);
        });

        // Reach counters shown to shops: gift points received and page visits.
        Schema::table('promoter_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('gift_points')->default(0)->after('is_active');
            $table->unsignedBigInteger('page_views')->default(0)->after('gift_points');
        });

        $now = now();
        DB::table('promoter_gifts')->insert([
            ['name' => 'وردة', 'icon' => '🌹', 'points' => 10, 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'قهوة', 'icon' => '☕', 'points' => 50, 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'هدية', 'icon' => '🎁', 'points' => 100, 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'تاج', 'icon' => '👑', 'points' => 500, 'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('promoter_profiles', function (Blueprint $table) {
            $table->dropColumn(['gift_points', 'page_views']);
        });
        Schema::table('loyalty_point_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'shop_id', 'status']);
            $table->dropConstrainedForeignId('promoter_gift_send_id');
        });
        Schema::dropIfExists('promoter_gift_sends');
        Schema::dropIfExists('promoter_gifts');
    }
};
