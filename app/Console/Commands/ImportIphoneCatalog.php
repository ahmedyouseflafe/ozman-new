<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImportIphoneCatalog extends Command
{
    protected $signature = 'catalog:import-iphones {shop? : Store ID, slug, or storefront URL} {--images=8 : Maximum licensed Commons images per model} {--without-images : Create catalog without downloading images}';
    protected $description = 'Import iPhone 11–17 models, colors, capacities, and openly licensed Wikimedia Commons images';

    public function handle(): int
    {
        $stores = Shop::query()->where('catalog_type', 'electronics')->where('is_active', true)->orderBy('id')->get();
        $input = trim((string) $this->argument('shop'));
        if (filter_var($input, FILTER_VALIDATE_URL)) $input = basename(parse_url($input, PHP_URL_PATH));
        $shop = $input !== ''
            ? $stores->first(fn (Shop $store) => (string) $store->id === $input || $store->slug === $input)
            : ($stores->count() === 1 ? $stores->first() : null);
        if (! $shop) {
            $this->error($stores->isEmpty() ? 'No active electronics stores were found.' : 'Choose an electronics store using its ID or exact slug:');
            if ($stores->isNotEmpty()) {
                $this->table(['ID', 'Store name', 'Slug', 'Command'], $stores->map(fn (Shop $store) => [
                    $store->id, $store->name, $store->slug, "php artisan catalog:import-iphones {$store->id} --images=".$this->option('images'),
                ])->all());
            }
            return self::FAILURE;
        }
        $this->info("Importing into: {$shop->name} (ID {$shop->id}, slug {$shop->slug})");

        $category = Category::firstOrCreate(
            ['shop_id' => $shop->id, 'slug' => 'iphone'],
            ['name' => 'آيفون', 'name_translations' => ['ar' => 'آيفون', 'en' => 'iPhone'], 'is_active' => true]
        );
        $created = $updated = $imageCount = 0;

        foreach (config('iphone_catalog', []) as $generation) {
            foreach ($generation['models'] as $model) {
                $slug = Str::slug($model['name']);
                $product = Product::firstOrNew(['shop_id' => $shop->id, 'slug' => $slug]);
                $product->fill([
                    'category_id' => $category->id,
                    'name' => $model['name'],
                    'name_translations' => ['ar' => $model['name'], 'en' => $model['name']],
                    'description' => "Apple {$model['name']} — صور مرخصة من Wikimedia Commons. السعر والكمية يحددهما المتجر.",
                    'catalog_attributes' => array_merge($product->catalog_attributes ?? [], [
                        'brand' => 'Apple', 'model' => $model['name'], 'condition' => 'جديد',
                        'network' => '5G', 'screen_size' => $model['screen'], 'processor' => $generation['chip'],
                    ]),
                    'price' => $product->price ?: 0.01,
                    'quantity' => $product->quantity ?: 0,
                    'is_active' => true,
                ]);
                $wasNew = ! $product->exists;
                $product->save();
                $wasNew ? $created++ : $updated++;

                foreach ($model['storages'] as $storage) {
                    foreach ($model['colors'] as $colorName => $hex) {
                        $product->variants()->firstOrCreate(
                            ['storage' => $storage, 'color_name' => $colorName],
                            ['ram' => null, 'color' => $hex, 'price' => null, 'quantity' => 0, 'is_active' => true]
                        );
                    }
                }

                if (! $this->option('without-images')) {
                    $downloaded = $this->importCommonsImages($product, $model, max(1, min(12, (int) $this->option('images'))));
                    $imageCount += $downloaded;
                    $this->line("{$model['name']}: {$downloaded} licensed image(s)");
                }
            }
        }

        $this->newLine();
        $this->info("Finished: {$created} created, {$updated} updated, {$imageCount} images downloaded.");
        $this->warn('Prices and stock remain unset. Review every image and product before publishing sales.');
        return self::SUCCESS;
    }

    private function importCommonsImages(Product $product, array $model, int $limit): int
    {
        $queries = collect(array_keys($model['colors'] ?? []))
            ->map(fn (string $color) => '"'.$product->name.'" "'.$color.'" filetype:bitmap')
            ->prepend('intitle:"'.$product->name.'" filetype:bitmap')
            ->prepend('"'.$product->name.'" Apple filetype:bitmap')
            ->unique()
            ->values();

        $pages = collect();
        foreach ($queries as $query) {
            if ($pages->count() >= $limit * 4) break;
            try {
                $response = Http::withHeaders(['User-Agent' => 'OzmanCatalogImporter/1.1 (catalog administration)'])
                    ->timeout(25)->retry(2, 500)->get('https://commons.wikimedia.org/w/api.php', [
                        'action' => 'query', 'generator' => 'search', 'gsrnamespace' => 6,
                        'gsrsearch' => $query, 'gsrlimit' => 30, 'gsrsort' => 'relevance',
                        'prop' => 'imageinfo', 'iiprop' => 'url|mime|extmetadata', 'iiurlwidth' => 1200,
                        'iiextmetadatafilter' => 'LicenseShortName|Artist|ImageDescription',
                        'format' => 'json', 'formatversion' => 2, 'origin' => '*',
                    ])->throw()->json();
                $pages = $pages->concat(data_get($response, 'query.pages', []));
            } catch (Throwable $error) {
                $this->warn("Commons lookup failed for {$product->name}: {$error->getMessage()}");
            }
        }

        $pages = $pages
            ->filter(fn (array $page) => $this->matchesModel((string) data_get($page, 'title'), $product->name))
            ->unique(fn (array $page) => (string) data_get($page, 'pageid'))
            ->values();

        $count = 0;
        foreach ($pages as $page) {
            if ($count >= $limit) break;
            $info = data_get($page, 'imageinfo.0', []);
            $license = trim(strip_tags((string) data_get($info, 'extmetadata.LicenseShortName.value')));
            if (! $this->isOpenLicense($license) || ! Str::startsWith((string) data_get($info, 'mime'), 'image/')) continue;
            $url = data_get($info, 'thumburl') ?: data_get($info, 'url');
            if (! $url) continue;
            $source = data_get($info, 'descriptionurl') ?: 'https://commons.wikimedia.org/?curid='.data_get($page, 'pageid');
            $author = trim(strip_tags((string) data_get($info, 'extmetadata.Artist.value', 'Wikimedia Commons contributor')));
            $extension = Str::contains((string) data_get($info, 'mime'), 'png') ? 'png' : 'jpg';
            $path = 'products/imported/iphone/'.$product->slug.'-'.substr(sha1($url), 0, 10).'.'.$extension;

            try {
                if (! Storage::disk('public')->exists($path)) {
                    $binary = Http::withHeaders(['User-Agent' => 'OzmanCatalogImporter/1.0'])->timeout(35)->retry(2, 500)->get($url)->throw()->body();
                    Storage::disk('public')->put($path, $binary);
                }
                $stored = 'storage/'.$path;
                if (! $product->main_image) $product->update(['main_image' => $stored]);
                $product->images()->firstOrCreate(['image' => $stored]);
                $attribution = "Image: {$author} — {$license} — {$source}";
                if (! Str::contains((string) $product->description, $source)) $product->update(['description' => trim($product->description."\n".$attribution)]);
                $count++;
            } catch (Throwable $error) {
                $this->warn("Image download skipped: {$error->getMessage()}");
            }
        }
        return $count;
    }

    private function matchesModel(string $title, string $model): bool
    {
        $normalize = static fn (string $value) => Str::of($value)
            ->lower()
            ->replace(['_', '-', '(', ')'], ' ')
            ->squish()
            ->toString();
        $title = $normalize($title);
        $model = $normalize($model);

        if (! Str::contains($title, $model)) return false;

        foreach (['pro max', 'pro', 'plus', 'mini', 'air', '16e', '17e', 'se'] as $qualifier) {
            if (Str::contains($title, $qualifier) !== Str::contains($model, $qualifier)) return false;
        }

        return ! Str::contains($title, ['case', 'cover', 'screen protector', 'dummy', 'clone']);
    }

    private function isOpenLicense(string $license): bool
    {
        $license = Str::lower($license);
        return Str::contains($license, ['cc by', 'cc0', 'public domain', 'gfdl'])
            && ! Str::contains($license, ['noncommercial', 'no derivatives', 'fair use']);
    }
}
