<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One-time QR codes a shop shows at the till so a customer gets points for a purchase made in the shop. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_point_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('points');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->foreignId('loyalty_point_entry_id')->nullable()->constrained('loyalty_point_entries')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'claimed_by', 'claimed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_point_codes');
    }
};
