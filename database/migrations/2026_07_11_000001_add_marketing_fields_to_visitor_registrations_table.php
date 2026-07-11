<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('visitor_registrations', 'shop_id')) {
            Schema::table('visitor_registrations', function (Blueprint $table) {
                $table->foreignId('shop_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('visitor_registrations', 'distributor_id')) {
            Schema::table('visitor_registrations', function (Blueprint $table) {
                $table->foreignId('distributor_id')
                    ->nullable()
                    ->after('shop_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('visitor_registrations', 'distributor_marketer_id')) {
            Schema::table('visitor_registrations', function (Blueprint $table) {
                $table->foreignId('distributor_marketer_id')
                    ->nullable()
                    ->after('distributor_id')
                    ->constrained('distributor_marketers')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('visitor_registrations', 'marketing_source')) {
            Schema::table('visitor_registrations', function (Blueprint $table) {
                $table->string('marketing_source', 40)
                    ->nullable()
                    ->after('distributor_marketer_id');
            });
        }

        Schema::table('visitor_registrations', function (Blueprint $table) {
            $table->index(['distributor_marketer_id', 'type', 'created_at'], 'vr_marketer_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_registrations', function (Blueprint $table) {
            $table->dropIndex('vr_marketer_type_created_idx');
            $table->dropColumn('marketing_source');
            $table->dropConstrainedForeignId('distributor_marketer_id');
            $table->dropConstrainedForeignId('distributor_id');
            $table->dropConstrainedForeignId('shop_id');
        });
    }
};
