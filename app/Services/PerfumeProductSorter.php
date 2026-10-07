<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PerfumeProductSorter
{
    public function sort(Collection $products): Collection
    {
        return $products->sortBy(fn (Product $product) => $this->key($product), SORT_NATURAL)->values();
    }

    private function key(Product $product): string
    {
        $brand = $this->normalize($product->catalog_attributes['brand'] ?? '');
        // Use the base name so switching storefront language never reshuffles the catalog.
        $name = $this->normalize($product->name);
        $aliases = [
            'armani' => ['emporio armani', 'giorgio armani', 'armani'],
            'hugo boss' => ['hugo boss', 'boss'],
            'yves saint laurent' => ['yves saint laurent', 'ysl'],
            'jean paul gaultier' => ['jean paul gaultier', 'jpg'],
        ];
        $prefixes = array_filter([$brand]);
        $group = $brand;
        foreach ($aliases as $canonical => $names) {
            if (in_array($brand, $names, true)) {
                $group = $canonical;
                $prefixes = $names;
                break;
            }
        }
        // Some titles repeat the brand, while other editions omit it.
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix.' ')) {
                $name = trim(substr($name, strlen($prefix)));
                break;
            }
        }
        return ($group === '' ? '1|' : '0|'.$group.'|').$name.'|'.str_pad((string)$product->id, 12, '0', STR_PAD_LEFT);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(Str::ascii(html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        return trim(preg_replace('/[^\pL\pN]+/u', ' ', $value));
    }
}
