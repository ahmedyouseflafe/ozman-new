<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->string('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->text('public_key');
            $table->string('auth_token', 255);
            $table->string('content_encoding', 30)->default('aes128gcm');
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['source_shop_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_push_subscriptions');
    }
};
