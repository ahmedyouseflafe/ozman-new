<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->whereNull('agent_id')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($category): void {
                $agentIds = DB::table('products')
                    ->where('category_id', $category->id)
                    ->whereNotNull('agent_id')
                    ->distinct()
                    ->pluck('agent_id');

                if ($agentIds->count() !== 1) {
                    return;
                }

                $hasStoreProducts = DB::table('products')
                    ->where('category_id', $category->id)
                    ->whereNull('agent_id')
                    ->exists();

                if ($hasStoreProducts) {
                    return;
                }

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['agent_id' => $agentIds->first()]);
            });
    }

    public function down(): void
    {
        // Data backfill only; keeping category ownership intact on rollback.
    }
};
