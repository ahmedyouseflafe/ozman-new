<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_wheel_segments', function (Blueprint $table) {
            $table->string('gift_image')->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('reward_wheel_segments', function (Blueprint $table) {
            $table->dropColumn('gift_image');
        });
    }
};
