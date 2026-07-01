<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_screens', function (Blueprint $table) {
            $table->string('placement', 20)->default('top')->after('media');
            $table->index(['placement', 'is_active', 'created_at'], 'main_screens_placement_active_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('main_screens', function (Blueprint $table) {
            $table->dropIndex('main_screens_placement_active_created_idx');
            $table->dropColumn('placement');
        });
    }
};
