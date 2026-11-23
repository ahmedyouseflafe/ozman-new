<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Services\MealChoiceService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A meal's sizes, add-ons, removable ingredients and choice groups, edited from the app.
 *
 * Stored exactly the way the website's form stores them in catalog_attributes ("name:price" lists), so the
 * website, the shop page, checkout and the app keep reading them the same way. Choice groups filled from the
 * menu or from sections are set up on the website only; saving from the app keeps them as they are.
 */
class MealOptions
{
    public function __construct(private MealChoiceService $choices) {}

    /** What the app's editor shows. */
    public function present(Product $product): array
    {
        $attributes = (array) $product->catalog_attributes;
        $groups = collect($attributes['meal_choice_groups'] ?? []);

        return [
            'sizes' => $this->priced($attributes['meal_size_prices'] ?? []),
            'addons' => $this->priced($attributes['addon_prices'] ?? []),
            'removable' => array_values(array_filter(array_map('strval', (array) ($attributes['removable_ingredients'] ?? [])), fn ($name) => trim($name) !== '')),
            'groups' => $groups->filter(fn ($group) => ($group['source'] ?? 'manual') === 'manual')->map(fn ($group) => [
                'id' => $group['id'], 'name' => $group['name'], 'min' => (int) $group['min'], 'max' => (int) $group['max'],
                'allow_repeat' => (bool) ($group['allow_repeat'] ?? false),
                'options' => collect($group['options'] ?? [])->map(fn ($option) => ['id' => $option['id'], 'name' => $option['name'], 'price' => (float) $option['price']])->values()->all(),
            ])->values()->all(),
            'website_groups' => $groups->reject(fn ($group) => ($group['source'] ?? 'manual') === 'manual')->pluck('name')->values()->all(),
        ];
    }

    /**
     * The product's attributes with the meal options replaced by $input; every other attribute is kept.
     *
     * @param  array{sizes?: array, addons?: array, removable?: array, groups?: array}  $input
     */
    public function apply(array $attributes, array $input, int $shopId): array
    {
        $name = ['required', 'string', 'max:60', 'not_regex:/[,:]/'];
        $price = ['required', 'numeric', 'min:0', 'max:100000'];
        $data = Validator::make($input, [
            'sizes' => ['sometimes', 'array', 'max:20'], 'sizes.*.name' => $name, 'sizes.*.price' => $price,
            'addons' => ['sometimes', 'array', 'max:40'], 'addons.*.name' => $name, 'addons.*.price' => $price,
            'removable' => ['sometimes', 'array', 'max:40'], 'removable.*' => $name,
            'groups' => ['sometimes', 'array', 'max:10'],
        ], [
            'sizes.*.name.not_regex' => 'اسم الحجم ما بينفع يكون فيه فاصلة أو نقطتين.',
            'addons.*.name.not_regex' => 'اسم الإضافة ما بينفع يكون فيه فاصلة أو نقطتين.',
            'removable.*.not_regex' => 'اسم المكوّن ما بينفع يكون فيه فاصلة أو نقطتين.',
        ])->validate();

        foreach (['sizes' => 'الأحجام', 'addons' => 'الإضافات', 'removable' => 'المكونات'] as $key => $label) {
            $names = collect($data[$key] ?? [])->map(fn ($row) => trim(is_array($row) ? $row['name'] : $row));
            if ($names->unique()->count() !== $names->count()) {
                throw ValidationException::withMessages([$key => "في اسم مكرر بـ{$label}."]);
            }
        }

        if (array_key_exists('sizes', $data)) {
            $this->put($attributes, 'meal_sizes', collect($data['sizes'])->map(fn ($row) => trim($row['name']))->all());
            $this->put($attributes, 'meal_size_prices', collect($data['sizes'])->map(fn ($row) => trim($row['name']).':'.$this->number($row['price']))->all());
        }
        if (array_key_exists('addons', $data)) {
            $this->put($attributes, 'addons', collect($data['addons'])->map(fn ($row) => trim($row['name']))->all());
            $this->put($attributes, 'addon_prices', collect($data['addons'])->map(fn ($row) => trim($row['name']).':'.$this->number($row['price']))->all());
        }
        if (array_key_exists('removable', $data)) {
            $this->put($attributes, 'removable_ingredients', collect($data['removable'])->map(fn ($name) => trim($name))->all());
        }
        if (array_key_exists('groups', $data)) {
            $manual = collect($data['groups'])->map(fn ($group) => [
                'id' => $this->id($group['id'] ?? null),
                'name' => (string) ($group['name'] ?? ''),
                'min' => (int) ($group['min'] ?? 0),
                'max' => (int) ($group['max'] ?? 1),
                'allow_repeat' => filter_var($group['allow_repeat'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'source' => 'manual',
                'options' => collect($group['options'] ?? [])->map(fn ($option) => [
                    'id' => $this->id($option['id'] ?? null),
                    'name' => (string) ($option['name'] ?? ''),
                    'price' => $option['price'] ?? 0,
                ])->values()->all(),
            ])->values();
            $fromWebsite = collect($attributes['meal_choice_groups'] ?? [])->reject(fn ($group) => ($group['source'] ?? 'manual') === 'manual')->values();
            $groups = $this->choices->normalize($manual->concat($fromWebsite)->all(), $shopId);
            $this->put($attributes, 'meal_choice_groups', $groups);
        }

        return $attributes;
    }

    private function priced($values): array
    {
        return collect(is_array($values) ? $values : [])->map(function ($value) {
            [$name, $price] = array_pad(explode(':', (string) $value, 2), 2, null);

            return trim((string) $name) !== '' && is_numeric($price) ? ['name' => trim($name), 'price' => max(0, (float) $price)] : null;
        })->filter()->values()->all();
    }

    private function put(array &$attributes, string $key, array $value): void
    {
        if ($value === []) {
            unset($attributes[$key]);
        } else {
            $attributes[$key] = array_values($value);
        }
    }

    /** Keeps an id the app sent back, or makes a new one for a group or choice added on the phone. */
    private function id($id): string
    {
        return is_string($id) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id) ? $id : 'm'.Str::lower(Str::random(10));
    }

    private function number($value): string
    {
        $number = round((float) $value, 2);

        return $number == (int) $number ? (string) (int) $number : (string) $number;
    }
}
