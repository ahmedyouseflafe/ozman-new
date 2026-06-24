<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_wheel_segments', function (Blueprint $table) {
            $table->unsignedSmallInteger('win_quota')->default(1)->after('gift_image');
        });
    }

    public function down(): void
    {
        Schema::table('reward_wheel_segments', function (Blueprint $table) {
            $table->dropColumn('win_quota');
        });
    }
};
