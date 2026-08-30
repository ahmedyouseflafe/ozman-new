<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('real_estate_properties')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->string('source', 30)->default('website');
            $table->string('status', 30)->default('new');
            $table->timestamp('viewing_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_leads');
    }
};
