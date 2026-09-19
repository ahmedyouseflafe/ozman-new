<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modular_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->string('slug');
            $table->text('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->string('icon_key', 40)->default('building');
            $table->string('cover_image')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
            $table->index(['shop_id', 'is_active', 'position']);
        });

        Schema::create('modular_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modular_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->string('slug');
            $table->text('short_description')->nullable();
            $table->json('short_description_translations')->nullable();
            $table->longText('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['modular_category_id', 'slug']);
            $table->index(['modular_category_id', 'is_active', 'position'], 'modular_services_listing_index');
        });

        Schema::create('modular_service_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modular_service_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('modular_option_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modular_service_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->text('help_text')->nullable();
            $table->json('help_text_translations')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['modular_service_id', 'key']);
        });

        Schema::create('modular_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modular_option_group_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->text('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['modular_option_group_id', 'key'], 'modular_option_value_group_key_unique');
        });

        Schema::create('modular_service_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modular_service_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 40);
            $table->string('city', 120)->nullable();
            $table->text('notes')->nullable();
            $table->json('selections');
            $table->string('locale', 5)->default('ar');
            $table->string('status', 30)->default('new');
            $table->timestamp('whatsapp_opened_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status', 'created_at'], 'modular_requests_dashboard_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modular_service_requests');
        Schema::dropIfExists('modular_option_values');
        Schema::dropIfExists('modular_option_groups');
        Schema::dropIfExists('modular_service_images');
        Schema::dropIfExists('modular_services');
        Schema::dropIfExists('modular_categories');
    }
};
