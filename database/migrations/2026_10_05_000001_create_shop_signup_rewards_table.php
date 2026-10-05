<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_signup_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone_hash', 64);
            $table->string('title');
            $table->json('segments');
            $table->unsignedTinyInteger('selected_index')->nullable();
            $table->timestamp('spun_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('front_order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['shop_id', 'phone_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_signup_rewards');
    }
};
