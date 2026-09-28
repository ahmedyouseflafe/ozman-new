<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('home_section', 40)->nullable()->after('catalog_type')->index();
        });

        DB::table('shops')
            ->where(function ($query) {
                $query
                    ->where('slug', 'curly-waves')
                    ->orWhereRaw('LOWER(name) = ?', ['curly waves']);
            })
            ->update(['home_section' => 'skin_care']);
    }

    public function down(): void
    {
        DB::table('shops')
            ->where('home_section', 'skin_care')
            ->where(function ($query) {
                $query
                    ->where('slug', 'curly-waves')
                    ->orWhereRaw('LOWER(name) = ?', ['curly waves']);
            })
            ->update(['home_section' => null]);

        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndex(['home_section']);
            $table->dropColumn('home_section');
        });
    }
};
