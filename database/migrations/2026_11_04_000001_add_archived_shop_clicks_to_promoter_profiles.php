<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Shop visits from expired stories that were cleaned up, so the page total never goes down. */
    public function up(): void
    {
        Schema::table('promoter_profiles', function (Blueprint $table) {
            $table->unsignedInteger('archived_shop_clicks')->default(0)->after('page_views');
        });
    }

    public function down(): void
    {
        Schema::table('promoter_profiles', function (Blueprint $table) {
            $table->dropColumn('archived_shop_clicks');
        });
    }
};
