<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_registrations', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('type');
            $table->string('public_token', 64)->nullable()->unique()->after('status');
            $table->timestamp('approved_at')->nullable()->after('public_token');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });

        DB::table('visitor_registrations')->orderBy('id')->each(function ($registration) {
            DB::table('visitor_registrations')->where('id', $registration->id)->update([
                'status' => $registration->type === 'merchant' ? 'pending' : 'approved',
                'public_token' => Str::random(64),
                'approved_at' => $registration->type === 'merchant' ? null : now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('visitor_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropUnique(['public_token']);
            $table->dropColumn(['status', 'public_token', 'approved_at']);
        });
    }
};
