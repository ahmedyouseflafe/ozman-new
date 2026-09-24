<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('service', 120);
            $table->dateTime('appointment_at');
            // The unique key makes two simultaneous booking requests for one slot impossible.
            $table->string('slot_key', 32)->nullable();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 60);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('new');
            $table->string('locale', 10)->nullable();
            $table->timestamp('whatsapp_opened_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'slot_key']);
            $table->index(['shop_id', 'appointment_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_appointments');
    }
};
