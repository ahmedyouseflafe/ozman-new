<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimated_preparation_minutes')->nullable()->after('order_type');
            $table->string('customer_push_token', 512)->nullable()->after('estimated_preparation_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropColumn(['estimated_preparation_minutes', 'customer_push_token']);
        });
    }
};
