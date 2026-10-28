<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('promoter_invitations', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('token_hash', 64)->unique(); $t->timestamp('expires_at'); $t->timestamp('used_at')->nullable();
        });
        Schema::create('community_push_subscriptions', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('endpoint_hash',64)->unique(); $t->text('endpoint'); $t->text('public_key'); $t->text('auth_token');
            $t->string('content_encoding')->default('aes128gcm'); $t->timestamps();
        });
        Schema::create('promoter_updates', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('promoter_post_id')->constrained()->cascadeOnDelete();
            $t->timestamp('read_at')->nullable(); $t->timestamps(); $t->unique(['user_id','promoter_post_id']);
        });
        Schema::create('community_push_deliveries', function (Blueprint $t) {
            $t->id(); $t->foreignId('subscription_id')->constrained('community_push_subscriptions')->cascadeOnDelete();
            $t->foreignId('update_id')->constrained('promoter_updates')->cascadeOnDelete();
            $t->unsignedTinyInteger('attempts')->default(0); $t->timestamp('available_at'); $t->timestamp('locked_at')->nullable();
            $t->timestamp('sent_at')->nullable(); $t->timestamp('failed_at')->nullable(); $t->unique(['subscription_id','update_id']);
        });
    }
    public function down(): void {
        foreach (['community_push_deliveries','promoter_updates','community_push_subscriptions','promoter_invitations'] as $table) Schema::dropIfExists($table);
    }
};
