<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('viewer_key', 64);
            $table->string('viewer_name')->nullable();
            $table->string('source', 20)->default('web');
            $table->timestamp('viewed_at');
            $table->timestamp('last_viewed_at');
            $table->timestamps();

            $table->unique(['shop_story_id', 'viewer_key']);
            $table->index(['shop_story_id', 'last_viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_story_views');
    }
};
