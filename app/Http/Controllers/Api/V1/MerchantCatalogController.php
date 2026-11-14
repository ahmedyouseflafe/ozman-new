<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Category, Product, ProductImage, Shop, User};
use Illuminate\Http\{JsonResponse, Request, UploadedFile};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The shop's products and sections, managed from the phone.
 *
 * Deliberately narrower than the website's product form: it edits the everyday fields (name, price,
 * photo, section, visibility, sizes and colours) and never touches what only the website can set up
 * (meal choices, device options, campaigns), so saving from the app can't wipe them.
 */
class MerchantCatalogController extends Controller
{
    private const IMAGE = ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];

    /** Shop types whose products need options that only the website's form can build. */
    private const WEBSITE_ONLY_CREATION = ['electronics', 'real_estate'];

    public function products(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        $data = $request->validate(['category_id' => ['nullable', 'integer'], 'q' => ['nullable', 'string', 'max:100']]);
        $products = Product::where('shop_id', $shop->id)
            ->when($data['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($data['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->orderByDesc('is_active')->latest('id')->limit(300)->get();

        return response()->json([
            'can_create' => ! in_array($shop->catalog_type, self::WEBSITE_ONLY_CREATION, true),
            'has_sizes' => $this->hasSizes($shop),
            'categories' => $this->categoriesOf($shop),
            'data' => $products->map(fn (Product $product) => $this->present($product)),
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $shop = $this->shopOf($request->user(), $product);

        return response()->json(['product' => $this->present($product->load('images'), true), 'has_sizes' => $this->hasSizes($shop), 'categories' => $this->categoriesOf($shop)]);
    }

    public function store(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop, 'products.store');
        abort_if(in_array($shop->catalog_type, self::WEBSITE_ONLY_CREATION, true), 422, 'إضافة هذا النوع بتحتاج خيارات (تخزين ولون) من لوحة التحكم على الموقع.');
        $data = $this->validated($request, $shop, true);
        $this->checkDiscount($data);

        $product = DB::transaction(function () use ($request, $shop, $data) {
            $product = Product::create([
                'shop_id' => $shop->id, 'category_id' => $data['category_id'], 'name' => $data['name'],
                'name_translations' => ['ar' => $data['name']], 'slug' => $this->productSlug($data['name']),
                'description' => $data['description'] ?? null,
                'description_translations' => array_filter(['ar' => $data['description'] ?? null]),
                'price' => $data['price'], 'discount_price' => $data['discount_price'] ?? null,
                // Restaurants don't count stock; other shops start uncounted (0) like the website.
                'quantity' => $shop->catalog_type === 'restaurant' ? 1 : 0, 'rating' => 0,
                'is_active' => $data['is_active'] ?? true, 'is_featured' => $data['is_featured'] ?? false,
                'catalog_attributes' => $this->sizeAttributes($shop, $data, []),
                'main_image' => $request->hasFile('image') ? $this->storeImage($request->file('image'), 'products/main') : null,
            ]);
            $this->addGallery($request, $product);

            return $product;
        });
        $this->notifySuperAdmin('product_created', $product, 'تمت إضافة منتج جديد', "المتجر {$shop->name} أضاف منتج: {$product->name}", route('products.show', $product));

        return response()->json(['product' => $this->present($product->load('images'), true), 'message' => 'تمت الإضافة.'], 201);
    }

    /** Sent as POST because phones upload the photo as multipart form data. */
    public function update(Request $request, Product $product): JsonResponse
    {
        $shop = $this->shopOf($request->user(), $product, 'products.update');
        $data = $this->validated($request, $shop, false);
        $this->checkDiscount($data, $product);

        DB::transaction(function () use ($request, $product, $shop, $data) {
            $changes = collect($data)->only(['name', 'description', 'price', 'category_id', 'is_active', 'is_featured'])->all();
            if (array_key_exists('discount_price', $data)) {
                $changes['discount_price'] = filled($data['discount_price']) ? $data['discount_price'] : null;
            }
            if (isset($data['name'])) {
                $changes['name_translations'] = ['ar' => $data['name']] + (array) $product->name_translations;
            }
            if (array_key_exists('description', $data)) {
                $changes['description_translations'] = array_filter(['ar' => $data['description']] + (array) $product->description_translations);
            }
            if (array_key_exists('sizes', $data) || array_key_exists('colors', $data)) {
                $changes['catalog_attributes'] = $this->sizeAttributes($shop, $data, (array) $product->catalog_attributes);
            }
            if ($request->hasFile('image')) {
                $old = $product->main_image;
                $changes['main_image'] = $this->storeImage($request->file('image'), 'products/main');
                $this->deleteImage($old);
            }
            $product->update($changes);
            foreach ($product->images()->whereIn('id', $data['delete_image_ids'] ?? [])->get() as $image) {
                $this->deleteImage($image->image);
                $image->delete();
            }
            $this->addGallery($request, $product);
        });

        return response()->json(['product' => $this->present($product->fresh('images'), true), 'message' => 'تم الحفظ.']);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->shopOf($request->user(), $product, 'products.destroy');
        $product->load(['images', 'campaigns']);
        $this->deleteImage($product->main_image);
        $this->deleteImage($product->video);
        foreach ($product->images as $image) {
            $this->deleteImage($image->image);
        }
        foreach ($product->campaigns as $campaign) {
            $this->deleteImage($campaign->media);
        }
        $product->delete();

        return response()->json(['message' => 'تم الحذف.']);
    }

    public function storeCategory(Request $request, Shop $shop): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop, 'categories.store');
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'is_active' => ['nullable', 'boolean']]);
        $category = Category::create([
            'shop_id' => $shop->id, 'name' => $data['name'], 'name_translations' => ['ar' => $data['name']],
            'slug' => $this->categorySlug($data['name']), 'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['category' => $this->presentCategory($category), 'categories' => $this->categoriesOf($shop), 'message' => 'تمت إضافة القسم.'], 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $shop = Shop::findOrFail($category->shop_id);
        $this->authorizeShop($request->user(), $shop, 'categories.update');
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:255'], 'is_active' => ['sometimes', 'boolean']]);
        if (isset($data['name'])) {
            $data['name_translations'] = ['ar' => $data['name']] + (array) $category->name_translations;
        }
        $category->update($data);

        return response()->json(['category' => $this->presentCategory($category), 'categories' => $this->categoriesOf($shop), 'message' => 'تم الحفظ.']);
    }

    /** Only empty sections can be deleted, so products are never lost by accident. */
    public function destroyCategory(Request $request, Category $category): JsonResponse
    {
        $shop = Shop::findOrFail($category->shop_id);
        $this->authorizeShop($request->user(), $shop, 'categories.destroy');
        abort_if(Product::where('category_id', $category->id)->exists(), 422, 'القسم فيه منتجات. انقلها لقسم ثاني أو احذفها أول.');
        $this->deleteImage($category->image);
        $category->delete();

        return response()->json(['categories' => $this->categoriesOf($shop), 'message' => 'تم حذف القسم.']);
    }

    private function validated(Request $request, Shop $shop, bool $creating): array
    {
        $need = $creating ? 'required' : 'sometimes';
        // Multipart forms send booleans and lists as text.
        foreach (['is_active', 'is_featured'] as $flag) {
            if ($request->has($flag)) {
                $request->merge([$flag => filter_var($request->input($flag), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
        foreach (['sizes', 'colors'] as $list) {
            if ($request->has($list) && ! is_array($request->input($list))) {
                $request->merge([$list => array_values(array_filter(array_map('trim', explode(',', (string) $request->input($list))), fn ($v) => $v !== ''))]);
            }
        }

        return $request->validate([
            'name' => [$need, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => [$need, 'numeric', 'min:0', 'max:99999999.99'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => [$need, 'integer', Rule::exists('categories', 'id')->where('shop_id', $shop->id)],
            'is_active' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sizes' => ['sometimes', 'array', 'max:40'], 'sizes.*' => ['string', 'max:60'],
            'colors' => ['sometimes', 'array', 'max:40'], 'colors.*' => ['string', 'max:60'],
            'image' => ['nullable', ...self::IMAGE],
            'images' => ['nullable', 'array', 'max:8'], 'images.*' => self::IMAGE,
            'delete_image_ids' => ['nullable', 'array'], 'delete_image_ids.*' => ['integer'],
        ], ['category_id.exists' => 'اختار قسم من أقسام محلك.']);
    }

    /** The sale price must be below the price it replaces (the one being saved, or the current one). */
    private function checkDiscount(array $data, ?Product $product = null): void
    {
        $price = $data['price'] ?? $product?->price;
        $discount = array_key_exists('discount_price', $data) ? $data['discount_price'] : $product?->discount_price;
        if (filled($discount) && (float) $discount >= (float) $price) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_price' => 'سعر الخصم لازم يكون أقل من السعر.']);
        }
    }

    private function hasSizes(Shop $shop): bool
    {
        return in_array($shop->catalog_type, ['clothing', 'shoes'], true);
    }

    /** Sizes and colours live in catalog_attributes; every other attribute is kept as it was. */
    private function sizeAttributes(Shop $shop, array $data, array $current): ?array
    {
        if ($this->hasSizes($shop)) {
            foreach (['sizes', 'colors'] as $key) {
                if (array_key_exists($key, $data)) {
                    $current[$key] = array_values(array_unique($data[$key]));
                    if ($current[$key] === []) {
                        unset($current[$key]);
                    }
                }
            }
        }

        return $current === [] ? null : $current;
    }

    private function addGallery(Request $request, Product $product): void
    {
        foreach ((array) $request->file('images', []) as $file) {
            if ($product->images()->count() >= 12) {
                break;
            }
            ProductImage::create(['product_id' => $product->id, 'image' => $this->storeImage($file, 'products/gallery')]);
        }
    }

    private function storeImage(UploadedFile $file, string $directory): string
    {
        return 'storage/'.$file->store($directory, 'public');
    }

    /** Only files this app or the website stored under storage/ are ever removed. */
    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete(substr($path, 8));
        }
    }

    private function productSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while (Product::where('slug', $slug)->exists());

        return $slug;
    }

    private function categorySlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while (Category::where('slug', $slug)->exists());

        return $slug;
    }

    private function categoriesOf(Shop $shop)
    {
        return Category::where('shop_id', $shop->id)->withCount('products')->orderBy('id')->get()
            ->map(fn (Category $category) => $this->presentCategory($category));
    }

    private function presentCategory(Category $category): array
    {
        return ['id' => $category->id, 'name' => $category->name, 'is_active' => (bool) $category->is_active, 'products_count' => (int) ($category->products_count ?? 0)];
    }

    private function present(Product $product, bool $full = false): array
    {
        $data = [
            'id' => $product->id, 'name' => $product->name, 'category_id' => $product->category_id,
            'image' => Presenter::media($product->main_image),
            'price' => Presenter::price($product->price), 'discount_price' => Presenter::price($product->discount_price),
            'is_active' => (bool) $product->is_active, 'is_featured' => (bool) $product->is_featured,
        ];
        if ($full) {
            $data += [
                'description' => $product->description,
                'sizes' => Presenter::optionList($product, 'sizes'), 'colors' => Presenter::optionList($product, 'colors'),
                'images' => $product->images->map(fn ($image) => ['id' => $image->id, 'url' => Presenter::media($image->image)])->values(),
                // Things set up on the website that the app leaves untouched.
                'has_website_options' => Presenter::mealOptions($product) !== null || $product->variants()->exists(),
            ];
        }

        return $data;
    }

    private function shopOf(User $user, Product $product, ?string $route = null): Shop
    {
        $shop = Shop::findOrFail($product->shop_id);
        $this->authorizeShop($user, $shop, $route);

        return $shop;
    }

    /** Owners and admins always; staff only when their account was given that permission on the website. */
    private function authorizeShop(User $user, Shop $shop, ?string $route = null): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
        abort_if($route && ! $user->isSuperAdmin() && ! $user->canAccessRouteName($route), 403, 'حسابك ما إله صلاحية على هذا.');
    }
}
