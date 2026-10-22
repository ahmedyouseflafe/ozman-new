<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 60);
            $table->string('event_key')->nullable()->unique();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title');
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['shop_id', 'created_at']);
            $table->index(['kind', 'id']);
        });
        Schema::create('shop_visit_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->string('visitor_hash', 64);
            $table->unsignedInteger('page_views')->default(0);
            $table->unique(['shop_id', 'day', 'visitor_hash']);
            $table->index('day');
        });
        Schema::create('shop_analytics_reads', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_event_id')->default(0);
        });
        Schema::table('front_orders', function (Blueprint $table) {
            $table->timestamp('sale_confirmed_at')->nullable()->index();
            $table->boolean('sale_date_estimated')->default(false);
            $table->string('checkout_token_hash', 64)->nullable()->unique();
            $table->string('checkout_payload_hash', 64)->nullable();
            $table->index(['shop_id', 'created_at']);
        });
        // Older orders do not have a completion timestamp. Keep that limitation explicit.
        DB::table('front_orders')->whereIn('status', ['completed', 'delivered'])->update([
            'sale_confirmed_at' => DB::raw('COALESCE(delivered_at, updated_at, created_at)'),
            'sale_date_estimated' => true,
        ]);
        // Publish readiness last so requests during deployment never use a partial schema.
        Schema::create('shop_analytics_state', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
        });
        DB::table('shop_analytics_state')->insert(['id' => 1, 'started_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_analytics_state');
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'created_at']);
            $table->dropIndex(['sale_confirmed_at']);
            $table->dropUnique(['checkout_token_hash']);
            $table->dropColumn(['sale_confirmed_at', 'sale_date_estimated', 'checkout_token_hash', 'checkout_payload_hash']);
        });
        Schema::dropIfExists('shop_analytics_reads');
        Schema::dropIfExists('shop_visit_days');
        Schema::dropIfExists('shop_activity_events');
    }
};
