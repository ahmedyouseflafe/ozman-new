<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['shop_id', 'is_active']);
        });

        Schema::table('front_orders', function (Blueprint $table) {
            $table->foreignId('restaurant_table_id')->nullable()->after('shop_id')->constrained()->nullOnDelete();
            $table->string('order_type', 30)->nullable()->after('order_channel');
            $table->text('customer_notes')->nullable()->after('customer_address');
            $table->index(['shop_id', 'order_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'order_type', 'status']);
            $table->dropConstrainedForeignId('restaurant_table_id');
            $table->dropColumn(['order_type', 'customer_notes']);
        });
        Schema::dropIfExists('restaurant_tables');
    }
};
