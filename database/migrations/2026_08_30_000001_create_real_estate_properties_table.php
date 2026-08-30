<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('slug');
            $table->string('purpose', 20);
            $table->string('property_type', 40);
            $table->string('title');
            $table->json('title_translations')->nullable();
            $table->longText('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('ILS');
            $table->string('city');
            $table->string('neighborhood')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('rooms', 4, 1)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->smallInteger('floor')->nullable();
            $table->unsignedSmallInteger('building_floors')->nullable();
            $table->boolean('furnished')->default(false);
            $table->unsignedSmallInteger('parking_spaces')->default(0);
            $table->json('amenities')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
            $table->unique(['shop_id', 'reference']);
            $table->index(['shop_id', 'status', 'published_at']);
            $table->index(['purpose', 'property_type', 'city', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_properties');
    }
};
