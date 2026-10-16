<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_signup_rewards', function (Blueprint $table) {
            $table->unsignedBigInteger('visitor_registration_id')->nullable()->change();
            $table->string('guest_token_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (DB::table('shop_signup_rewards')->whereNull('visitor_registration_id')->exists()) {
            throw new RuntimeException('Guest rewards exist; migrate these records before rolling back.');
        }
        Schema::table('shop_signup_rewards', function (Blueprint $table) {
            $table->dropUnique(['guest_token_hash']);
            $table->dropColumn('guest_token_hash');
            $table->unsignedBigInteger('visitor_registration_id')->nullable(false)->change();
        });
    }
};
