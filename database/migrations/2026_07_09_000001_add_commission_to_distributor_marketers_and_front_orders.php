<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_marketers', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('email');
        });

        Schema::table('front_orders', function (Blueprint $table) {
            $table->decimal('marketer_commission_rate', 5, 2)->nullable()->after('marketing_source');
            $table->decimal('marketer_commission_amount', 10, 2)->nullable()->after('marketer_commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropColumn(['marketer_commission_rate', 'marketer_commission_amount']);
        });

        Schema::table('distributor_marketers', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
