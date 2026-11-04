<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // @username for community accounts (login by email or username, like Instagram).
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
        });

        // Promoters who sign up themselves wait for admin approval; existing pages are already approved.
        Schema::table('promoter_profiles', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('is_active');
        });
        DB::table('promoter_profiles')->update(['approved_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        Schema::table('promoter_profiles', fn (Blueprint $table) => $table->dropColumn('approved_at'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
