<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            // When the kitchen marked the order ready: with preparation_started_at it gives the real prep time.
            $table->timestamp('ready_at')->nullable();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('share_opens')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', fn (Blueprint $table) => $table->dropColumn('ready_at'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('share_opens'));
    }
};
