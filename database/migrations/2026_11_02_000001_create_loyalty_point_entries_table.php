<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Points ledger: the only source of truth for balances. A balance is the sum of confirmed rows.
        Schema::create('loyalty_point_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('front_order_id')->nullable()->constrained('front_orders')->nullOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');
            $table->string('type', 20); // earn, reverse, manual_adjust, redeem
            $table->string('status', 20); // pending, confirmed, cancelled
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['front_order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_entries');
    }
};
