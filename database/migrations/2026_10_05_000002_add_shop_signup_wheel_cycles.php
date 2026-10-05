<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_signup_wheel_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->json('quotas');
            $table->json('remaining');
            $table->timestamps();
        });
        Schema::table('shop_signup_rewards', function (Blueprint $table) {
            $table->foreignId('cycle_id')->nullable()->constrained('shop_signup_wheel_cycles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shop_signup_rewards', fn (Blueprint $table) => $table->dropConstrainedForeignId('cycle_id'));
        Schema::dropIfExists('shop_signup_wheel_cycles');
    }
};
