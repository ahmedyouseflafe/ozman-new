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
      Schema::create('agents', function (Blueprint $table) {

    $table->id();

    $table->foreignId('shop_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->string('name');

    $table->string('image')->nullable();

    $table->string('phone')->nullable();

    $table->string('whatsapp')->nullable();

    $table->string('email')->nullable();

    $table->string('address')->nullable();

    $table->decimal('latitude', 10, 7)->nullable();

    $table->decimal('longitude', 10, 7)->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
