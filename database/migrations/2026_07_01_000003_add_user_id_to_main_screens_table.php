<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_screens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'created_at'], 'main_screens_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('main_screens', function (Blueprint $table) {
            $table->dropIndex('main_screens_user_created_idx');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
