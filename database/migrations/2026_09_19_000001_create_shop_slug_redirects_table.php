<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_slug_redirects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        DB::transaction(function (): void {
            $shops = DB::table('shops')
                ->where('catalog_type', 'real_estate')
                ->orderBy('id')
                ->get(['id', 'name', 'slug']);

            foreach ($shops as $shop) {
                $base = Str::slug((string) $shop->name);
                if ($base === '') {
                    $base = Str::of((string) $shop->name)
                        ->lower()
                        ->replaceMatches('/[^\p{L}\p{N}]+/u', '-')
                        ->trim('-')
                        ->toString();
                }
                $base = $base !== '' ? $base : 'shop';
                $slug = $base;
                $counter = 2;

                while (
                    DB::table('shops')->where('slug', $slug)->where('id', '!=', $shop->id)->exists()
                    || DB::table('shop_slug_redirects')->where('slug', $slug)->exists()
                ) {
                    $slug = "{$base}-{$counter}";
                    $counter++;
                }

                if ($slug === $shop->slug) {
                    continue;
                }

                DB::table('shops')->where('id', $shop->id)->update([
                    'slug' => $slug,
                    'updated_at' => now(),
                ]);
                DB::table('shop_slug_redirects')->insert([
                    'shop_id' => $shop->id,
                    'slug' => $shop->slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_slug_redirects');
    }
};
