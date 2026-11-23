<?php

namespace App\Services\Storefront;

use App\Models\Advertisement;
use App\Models\Distributor;
use App\Models\DistributorMarketer;
use App\Models\MainScreen;
use App\Models\Product;
use App\Models\RewardWheel;
use App\Models\Shop;
use App\Services\FrontOrderPricing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StorefrontPayloads
{
    public function lightweightShopPayload(Shop $shop): array
    {
        return [
            'id' => $shop->id,
            'title' => $shop->name,
            'catalog_type' => $shop->catalog_type,
            'home_section' => $shop->home_section ?: $shop->catalog_type,
            'public_url' => $this->shopPublicUrl($shop),
            'img' => $this->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg'),
            'logo' => $this->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg'),
            'whatsapp_number' => $this->contactWhatsappNumber($shop->whatsapp ?: $shop->phone),
            'marketing_context' => null,
            'purchase_reward_wheels' => [],
            'social_links' => [],
            'agents' => [],
            'distributors' => [],
            'display_items' => [],
            'departments' => [],
            'products_db' => [],
            'address' => $shop->address,
            'latitude' => $shop->latitude !== null ? (float) $shop->latitude : null,
            'longitude' => $shop->longitude !== null ? (float) $shop->longitude : null,
            'map_url' => $this->shopMapUrl($shop),
        ];
    }

    public function productPayload(Product $product): array
    {
        $gallery = $product->images
            ->pluck('image')
            ->prepend($product->main_image)
            ->filter()
            ->map(fn($image) => $this->imageUrl($image, 'images/1.jpg'))
            ->unique()
            ->values()
            ->all();

        $campaignMedia = $product->campaigns
            ->map(fn($campaign) => [
                'type' => $campaign->type,
                'src' => $this->imageUrl($campaign->media, ''),
                'title' => $campaign->localized('title'),
                // An offer outside its dates keeps its media but stops changing the cart price,
                // exactly like the server-side pricing in FrontOrderPricing.
                'offer_type' => FrontOrderPricing::campaignIsRunning($campaign) ? $campaign->offer_type : null,
                'unit_key' => $campaign->unit_key,
                'offer_quantity' => $campaign->offer_quantity,
                'min_quantity' => $campaign->min_quantity,
                'max_quantity' => $campaign->max_quantity,
                'offer_price' => $campaign->offer_price !== null ? (float) $campaign->offer_price : null,
                'offer_note' => $campaign->localized('offer_note'),
                'starts_at' => $campaign->starts_at?->toDateString(),
                'ends_at' => $campaign->ends_at?->toDateString(),
            ])
            ->filter(fn($campaign) => filled($campaign['src']) || filled($campaign['title']) || filled($campaign['offer_note']))
            ->values()
            ->all();

        $image = $this->imageUrl($product->main_image, 'images/1.jpg');
        $customerPackagePrice = $product->show_customer_package_price && $product->customer_package_price !== null ? (float) $product->customer_package_price : null;
        $customerCartonPrice = $product->show_customer_carton_price && $product->customer_carton_price !== null ? (float) $product->customer_carton_price : null;
        $customerPalletPrice = $product->show_customer_pallet_price && $product->customer_pallet_price !== null ? (float) $product->customer_pallet_price : null;
        $packagePrice = $product->show_package_price && $product->package_price !== null ? (float) $product->package_price : null;
        $palletPrice = $product->show_pallet_price && $product->pallet_price !== null ? (float) $product->pallet_price : null;
        $cartonPrice = $product->show_carton_price && $product->carton_price !== null ? (float) $product->carton_price : null;
        $customerDefaultPrice = $customerPackagePrice ?? $customerCartonPrice ?? $customerPalletPrice;
        $merchantDefaultPrice = $packagePrice ?? $cartonPrice ?? $palletPrice;

        return [
            'id' => $product->id,
            'name' => $product->localized('name'),
            'price' => $customerDefaultPrice !== null ? number_format($customerDefaultPrice, 2) . ' ₪' : null,
            'customer_price' => $customerDefaultPrice !== null ? number_format($customerDefaultPrice, 2) . ' ₪' : null,
            'customer_package_price' => $customerPackagePrice !== null ? number_format($customerPackagePrice, 2) . ' ₪' : null,
            'customer_carton_price' => $customerCartonPrice !== null ? number_format($customerCartonPrice, 2) . ' ₪' : null,
            'customer_pallet_price' => $customerPalletPrice !== null ? number_format($customerPalletPrice, 2) . ' ₪' : null,
            'merchant_price' => $merchantDefaultPrice !== null ? number_format($merchantDefaultPrice, 2) . ' ₪' : null,
            'package_price' => $packagePrice !== null ? number_format($packagePrice, 2) . ' ₪' : null,
            'pallet_price' => $palletPrice !== null ? number_format($palletPrice, 2) . ' ₪' : null,
            'carton_price' => $cartonPrice !== null ? number_format($cartonPrice, 2) . ' ₪' : null,
            'img' => $image,
            'gallery' => $gallery ?: [$image],
            'video' => $this->imageUrl($product->video, ''),
            'campaigns' => $campaignMedia,
            'description' => $product->localized('description'),
        ];
    }

    public function imageUrl(?string $path, string $fallback): string
    {
        if ($fallback === 'images/logo.jpg') {
            $fallback = 'images/logo.svg';
        }

        if (!$path) {
            if ($fallback === '') {
                return '';
            }

            return asset($fallback);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }

    public function shopOrderContext(Shop $shop): array
    {
        $marketer = $shop->distributorMarketer;
        $distributor = $marketer?->distributor;

        if ($marketer?->is_active && $distributor?->is_active) {
            return [
                'whatsapp_number' => $this->contactWhatsappNumber($distributor->whatsapp ?: $distributor->phone),
                'marketing_context' => [
                    'source' => 'marketer',
                    'distributor_id' => $distributor->id,
                    'distributor_name' => $distributor->name,
                    'marketer_id' => $marketer->id,
                    'marketer_name' => $marketer->name,
                    'tracking_code' => $marketer->tracking_code,
                    'commission_rate' => $marketer->commission_rate,
                ],
            ];
        }

        return [
            'whatsapp_number' => $this->contactWhatsappNumber($shop->whatsapp ?: $shop->phone),
            'marketing_context' => null,
        ];
    }

    public function socialLinksPayload(?Shop $shop): array
    {
        $social = optional($shop?->social);
        $normalize = function (?string $url): ?string {
            if (! filled($url)) return null;

            $url = trim($url);
            if (str_starts_with($url, '@')) return null;

            return preg_match('/^https?:\/\//i', $url)
                ? $url
                : 'https://' . ltrim($url, '/');
        };
        $normalizeWhatsapp = function (?string $value): ?string {
            if (! filled($value)) return null;

            $value = trim($value);

            if (preg_match('/^https?:\/\//i', $value)) {
                return $value;
            }

            $digits = preg_replace('/\D+/', '', $value);

            return $digits ? 'https://wa.me/' . $digits : null;
        };

        return collect([
            ['title' => 'فيسبوك', 'icon' => 'fab fa-facebook-f', 'url' => $normalize($social->facebook)],
            ['title' => 'تويتر', 'icon' => 'fab fa-twitter', 'url' => $normalize($social->twitter)],
            ['title' => 'انستجرام', 'icon' => 'fab fa-instagram', 'url' => $normalize($social->instagram)],
            ['title' => 'تيك توك', 'icon' => 'fab fa-tiktok', 'url' => $normalize($social->tiktok)],
            ['title' => 'تلجرام', 'icon' => 'fab fa-telegram', 'url' => $normalize($social->telegram)],
            ['title' => 'يوتيوب', 'icon' => 'fab fa-youtube', 'url' => $normalize($social->youtube)],
            ['title' => 'سناب شات', 'icon' => 'fab fa-snapchat', 'url' => $normalize($social->snapchat)],
            ['title' => 'واتساب', 'icon' => 'fab fa-whatsapp', 'url' => $normalizeWhatsapp($social->whatsapp ?: $shop?->whatsapp)],
        ])->filter(fn($item) => filled($item['url']))->values()->all();
    }

    public function contactPeoplePayload($people, Shop $shop, string $type, array $shopProductsDb = [], array $shopDepartments = [], $availableShops = null): array
    {
        $fallbackLogo = $this->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg');
        $availableShops = collect($availableShops ?? []);

        return collect($people)
            ->map(function ($person) use ($shop, $fallbackLogo, $type, $shopProductsDb, $shopDepartments, $availableShops) {
                $personProductsDb = $type === 'agent'
                    ? $this->personProductsDb($person->products ?? collect(), $person->categories ?? collect())
                    : [];
                $personDepartments = $type === 'agent'
                    ? $this->personDepartments($personProductsDb, $shop, $person->categories ?? collect())
                    : [];
                $ownedShops = $this->ownedShopChoicesForPerson($person, $availableShops);
                $mergedProductsDb = $type === 'agent'
                    ? $this->mergeProductsDb($shopProductsDb, $personProductsDb)
                    : $shopProductsDb;
                $mergedDepartments = $ownedShops !== []
                    ? $ownedShops
                    : ($type === 'agent'
                        ? $this->mergeDepartments($shopDepartments, $personDepartments)
                        : $shopDepartments);

                return [
                    'id' => $person->id,
                    'type' => $type,
                    'display_label' => $type === 'distributor' ? 'الموزع' : 'الوكيل',
                    'name' => $person->name,
                    'image' => $person->image ? $this->imageUrl($person->image, 'images/logo.jpg') : $fallbackLogo,
                    'contact' => $person->phone ?: $person->whatsapp ?: $shop->name,
                    'whatsapp_number' => $this->contactWhatsappNumber($person->whatsapp ?: $person->phone ?: $shop->whatsapp ?: $shop->phone),
                    'shop_id' => $shop->id,
                    'shop_title' => $shop->name,
                    'address' => $person->address,
                    'latitude' => $person->latitude !== null ? (float) $person->latitude : null,
                    'longitude' => $person->longitude !== null ? (float) $person->longitude : null,
                    'map_url' => $this->personMapUrl($person),
                    'owned_shops' => $ownedShops,
                    'departments' => $mergedDepartments,
                    'products_db' => $mergedProductsDb,
                ];
            })
            ->values()
            ->all();
    }

    public function personMapUrl($person): string
    {
        if ($person->latitude !== null && $person->longitude !== null) {
            return 'https://www.google.com/maps/dir/?api=1&destination=' . $person->latitude . ',' . $person->longitude;
        }

        if (filled($person->address)) {
            return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($person->address);
        }

        return '#';
    }

    public function contactWhatsappNumber(?string $value): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $value);

        return $number !== '' ? $number : null;
    }

    public function ownedShopChoicesForPerson($person, $availableShops): array
    {
        if (! $person->user_id) {
            return [];
        }

        return collect($availableShops)
            ->filter(fn(Shop $candidate) => (int) $candidate->user_id === (int) $person->user_id)
            ->map(fn(Shop $candidate) => [
                'title' => $candidate->name,
                'img' => $this->imageUrl($candidate->logo ?: $candidate->banner, 'images/logo.jpg'),
                'kind' => 'shop',
                'shop_id' => $candidate->id,
                'contact' => $candidate->address ?: $candidate->phone ?: $candidate->city ?: '',
            ])
            ->values()
            ->all();
    }

    public function mergeProductsDb(array $shopProductsDb, array $personProductsDb): array
    {
        $merged = $shopProductsDb;

        foreach ($personProductsDb as $categoryName => $products) {
            $merged[$categoryName] = array_values(array_merge($merged[$categoryName] ?? [], $products));
        }

        return $merged;
    }

    public function mergeDepartments(array $shopDepartments, array $personDepartments): array
    {
        $merged = collect($shopDepartments)->keyBy('title');

        foreach ($personDepartments as $department) {
            $merged->put($department['title'], $department);
        }

        return $merged->values()->all();
    }

    public function personProductsDb($products, $categories): array
    {
        $productsDb = collect($categories)
            ->mapWithKeys(fn($category) => [$category->localized('name') => []])
            ->all();

        collect($products)
            ->filter(fn($product) => $product->category)
            ->groupBy(fn($product) => $product->category->localized('name'))
            ->each(function ($group, $categoryName) use (&$productsDb) {
                $productsDb[$categoryName] = $group
                ->map(fn($product) => $this->productPayload($product))
                ->values()
                    ->all();
            });

        return $productsDb;
    }

    public function personDepartments(array $productsDb, Shop $shop, $categories): array
    {
        return collect($productsDb)
            ->map(function ($products, $categoryName) use ($shop, $categories) {
                $category = collect($categories)->first(fn($category) => $category->localized('name') === $categoryName)
                    ?: $shop->categories->first(fn($category) => $category->localized('name') === $categoryName);

                return [
                    'title' => $categoryName,
                    'img' => $this->imageUrl($category?->image, 'images/logo.jpg'),
                ];
            })
            ->values()
            ->all();
    }

    public function displayItemsPayload($items): array
    {
        return collect($items)
            ->filter(fn($item) => filled($item->media))
            ->map(fn($item) => [
                'type' => $item->type ?? 'image',
                'src' => $this->imageUrl($item->media, ''),
                'title' => $item->title ?? '',
                'duration' => max((int) ($item->duration ?? 8), 1) * 1000,
            ])
            ->values()
            ->all();
    }

    public function shopMapUrl(Shop $shop): string
    {
        if ($shop->latitude !== null && $shop->longitude !== null) {
            return 'https://www.google.com/maps/dir/?api=1&destination=' . $shop->latitude . ',' . $shop->longitude;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($shop->address ?: $shop->name);
    }

    public function customerSignupWheelPayload(): ?array
    {
        $wheel = RewardWheel::query()
            ->where('key', RewardWheel::CUSTOMER_SIGNUP_DISCOUNTS)
            ->where('is_active', true)
            ->with(['segments' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
            ])
            ->first();

        if (! $wheel || $wheel->segments->count() < 2) {
            return null;
        }

        return [
            'title' => $wheel->title,
            'segments' => $wheel->segments
                ->map(fn($segment) => [
                    'label' => $segment->label,
                    'discount_value' => $segment->discount_value,
                    'discount_type' => $segment->discount_type,
                    'gift_image' => $segment->discount_type === 'gift' && $segment->gift_image ? asset($segment->gift_image) : null,
                    'color' => $segment->color,
                ])
                ->values()
                ->all(),
        ];
    }

    public function raffleSettings(): array
    {
        $defaults = [
            'whatsapp' => '',
        ];

        if (! Storage::disk('local')->exists('ozman_settings.json')) {
            return $defaults;
        }

        $stored = json_decode(Storage::disk('local')->get('ozman_settings.json'), true);
        $settings = is_array($stored) ? ($stored['raffle'] ?? []) : [];

        return array_replace($defaults, is_array($settings) ? $settings : []);
    }

    public function shopPublicUrl(Shop $shop): string
    {
        return $shop->publicUrl();
    }

    public function purchaseRewardWheelsPayload(?Shop $shop): array
    {
        if (! $shop?->exists) {
            return [];
        }

        $wheels = $shop->relationLoaded('rewardWheels')
            ? $shop->rewardWheels
            : $shop->rewardWheels()
                ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
                ->where('is_active', true)
                ->with(['segments' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                ])
                ->orderBy('min_order_total')
                ->get();

        return $wheels
            ->filter(fn($wheel) => $wheel->segments->count() >= 2)
            ->map(fn($wheel) => [
                'id' => $wheel->id,
                'title' => $wheel->title,
                'min_order_total' => (float) $wheel->min_order_total,
                'max_order_total' => $wheel->max_order_total !== null ? (float) $wheel->max_order_total : null,
                'segments' => $wheel->segments
                    ->map(fn($segment) => [
                        'label' => $segment->label,
                        'discount_value' => $segment->discount_value,
                        'discount_type' => $segment->discount_type,
                        'gift_image' => $segment->discount_type === 'gift' && $segment->gift_image ? asset($segment->gift_image) : null,
                        'color' => $segment->color,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
