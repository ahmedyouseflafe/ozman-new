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
            'in_stock' => $product->quantity === null || (int) $product->quantity > 0,
            'is_featured' => (bool) $product->is_featured,
            'likes_count' => (int) ($product->likes_count ?? 0),
        ];
        if ($full) {
            $data += [
                'description' => $product->localized('description'),
                'images' => $product->images->map(fn ($image) => self::media($image->image))->filter()->values(),
                'video' => self::media($product->video),
                'attributes' => $product->catalog_attributes ?: (object) [],
                'variants' => $product->variants->where('is_active', true)->values()->map(fn ($variant) => [
                    'id' => $variant->id,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'color_name' => $variant->color_name,
                    'storage' => $variant->storage,
                    'ram' => $variant->ram,
                    'price' => self::price($variant->price),
                    'in_stock' => $variant->quantity === null || (int) $variant->quantity > 0,
                ]),
                'share_url' => route('share.product', $product),
            ];
        }

        return $data;
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
        ];
    }
}
