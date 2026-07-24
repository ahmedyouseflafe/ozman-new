<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('catalog_type', 40)->default('general')->after('slug')->index();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('catalog_attributes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('catalog_attributes'));
        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndex(['catalog_type']);
            $table->dropColumn('catalog_type');
        });
    }
};
