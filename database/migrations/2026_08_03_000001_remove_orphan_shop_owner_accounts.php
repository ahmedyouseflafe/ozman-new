<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'shop_owner')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('shops')
                    ->whereColumn('shops.user_id', 'users.id');
            })
            ->delete();
    }

    public function down(): void
    {
        // الحساب اليتيم لم يكن مرتبطاً بمتجر يمكن استعادته.
    }
};
