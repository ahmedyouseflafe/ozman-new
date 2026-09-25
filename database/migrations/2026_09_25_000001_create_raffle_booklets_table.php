<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_booklets', function (Blueprint $table) {
            $table->id();
            $table->string('start_card_number', 6)->unique();
            $table->string('end_card_number', 6)->unique();
            $table->unsignedTinyInteger('cards_count')->default(48);
            $table->unsignedTinyInteger('winning_cards_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_card_number', 'end_card_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_booklets');
    }
};
