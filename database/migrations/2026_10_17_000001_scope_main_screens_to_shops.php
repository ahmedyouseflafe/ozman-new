<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_screens', function (Blueprint $table) {
            // A deleted shop must never turn its screens into platform screens.
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('needs_shop_assignment')->default(false);
            $table->index(['shop_id', 'is_active', 'placement']);
        });

        User::whereIn('id', DB::table('main_screens')->whereNotNull('user_id')->select('user_id'))
            ->whereNotIn('role', ['super_admin', 'employee'])
            ->each(function (User $user) {
                $shopIds = $user->accessibleShopIds();
                // The old schema did not record which shop was selected. Never guess
                // for multi-shop accounts, and never publish their media globally.
                DB::table('main_screens')->where('user_id', $user->id)->update([
                    'shop_id' => count($shopIds) === 1 ? $shopIds[0] : null,
                    'needs_shop_assignment' => count($shopIds) !== 1,
                ]);
            });
    }

    public function down(): void
    {
        // Rollback must not republish shop media through the old global queries.
        DB::table('main_screens')->whereNotNull('shop_id')->orWhere('needs_shop_assignment', true)
            ->update(['is_active' => false]);
        Schema::table('main_screens', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'is_active', 'placement']);
            $table->dropConstrainedForeignId('shop_id');
            $table->dropColumn('needs_shop_assignment');
        });
    }
};
