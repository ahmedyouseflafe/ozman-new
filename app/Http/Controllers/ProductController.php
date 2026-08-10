<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCampaign;
use App\Models\ProductImage;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['shop', 'category'])
            ->when(! $this->hasGlobalDashboardAccess(), fn($query) => $this->scopeToAccessibleShops($query))
            ->when(auth()->user()?->isAgent(), fn($query) => $query->whereIn('agent_id', $this->currentUserAgentIds()))
            ->latest()
            ->get();

        return view('admin.products.products', [
            'products' => $products,
            'categories' => $this->scopeToAccessibleShops(Category::query())->orderBy('name')->get(),
            'productsCount' => $products->count(),
            'featuredProductsCount' => $products->where('is_featured', true)->count(),
            'outOfStockProductsCount' => $products->where('quantity', '<=', 0)->count(),
            'averageProductPrice' => round($products->avg('price') ?: 0),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeProductManagement();

        return view('admin.products.products_create', array_merge(
            $this->formOptions(),
            [
                'selectedShopId' => $request->integer('shop_id') ?: null,
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
        $this->applyAgentOwnership($data);
        $this->authorizeCategoryForShop((int) $data['category_id'], (int) $data['shop_id']);
        $this->authorizeAgentForShop(isset($data['agent_id']) ? (int) $data['agent_id'] : null, (int) $data['shop_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
        $data['name_translations'] = $this->localizedInput($request, 'name', $data['name']);
        $data['description_translations'] = $this->localizedInput($request, 'description', $data['description'] ?? null);
        $data['quantity'] = $data['quantity'] ?? 0;
        $data['rating'] = $data['rating'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->storeUpload($request, 'main_image', 'products/main');
        }

        if ($request->hasFile('video')) {
            $data['video'] = $this->storeUpload($request, 'video', 'products/videos');
        }

        $product = Product::create($data);
        $this->syncVariants($request, $product);
        $this->storeGalleryImages($request, $product);
        $createdCampaigns = $this->storeCampaigns($request, $product);

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
            ->with('status', 'تمت إضافة المنتج بنجاح.');
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
            ['product' => $product],
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
        $this->applyAgentOwnership($data, $product);
        $this->authorizeCategoryForShop((int) $data['category_id'], (int) $data['shop_id']);
        $this->authorizeAgentForShop(isset($data['agent_id']) ? (int) $data['agent_id'] : null, (int) $data['shop_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name'], $product);
        $data['name_translations'] = $this->localizedInput($request, 'name', $data['name']);
        $data['description_translations'] = $this->localizedInput($request, 'description', $data['description'] ?? null);
        $data['quantity'] = $data['quantity'] ?? 0;
        $data['rating'] = $data['rating'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('main_image')) {
            $this->deleteUpload($product->main_image);
            $data['main_image'] = $this->storeUpload($request, 'main_image', 'products/main');
        } elseif ($request->boolean('delete_main_image')) {
            $this->deleteUpload($product->main_image);
            $data['main_image'] = null;
        }

        if ($request->hasFile('video')) {
            $this->deleteUpload($product->video);
            $data['video'] = $this->storeUpload($request, 'video', 'products/videos');
        } elseif ($request->boolean('delete_video')) {
            $this->deleteUpload($product->video);
            $data['video'] = null;
        }

        $product->update($data);
        $this->syncVariants($request, $product);
        $this->deleteGalleryImages($request, $product);
        $this->deleteCampaigns($request, $product);
        $this->updateCampaigns($request, $product);
        $this->storeGalleryImages($request, $product);
        $createdCampaigns = $this->storeCampaigns($request, $product);

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
            ->with('status', 'تم تحديث المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeProductManagement($product);
        $this->authorizeShopAccess($product);
        $product->load(['images', 'campaigns']);
        $this->deleteUpload($product->main_image);
        $this->deleteUpload($product->video);

        foreach ($product->images as $image) {
            $this->deleteUpload($image->image);
        }

        foreach ($product->campaigns as $campaign) {
            $this->deleteUpload($campaign->media);
        }

        $product->delete();

        return redirect()
            ->route('products')
            ->with('status', 'تم حذف المنتج بنجاح.');
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
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return Agent::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
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
            'customer_package_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'show_customer_package_price' => ['required', 'boolean'],
            'customer_carton_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'customer_pallet_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'show_customer_carton_price' => ['required', 'boolean'],
            'show_customer_pallet_price' => ['required', 'boolean'],
            'merchant_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'package_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'pallet_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'carton_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'show_package_price' => ['required', 'boolean'],
            'show_carton_price' => ['required', 'boolean'],
            'show_pallet_price' => ['required', 'boolean'],
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
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.quantity' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function syncVariants(Request $request, Product $product): void
    {
        $shopType = $product->shop()->value('catalog_type') ?: 'general';
        if (! in_array($shopType, ['clothing', 'shoes', 'electronics'], true)) {
            $product->variants()->delete();
            return;
        }

        $variants = collect($request->input('variants', []))
            ->filter(fn ($variant) => filled($variant['size'] ?? null) || filled($variant['storage'] ?? null) || filled($variant['ram'] ?? null) || filled($variant['color'] ?? null))
            ->map(fn ($variant) => [
                'size' => filled($variant['size'] ?? null) ? trim($variant['size']) : null,
                'storage' => filled($variant['storage'] ?? null) ? trim($variant['storage']) : null,
                'ram' => filled($variant['ram'] ?? null) ? trim($variant['ram']) : null,
                'color' => filled($variant['color'] ?? null) ? trim($variant['color']) : null,
                'sku' => filled($variant['sku'] ?? null) ? trim($variant['sku']) : null,
                'price' => filled($variant['price'] ?? null) ? $variant['price'] : null,
                'quantity' => max(0, (int) ($variant['quantity'] ?? 0)),
                'is_active' => filter_var($variant['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->values();

        $product->variants()->delete();
        if ($variants->isNotEmpty()) {
            $product->variants()->createMany($variants->all());
            $product->update(['quantity' => $variants->where('is_active', true)->sum('quantity')]);
        }
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

        return $attributes;
    }

    private function syncLegacyPrices(array &$data, ?Product $product = null): void
    {
        $data['price'] = $data['customer_package_price']
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
        $data['discount_price'] = null;
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

    private function storeGalleryImages(Request $request, Product $product): void
    {
        foreach ($request->file('images', []) as $image) {
            $path = $image->store('products/gallery', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image' => 'storage/' . $path,
            ]);
        }
    }

    private function deleteGalleryImages(Request $request, Product $product): void
    {
        $ids = collect($request->input('delete_image_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->all();

        if (empty($ids)) {
            return;
        }

        $images = $product->images()->whereIn('id', $ids)->get();

        foreach ($images as $image) {
            $this->deleteUpload($image->image);
            $image->delete();
        }
    }

    private function storeCampaigns(Request $request, Product $product): \Illuminate\Support\Collection
    {
        $createdCampaigns = collect();

        foreach ($request->input('campaigns', []) as $index => $campaign) {
            $title = trim($campaign['title'] ?? '');
            $titleEn = trim($campaign['title_en'] ?? '');
            $titleHe = trim($campaign['title_he'] ?? '');
            $type = $campaign['type'] ?? null;
            $file = $request->file("campaigns.{$index}.media");
            $offerType = $campaign['offer_type'] ?? null;
            $unitKey = $campaign['unit_key'] ?? null;
            $offerQuantity = $campaign['offer_quantity'] ?? null;
            $minQuantity = $campaign['min_quantity'] ?? null;
            $maxQuantity = $campaign['max_quantity'] ?? null;
            $offerPrice = $campaign['offer_price'] ?? null;
            $offerNote = trim($campaign['offer_note'] ?? '');
            $offerNoteEn = trim($campaign['offer_note_en'] ?? '');
            $offerNoteHe = trim($campaign['offer_note_he'] ?? '');
            $startsAt = $campaign['starts_at'] ?? null;
            $endsAt = $campaign['ends_at'] ?? null;
            $hasOffer = filled($offerQuantity)
                || filled($minQuantity)
                || filled($maxQuantity)
                || filled($offerPrice)
                || $offerNote !== ''
                || filled($startsAt)
                || filled($endsAt);

            if ($title === '' && ! $file && ! $hasOffer) {
                continue;
            }

            if ($title === '' && $hasOffer) {
                $title = filled($offerQuantity) && filled($offerPrice)
                    ? "عرض {$offerQuantity} بسعر {$offerPrice}"
                    : 'عرض المنتج';
            }

            if ($title === '') {
                continue;
            }

            $media = null;
            if ($file) {
                if (! in_array($type, ['image', 'video'], true)) {
                    continue;
                }

                $this->validateCampaignFile($file, $type);

                $directory = $type === 'image' ? 'products/campaigns/images' : 'products/campaigns/videos';
                $path = $file->store($directory, 'public');
                $media = 'storage/' . $path;
            }

            $campaign = ProductCampaign::create([
                'product_id' => $product->id,
                'title' => $title,
                'title_translations' => array_filter([
                    'ar' => $title,
                    'en' => $titleEn,
                    'he' => $titleHe,
                ], fn($value) => filled($value)),
                'type' => $file ? $type : 'image',
                'media' => $media,
                'offer_type' => $offerType ?: null,
                'unit_key' => $unitKey ?: null,
                'offer_quantity' => filled($offerQuantity) ? (int) $offerQuantity : null,
                'min_quantity' => filled($minQuantity) ? (int) $minQuantity : null,
                'max_quantity' => filled($maxQuantity) ? (int) $maxQuantity : null,
                'offer_price' => filled($offerPrice) ? $offerPrice : null,
                'offer_note' => $offerNote !== '' ? $offerNote : null,
                'offer_note_translations' => array_filter([
                    'ar' => $offerNote,
                    'en' => $offerNoteEn,
                    'he' => $offerNoteHe,
                ], fn($value) => filled($value)),
                'starts_at' => $startsAt ?: null,
                'ends_at' => $endsAt ?: null,
                'is_active' => true,
            ]);
            $createdCampaigns->push($campaign);
        }

        return $createdCampaigns;
    }

    private function deleteCampaigns(Request $request, Product $product): void
    {
        $ids = collect($request->input('delete_campaign_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->all();

        if (empty($ids)) {
            return;
        }

        $campaigns = $product->campaigns()->whereIn('id', $ids)->get();

        foreach ($campaigns as $campaign) {
            $this->deleteUpload($campaign->media);
            $campaign->delete();
        }
    }

    private function updateCampaigns(Request $request, Product $product): void
    {
        $deletedIds = collect($request->input('delete_campaign_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->all();

        foreach ($request->input('existing_campaigns', []) as $campaignId => $campaignData) {
            $campaignId = (int) $campaignId;

            if (in_array($campaignId, $deletedIds, true)) {
                continue;
            }

            $campaign = $product->campaigns()->whereKey($campaignId)->first();
            if (! $campaign) {
                continue;
            }

            $title = trim($campaignData['title'] ?? '');
            $titleEn = trim($campaignData['title_en'] ?? '');
            $titleHe = trim($campaignData['title_he'] ?? '');
            $offerNote = trim($campaignData['offer_note'] ?? '');
            $offerNoteEn = trim($campaignData['offer_note_en'] ?? '');
            $offerNoteHe = trim($campaignData['offer_note_he'] ?? '');
            $type = $campaignData['type'] ?? $campaign->type;
            $unitKey = $campaignData['unit_key'] ?? null;
            $file = $request->file("existing_campaigns.{$campaignId}.media");

            if ($title === '') {
                $title = $campaign->title ?: (
                    filled($campaignData['offer_quantity'] ?? null) && filled($campaignData['offer_price'] ?? null)
                        ? "عرض {$campaignData['offer_quantity']} بسعر {$campaignData['offer_price']}"
                        : 'عرض المنتج'
                );
            }

            $updates = [
                'title' => $title,
                'title_translations' => array_filter([
                    'ar' => $title,
                    'en' => $titleEn,
                    'he' => $titleHe,
                ], fn($value) => filled($value)),
                'type' => in_array($type, ['image', 'video'], true) ? $type : $campaign->type,
                'offer_type' => $campaignData['offer_type'] ?? null,
                'unit_key' => $unitKey ?: null,
                'offer_quantity' => filled($campaignData['offer_quantity'] ?? null) ? (int) $campaignData['offer_quantity'] : null,
                'min_quantity' => filled($campaignData['min_quantity'] ?? null) ? (int) $campaignData['min_quantity'] : null,
                'max_quantity' => filled($campaignData['max_quantity'] ?? null) ? (int) $campaignData['max_quantity'] : null,
                'offer_price' => filled($campaignData['offer_price'] ?? null) ? $campaignData['offer_price'] : null,
                'offer_note' => $offerNote !== '' ? $offerNote : null,
                'offer_note_translations' => array_filter([
                    'ar' => $offerNote,
                    'en' => $offerNoteEn,
                    'he' => $offerNoteHe,
                ], fn($value) => filled($value)),
                'starts_at' => $campaignData['starts_at'] ?? null ?: null,
                'ends_at' => $campaignData['ends_at'] ?? null ?: null,
            ];

            if ($file) {
                $mediaType = $updates['type'];
                $this->validateCampaignFile($file, $mediaType);
                $this->deleteUpload($campaign->media);

                $directory = $mediaType === 'image' ? 'products/campaigns/images' : 'products/campaigns/videos';
                $path = $file->store($directory, 'public');
                $updates['media'] = 'storage/' . $path;
            }

            $campaign->update($updates);
        }
    }

    private function validateCampaignFile($file, string $type): void
    {
        $mime = $file->getMimeType();

        abort_if($type === 'image' && ! str_starts_with($mime, 'image/'), 422);
        abort_if($type === 'video' && ! str_starts_with($mime, 'video/'), 422);
    }

    private function localizedInput(Request $request, string $field, ?string $arabicValue = null): array
    {
        return array_filter([
            'ar' => $arabicValue,
            'en' => $request->input("{$field}_en"),
            'he' => $request->input("{$field}_he"),
        ], fn($value) => filled($value));
    }

    private function storeUpload(Request $request, string $field, string $directory): string
    {
        $path = $request->file($field)->store($directory, 'public');

        return 'storage/' . $path;
    }

    private function deleteUpload(?string $path): void
    {
        if (!$path) {
            return;
        }

        $path = Str::of($path)->replaceStart('storage/', '')->toString();
        Storage::disk('public')->delete($path);
    }
}
