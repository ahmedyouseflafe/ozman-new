<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('close_time');
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('payment_account_holder')->nullable()->after('payment_provider');
            $table->string('payment_account_number')->nullable()->after('payment_account_holder');
            $table->string('payment_iban')->nullable()->after('payment_account_number');
            $table->string('payment_wallet_number')->nullable()->after('payment_iban');
            $table->text('payment_notes')->nullable()->after('payment_wallet_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_provider',
                'payment_account_holder',
                'payment_account_number',
                'payment_iban',
                'payment_wallet_number',
                'payment_notes',
            ]);
        });
    }
};
