<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['main_screens', 'advertisements', 'products', 'product_campaigns'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('video_status', 20)->nullable()->index();
                $table->string('video_poster')->nullable();
                $table->text('video_error')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['video_status', 'video_poster', 'video_error']);
            });
        }
    }
};
