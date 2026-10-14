<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('shops', fn(Blueprint $table) => $table->unsignedInteger('customer_registration_version')->default(1)); }
    public function down(): void { Schema::table('shops', fn(Blueprint $table) => $table->dropColumn('customer_registration_version')); }
};
