<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A product's 3D model for "see it in your room" (AR), and the jobs that make one from the product's photo. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('model_3d')->nullable()->after('video');
            // Real size in centimetres {w, d, h}, so the piece shows at its true size in the room.
            $table->json('model_3d_size')->nullable()->after('model_3d');
        });

        Schema::create('product_3d_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 20)->default('meshy');
            $table->string('provider_task_id')->nullable();
            // queued → running → ready (waiting for the owner) → approved | discarded; or failed
            $table->string('status', 20)->default('queued');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('result_path')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_3d_jobs');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['model_3d', 'model_3d_size']);
        });
    }
};
