<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->index(['is_active', 'slug'], 'shops_active_slug_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['shop_id', 'is_active', 'agent_id'], 'categories_shop_active_agent_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['shop_id', 'is_active', 'agent_id'], 'products_shop_active_agent_idx');
            $table->index(['category_id', 'is_active', 'agent_id'], 'products_category_active_agent_idx');
            $table->index(['shop_id', 'is_featured', 'created_at'], 'products_shop_featured_created_idx');
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $table->index(['shop_id', 'is_active', 'sort_order'], 'ads_shop_active_sort_idx');
        });

        Schema::table('main_screens', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'main_screens_active_created_idx');
        });

        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->index(['wheel_type', 'is_active', 'min_order_total'], 'reward_wheels_type_active_min_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reward_wheels', function (Blueprint $table) {
            $table->dropIndex('reward_wheels_type_active_min_idx');
        });

        Schema::table('main_screens', function (Blueprint $table) {
            $table->dropIndex('main_screens_active_created_idx');
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropIndex('ads_shop_active_sort_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_shop_active_agent_idx');
            $table->dropIndex('products_category_active_agent_idx');
            $table->dropIndex('products_shop_featured_created_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_shop_active_agent_idx');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndex('shops_active_slug_idx');
        });
    }
};
