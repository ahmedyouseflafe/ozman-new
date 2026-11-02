<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How many visitors opened the linked shop from this post: the number shops care about most.
        Schema::table('promoter_posts', function (Blueprint $table) {
            $table->unsignedInteger('shop_clicks')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('promoter_posts', function (Blueprint $table) {
            $table->dropColumn('shop_clicks');
        });
    }
};
