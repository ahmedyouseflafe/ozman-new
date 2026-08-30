<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('real_estate_properties', function (Blueprint $table): void {
            $table->date('available_from')->nullable()->after('published_at');
            $table->boolean('is_new_project')->default(false)->after('furnished');
            $table->boolean('has_elevator')->default(false)->after('is_new_project');
            $table->boolean('has_balcony')->default(false)->after('has_elevator');
            $table->boolean('has_garden')->default(false)->after('has_balcony');
            $table->boolean('has_storage')->default(false)->after('has_garden');
            $table->boolean('has_air_conditioning')->default(false)->after('has_storage');
            $table->json('nearby_places')->nullable()->after('amenities');
            $table->string('video_url')->nullable()->after('nearby_places');
            $table->string('virtual_tour_url')->nullable()->after('video_url');
            $table->decimal('monthly_fees', 10, 2)->nullable()->after('price');

            $table->index(['city', 'neighborhood', 'purpose']);
            $table->index(['rooms', 'bathrooms', 'area']);
        });
    }

    public function down(): void
    {
        Schema::table('real_estate_properties', function (Blueprint $table): void {
            $table->dropIndex(['city', 'neighborhood', 'purpose']);
            $table->dropIndex(['rooms', 'bathrooms', 'area']);
            $table->dropColumn([
                'available_from', 'is_new_project', 'has_elevator', 'has_balcony',
                'has_garden', 'has_storage', 'has_air_conditioning', 'nearby_places',
                'video_url', 'virtual_tour_url', 'monthly_fees',
            ]);
        });
    }
};
