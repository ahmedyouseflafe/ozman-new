<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','company_admin','shop_owner','agent','distributor','marketer','customer') NOT NULL DEFAULT 'customer'");
        }

        Schema::create('reward_wheel_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_wheel_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->string('answer')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_wheel_questions');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','company_admin','shop_owner','agent','distributor','customer') NOT NULL DEFAULT 'customer'");
        }
    }
};
