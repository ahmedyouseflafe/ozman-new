<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Follows by visitors without an account. Only the SHA-256 of the device cookie is stored.
        Schema::create('promoter_guest_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promoter_profile_id')->constrained()->cascadeOnDelete();
            $table->char('guest_token_hash', 64);
            $table->timestamps();
            $table->unique(['promoter_profile_id', 'guest_token_hash'], 'promoter_guest_followers_pair_unique');
            $table->index('guest_token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promoter_guest_followers');
    }
};
