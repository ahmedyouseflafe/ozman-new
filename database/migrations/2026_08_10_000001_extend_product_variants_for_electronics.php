<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('storage', 100)->nullable()->after('size');
            $table->string('ram', 100)->nullable()->after('storage');
            $table->unique(['product_id', 'storage', 'ram', 'color'], 'product_variant_option_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variant_option_unique');
            $table->dropColumn(['storage', 'ram']);
        });
    }
};
