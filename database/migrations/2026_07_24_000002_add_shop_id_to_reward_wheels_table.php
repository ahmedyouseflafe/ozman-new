<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->foreignId('shop_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            $table->index(['shop_id', 'wheel_type', 'is_active']);
        });

        $ozmanShopId = DB::table('shops')->where('slug', 'ozman')->value('id');
        if ($ozmanShopId) {
            DB::table('reward_wheels')
                ->where('wheel_type', 'purchase_amount')
                ->whereNull('shop_id')
                ->update(['shop_id' => $ozmanShopId]);
        }
    }

    public function down(): void
    {
        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'wheel_type', 'is_active']);
            $table->dropConstrainedForeignId('shop_id');
        });
    }
};
