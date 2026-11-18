<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A customer's rating of a finished order (one per order), shown on the shop's page; the owner can reply.
        Schema::create('shop_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('front_order_id')->unique()->constrained('front_orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['shop_id', 'is_visible', 'created_at']);
        });

        // Discount codes a shop gives out.
        Schema::create('shop_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('type', 10); // percent | amount
            $table->decimal('value', 10, 2);
            $table->decimal('min_total', 10, 2)->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_id', 'code']);
        });

        Schema::table('front_orders', function (Blueprint $table) {
            $table->foreignId('shop_coupon_id')->nullable()->constrained('shop_coupons')->nullOnDelete();
            $table->string('coupon_code', 40)->nullable();
            $table->decimal('coupon_discount', 10, 2)->default(0);
        });

        Schema::table('salon_appointments', function (Blueprint $table) {
            $table->timestamp('reminded_day_at')->nullable();
            $table->timestamp('reminded_hour_at')->nullable();
        });

        // Where the driver is while delivering, so the customer can follow the order on a map.
        Schema::table('restaurant_drivers', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_drivers', fn (Blueprint $table) => $table->dropColumn(['latitude', 'longitude', 'location_at']));
        Schema::table('salon_appointments', fn (Blueprint $table) => $table->dropColumn(['reminded_day_at', 'reminded_hour_at']));
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shop_coupon_id');
            $table->dropColumn(['coupon_code', 'coupon_discount']);
        });
        Schema::dropIfExists('shop_coupons');
        Schema::dropIfExists('shop_reviews');
    }
};
