<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** "المتجر الحر": community members list their own products; buyers order through the site. */
    public function up(): void
    {
        Schema::create('market_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('category', 40);
            $table->string('condition', 10);
            $table->string('city', 60);
            $table->unsignedSmallInteger('quantity')->default(1);
            // active: shown; hidden: the seller hid it; sold: no stock left; removed: hidden by the admin.
            $table->string('status', 10)->default('active');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['status', 'category']);
        });

        Schema::create('market_listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('market_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('market_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            // Copies, so the order still reads correctly after the listing changes or is deleted.
            $table->string('listing_title', 120);
            $table->decimal('unit_price', 10, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('total', 10, 2);
            $table->string('buyer_name', 100);
            $table->string('buyer_phone', 30);
            $table->string('city', 60);
            $table->string('address', 255);
            $table->string('note', 500)->nullable();
            // pending → accepted → completed; pending → rejected (seller) / cancelled (buyer); accepted → cancelled (seller).
            $table->string('status', 10)->default('pending');
            $table->boolean('stock_taken')->default(false);
            $table->timestamps();
            $table->index(['seller_id', 'status']);
            $table->index(['buyer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_orders');
        Schema::dropIfExists('market_listing_images');
        Schema::dropIfExists('market_listings');
    }
};
