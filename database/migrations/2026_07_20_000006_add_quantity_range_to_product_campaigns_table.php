<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_campaigns', function (Blueprint $table) {
            $table->unsignedInteger('min_quantity')->nullable()->after('offer_quantity');
            $table->unsignedInteger('max_quantity')->nullable()->after('min_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('product_campaigns', function (Blueprint $table) {
            $table->dropColumn(['min_quantity', 'max_quantity']);
        });
    }
};
