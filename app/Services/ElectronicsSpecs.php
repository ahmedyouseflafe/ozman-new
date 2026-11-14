<?php

namespace App\Services;

use App\Models\Product;

/** A device's specifications as the electronics store's compare table shows them (website and app). */
class ElectronicsSpecs
{
    public const ROWS = [
        'brand' => 'الماركة', 'model' => 'الموديل', 'condition' => 'الحالة', 'network' => 'الشبكة', 'screen_size' => 'الشاشة',
        'processor' => 'المعالج', 'storage' => 'سعات التخزين', 'ram' => 'الرام', 'colors' => 'الألوان', 'battery' => 'البطارية',
        'battery_health' => 'صحة البطارية', 'cameras' => 'الكاميرات', 'warranty_months' => 'الضمان',
    ];

    public static function value(Product $product, string $key): string
    {
        $variants = $product->relationLoaded('variants') ? $product->variants->where('is_active', true) : $product->variants()->where('is_active', true)->get();
        $attribute = data_get($product->catalog_attributes, $key);
        $value = match ($key) {
            'storage' => $variants->pluck('storage')->filter()->unique()->implode('، '),
            'ram' => $variants->pluck('ram')->filter()->unique()->implode('، '),
            'colors' => $variants->pluck('color_name')->filter()->unique()->implode('، '),
            'battery_health' => filled($attribute) ? $attribute.'%' : '',
            'warranty_months' => filled($attribute) ? $attribute.' شهر' : '',
            default => is_array($attribute) ? implode('، ', $attribute) : (string) $attribute,
        };

        return trim($value) !== '' ? $value : '—';
    }

    /** Only the filled rows, for a product page. */
    public static function list(Product $product): array
    {
        return collect(self::ROWS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => self::value($product, $key)])
            ->reject(fn ($row) => $row['value'] === '—')->values()->all();
    }
}
