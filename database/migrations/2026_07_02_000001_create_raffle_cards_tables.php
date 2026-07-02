<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_number', 5)->unique();
            $table->string('prize_title');
            $table->string('prize_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('used_at')->nullable();
            $table->string('used_customer_name')->nullable();
            $table->string('used_customer_phone')->nullable();
            $table->string('used_customer_whatsapp')->nullable();
            $table->json('used_customer_payload')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'used_at']);
        });

        Schema::create('raffle_entries', function (Blueprint $table) {
            $table->id();
            $table->string('card_number', 5)->unique();
            $table->foreignId('raffle_card_id')->nullable()->constrained('raffle_cards')->nullOnDelete();
            $table->string('outcome', 30)->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_whatsapp')->nullable();
            $table->json('customer_payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_entries');
        Schema::dropIfExists('raffle_cards');
    }
};
