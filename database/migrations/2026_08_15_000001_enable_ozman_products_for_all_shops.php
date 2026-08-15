<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shops') || ! Schema::hasColumn('shops', 'show_ozman_products')) {
            return;
        }

        DB::table('shops')
            ->where('slug', '!=', 'ozman')
            ->update(['show_ozman_products' => true]);

        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('show_ozman_products')->default(true)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shops') || ! Schema::hasColumn('shops', 'show_ozman_products')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('show_ozman_products')->default(false)->change();
        });
    }
};
