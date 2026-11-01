<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Set only when a signed-in community customer places the order.
        Schema::table('front_orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('shop_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
