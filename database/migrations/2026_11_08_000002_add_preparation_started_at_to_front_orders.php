<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            // When the kitchen started the order; with estimated_preparation_minutes it drives the countdown.
            $table->timestamp('preparation_started_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', fn (Blueprint $table) => $table->dropColumn('preparation_started_at'));
    }
};
