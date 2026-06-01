<?php

namespace App\Http\Controllers;

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
            ->when(! $this->isSuperAdmin(), fn($query) => $this->scopeToAccessibleShops($query))
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
        $data = $this->validatedData($request);
        unset($data['images'], $data['campaigns']);
        $this->normalizeShopId($data);
        $this->authorizeCategoryForShop((int) $data['category_id'], (int) $data['shop_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);
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
        $this->storeGalleryImages($request, $product);
        $this->storeCampaigns($request, $product);

        return redirect()
            ->route('products')
            ->with('status', 'تمت إضافة المنتج بنجاح.');
    }

    public function show(Product $product): View
    {
        $this->authorizeShopAccess($product);
        $product->load(['shop', 'category', 'images', 'campaigns']);

        return view('admin.products.products_show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorizeShopAccess($product);
        $product->load(['images', 'campaigns']);

        return view('admin.products.products_edit', array_merge(
            ['product' => $product],
            $this->formOptions()
        ));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        unset($data['images'], $data['campaigns'], $data['delete_campaign_ids']);
        $this->normalizeShopId($data);
        $this->authorizeCategoryForShop((int) $data['category_id'], (int) $data['shop_id']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name'], $product);
        $data['quantity'] = $data['quantity'] ?? 0;
        $data['rating'] = $data['rating'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('main_image')) {
            $this->deleteUpload($product->main_image);
            $data['main_image'] = $this->storeUpload($request, 'main_image', 'products/main');
        }

        if ($request->hasFile('video')) {
            $this->deleteUpload($product->video);
            $data['video'] = $this->storeUpload($request, 'video', 'products/videos');
        }

        $product->update($data);
        $this->deleteCampaigns($request, $product);
        $this->storeGalleryImages($request, $product);
        $this->storeCampaigns($request, $product);

        return redirect()
            ->route('products')
            ->with('status', 'تم تحديث المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
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
        return [
            'shops' => $this->accessibleShops(),
            'categories' => $this->scopeToAccessibleShops(Category::query())->with('shop')->orderBy('name')->get(),
        ];
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'shop_id' => ['required', 'integer', Rule::exists('shops', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($product?->id),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'main_image' => ['nullable', 'image', 'max:4096'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm', 'max:20480'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'campaigns' => ['nullable', 'array'],
            'campaigns.*.title' => ['nullable', 'string', 'max:255'],
            'campaigns.*.type' => ['nullable', Rule::in(['image', 'video'])],
            'campaigns.*.media' => ['nullable', 'file', 'max:20480'],
            'delete_campaign_ids' => ['nullable', 'array'],
            'delete_campaign_ids.*' => ['integer'],
        ]);
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

    private function storeCampaigns(Request $request, Product $product): void
    {
        foreach ($request->input('campaigns', []) as $index => $campaign) {
            $title = trim($campaign['title'] ?? '');
            $type = $campaign['type'] ?? null;
            $file = $request->file("campaigns.{$index}.media");

            if ($title === '' && ! $file) {
                continue;
            }

            if ($title === '' || ! in_array($type, ['image', 'video'], true) || ! $file) {
                continue;
            }

            $this->validateCampaignFile($file, $type);

            $directory = $type === 'image' ? 'products/campaigns/images' : 'products/campaigns/videos';
            $path = $file->store($directory, 'public');

            ProductCampaign::create([
                'product_id' => $product->id,
                'title' => $title,
                'type' => $type,
                'media' => 'storage/' . $path,
            ]);
        }
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

    private function validateCampaignFile($file, string $type): void
    {
        $mime = $file->getMimeType();

        abort_if($type === 'image' && ! str_starts_with($mime, 'image/'), 422);
        abort_if($type === 'video' && ! str_starts_with($mime, 'video/'), 422);
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
