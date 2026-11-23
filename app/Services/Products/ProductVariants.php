<?php

namespace App\Services\Products;

use App\Models\Product;

/**
 * A product's options (size, storage, RAM, colour) with their own price and stock, shared by the website's
 * product form and the app. Saving replaces all of them; the product's stock (and a device's starting price)
 * follow the active ones.
 */
class ProductVariants
{
    /** @param array<int, array<string, mixed>> $input the variant rows as the form sent them */
    public function sync(Product $product, array $input): void
    {
        $shopType = $product->shop()->value('catalog_type') ?: 'general';
        if (! in_array($shopType, ['clothing', 'shoes', 'electronics'], true)) {
            $product->variants()->delete();
            return;
        }

        $variants = collect($input)
            ->filter(fn ($variant) => filled($variant['size'] ?? null) || filled($variant['storage'] ?? null) || filled($variant['ram'] ?? null) || filled($variant['color'] ?? null))
            ->map(fn ($variant) => [
                'size' => filled($variant['size'] ?? null) ? trim($variant['size']) : null,
                'storage' => filled($variant['storage'] ?? null) ? trim($variant['storage']) : null,
                'ram' => filled($variant['ram'] ?? null) ? trim($variant['ram']) : null,
                'color' => filled($variant['color'] ?? null) ? trim($variant['color']) : null,
                'color_name' => filled($variant['color_name'] ?? null) ? trim($variant['color_name']) : null,
                'sku' => filled($variant['sku'] ?? null) ? trim($variant['sku']) : null,
                'price' => filled($variant['price'] ?? null) ? $variant['price'] : null,
                'quantity' => max(0, (int) ($variant['quantity'] ?? 0)),
                'is_active' => filter_var($variant['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->values();

        if ($shopType === 'electronics') {
            abort_if($variants->isEmpty(), 422, 'أضف خيار تخزين ولون واحدًا على الأقل للجهاز.');
            abort_if($variants->contains(fn ($variant) => ! filled($variant['storage']) || ! filled($variant['color']) || $variant['price'] === null), 422, 'التخزين واللون والسعر مطلوبة لكل خيار جهاز.');
            abort_if($variants->contains(fn ($variant) => ! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $variant['color'])), 422, 'اختر لونًا صحيحًا لكل خيار جهاز.');
        }

        $product->variants()->delete();
        if ($variants->isNotEmpty()) {
            $product->variants()->createMany($variants->all());
            $updates = ['quantity' => $variants->where('is_active', true)->sum('quantity')];
            if ($shopType === 'electronics') {
                $updates['price'] = $variants->where('is_active', true)->min('price') ?? $variants->min('price') ?? 0;
            }
            $product->update($updates);
        }
    }
}
