<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('media');
            $table->string('type', 10);
            $table->string('caption', 300)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['shop_id', 'expires_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('shop_stories'); }
};
