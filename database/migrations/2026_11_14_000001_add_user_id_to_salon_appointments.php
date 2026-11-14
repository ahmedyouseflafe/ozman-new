<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bookings made from the app remember the customer's account, so "my bookings" can list them.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_appointments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('shop_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salon_appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
