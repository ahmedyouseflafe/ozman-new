<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCampaign;
use App\Models\ProductImage;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private \App\Services\Products\ProductMedia $media,
        private \App\Services\Products\ProductCampaigns $campaigns,
        private \App\Services\Products\ProductVariants $variants,
    ) {
    }

    public function index(Request $request): View
    {
        $shops = $this->accessibleShops();
        $currentShop = null;

        if ($request->user()?->isShopOwner() || $request->filled('shop_id')) {
            $preferredShopId = $request->integer('shop_id')
                ?: (int) $request->session()->get('current_shop_id')
                ?: $this->firstAccessibleShopId();
            $currentShop = $shops->firstWhere('id', $preferredShopId) ?: $shops->first();
        }

        $products = Product::query()
            ->with(['shop', 'category'])
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $this->scopeToAccessibleShops($query))
            ->when($currentShop, fn($query) => $query->where('shop_id', $currentShop->id))
            ->when(auth()->user()?->isAgent(), fn($query) => $query->whereIn('agent_id', $this->currentUserAgentIds()))
            ->latest()
            ->get();

        $categories = $this->scopeToAccessibleShops(Category::query())
            ->when($currentShop, fn($query) => $query->where('shop_id', $currentShop->id))
            ->when($request->user()?->isAgent(), fn($query) => $query->whereIn('agent_id', $this->currentUserAgentIds()))
            ->orderBy('name')
            ->get();

        return view('admin.products.products', [
            'products' => $products,
            'categories' => $categories,
            'currentShop' => $currentShop,
            'productsCount' => $products->count(),
            'featuredProductsCount' => $products->where('is_featured', true)->count(),
            // Most shops never fill in a quantity (it defaults to 0), so "out of stock" only means
            // something once the shop actually counts stock on at least one item.
            'tracksStock' => $products->contains(fn ($product) => (int) $product->quantity > 0),
            'outOfStockProductsCount' => $products->where('quantity', '<=', 0)->count(),
            'hiddenProductsCount' => $products->where('is_active', false)->count(),
            'averageProductPrice' => round($products->avg('price') ?: 0),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeProductManagement();

        $options = $this->formOptions();
        $preferredShopId = $request->integer('shop_id')
            ?: (int) $request->session()->get('current_shop_id')
            ?: $this->firstAccessibleShopId();
        $selectedShop = $options['shops']->firstWhere('id', $preferredShopId)
            ?: ($request->user()?->isShopOwner() ? $options['shops']->first() : null);

        return view('admin.products.products_create', array_merge(
            $options,
            [
                'selectedShopId' => $selectedShop?->id,
                'selectedShop' => $selectedShop,
                'lockShopSelection' => $request->user()?->isShopOwner() && $selectedShop !== null,
                'selectedCategoryId' => $request->integer('category_id') ?: null,
            ]
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeProductManagement();

        $data = $this->validatedData($request);
        $this->syncLegacyPrices($data);
        unset($data['images'], $data['campaigns'], $data['variants']);
        $this->normalizeShopId($data);
        $data['catalog_attributes'] = $this->catalogAttributes($request, (int) $data['shop_id']);
        $isRestaurant = $this->isRestaurantShop((int) $data['shop_id']);
        $this->applyAgentOwnership($data);
        $this->authorizeCategoryForShop((int) $data['category_id'], (int) $data['shop_id']);
        $this->authorizeAgentForShop(isset($data['agent_id']) ? (int) $data['agent_id'] : null, (int) $data['shop_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $data['name_translations'] = $this->localizedInput($request, 'name', $data['name']);
        $data['description_translations'] = $this->localizedInput($request, 'description', $data['description'] ?? null);
        $data['quantity'] = $isRestaurant ? 1 : ($data['quantity'] ?? 0);
        $data['rating'] = $data['rating'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->media->storeProductImage(
                $request->file('main_image'),
                'products/main',
                $this->isElectronicsShop((int) $data['shop_id'])
            );
        }

        if ($request->hasFile('video')) {
            $data['video'] = $this->media->storeUpload($request, 'video', 'products/videos');
        }

        $product = Product::create($data);
        $this->syncVariants($request, $product);
        $this->media->storeGalleryImages($request, $product);
        $createdCampaigns = $this->campaigns->storeCampaigns($request, $product);

        $this->notifySuperAdmin(
            'product_created',
            $product,
            'تمت إضافة منتج جديد',
            "المتجر {$product->shop?->name} أضاف منتج: {$product->name}",
            route('products.show', $product)
        );

        if ((int) $product->quantity <= 0) {
            $this->notifySuperAdmin(
                'product_out_of_stock',
                $product,
                'منتج منتهي الكمية',
                "المنتج {$product->name} في متجر {$product->shop?->name} كميته صفر.",
                route('products.show', $product)
            );
        }

        foreach ($createdCampaigns as $campaign) {
            $this->notifySuperAdmin(
                'campaign_created',
                $campaign,
                'تمت إضافة حملة جديدة',
                "المتجر {$product->shop?->name} أضاف حملة للمنتج: {$product->name}",
                route('products.show', $product),
                ['shop_id' => $product->shop_id, 'product_id' => $product->id, 'product_name' => $product->name]
            );
        }

        return redirect()
            ->route('products')
            ->with('status', $isRestaurant ? 'تمت إضافة الوجبة بنجاح.' : 'تمت إضافة المنتج بنجاح.');
    }

    public function show(Product $product): View
    {
        $this->authorizeShopAccess($product);
        $this->authorizeProductVisibility($product);
        $product->load(['shop', 'category', 'images', 'campaigns', 'variants']);

        return view('admin.products.products_show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorizeProductManagement($product);
        $this->authorizeShopAccess($product);
        $product->load(['images', 'campaigns', 'variants']);

        return view('admin.products.products_edit', array_merge(
            [
                'product' => $product,
                'selectedShop' => $product->shop,
                'lockShopSelection' => auth()->user()?->isShopOwner() === true,
            ],
            $this->formOptions()
        ));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProductManagement($product);
        $wasInStock = (int) $product->quantity > 0;

        $data = $this->validatedData($request, $product);
        $this->syncLegacyPrices($data, $product);
        unset(
            $data['images'],
            $data['campaigns'],
            $data['existing_campaigns'],
            $data['delete_campaign_ids'],
            $data['delete_image_ids'],
            $data['delete_main_image'],
            $data['delete_video'],
            $data['variants']
        );
        $this->normalizeShopId($data);
        $data['catalog_attributes'] = $this->catalogAttributes($request, (int) $data['shop_id']);
        $isRestaurant = $this->isRestaurantShop((int) $data['shop_id']);
        $this->applyAgentOwnership($data, $product);
        $this->authorizeCategoryForShop((int) $data['category_id'], (int) $data['shop_id']);
        $this->authorizeAgentForShop(isset($data['agent_id']) ? (int) $data['agent_id'] : null, (int) $data['shop_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name'], $product);
        $data['name_translations'] = $this->localizedInput($request, 'name', $data['name']);
        $data['description_translations'] = $this->localizedInput($request, 'description', $data['description'] ?? null);
        $data['quantity'] = $isRestaurant ? 1 : ($data['quantity'] ?? 0);
        $data['rating'] = $data['rating'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('main_image')) {
            $this->media->deleteUpload($product->main_image);
            $data['main_image'] = $this->media->storeProductImage(
                $request->file('main_image'),
                'products/main',
                $this->isElectronicsShop((int) $data['shop_id'])
            );
        } elseif ($request->boolean('delete_main_image')) {
            $this->media->deleteUpload($product->main_image);
            $data['main_image'] = null;
        }

        if ($request->hasFile('video')) {
            $this->media->deleteUpload($product->video);
            $data['video'] = $this->media->storeUpload($request, 'video', 'products/videos');
        } elseif ($request->boolean('delete_video')) {
            $this->media->deleteUpload($product->video);
            $data['video'] = null;
        }

        $product->update($data);
        $this->syncVariants($request, $product);
        $this->media->deleteGalleryImages($request, $product);
        $this->campaigns->deleteCampaigns($request, $product);
        $this->campaigns->updateCampaigns($request, $product);
        $this->media->storeGalleryImages($request, $product);
        $createdCampaigns = $this->campaigns->storeCampaigns($request, $product);

        if ($wasInStock && (int) $product->quantity <= 0) {
            $this->notifySuperAdmin(
                'product_out_of_stock',
                $product,
                'منتج منتهي الكمية',
                "المنتج {$product->name} في متجر {$product->shop?->name} وصلت كميته إلى صفر.",
                route('products.show', $product)
            );
        }

        foreach ($createdCampaigns as $campaign) {
            $this->notifySuperAdmin(
                'campaign_created',
                $campaign,
                'تمت إضافة حملة جديدة',
                "المتجر {$product->shop?->name} أضاف حملة للمنتج: {$product->name}",
                route('products.show', $product),
                ['shop_id' => $product->shop_id, 'product_id' => $product->id, 'product_name' => $product->name]
            );
        }

        return redirect()
            ->route('products')
            ->with('status', $isRestaurant ? 'تم تحديث الوجبة بنجاح.' : 'تم تحديث المنتج بنجاح.');
    }

    public function quickSettings(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProductManagement($product);
        $this->authorizeShopAccess($product);

        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['required', 'boolean'],
        ]);

        $product->update([
            'price' => $data['price'],
            'is_active' => (bool) $data['is_active'],
        ]);

        return response()->json([
            'message' => 'تم حفظ إعدادات المنتج.',
            'price' => (float) $product->price,
            'is_active' => (bool) $product->is_active,
        ]);
    }

    public function moveCategory(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProductManagement($product);
        $this->authorizeShopAccess($product);
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('shop_id', $product->shop_id)],
        ]);
        $this->authorizeCategoryForShop((int) $data['category_id'], (int) $product->shop_id);
        // A category move must never overwrite unsaved prices, stock or other fields.
        $product->update(['category_id' => $data['category_id']]);

        return response()->json([
            'message' => 'تم نقل المنتج إلى القسم الجديد.',
            'category_id' => $product->category_id,
            'category_name' => $product->category()->value('name'),
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeProductManagement($product);
        $this->authorizeShopAccess($product);
        $isRestaurant = $product->shop?->catalog_type === 'restaurant';
        $product->load(['images', 'campaigns']);
        $this->media->deleteUpload($product->main_image);
        $this->media->deleteUpload($product->video);

        foreach ($product->images as $image) {
            $this->media->deleteUpload($image->image);
        }

        foreach ($product->campaigns as $campaign) {
            $this->media->deleteUpload($campaign->media);
        }

        $product->delete();

        return redirect()
            ->route('products')
            ->with('status', $isRestaurant ? 'تم حذف الوجبة بنجاح.' : 'تم حذف المنتج بنجاح.');
    }

    private function formOptions(): array
    {
        $agentsQuery = $this->scopeToAccessibleShops(Agent::query())
            ->with('shop')
            ->orderBy('name');
        $categoriesQuery = $this->scopeToAccessibleShops(Category::query())
            ->with('shop')
            ->orderBy('name');

        if (auth()->user()?->isAgent()) {
            $agentsQuery->whereIn('id', $this->currentUserAgentIds());
            $categoriesQuery->whereIn('agent_id', $this->currentUserAgentIds());
        }

        return [
            'shops' => $this->accessibleShops(),
            'categories' => $categoriesQuery->get(),
            'agents' => $agentsQuery->get(),
            'catalogTypes' => config('catalog_types', []),
        ];
    }

    private function authorizeProductManagement(?Product $product = null): void
    {
        $user = auth()->user();

        if ($user?->isDistributor() && ! $user->canAccessRouteName(request()->route()?->getName())) {
            abort(403);
        }

        if (! $user?->isAgent()) {
            return;
        }

        if (! $product) {
            return;
        }

        abort_unless(in_array((int) $product->agent_id, $this->currentUserAgentIds(), true), 403);
    }

    private function authorizeProductVisibility(Product $product): void
    {
        if (! auth()->user()?->isAgent()) {
            return;
        }

        abort_unless(in_array((int) $product->agent_id, $this->currentUserAgentIds(), true), 403);
    }

    private function applyAgentOwnership(array &$data, ?Product $product = null): void
    {
        $user = auth()->user();

        if (! $user?->isAgent()) {
            return;
        }

        $agentId = $product?->agent_id ?: $this->currentUserAgentIdForShop((int) $data['shop_id']);
        abort_if(! $agentId, 403);

        $data['agent_id'] = $agentId;
    }

    private function currentUserAgentIds(): array
    {
        return \App\Support\AgentAccess::idsFor(auth()->user());
    }

    private function currentUserAgentIdForShop(int $shopId): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return Agent::query()
            ->where('shop_id', $shopId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->value('id');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'shop_id' => ['required', 'integer', Rule::exists('shops', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_he' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($product?->id),
            ],
            'description' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_he' => ['nullable', 'string'],
            'catalog_attributes' => ['nullable', 'array'],
            'catalog_attributes.*' => ['nullable'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'discount_ends_at' => ['nullable', 'date', 'after:now'],
            'customer_package_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'show_customer_package_price' => ['nullable', 'boolean'],
            'customer_carton_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'customer_pallet_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'show_customer_carton_price' => ['nullable', 'boolean'],
            'show_customer_pallet_price' => ['nullable', 'boolean'],
            'merchant_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'package_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'pallet_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'carton_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'show_package_price' => ['nullable', 'boolean'],
            'show_carton_price' => ['nullable', 'boolean'],
            'show_pallet_price' => ['nullable', 'boolean'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'main_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:1048576'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:20480'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:1048576'],
            'delete_main_image' => ['nullable', 'boolean'],
            'delete_video' => ['nullable', 'boolean'],
            'delete_image_ids' => ['nullable', 'array'],
            'delete_image_ids.*' => ['integer'],
            'campaigns' => ['nullable', 'array'],
            'campaigns.*.title' => ['nullable', 'string', 'max:255'],
            'campaigns.*.title_en' => ['nullable', 'string', 'max:255'],
            'campaigns.*.title_he' => ['nullable', 'string', 'max:255'],
            'campaigns.*.type' => ['nullable', Rule::in(['image', 'video'])],
            'campaigns.*.media' => ['nullable', 'file', 'max:1048576'],
            'campaigns.*.offer_type' => ['nullable', Rule::in(['bundle_price', 'range_price', 'custom'])],
            'campaigns.*.unit_key' => ['nullable', Rule::in(['package', 'pallet', 'carton'])],
            'campaigns.*.offer_quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'campaigns.*.min_quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'campaigns.*.max_quantity' => ['nullable', 'integer', 'min:1', 'max:999999', 'gte:campaigns.*.min_quantity'],
            'campaigns.*.offer_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'campaigns.*.offer_note' => ['nullable', 'string', 'max:500'],
            'campaigns.*.offer_note_en' => ['nullable', 'string', 'max:500'],
            'campaigns.*.offer_note_he' => ['nullable', 'string', 'max:500'],
            'campaigns.*.starts_at' => ['nullable', 'date'],
            'campaigns.*.ends_at' => ['nullable', 'date'],
            'existing_campaigns' => ['nullable', 'array'],
            'existing_campaigns.*.title' => ['nullable', 'string', 'max:255'],
            'existing_campaigns.*.title_en' => ['nullable', 'string', 'max:255'],
            'existing_campaigns.*.title_he' => ['nullable', 'string', 'max:255'],
            'existing_campaigns.*.type' => ['nullable', Rule::in(['image', 'video'])],
            'existing_campaigns.*.media' => ['nullable', 'file', 'max:1048576'],
            'existing_campaigns.*.offer_type' => ['nullable', Rule::in(['bundle_price', 'range_price', 'custom'])],
            'existing_campaigns.*.unit_key' => ['nullable', Rule::in(['package', 'pallet', 'carton'])],
            'existing_campaigns.*.offer_quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'existing_campaigns.*.min_quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'existing_campaigns.*.max_quantity' => ['nullable', 'integer', 'min:1', 'max:999999', 'gte:existing_campaigns.*.min_quantity'],
            'existing_campaigns.*.offer_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'existing_campaigns.*.offer_note' => ['nullable', 'string', 'max:500'],
            'existing_campaigns.*.offer_note_en' => ['nullable', 'string', 'max:500'],
            'existing_campaigns.*.offer_note_he' => ['nullable', 'string', 'max:500'],
            'existing_campaigns.*.starts_at' => ['nullable', 'date'],
            'existing_campaigns.*.ends_at' => ['nullable', 'date'],
            'delete_campaign_ids' => ['nullable', 'array'],
            'delete_campaign_ids.*' => ['integer'],
            'variants' => ['nullable', 'array', 'max:200'],
            'variants.*.size' => ['nullable', 'string', 'max:100'],
            'variants.*.storage' => ['nullable', 'string', 'max:100'],
            'variants.*.ram' => ['nullable', 'string', 'max:100'],
            'variants.*.color' => ['nullable', 'string', 'max:100'],
            'variants.*.color_name' => ['nullable', 'string', 'max:100'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.quantity' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function syncVariants(Request $request, Product $product): void
    {
        $this->variants->sync($product, (array) $request->input('variants', []));
    }

    private function catalogAttributes(Request $request, int $shopId): array
    {
        $shop = Shop::query()->findOrFail($shopId);
        $fields = config('catalog_types.' . ($shop->catalog_type ?: 'general') . '.fields', []);
        $input = $request->input('catalog_attributes', []);
        $attributes = [];

        foreach ($fields as $key => $definition) {
            $value = $input[$key] ?? null;
            if (($definition['type'] ?? null) === 'list') {
                $value = collect(is_array($value) ? $value : explode(',', (string) $value))
                    ->map(fn ($item) => trim($item))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            } elseif (($definition['type'] ?? null) === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (($definition['type'] ?? null) === 'number') {
                $value = filled($value) ? (float) $value : null;
            } else {
                $value = is_string($value) ? trim($value) : $value;
            }

            if ($value !== null && $value !== '' && $value !== []) {
                $attributes[$key] = $value;
            }
        }

        if ($shop->catalog_type === 'restaurant') {
            $request->validate(['meal_choice_groups'=>['nullable','array','max:10']]);
            $attributes['meal_choice_groups'] = app(\App\Services\MealChoiceService::class)->normalize($request->input('meal_choice_groups', []) ?? [], $shop->id);
        }
        return $attributes;
    }

    private function syncLegacyPrices(array &$data, ?Product $product = null): void
    {
        $data['price'] = $data['price']
            ?? $data['customer_package_price']
            ?? $data['customer_carton_price']
            ?? $data['customer_pallet_price']
            ?? $data['package_price']
            ?? $data['carton_price']
            ?? $data['pallet_price']
            ?? $product?->price
            ?? 0;
        $data['merchant_price'] = $data['package_price']
            ?? $data['carton_price']
            ?? $data['pallet_price']
            ?? null;
        $data['discount_price'] = filled($data['discount_price'] ?? null)
            ? $data['discount_price']
            : null;
        // The form's time is the shop's local time; it is kept in UTC like every other time.
        if (filled($data['discount_ends_at'] ?? null)) {
            $data['discount_ends_at'] = \Illuminate\Support\Carbon::parse($data['discount_ends_at'], 'Asia/Hebron')->utc();
        }
    }

    private function uniqueSlug(string $value, ?Product $product = null): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'product';
        $slug = $base;
        $counter = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($product, fn($query) => $query->where('id', '!=', $product->id))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function authorizeCategoryForShop(int $categoryId, int $shopId): void
    {
        abort_unless(
            Category::query()
                ->whereKey($categoryId)
                ->where('shop_id', $shopId)
                ->when(auth()->user()?->isAgent(), fn($query) => $query->whereIn('agent_id', $this->currentUserAgentIds()))
                ->exists(),
            422
        );
    }

    private function authorizeAgentForShop(?int $agentId, int $shopId): void
    {
        if (! $agentId) {
            return;
        }

        abort_unless(
            Agent::query()
                ->whereKey($agentId)
                ->where('shop_id', $shopId)
                ->exists(),
            422
        );
    }

    private function localizedInput(Request $request, string $field, ?string $arabicValue = null): array
    {
        return array_filter([
            'ar' => $arabicValue,
            'en' => $request->input("{$field}_en"),
            'he' => $request->input("{$field}_he"),
        ], fn($value) => filled($value));
    }

    private function isElectronicsShop(int $shopId): bool
    {
        return Shop::query()->whereKey($shopId)->where('catalog_type', 'electronics')->exists();
    }

    private function isRestaurantShop(int $shopId): bool
    {
        return Shop::query()->whereKey($shopId)->where('catalog_type', 'restaurant')->exists();
    }

}
