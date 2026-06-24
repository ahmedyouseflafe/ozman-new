<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
            $table->json('description_translations')->nullable()->after('description');
        });

        Schema::table('product_campaigns', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('offer_note_translations')->nullable()->after('offer_note');
        });
    }

    public function down(): void
    {
        Schema::table('product_campaigns', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'offer_note_translations']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_translations', 'description_translations']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('name_translations');
        });
    }
};
