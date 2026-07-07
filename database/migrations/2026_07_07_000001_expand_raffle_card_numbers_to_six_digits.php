<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffle_cards', function (Blueprint $table) {
            $table->string('card_number', 6)->change();
        });

        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->string('card_number', 6)->change();
        });
    }

    public function down(): void
    {
        Schema::table('raffle_cards', function (Blueprint $table) {
            $table->string('card_number', 5)->change();
        });

        Schema::table('raffle_entries', function (Blueprint $table) {
            $table->string('card_number', 5)->change();
        });
    }
};
