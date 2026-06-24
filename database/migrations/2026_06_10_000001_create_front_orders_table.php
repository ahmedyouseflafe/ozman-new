<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reward_wheel_id')->nullable()->constrained('reward_wheels')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_whatsapp')->nullable();
            $table->text('customer_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('map_link')->nullable();
            $table->json('items')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('order_channel', 30)->default('whatsapp');
            $table->string('payment_method', 60)->nullable();
            $table->string('payment_status', 60)->default('pending');
            $table->string('status', 40)->default('new');
            $table->string('reward_label')->nullable();
            $table->decimal('reward_discount_value', 10, 2)->nullable();
            $table->string('reward_discount_type', 30)->nullable();
            $table->string('reward_gift_image')->nullable();
            $table->string('reward_color', 30)->nullable();
            $table->timestamp('reward_won_at')->nullable();
            $table->timestamps();

            $table->index(['order_channel', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['reward_wheel_id', 'reward_won_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_orders');
    }
};
