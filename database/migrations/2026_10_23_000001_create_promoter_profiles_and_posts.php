<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $roles = ['super_admin', 'company_admin', 'shop_owner', 'agent', 'distributor', 'marketer', 'employee', 'restaurant_driver', 'customer'];

    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->enum('role', [...$this->roles, 'promoter'])->default('customer')->change());
        Schema::create('promoter_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->string('display_name', 100);
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('promoter_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promoter_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 10);
            $table->string('type', 10);
            $table->string('media');
            $table->string('poster')->nullable();
            $table->text('caption')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['promoter_profile_id', 'kind', 'is_active', 'expires_at'], 'promoter_posts_feed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promoter_posts');
        Schema::dropIfExists('promoter_profiles');
        DB::table('users')->where('role', 'promoter')->update(['role' => 'customer']);
        Schema::table('users', fn (Blueprint $table) => $table->enum('role', $this->roles)->default('customer')->change());
    }
};
