<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // The shop's own profile on Zernio, which holds its connected social accounts.
            $table->string('zernio_profile_id', 64)->nullable();
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('caption');
            $table->string('media_url', 2048)->nullable();
            $table->json('targets');
            $table->timestamp('scheduled_for')->nullable();
            $table->string('status', 32)->default('scheduled');
            $table->string('zernio_post_id', 64)->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::table('shops', fn (Blueprint $table) => $table->dropColumn('zernio_profile_id'));
    }
};
