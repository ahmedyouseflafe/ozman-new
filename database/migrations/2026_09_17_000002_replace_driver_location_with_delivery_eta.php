<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropColumn([
                'driver_latitude', 'driver_longitude',
                'driver_location_accuracy_meters', 'driver_location_at',
            ]);
            $table->timestamp('estimated_delivery_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropColumn('estimated_delivery_at');
            $table->decimal('driver_latitude', 10, 7)->nullable();
            $table->decimal('driver_longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('driver_location_accuracy_meters')->nullable();
            $table->timestamp('driver_location_at')->nullable();
        });
    }
};
