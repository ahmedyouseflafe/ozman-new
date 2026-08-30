<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('channel', 20)->default('email');
            $table->json('filters');
            $table->string('locale', 5)->default('ar');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_alerts');
    }
};
