<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('customer_carton_price', 10, 2)->nullable()->after('discount_price');
            $table->decimal('customer_pallet_price', 10, 2)->nullable()->after('customer_carton_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['customer_carton_price', 'customer_pallet_price']);
        });
    }
};
