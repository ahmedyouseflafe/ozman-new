<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('front_orders', fn(Blueprint $table) => $table->json('purchase_reward')->nullable()); }
    public function down(): void { Schema::table('front_orders', fn(Blueprint $table) => $table->dropColumn('purchase_reward')); }
};
