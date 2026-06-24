<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('package_price', 10, 2)->nullable()->after('merchant_price');
            $table->decimal('pallet_price', 10, 2)->nullable()->after('package_price');
            $table->decimal('carton_price', 10, 2)->nullable()->after('pallet_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['package_price', 'pallet_price', 'carton_price']);
        });
    }
};
