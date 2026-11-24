<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Searches that found nothing ("lost demand"). Anonymous: no user, no IP, only the words and where they were typed. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_misses', function (Blueprint $table) {
            $table->id();
            $table->string('term', 100);
            $table->string('shown', 100);
            $table->string('source', 20);
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('catalog_type', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['shop_id', 'created_at']);
            $table->index(['catalog_type', 'created_at']);
            $table->index(['term', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_misses');
    }
};
