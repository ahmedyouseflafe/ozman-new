<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_delivery_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('front_order_id')->constrained('front_orders')->cascadeOnDelete();
            $table->foreignId('restaurant_driver_id')->constrained('restaurant_drivers')->cascadeOnDelete();
            $table->string('status', 20)->default('offered');
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['front_order_id', 'restaurant_driver_id'], 'restaurant_delivery_offer_unique');
            $table->index(['restaurant_driver_id', 'status'], 'restaurant_delivery_offer_driver_status');
            $table->index(['front_order_id', 'status'], 'restaurant_delivery_offer_order_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_delivery_offers');
    }
};
