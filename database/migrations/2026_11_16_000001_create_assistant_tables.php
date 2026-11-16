<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The AI assistant: conversations, their messages, and the changes it proposes for the user to confirm. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 30)->default('shop_owner'); // shop_owner now; the customer bot later
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'shop_id', 'kind']);
        });

        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 12); // user | assistant
            $table->text('content');
            $table->boolean('has_image')->default(false);
            $table->json('tools')->nullable(); // tool names the reply used, for review
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamps();
            $table->index(['assistant_conversation_id', 'id']);
        });

        Schema::create('assistant_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistant_message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->json('payload');
            $table->string('summary', 500);
            $table->string('status', 20)->default('pending'); // pending | done | cancelled | failed | expired
            $table->string('result', 500)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_actions');
        Schema::dropIfExists('assistant_messages');
        Schema::dropIfExists('assistant_conversations');
    }
};
