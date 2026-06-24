<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->string('wheel_type', 30)->default('customer_signup')->after('key');
            $table->decimal('min_order_total', 10, 2)->nullable()->after('title');
            $table->decimal('max_order_total', 10, 2)->nullable()->after('min_order_total');
        });
    }

    public function down(): void
    {
        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->dropColumn(['wheel_type', 'min_order_total', 'max_order_total']);
        });
    }
};
