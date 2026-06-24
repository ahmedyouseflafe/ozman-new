<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_campaigns', function (Blueprint $table) {
            $table->string('media')->nullable()->change();
            $table->string('offer_type')->nullable()->after('media');
            $table->unsignedInteger('offer_quantity')->nullable()->after('offer_type');
            $table->decimal('offer_price', 10, 2)->nullable()->after('offer_quantity');
            $table->text('offer_note')->nullable()->after('offer_price');
            $table->date('starts_at')->nullable()->after('offer_note');
            $table->date('ends_at')->nullable()->after('starts_at');
            $table->boolean('is_active')->default(true)->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'offer_type',
                'offer_quantity',
                'offer_price',
                'offer_note',
                'starts_at',
                'ends_at',
                'is_active',
            ]);
            $table->string('media')->nullable(false)->change();
        });
    }
};
