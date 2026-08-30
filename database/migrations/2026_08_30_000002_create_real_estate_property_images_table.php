<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_property_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('real_estate_properties')->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['property_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_property_images');
    }
};
