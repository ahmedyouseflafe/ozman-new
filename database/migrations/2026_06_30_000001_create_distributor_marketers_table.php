<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_marketers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('tracking_code')->unique();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['distributor_id', 'is_active']);
        });

        Schema::table('front_orders', function (Blueprint $table) {
            $table->foreignId('distributor_id')->nullable()->after('shop_id')->constrained()->nullOnDelete();
            $table->foreignId('distributor_marketer_id')->nullable()->after('distributor_id')->constrained('distributor_marketers')->nullOnDelete();
            $table->string('marketing_source', 40)->nullable()->after('distributor_marketer_id');

            $table->index(['distributor_marketer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('front_orders', function (Blueprint $table) {
            $table->dropIndex(['distributor_marketer_id', 'created_at']);
            $table->dropConstrainedForeignId('distributor_marketer_id');
            $table->dropConstrainedForeignId('distributor_id');
            $table->dropColumn('marketing_source');
        });

        Schema::dropIfExists('distributor_marketers');
    }
};
