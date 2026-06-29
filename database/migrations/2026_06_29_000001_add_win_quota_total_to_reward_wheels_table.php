<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->unsignedSmallInteger('win_quota_total')->default(200)->after('max_order_total');
        });
    }

    public function down(): void
    {
        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->dropColumn('win_quota_total');
        });
    }
};
