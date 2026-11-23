<?php

namespace App\Http\Controllers;

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

class FrontController extends Controller
{
    public function __construct(private \App\Services\Storefront\StorefrontPayloads $payloads) {}

    public function csrfToken(): JsonResponse
    {
        return response()
            ->json(['token' => csrf_token()])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function index(?Shop $shop = null): View|RedirectResponse
    {
        if ($shop?->exists && $shop->catalog_type === 'krembo' && !request()->routeIs('krembo.catalog')) {
            return redirect()->route('krembo.store', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'dental') {
            return redirect()->route('dental.clinic', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'restaurant') {
            return redirect()->route('restaurant.menu', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'advertising_services') {
            return redirect()->route('advertising.store', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'electronics') {
            return redirect()->route('electronics.store', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'clothing') {
            return redirect()->route('clothing.store', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'cosmetics') {
            return redirect()->route('cosmetics.store', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'sweets') {
            return redirect()->route('sweets.store', $shop);
        }
        if ($shop?->exists && ($shop->catalog_type === 'furniture_appliances' || $shop->home_section === 'home_furniture')) {
            return redirect()->route('furniture.store', $shop);
        }
        if ($shop?->exists && $shop->catalog_type === 'real_estate') {
            return redirect()->route('real-estate.company', $shop);
        }

        $shopSummaries = Shop::query()
            ->where('is_active', true)
            ->select([
                'id', 'name', 'slug', 'catalog_type', 'home_section', 'logo', 'banner',
                'phone', 'whatsapp', 'address', 'latitude', 'longitude',
            ])
            ->latest()
            ->get();

        $ozmanSummary = $shopSummaries->firstWhere('slug', 'ozman');
        $selectedShopId = $shop?->exists ? $shop->id : $ozmanSummary?->id;
        $selectedShopId ??= $shopSummaries->first()?->id;

        $shop = $selectedShopId
            ? Shop::query()
                ->with($this->shopFrontRelations(
                    includeProducts: true,
                    ozmanCategoriesOnly: (int) $selectedShopId === (int) $ozmanSummary?->id,
                ))
                ->find($selectedShopId)
            : null;

        $ozmanShop = $ozmanSummary?->id
            ? ((int) $shop?->id === (int) $ozmanSummary->id
                ? $shop
                : Shop::query()
                    ->with($this->shopFrontRelations(includeProducts: true, ozmanCategoriesOnly: true))
                    ->find($ozmanSummary->id))
            : null;

        // The legacy catalog calls its featured company "ozmanShop". For a
        // Krembo company, its own products and network are the featured source.
        if ($shop?->catalog_type === 'krembo') $ozmanShop = $shop;

        $shops = $shopSummaries
            ->where('slug', '!=', 'ozman')
            ->values();

        $frontShopIds = collect([$shop?->id])
            ->filter()
            ->merge($shopSummaries->pluck('id'))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
        $frontShopOrder = $frontShopIds->flip();
        $frontShops = Shop::query()
            ->whereIn('id', $frontShopIds)
            ->with($this->shopFrontRelations())
            ->get()
            ->sortBy(fn(Shop $listedShop) => $frontShopOrder->get($listedShop->id, PHP_INT_MAX))
            ->values();

        $ozmanCategories = $ozmanShop?->categories ?? collect();
        $ozmanScreens = MainScreen::query()
            ->when($ozmanShop?->catalog_type === 'krembo', fn($query) => $query->where('shop_id', $ozmanShop->id), fn($query) => $query->forPlatform())
            ->where('is_active', true)
            ->latest()
            ->get();
        $ozmanBottomScreens = $ozmanScreens
            ->filter(fn(MainScreen $screen) => ($screen->placement ?? 'top') === 'bottom')
            ->values();
        $ozmanAdvertisements = Advertisement::query()
            ->where('is_active', true)
            ->where(function ($query) use ($ozmanShop) {
                if ($ozmanShop?->catalog_type === 'krembo') {
                    $query->where('shop_id', $ozmanShop->id);
                    return;
                }
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
            'frontData' => $this->frontData($frontShops, $ozmanCategories, $ozmanShop, $ozmanBottomScreens),
            'customerSignupWheel' => $this->payloads->customerSignupWheelPayload(),
            'purchaseRewardWheels' => $this->payloads->purchaseRewardWheelsPayload($shop),
            'raffleSettings' => $this->payloads->raffleSettings(),
        ]);
    }

    public function distributor(Distributor $distributor): View
    {
        return $this->distributorStoreView($distributor);
    }

    public function marketer(DistributorMarketer $marketer): View
    {
        abort_unless($marketer->is_active, 404);

        $marketer->load('distributor');

        return $this->distributorStoreView($marketer->distributor, $marketer);
    }

    private function distributorStoreView(Distributor $distributor, ?DistributorMarketer $marketer = null): View
    {
        abort_unless($distributor->is_active, 404);

        request()->session()->put('merchant_referral', [
            'distributor_id' => $distributor->id,
            'distributor_marketer_id' => $marketer?->id,
        ]);

        $distributor->load(['shop', 'user']);

        $ozmanShop = Shop::query()
            ->where('slug', 'ozman')
            ->with($this->shopFrontRelations(includeProducts: true, ozmanCategoriesOnly: true))
            ->first();

        $shopIds = collect();

        if ($distributor->user_id) {
            $shopIds = Shop::query()
                ->where('user_id', $distributor->user_id)
                ->where('is_active', true)
                ->pluck('id');
        }

        if ($distributor->shop_id) {
            $shopIds->push($distributor->shop_id);
        }

        $directDistributorShopIds = Shop::query()
            ->where('distributor_id', $distributor->id)
            ->where('is_active', true)
            ->pluck('id');

        $shopIds = $shopIds->merge($directDistributorShopIds);

        $marketerShopIds = Shop::query()
            ->where('is_active', true)
            ->whereHas('distributorMarketer', fn($query) => $query
                ->where('distributor_id', $distributor->id)
                ->where('is_active', true))
            ->pluck('id');

        $shopIds = $shopIds->merge($marketerShopIds);

        $shopIds = $shopIds
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        abort_if($shopIds->isEmpty(), 404);

        $shops = Shop::query()
            ->whereIn('id', $shopIds)
            ->where('is_active', true)
            ->with($this->shopFrontRelations())
            ->latest()
            ->get();

        abort_if($shops->isEmpty(), 404);

        $selectedShop = $shops->firstWhere('id', $distributor->shop_id) ?? $shops->first();
        if ($selectedShop->catalog_type === 'krembo') $ozmanShop = $selectedShop;
        $shops = $shops
            ->reject(fn(Shop $listedShop) => $listedShop->id === $selectedShop->id)
            ->prepend($selectedShop)
            ->values();

        $ozmanCategories = $ozmanShop?->categories ?? collect();

        $ozmanScreens = MainScreen::query()
            ->when($ozmanShop?->catalog_type === 'krembo', fn($query) => $query->where('shop_id', $ozmanShop->id), fn($query) => $query->forPlatform())
            ->where('is_active', true)
            ->latest()
            ->get();
        $ozmanBottomScreens = $ozmanScreens
            ->filter(fn(MainScreen $screen) => ($screen->placement ?? 'top') === 'bottom')
            ->values();

        return view('front.index', [
            'ozmanShop' => $ozmanShop,
            'shop' => $selectedShop,
            'shops' => $shops,
            'agents' => $ozmanShop?->agents ?? collect(),
            'distributors' => $ozmanShop?->distributors ?? collect(),
            'ozmanCategories' => $ozmanCategories,
            'ozmanScreens' => $ozmanScreens,
            'ozmanAdvertisements' => Advertisement::query()
                ->where('is_active', true)
                ->where(function ($query) use ($ozmanShop) {
                    if ($ozmanShop?->catalog_type === 'krembo') {
                        $query->where('shop_id', $ozmanShop->id);
                        return;
                    }
                    $query->whereNull('shop_id');

                    if ($ozmanShop?->exists) {
                        $query->orWhere('shop_id', $ozmanShop->id);
                    }
                })
                ->orderBy('sort_order')
                ->latest()
                ->get(),
            'frontData' => $this->frontData($shops, $ozmanCategories, $ozmanShop, $ozmanBottomScreens),
            'customerSignupWheel' => $this->payloads->customerSignupWheelPayload(),
            'purchaseRewardWheels' => $this->payloads->purchaseRewardWheelsPayload($selectedShop),
            'raffleSettings' => $this->payloads->raffleSettings(),
            'initialPersonContext' => [
                'type' => 'distributor',
                'id' => $distributor->id,
                'shop_id' => $selectedShop->id,
            ],
            'marketingContext' => [
                'source' => $marketer ? 'marketer' : 'distributor',
                'distributor_id' => $distributor->id,
                'distributor_name' => $distributor->name,
                'marketer_id' => $marketer?->id,
                'marketer_name' => $marketer?->name,
                'tracking_code' => $marketer?->tracking_code,
            ],
        ]);
    }

    public function dashboardPreview(Request $request): View
    {
        $selectedShop = $this->previewShop($request);

        $shop = $selectedShop->load([
            'social',
            'activeScreens',
            'distributorMarketer.distributor',
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
            'frontData' => $this->frontData($shops, $ozmanCategories, null, collect()),
            'customerSignupWheel' => null,
            'purchaseRewardWheels' => [],
            'raffleSettings' => $this->payloads->raffleSettings(),
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

    private function shopFrontRelations(bool $includeProducts = false, bool $ozmanCategoriesOnly = false): array
    {
        $relations = [
            'social',
            'activeScreens',
            'distributorMarketer.distributor',
            'rewardWheels' => fn($query) => $query
                ->where('wheel_type', RewardWheel::TYPE_PURCHASE_AMOUNT)
                ->where('is_active', true)
                ->with(['segments' => fn($segmentQuery) => $segmentQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                ])
                ->orderBy('min_order_total'),
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
                ->when($ozmanCategoriesOnly, fn($categoryQuery) => $categoryQuery->whereNull('agent_id'))
                ->with(['products' => fn($productQuery) => $productQuery
                    ->where('is_active', true)
                    ->when($ozmanCategoriesOnly, fn($query) => $query->whereNull('agent_id'))
                    ->with(['images', 'campaigns'])
                    ->latest()
                ])
                ->latest(),
        ];

        if ($includeProducts) {
            $relations['products'] = fn($query) => $query
                ->where('is_active', true)
                ->when($ozmanCategoriesOnly, fn($productQuery) => $productQuery->whereNull('agent_id'))
                ->with(['images', 'campaigns', 'category'])
                ->orderByDesc('is_featured')
                ->latest();
        }

        return $relations;
    }

    private function frontData($shops, $ozmanCategories, ?Shop $ozmanShop = null, $ozmanBottomScreens = null): array
    {
        $ozmanBottomScreens = collect($ozmanBottomScreens ?? []);
        $productsDb = [];
        foreach ($ozmanCategories as $category) {
            $categoryTitle = $category->localized('name');
            $productsDb[$categoryTitle] = $category->products
                ->map(fn($product) => $this->payloads->productPayload($product))
                ->values()
                ->all();
        }

        $centersData = $shops->map(function (Shop $shop) use (&$productsDb, $shops, $ozmanShop, $ozmanBottomScreens, $ozmanCategories) {
            if (! $shop->relationLoaded('categories')) {
                return $this->payloads->lightweightShopPayload($shop);
            }

            $shopProductsDb = [];
            $shopDepartments = [];

            $shopDepartments = $shop->categories->map(function ($category) use (&$productsDb, &$shopProductsDb) {
                    $products = $category->products->map(fn($product) => $this->payloads->productPayload($product))->values()->all();
                    $categoryTitle = $category->localized('name');
                    $productsDb[$categoryTitle] = $products;
                    $shopProductsDb[$categoryTitle] = $products;

                    return [
                        'title' => $categoryTitle,
                        'img' => $this->payloads->imageUrl($category->image, 'images/logo.jpg'),
                    ];
                })->values()->all();

            $showOzmanProducts = (bool) $shop->show_ozman_products
                && ! ($ozmanShop?->exists && $shop->id === $ozmanShop->id);

            if ($showOzmanProducts) {
                foreach ($ozmanCategories as $category) {
                    $categoryTitle = $category->localized('name');
                    $products = $category->products
                        ->map(fn($product) => $this->payloads->productPayload($product))
                        ->values()
                        ->all();

                    if (isset($shopProductsDb[$categoryTitle])) {
                        $shopProductsDb[$categoryTitle] = array_values(array_merge($shopProductsDb[$categoryTitle], $products));
                        $productsDb[$categoryTitle] = $shopProductsDb[$categoryTitle];

                        continue;
                    }

                    $productsDb[$categoryTitle] = $products;
                    $shopProductsDb[$categoryTitle] = $products;
                    $shopDepartments[] = [
                        'title' => $categoryTitle,
                        'img' => $this->payloads->imageUrl($category->image, 'images/logo.jpg'),
                        'shared_source' => 'ozman',
                    ];
                }
            }

            $displayItems = $shop->activeScreens->toBase()->concat($shop->advertisements ?? collect());
            if ($ozmanShop?->exists && $shop->id === $ozmanShop->id) {
                $displayItems = $ozmanBottomScreens->toBase()->concat($displayItems);
            }

            $shopOrderContext = $this->payloads->shopOrderContext($shop);

            return [
                'id' => $shop->id,
                'title' => $shop->name,
                'catalog_type' => $shop->catalog_type,
                'home_section' => $shop->home_section ?: $shop->catalog_type,
                'public_url' => $this->payloads->shopPublicUrl($shop),
                'img' => $this->payloads->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg'),
                'logo' => $this->payloads->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg'),
                'whatsapp_number' => $shopOrderContext['whatsapp_number'],
                'marketing_context' => $shopOrderContext['marketing_context'],
                'purchase_reward_wheels' => $this->payloads->purchaseRewardWheelsPayload($shop),
                'social_links' => $this->payloads->socialLinksPayload($shop),
                'agents' => $this->payloads->contactPeoplePayload($shop->agents ?? collect(), $shop, 'agent', $shopProductsDb, $shopDepartments, $shops),
                'distributors' => $this->payloads->contactPeoplePayload($shop->distributors ?? collect(), $shop, 'distributor', $shopProductsDb, $shopDepartments, $shops),
                'display_items' => $this->payloads->displayItemsPayload($displayItems),
                'departments' => $shopDepartments,
                'products_db' => $shopProductsDb,
                'address' => $shop->address,
                'latitude' => $shop->latitude !== null ? (float) $shop->latitude : null,
                'longitude' => $shop->longitude !== null ? (float) $shop->longitude : null,
                'map_url' => $this->payloads->shopMapUrl($shop),
            ];
        })->values()->all();

        if ($ozmanShop?->exists && ! collect($centersData)->contains('id', $ozmanShop->id)) {
            $ozmanProductsDb = [];
            $ozmanDepartments = [];

            $ozmanDepartments = $ozmanCategories->map(function ($category) use (&$productsDb, &$ozmanProductsDb) {
                    $products = $category->products->map(fn($product) => $this->payloads->productPayload($product))->values()->all();
                    $categoryTitle = $category->localized('name');
                    $productsDb[$categoryTitle] = $products;
                    $ozmanProductsDb[$categoryTitle] = $products;

                    return [
                        'title' => $categoryTitle,
                        'img' => $this->payloads->imageUrl($category->image, 'images/logo.jpg'),
                    ];
                })->values()->all();

            $centersData[] = [
                'id' => $ozmanShop->id,
                'title' => $ozmanShop->name,
                'catalog_type' => $ozmanShop->catalog_type,
                'home_section' => $ozmanShop->home_section ?: $ozmanShop->catalog_type,
                'public_url' => $this->payloads->shopPublicUrl($ozmanShop),
                'img' => $this->payloads->imageUrl($ozmanShop->logo ?: $ozmanShop->banner, 'images/logo.jpg'),
                'logo' => $this->payloads->imageUrl($ozmanShop->logo ?: $ozmanShop->banner, 'images/logo.jpg'),
                'whatsapp_number' => $this->payloads->contactWhatsappNumber($ozmanShop->whatsapp ?: $ozmanShop->phone),
                'marketing_context' => null,
                'purchase_reward_wheels' => $this->payloads->purchaseRewardWheelsPayload($ozmanShop),
                'social_links' => $this->payloads->socialLinksPayload($ozmanShop),
                'agents' => $this->payloads->contactPeoplePayload($ozmanShop->agents ?? collect(), $ozmanShop, 'agent', $ozmanProductsDb, $ozmanDepartments, $shops),
                'distributors' => $this->payloads->contactPeoplePayload($ozmanShop->distributors ?? collect(), $ozmanShop, 'distributor', $ozmanProductsDb, $ozmanDepartments, $shops),
                'display_items' => $this->payloads->displayItemsPayload($ozmanBottomScreens->toBase()->concat($ozmanShop->activeScreens)->concat($ozmanShop->advertisements ?? collect())),
                'departments' => $ozmanDepartments,
                'products_db' => $ozmanProductsDb,
                'address' => $ozmanShop->address,
                'latitude' => $ozmanShop->latitude !== null ? (float) $ozmanShop->latitude : null,
                'longitude' => $ozmanShop->longitude !== null ? (float) $ozmanShop->longitude : null,
                'map_url' => $this->payloads->shopMapUrl($ozmanShop),
            ];
        }

        $carouselProductsDb = $ozmanCategories
            ->mapWithKeys(fn($category) => [$category->localized('name') => [
                'name' => $category->localized('name'),
                'price' => '',
                'img' => $this->payloads->imageUrl($category->image, 'images/logo.jpg'),
                'gallery' => [$this->payloads->imageUrl($category->image, 'images/logo.jpg')],
                'description' => 'فئة من متجر Ozman.',
            ]])
            ->all();

        return [
            'centersData' => $centersData,
            'productsDb' => $productsDb,
            'carouselProductsDb' => $carouselProductsDb,
        ];
    }

}
