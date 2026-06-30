<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_marketers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('distributor_id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('distributor_marketers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
