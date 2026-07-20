<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_package_price')->default(true)->after('carton_price');
            $table->boolean('show_carton_price')->default(true)->after('show_package_price');
            $table->boolean('show_pallet_price')->default(true)->after('show_carton_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['show_package_price', 'show_carton_price', 'show_pallet_price']);
        });
    }
};
