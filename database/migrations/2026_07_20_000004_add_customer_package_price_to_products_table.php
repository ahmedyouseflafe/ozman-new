<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('customer_package_price', 10, 2)->nullable()->after('discount_price');
            $table->boolean('show_customer_package_price')->default(true)->after('customer_package_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['customer_package_price', 'show_customer_package_price']);
        });
    }
};
