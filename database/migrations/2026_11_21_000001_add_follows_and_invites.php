<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers following a shop get a notification when it puts something on offer.
        Schema::create('shop_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['shop_id', 'user_id']);
            $table->index('user_id');
        });

        // "Invite a friend": each customer has a code; the friend's first completed order rewards both.
        Schema::table('users', function (Blueprint $table) {
            $table->string('invite_code', 12)->nullable()->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invite_rewarded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
            $table->dropUnique(['invite_code']);
            $table->dropColumn(['invite_code', 'invite_rewarded_at']);
        });
        Schema::dropIfExists('shop_follows');
    }
};
