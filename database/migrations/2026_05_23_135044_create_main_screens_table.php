<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('main_screens', function (Blueprint $table) {

            $table->id();

            $table->string('title');

            $table->enum('type', [
                'image',
                'video',
                'youtube'
            ]);

            $table->string('media');

            $table->integer('duration')->default(10);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_screens');
    }
};
