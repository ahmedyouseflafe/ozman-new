<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_customer_carton_price')->default(true)->after('customer_pallet_price');
            $table->boolean('show_customer_pallet_price')->default(true)->after('show_customer_carton_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['show_customer_carton_price', 'show_customer_pallet_price']);
        });
    }
};
