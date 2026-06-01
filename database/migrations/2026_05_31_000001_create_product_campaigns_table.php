<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['image', 'video']);
            $table->string('media');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_campaigns');
    }
};
