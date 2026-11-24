<?php

namespace App\Services\Basket;

use App\Http\Controllers\Api\V1\Presenter;
use App\Models\Product;

/**
 * Turns remembered order items ({product_id, name, qty, variant_id, size, color, addons…}) into lines the app can put
 * straight in the basket. What can't be bought as it was (product gone, option gone, extras or meal choices that
 * have to be picked again) is listed by name as skipped.
 */
class BasketLines
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  int|null  $shopId  only this shop's products (an old order is from one shop)
     * @return array{lines: array<int, array<string, mixed>>, skipped: array<int, string>}
     */
    public static function from(array $items, ?int $shopId = null, bool $withShop = false): array
    {
        $ids = collect($items)->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->unique()->all();
        $products = Product::with(['images', 'variants', 'shop'])->where('is_active', true)->whereIn('id', $ids)
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->whereHas('shop', fn ($q) => $q->where('is_active', true))
            ->get()->keyBy('id');
        // Names of what can't be bought any more, for items that didn't carry their name (e.g. ids from the assistant).
        $names = Product::whereIn('id', array_diff($ids, $products->keys()->all()))->pluck('name', 'id');
        $lines = [];
        $skipped = [];
        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            $custom = ! empty($item['addons']) || ! empty($item['choices']) || ! empty($item['excluded']);
            $name = (string) ($item['name'] ?? $product?->name ?? $names[(int) ($item['product_id'] ?? 0)] ?? __('صنف'));
            if (! $product || $custom) {
                $skipped[] = $name;
                continue;
            }
            $variantId = $item['variant_id'] ?? null;
            if ($variantId && ! $product->variants->where('is_active', true)->contains('id', (int) $variantId)) {
                $skipped[] = $name;
                continue;
            }
            $lines[] = array_filter([
                'product' => Presenter::product($product, true),
                'shop' => $withShop && $product->shop ? Presenter::shop($product->shop) : null,
                'url' => $withShop ? route('share.product', $product) : null,
                'qty' => max(1, min(50, (int) ($item['qty'] ?? 1))),
                'variant_id' => $variantId ? (int) $variantId : null,
                'size' => isset($item['size']) && $item['size'] !== '' ? (string) $item['size'] : null,
                'color' => isset($item['color']) && $item['color'] !== '' ? (string) $item['color'] : null,
            ], fn ($v) => $v !== null);
        }

        return ['lines' => $lines, 'skipped' => $skipped];
    }
}
