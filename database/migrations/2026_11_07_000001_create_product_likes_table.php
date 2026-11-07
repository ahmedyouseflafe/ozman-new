<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Guests are counted per device: sha256 of a random cookie, never the raw value.
            $table->char('visitor_hash', 64)->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'user_id']);
            $table->unique(['product_id', 'visitor_hash']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('likes_count'));
        Schema::dropIfExists('product_likes');
    }
};
