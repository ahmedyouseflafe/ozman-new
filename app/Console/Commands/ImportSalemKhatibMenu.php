<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSalemKhatibMenu extends Command
{
    protected $signature = 'restaurant:import-salem-menu
        {shop? : Restaurant ID, slug, or storefront URL}
        {--dry-run : Validate and preview without changing the database}';

    protected $description = 'Import the supplied Salem Al-Khatib restaurant menu and its licensed stock photos';

    public function handle(): int
    {
        $shop = $this->resolveShop(trim((string) $this->argument('shop')));
        if (! $shop) {
            $this->error('Specify the exact Salem Al-Khatib restaurant ID, slug, or storefront URL.');
            return self::FAILURE;
        }

        if (! str_contains($shop->name, 'سالم') || ! str_contains($shop->name, 'الخطيب')) {
            $this->error("Refusing to import into a different restaurant: {$shop->name}");
            return self::FAILURE;
        }

        $categories = config('salem_khatib_menu.categories', []);
        $imageBase = 'media/restaurant/salem-al-khatib/';
        $productCount = 0;
        foreach ($categories as $category) {
            $images = array_merge([$category['image']], array_column($category['products'], 'image'));
            foreach ($images as $image) {
                if (! is_file(public_path($imageBase.$image))) {
                    $this->error("Missing photo: {$imageBase}{$image}");
                    return self::FAILURE;
                }
            }
            $productCount += count($category['products']);
        }

        $this->info("Restaurant: {$shop->name} ({$shop->id})");
        $this->line(count($categories)." categories, {$productCount} menu items, all photos present.");
        if ($this->option('dry-run')) {
            $this->comment('Dry run only; no database changes were made.');
            return self::SUCCESS;
        }

        $counts = DB::transaction(function () use ($shop, $categories, $imageBase): array {
            $counts = ['categories_created' => 0, 'products_created' => 0, 'existing_products' => 0];

            foreach ($categories as $categoryData) {
                $category = Category::query()->firstOrNew([
                    'shop_id' => $shop->id,
                    'name' => $categoryData['name'],
                ]);

                if (! $category->exists) {
                    $category->fill([
                        'slug' => "salem-khatib-{$shop->id}-{$categoryData['key']}",
                        'name_translations' => $this->translations($categoryData),
                        'image' => $imageBase.$categoryData['image'],
                        'is_active' => true,
                    ]);
                    $category->save();
                    $counts['categories_created']++;
                } elseif (! $category->image) {
                    $category->update(['image' => $imageBase.$categoryData['image']]);
                }

                foreach ($categoryData['products'] as $productData) {
                    $product = Product::query()->firstOrNew([
                        'shop_id' => $shop->id,
                        'name' => $productData['name'],
                    ]);

                    if (! $product->exists) {
                        $product->fill([
                            'category_id' => $category->id,
                            'slug' => "salem-khatib-{$shop->id}-{$productData['key']}",
                            'name_translations' => $this->translations($productData),
                            'price' => $productData['price'],
                            'quantity' => 1,
                            'main_image' => $imageBase.$productData['image'],
                            'catalog_attributes' => ['photo_is_illustrative' => true],
                            'is_active' => true,
                        ]);
                        $product->save();
                        $counts['products_created']++;
                    } else {
                        $counts['existing_products']++;
                        if (! $product->main_image) {
                            $product->update([
                                'main_image' => $imageBase.$productData['image'],
                                'catalog_attributes' => array_merge($product->catalog_attributes ?? [], [
                                    'photo_is_illustrative' => true,
                                ]),
                            ]);
                        }
                    }
                }
            }

            return $counts;
        });

        $this->info("Created {$counts['categories_created']} categories and {$counts['products_created']} menu items; {$counts['existing_products']} existing items preserved.");
        if (! $shop->is_active) {
            $this->warn('The restaurant is inactive, so its public menu is not visible yet.');
        }

        return self::SUCCESS;
    }

    private function resolveShop(string $input): ?Shop
    {
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            $input = urldecode(basename((string) parse_url($input, PHP_URL_PATH)));
        }

        $query = Shop::query()->where('catalog_type', 'restaurant');
        if ($input !== '') {
            return $query->where(function ($query) use ($input) {
                $query->where('slug', $input);
                if (ctype_digit($input)) {
                    $query->orWhereKey((int) $input);
                }
            })->first();
        }

        $matches = $query->where('name', 'like', '%سالم%')
            ->where('name', 'like', '%الخطيب%')
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function translations(array $entry): array
    {
        return [
            'ar' => $entry['name'],
            'he' => $entry['name_he'],
            'en' => $entry['name_en'],
        ];
    }
}
