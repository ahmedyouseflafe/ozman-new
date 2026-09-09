<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','company_admin','shop_owner','agent','distributor','marketer','employee','restaurant_driver','customer') NOT NULL DEFAULT 'customer'");
        }

        Schema::create('restaurant_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shop_id', 'is_active']);
        });

        Schema::table('front_orders', function (Blueprint $table) {
            $table->foreignId('restaurant_driver_id')->nullable()->after('restaurant_table_id')
                ->constrained('restaurant_drivers')->nullOnDelete();
            $table->timestamp('driver_assigned_at')->nullable()->after('customer_push_token');
            $table->timestamp('picked_up_at')->nullable()->after('driver_assigned_at');
            $table->timestamp('delivered_at')->nullable()->after('picked_up_at');
            $table->index(['restaurant_driver_id', 'status'], 'restaurant_driver_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropIndex('restaurant_driver_status_idx');
            $table->dropConstrainedForeignId('restaurant_driver_id');
            $table->dropColumn(['driver_assigned_at', 'picked_up_at', 'delivered_at']);
        });

        Schema::dropIfExists('restaurant_drivers');

        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'restaurant_driver')->update(['role' => 'customer']);
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','company_admin','shop_owner','agent','distributor','marketer','employee','customer') NOT NULL DEFAULT 'customer'");
        }
    }
};
