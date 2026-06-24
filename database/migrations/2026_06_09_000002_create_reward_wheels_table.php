<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_wheels', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reward_wheel_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_wheel_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('discount_value')->nullable();
            $table->string('discount_type', 20)->default('percent');
            $table->string('color', 20)->default('#00e5ff');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['reward_wheel_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_wheel_segments');
        Schema::dropIfExists('reward_wheels');
    }
};
