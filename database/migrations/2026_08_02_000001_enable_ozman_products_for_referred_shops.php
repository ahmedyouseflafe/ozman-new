<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shops') || ! Schema::hasColumn('shops', 'show_ozman_products')) {
            return;
        }

        DB::table('shops')
            ->where(function ($query) {
                $query->whereNotNull('distributor_id')
                    ->orWhereNotNull('distributor_marketer_id');
            })
            ->where('show_ozman_products', false)
            ->update(['show_ozman_products' => true]);
    }

    public function down(): void
    {
        // لا نطفئ الخيار عند التراجع حتى لا نلغي اختياراً فعّله صاحب متجر يدوياً.
    }
};
