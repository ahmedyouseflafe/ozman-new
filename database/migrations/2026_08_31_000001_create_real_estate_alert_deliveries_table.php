<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_alert_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('real_estate_alert_id')->constrained()->cascadeOnDelete();
            $table->foreignId('real_estate_property_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 40);
            $table->string('channel', 20);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('provider_reference')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['real_estate_alert_id', 'real_estate_property_id', 'fingerprint'], 'real_estate_alert_delivery_unique');
            $table->index(['status', 'attempts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_alert_deliveries');
    }
};
