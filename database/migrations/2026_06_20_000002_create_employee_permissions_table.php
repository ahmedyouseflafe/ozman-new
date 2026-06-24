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
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','company_admin','shop_owner','agent','distributor','marketer','employee','customer') NOT NULL DEFAULT 'customer'");
        }

        Schema::create('employee_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission');
            $table->timestamps();

            $table->unique(['user_id', 'permission']);
            $table->index('permission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_permissions');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin','company_admin','shop_owner','agent','distributor','marketer','customer') NOT NULL DEFAULT 'customer'");
        }
    }
};
