<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

/** Turns models into the JSON shapes the mobile app reads. Never exposes private fields. */
class Presenter
{
    public static function media(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return preg_match('~^https?://~i', $path) ? $path : asset(ltrim($path, '/'));
    }

    public static function price($value): ?float
    {
        return $value === null || $value === '' ? null : round((float) $value, 2);
    }

    public static function shop(Shop $shop, bool $full = false): array
    {
        $data = [
            'id' => $shop->id,
            'name' => $shop->name,
            'slug' => $shop->slug,
            'type' => $shop->catalog_type,
            'logo' => self::media($shop->logo),
            'banner' => self::media($shop->banner),
            'city' => $shop->city,
            'is_accepting_orders' => (bool) ($shop->is_accepting_orders ?? true),
            'web_url' => $shop->publicUrl(),
        ];
        if ($full) {
            $data += [
                'description' => $shop->description,
                'address' => $shop->address,
                'phone' => $shop->phone,
                'whatsapp' => $shop->whatsapp,
                'latitude' => $shop->latitude !== null ? (float) $shop->latitude : null,
                'longitude' => $shop->longitude !== null ? (float) $shop->longitude : null,
                'open_time' => $shop->open_time,
                'close_time' => $shop->close_time,
                'loyalty_enabled' => (bool) $shop->loyalty_enabled,
            ];
        }

        return $data;
    }

    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->localized('name'),
            'image' => self::media($category->image),
            'products_count' => $category->products_count ?? null,
        ];
    }

    public static function product(Product $product, bool $full = false): array
    {
        $price = self::price($product->price);
        $discount = self::price($product->discount_price);
        $data = [
            'id' => $product->id,
            'shop_id' => $product->shop_id,
            'category_id' => $product->category_id,
            'name' => $product->localized('name'),
            'image' => self::media($product->main_image),
            'price' => $price,
            'discount_price' => $discount && $price && $discount < $price ? $discount : null,
            'final_price' => $discount && $price && $discount < $price ? $discount : $price,
            // The website does not track stock (quantity is 0 by default), so products are always orderable.
            'in_stock' => true,
            'is_featured' => (bool) $product->is_featured,
            'likes_count' => (int) ($product->likes_count ?? 0),
            // True when the customer must pick a size, colour or option before adding to the basket.
            'has_options' => self::hasOptions($product),
        ];
        if ($full) {
            $data += [
                'description' => $product->localized('description'),
                'images' => $product->images->map(fn ($image) => self::media($image->image))->filter()->values(),
                'video' => self::media($product->video),
                'attributes' => $product->catalog_attributes ?: (object) [],
                'sizes' => self::optionList($product, 'sizes'),
                'colors' => self::optionList($product, 'colors'),
                // Restaurant meals: sizes, extras, "without…" and choice groups, as the menu page offers them.
                'meal' => self::mealOptions($product),
                'variants' => $product->variants->where('is_active', true)->values()->map(fn ($variant) => [
                    'id' => $variant->id,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'color_name' => $variant->color_name,
                    'storage' => $variant->storage,
                    'ram' => $variant->ram,
                    'price' => self::price($variant->price),
                    'in_stock' => true,
                ]),
                'share_url' => route('share.product', $product),
            ];
        }

        return $data;
    }

    public static function hasOptions(Product $product): bool
    {
        $variants = $product->relationLoaded('variants')
            ? $product->variants->where('is_active', true)->count()
            : (int) ($product->active_variants_count ?? 0);

        return $variants > 0 || ! empty(self::optionList($product, 'sizes')) || ! empty(self::optionList($product, 'colors'))
            // Cheap check for lists (no menu lookups): does the meal have any choices at all?
            || collect(['meal_size_prices', 'addon_prices', 'removable_ingredients', 'meal_choice_groups'])
                ->contains(fn ($key) => ! empty(($product->catalog_attributes ?? [])[$key] ?? null));
    }

    /**
     * A meal's choices in the website's own format ("name:price" lists and choice groups), or null.
     * The checkout re-checks every pick and price on the server.
     */
    public static function mealOptions(Product $product): ?array
    {
        $attributes = $product->catalog_attributes ?? [];
        $priced = fn ($values) => collect(is_array($values) ? $values : [])->map(function ($value) {
            [$name, $price] = array_pad(explode(':', (string) $value, 2), 2, null);
            return trim((string) $name) !== '' && is_numeric($price) ? ['name' => trim($name), 'price' => max(0, (float) $price)] : null;
        })->filter()->values()->all();
        $sizes = $priced($attributes['meal_size_prices'] ?? []);
        $addons = $priced($attributes['addon_prices'] ?? []);
        $removable = array_values(array_filter(array_map('strval', (array) ($attributes['removable_ingredients'] ?? [])), fn ($name) => trim($name) !== ''));
        $groups = ! empty($attributes['meal_choice_groups'])
            ? collect(app(\App\Services\MealChoiceService::class)->forProduct($product))->map(fn ($group) => [
                'id' => $group['id'], 'name' => $group['name'], 'min' => (int) $group['min'], 'max' => (int) $group['max'],
                'allow_repeat' => (bool) ($group['allow_repeat'] ?? false),
                'options' => collect($group['options'] ?? [])->map(fn ($option) => ['id' => $option['id'], 'name' => $option['name'], 'price' => (float) $option['price']])->values()->all(),
            ])->filter(fn ($group) => $group['options'] !== [])->values()->all()
            : [];

        return ($sizes || $addons || $removable || $groups)
            ? ['sizes' => $sizes, 'addons' => $addons, 'removable' => $removable, 'groups' => $groups]
            : null;
    }

    /** Sizes or colours a shop typed for a product (an array, or text separated by commas). */
    public static function optionList(Product $product, string $key): array
    {
        $value = data_get($product->catalog_attributes, $key, []);
        $list = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $list), fn ($item) => $item !== ''));
    }

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'phone' => $user->phone,
            'role' => $user->role,
            'avatar' => $user->avatarUrl(),
            'shop_ids' => $user->isShopOwner() ? $user->shops()->pluck('id') : [],
            // Shows the "my shop" section in the app (owners, their staff, admins).
            'is_merchant' => $user->isSuperAdmin() || $user->accessibleShopIds() !== [],
            'is_driver' => $user->isRestaurantDriver(),
            // Publishing stories is the owner's (or admin's) job on the website too.
            'can_post_stories' => $user->isSuperAdmin() || $user->isShopOwner(),
        ];
    }
}
