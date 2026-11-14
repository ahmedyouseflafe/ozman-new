<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public catalog for the app: shop types, shops, a shop's menu/categories and products. */
class CatalogController extends Controller
{
    /** Shop types shown on the home screen, with how many active shops each has. */
    public const TYPES = [
        'restaurant' => ['ar' => 'مطاعم', 'he' => 'מסעדות', 'en' => 'Restaurants'],
        'cosmetics' => ['ar' => 'مكياج وعناية', 'he' => 'קוסמטיקה', 'en' => 'Cosmetics'],
        'clothing' => ['ar' => 'ملابس', 'he' => 'ביגוד', 'en' => 'Clothing'],
        'electronics' => ['ar' => 'إلكترونيات', 'he' => 'אלקטרוניקה', 'en' => 'Electronics'],
        'sweets' => ['ar' => 'حلويات', 'he' => 'מתוקים', 'en' => 'Sweets'],
        'furniture_appliances' => ['ar' => 'أثاث وأجهزة', 'he' => 'רהיטים ומכשירים', 'en' => 'Furniture'],
        'dental' => ['ar' => 'عيادات', 'he' => 'מרפאות', 'en' => 'Clinics'],
        'advertising_services' => ['ar' => 'خدمات', 'he' => 'שירותים', 'en' => 'Services'],
        'krembo' => ['ar' => 'كريمبو', 'he' => 'קרמבו', 'en' => 'Krembo'],
        'real_estate' => ['ar' => 'عقارات', 'he' => 'נדל״ן', 'en' => 'Real estate'],
        'general' => ['ar' => 'متاجر', 'he' => 'חנויות', 'en' => 'Stores'],
    ];

    public function home(): JsonResponse
    {
        $locale = app()->getLocale();
        $counts = Shop::where('is_active', true)->selectRaw('catalog_type, COUNT(*) AS c')->groupBy('catalog_type')->pluck('c', 'catalog_type');
        $types = collect(self::TYPES)->map(fn ($names, $key) => ['key' => $key, 'name' => $names[$locale] ?? $names['ar'], 'shops_count' => (int) ($counts[$key] ?? 0)])
            ->filter(fn ($type) => $type['shops_count'] > 0)->values();

        return response()->json([
            'types' => $types,
            'featured_shops' => Shop::where('is_active', true)->latest()->limit(12)->get()->map(fn ($shop) => Presenter::shop($shop)),
            'featured_products' => $this->activeProducts()->where('products.is_featured', true)->latest('products.id')->limit(12)->get()->map(fn ($p) => Presenter::product($p)),
        ]);
    }

    public function shops(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $shops = Shop::where('is_active', true)
            ->when($data['type'] ?? null, fn ($q, $type) => $q->where('catalog_type', $type))
            ->when($data['q'] ?? null, fn ($q, $term) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->orderByDesc('is_accepting_orders')->latest()
            ->paginate($data['per_page'] ?? 20);

        return response()->json([
            'data' => collect($shops->items())->map(fn ($shop) => Presenter::shop($shop)),
            'meta' => ['page' => $shops->currentPage(), 'last_page' => $shops->lastPage(), 'total' => $shops->total()],
        ]);
    }

    public function shop(Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active, 404);
        $categories = Category::where('shop_id', $shop->id)->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderBy('id')->get()
            ->filter(fn ($category) => $category->products_count > 0)->values();

        return response()->json([
            'shop' => Presenter::shop($shop, true),
            'categories' => $categories->map(fn ($category) => Presenter::category($category)),
        ]);
    }

    public function products(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active, 404);
        $data = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $products = $this->activeProducts()->where('products.shop_id', $shop->id)
            ->when($data['category_id'] ?? null, fn ($q, $id) => $q->where('products.category_id', $id))
            ->when($data['q'] ?? null, fn ($q, $term) => $q->where('products.name', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->orderByDesc('products.is_featured')->orderBy('products.id')
            ->paginate($data['per_page'] ?? 50);

        return response()->json([
            'data' => collect($products->items())->map(fn ($p) => Presenter::product($p)),
            'meta' => ['page' => $products->currentPage(), 'last_page' => $products->lastPage(), 'total' => $products->total()],
        ]);
    }

    public function product(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active && $product->shop?->is_active, 404);
        $product->load(['images', 'variants', 'shop']);
        $liked = $request->user()
            ? \App\Models\ProductLike::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists()
            : false;

        return response()->json([
            'product' => Presenter::product($product, true) + ['liked' => $liked],
            'shop' => Presenter::shop($product->shop),
        ]);
    }

    /** One box for everything: shops by name, and products by name across all open shops. */
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $like = '%'.addcslashes(trim($data['q']), '%_\\').'%';
        $shops = Shop::where('is_active', true)->where('name', 'like', $like)
            ->orderByDesc('is_accepting_orders')->limit(10)->get();
        $products = $this->activeProducts()->with('shop:id,name,catalog_type')
            ->where(fn ($q) => $q->where('products.name', 'like', $like)->orWhere('products.description', 'like', $like)
                // English names typed by the shop (Hebrew is stored escaped, so it can't be matched this way).
                ->orWhere('products.name_translations', 'like', $like))
            // Name matches first, then featured items.
            ->orderByRaw('CASE WHEN products.name LIKE ? THEN 0 ELSE 1 END', [$like])
            ->orderByDesc('products.is_featured')->limit(40)->get();

        return response()->json([
            'shops' => $shops->map(fn ($shop) => Presenter::shop($shop)),
            'products' => $products->map(fn ($product) => Presenter::product($product) + ['shop_name' => $product->shop?->name]),
        ]);
    }

    /** The products this account has liked, newest first: the app's favourites list. */
    public function liked(Request $request): JsonResponse
    {
        $ids = \App\Models\ProductLike::where('user_id', $request->user()->id)->latest('id')->limit(200)->pluck('product_id');
        $products = $this->activeProducts()->with('shop:id,name,catalog_type')->whereIn('products.id', $ids)->get()
            ->sortBy(fn ($product) => $ids->search($product->id))->values();

        return response()->json([
            'data' => $products->map(fn ($product) => Presenter::product($product) + ['shop_name' => $product->shop?->name, 'liked' => true]),
        ]);
    }

    private function activeProducts()
    {
        return Product::query()->select('products.*')->where('products.is_active', true)
            ->whereHas('shop', fn ($q) => $q->where('is_active', true))
            ->withCount(['variants as active_variants_count' => fn ($q) => $q->where('is_active', true)]);
    }
}
