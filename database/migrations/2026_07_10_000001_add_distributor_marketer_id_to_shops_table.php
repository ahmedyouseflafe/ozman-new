<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->foreignId('distributor_marketer_id')
                ->nullable()
                ->after('user_id')
                ->constrained('distributor_marketers')
                ->nullOnDelete();

            $table->index(['distributor_marketer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropIndex(['distributor_marketer_id', 'is_active']);
            $table->dropConstrainedForeignId('distributor_marketer_id');
        });
    }
};
