<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\MainScreen;
use App\Models\Product;
use App\Models\RewardWheel;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FrontController extends Controller
{
    public function index(?Shop $shop = null): View
    {
        $ozmanShop = Shop::query()
            ->where('slug', 'ozman')
            ->with([
                'social',
                'advertisements' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->latest(),
                'agents' => fn($query) => $query
                    ->where('is_active', true)
                    ->with(['categories' => fn($categoryQuery) => $categoryQuery
                        ->where('is_active', true)
                        ->latest()
                    ])
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->with(['images', 'campaigns', 'category'])
                        ->latest()
                    ])
                    ->latest(),
                'distributors' => fn($query) => $query->where('is_active', true)->latest(),
                'categories' => fn($query) => $query
                    ->where('is_active', true)
                    ->whereNull('agent_id')
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->whereNull('agent_id')
                        ->with(['images', 'campaigns'])
                        ->latest()
                    ])
                    ->latest(),
                'products' => fn($query) => $query
                    ->where('is_active', true)
                    ->whereNull('agent_id')
                    ->with(['images', 'campaigns', 'category'])
                    ->orderByDesc('is_featured')
                    ->latest(),
            ])
            ->first();

        $shops = Shop::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'ozman')
            ->with([
                'social',
                'advertisements' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->latest(),
                'agents' => fn($query) => $query
                    ->where('is_active', true)
                    ->with(['categories' => fn($categoryQuery) => $categoryQuery
                        ->where('is_active', true)
                        ->latest()
                    ])
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->with(['images', 'campaigns', 'category'])
                        ->latest()
                    ])
                    ->latest(),
                'distributors' => fn($query) => $query->where('is_active', true)->latest(),
                'categories' => fn($query) => $query
                    ->where('is_active', true)
                    ->whereNull('agent_id')
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->whereNull('agent_id')
                        ->with(['images', 'campaigns'])
                        ->latest()
                    ])
                    ->latest(),
            ])
            ->latest()
            ->get();

        $shop = $shop?->exists
            ? $shops->firstWhere('id', $shop->id) ?? $shop->load([
                'social',
                'advertisements' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->latest(),
                'agents' => fn($query) => $query
                    ->where('is_active', true)
                    ->with(['categories' => fn($categoryQuery) => $categoryQuery
                        ->where('is_active', true)
                        ->latest()
                    ])
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->with(['images', 'campaigns', 'category'])
                        ->latest()
                    ])
                    ->latest(),
                'distributors' => fn($query) => $query->where('is_active', true)->latest(),
                'categories' => fn($query) => $query
                    ->where('is_active', true)
                    ->whereNull('agent_id')
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->whereNull('agent_id')
                        ->with(['images', 'campaigns'])
                        ->latest()
                    ]),
            ])
            : $shops->first();

        if ($shop?->exists) {
            $shops = $shops
                ->reject(fn(Shop $listedShop) => $listedShop->id === $shop->id)
                ->prepend($shop)
                ->values();
        }

        if (! $shop?->exists && $ozmanShop?->exists) {
            $shop = $ozmanShop;
        }

        $frontShops = $shops->isNotEmpty()
            ? $shops
            : collect($shop?->exists ? [$shop] : []);

        $ozmanCategories = $ozmanShop?->categories ?? collect();
        $ozmanScreens = MainScreen::query()
            ->where('is_active', true)
            ->latest()
            ->get();
        $ozmanAdvertisements = Advertisement::query()
            ->where('is_active', true)
            ->where(function ($query) use ($ozmanShop) {
                $query->whereNull('shop_id');

                if ($ozmanShop?->exists) {
                    $query->orWhere('shop_id', $ozmanShop->id);
                }
            })
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return view('front.index', [
            'ozmanShop' => $ozmanShop,
            'shop' => $shop,
            'shops' => $shops,
            'agents' => $ozmanShop?->agents ?? collect(),
            'distributors' => $ozmanShop?->distributors ?? collect(),
            'ozmanCategories' => $ozmanCategories,
            'ozmanScreens' => $ozmanScreens,
            'ozmanAdvertisements' => $ozmanAdvertisements,
            'frontData' => $this->frontData($frontShops, $ozmanCategories, $ozmanShop),
            'customerSignupWheel' => $this->customerSignupWheelPayload(),
            'purchaseRewardWheels' => $this->purchaseRewardWheelsPayload(),
        ]);
    }

    public function dashboardPreview(Request $request): View
    {
        $selectedShop = $this->previewShop($request);

        $shop = $selectedShop->load([
            'social',
            'advertisements' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->latest(),
            'agents' => fn($query) => $query
                ->where('is_active', true)
                ->with(['categories' => fn($categoryQuery) => $categoryQuery
                    ->where('is_active', true)
                    ->latest()
                ])
                ->with(['products' => fn($productQuery) => $productQuery
                    ->where('is_active', true)
                    ->with(['images', 'campaigns', 'category'])
                    ->latest()
                ])
                ->latest(),
            'distributors' => fn($query) => $query->where('is_active', true)->latest(),
            'categories' => fn($query) => $query
                ->where('is_active', true)
                ->whereNull('agent_id')
                ->with(['products' => fn($productQuery) => $productQuery
                    ->where('is_active', true)
                    ->whereNull('agent_id')
                    ->with(['images', 'campaigns'])
                    ->latest()
                ])
                ->latest(),
        ]);

        $shops = collect([$shop]);
        $ozmanCategories = collect();

        return view('front.index', [
            'ozmanShop' => null,
            'shop' => $shop,
            'shops' => $shops,
            'agents' => $shop->agents,
            'distributors' => $shop->distributors,
            'ozmanCategories' => $ozmanCategories,
            'ozmanScreens' => collect(),
            'ozmanAdvertisements' => collect(),
            'frontData' => $this->frontData($shops, $ozmanCategories, null),
            'customerSignupWheel' => null,
            'purchaseRewardWheels' => [],
            'isDashboardPreview' => true,
        ]);
    }

    private function previewShop(Request $request): Shop
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $shopId = $request->integer('shop_id') ?: (int) $request->session()->get('current_shop_id');

        if ($user->isSuperAdmin() || $user->isEmployee()) {
            return Shop::query()
                ->where('slug', '!=', 'ozman')
                ->when($shopId, fn($query) => $query->whereKey($shopId))
                ->latest()
                ->firstOrFail();
        }

        $ownedShopIds = $user->accessibleShopIds();
        if ($shopId && ! in_array((int) $shopId, $ownedShopIds, true)) {
            $shopId = null;
        }

        return Shop::query()
            ->whereIn('id', $ownedShopIds)
            ->when($shopId, fn($query) => $query->whereKey($shopId))
            ->firstOrFail();
    }

    private function frontData($shops, $ozmanCategories, ?Shop $ozmanShop = null): array
    {
        $productsDb = [];
        foreach ($ozmanCategories as $category) {
            $categoryTitle = $category->localized('name');
            $productsDb[$categoryTitle] = $category->products
                ->map(fn($product) => $this->productPayload($product))
                ->values()
                ->all();
        }

        $centersData = $shops->map(function (Shop $shop) use (&$productsDb, $shops) {
            $shopProductsDb = [];
            $shopDepartments = [];

            $shopDepartments = $shop->categories->map(function ($category) use (&$productsDb, &$shopProductsDb) {
                    $products = $category->products->map(fn($product) => $this->productPayload($product))->values()->all();
                    $categoryTitle = $category->localized('name');
                    $productsDb[$categoryTitle] = $products;
                    $shopProductsDb[$categoryTitle] = $products;

                    return [
                        'title' => $categoryTitle,
                        'img' => $this->imageUrl($category->image, 'images/logo.jpg'),
                    ];
                })->values()->all();

            return [
                'id' => $shop->id,
                'title' => $shop->name,
                'img' => $this->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg'),
                'logo' => $this->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg'),
                'social_links' => $this->socialLinksPayload($shop),
                'agents' => $this->contactPeoplePayload($shop->agents ?? collect(), $shop, 'agent', $shopProductsDb, $shopDepartments, $shops),
                'distributors' => $this->contactPeoplePayload($shop->distributors ?? collect(), $shop, 'distributor', $shopProductsDb, $shopDepartments, $shops),
                'display_items' => $this->displayItemsPayload($shop->advertisements ?? collect()),
                'departments' => $shopDepartments,
                'products_db' => $shopProductsDb,
                'address' => $shop->address,
                'latitude' => $shop->latitude !== null ? (float) $shop->latitude : null,
                'longitude' => $shop->longitude !== null ? (float) $shop->longitude : null,
                'map_url' => $this->shopMapUrl($shop),
            ];
        })->values()->all();

        if ($ozmanShop?->exists && ! collect($centersData)->contains('id', $ozmanShop->id)) {
            $ozmanProductsDb = [];
            $ozmanDepartments = [];

            $ozmanDepartments = $ozmanCategories->map(function ($category) use (&$productsDb, &$ozmanProductsDb) {
                    $products = $category->products->map(fn($product) => $this->productPayload($product))->values()->all();
                    $categoryTitle = $category->localized('name');
                    $productsDb[$categoryTitle] = $products;
                    $ozmanProductsDb[$categoryTitle] = $products;

                    return [
                        'title' => $categoryTitle,
                        'img' => $this->imageUrl($category->image, 'images/logo.jpg'),
                    ];
                })->values()->all();

            $centersData[] = [
                'id' => $ozmanShop->id,
                'title' => $ozmanShop->name,
                'img' => $this->imageUrl($ozmanShop->logo ?: $ozmanShop->banner, 'images/logo.jpg'),
                'logo' => $this->imageUrl($ozmanShop->logo ?: $ozmanShop->banner, 'images/logo.jpg'),
                'social_links' => $this->socialLinksPayload($ozmanShop),
                'agents' => $this->contactPeoplePayload($ozmanShop->agents ?? collect(), $ozmanShop, 'agent', $ozmanProductsDb, $ozmanDepartments, $shops),
                'distributors' => $this->contactPeoplePayload($ozmanShop->distributors ?? collect(), $ozmanShop, 'distributor', $ozmanProductsDb, $ozmanDepartments, $shops),
                'display_items' => $this->displayItemsPayload($ozmanShop->advertisements ?? collect()),
                'departments' => $ozmanDepartments,
                'products_db' => $ozmanProductsDb,
                'address' => $ozmanShop->address,
                'latitude' => $ozmanShop->latitude !== null ? (float) $ozmanShop->latitude : null,
                'longitude' => $ozmanShop->longitude !== null ? (float) $ozmanShop->longitude : null,
                'map_url' => $this->shopMapUrl($ozmanShop),
            ];
        }

        $carouselProductsDb = $ozmanCategories
            ->mapWithKeys(fn($category) => [$category->localized('name') => [
                'name' => $category->localized('name'),
                'price' => '',
                'img' => $this->imageUrl($category->image, 'images/logo.jpg'),
                'gallery' => [$this->imageUrl($category->image, 'images/logo.jpg')],
                'description' => 'فئة من متجر Ozman.',
            ]])
            ->all();

        return [
            'centersData' => $centersData,
            'productsDb' => $productsDb,
            'carouselProductsDb' => $carouselProductsDb,
        ];
    }

    private function productPayload(Product $product): array
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
                'offer_type' => $campaign->offer_type,
                'unit_key' => $campaign->unit_key,
                'offer_quantity' => $campaign->offer_quantity,
                'offer_price' => $campaign->offer_price !== null ? (float) $campaign->offer_price : null,
                'offer_note' => $campaign->localized('offer_note'),
                'starts_at' => $campaign->starts_at?->toDateString(),
                'ends_at' => $campaign->ends_at?->toDateString(),
            ])
            ->filter(fn($campaign) => filled($campaign['src']) || filled($campaign['title']) || filled($campaign['offer_note']))
            ->values()
            ->all();

        $image = $this->imageUrl($product->main_image, 'images/1.jpg');
        $merchantPrice = $product->merchant_price !== null ? (float) $product->merchant_price : null;
        $packagePrice = $product->package_price !== null ? (float) $product->package_price : null;
        $palletPrice = $product->pallet_price !== null ? (float) $product->pallet_price : null;
        $cartonPrice = $product->carton_price !== null ? (float) $product->carton_price : null;

        return [
            'name' => $product->localized('name'),
            'price' => number_format((float) ($product->discount_price ?: $product->price), 2) . ' ₪',
            'customer_price' => number_format((float) ($product->discount_price ?: $product->price), 2) . ' ₪',
            'merchant_price' => $merchantPrice !== null ? number_format($merchantPrice, 2) . ' ₪' : null,
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

    private function imageUrl(?string $path, string $fallback): string
    {
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

    private function socialLinksPayload(?Shop $shop): array
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

        return collect([
            ['title' => 'فيسبوك', 'icon' => 'fab fa-facebook-f', 'url' => $normalize($social->facebook)],
            ['title' => 'تويتر', 'icon' => 'fab fa-twitter', 'url' => $normalize($social->twitter)],
            ['title' => 'انستجرام', 'icon' => 'fab fa-instagram', 'url' => $normalize($social->instagram)],
            ['title' => 'تيك توك', 'icon' => 'fab fa-tiktok', 'url' => $normalize($social->tiktok)],
            ['title' => 'تلجرام', 'icon' => 'fab fa-telegram', 'url' => $normalize($social->telegram)],
            ['title' => 'يوتيوب', 'icon' => 'fab fa-youtube', 'url' => $normalize($social->youtube)],
            ['title' => 'سناب شات', 'icon' => 'fab fa-snapchat', 'url' => $normalize($social->snapchat)],
        ])->filter(fn($item) => filled($item['url']))->values()->all();
    }

    private function contactPeoplePayload($people, Shop $shop, string $type, array $shopProductsDb = [], array $shopDepartments = [], $availableShops = null): array
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
                    'name' => $person->name,
                    'image' => $person->image ? $this->imageUrl($person->image, 'images/logo.jpg') : $fallbackLogo,
                    'contact' => $person->phone ?: $person->whatsapp ?: $shop->name,
                    'shop_id' => $shop->id,
                    'shop_title' => $shop->name,
                    'owned_shops' => $ownedShops,
                    'departments' => $mergedDepartments,
                    'products_db' => $mergedProductsDb,
                ];
            })
            ->values()
            ->all();
    }

    private function ownedShopChoicesForPerson($person, $availableShops): array
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

    private function mergeProductsDb(array $shopProductsDb, array $personProductsDb): array
    {
        $merged = $shopProductsDb;

        foreach ($personProductsDb as $categoryName => $products) {
            $merged[$categoryName] = array_values(array_merge($merged[$categoryName] ?? [], $products));
        }

        return $merged;
    }

    private function mergeDepartments(array $shopDepartments, array $personDepartments): array
    {
        $merged = collect($shopDepartments)->keyBy('title');

        foreach ($personDepartments as $department) {
            $merged->put($department['title'], $department);
        }

        return $merged->values()->all();
    }

    private function personProductsDb($products, $categories): array
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

    private function personDepartments(array $productsDb, Shop $shop, $categories): array
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

    private function displayItemsPayload($items): array
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

    private function shopMapUrl(Shop $shop): string
    {
        if ($shop->latitude !== null && $shop->longitude !== null) {
            return 'https://www.google.com/maps/dir/?api=1&destination=' . $shop->latitude . ',' . $shop->longitude;
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($shop->address ?: $shop->name);
    }

    private function customerSignupWheelPayload(): ?array
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

    private function purchaseRewardWheelsPayload(): array
    {
        return RewardWheel::query()
            ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
            ->where('is_active', true)
            ->with(['segments' => fn($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
            ])
            ->orderBy('min_order_total')
            ->get()
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
