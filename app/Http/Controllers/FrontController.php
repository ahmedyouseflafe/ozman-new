<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
                'agents' => fn($query) => $query->where('is_active', true)->latest(),
                'distributors' => fn($query) => $query->where('is_active', true)->latest(),
                'categories' => fn($query) => $query
                    ->where('is_active', true)
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->with(['images', 'campaigns'])
                        ->latest()
                    ])
                    ->latest(),
                'products' => fn($query) => $query
                    ->where('is_active', true)
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
                'agents' => fn($query) => $query->where('is_active', true)->latest(),
                'distributors' => fn($query) => $query->where('is_active', true)->latest(),
                'categories' => fn($query) => $query
                    ->where('is_active', true)
                    ->with(['products' => fn($productQuery) => $productQuery
                        ->where('is_active', true)
                        ->with(['images', 'campaigns'])
                        ->latest()
                    ])
                    ->latest(),
            ])
            ->latest()
            ->get();

        $shop = $shop?->exists
            ? $shops->firstWhere('id', $shop->id) ?? $shop->load(['social', 'agents', 'distributors', 'categories.products.images', 'categories.products.campaigns'])
            : $shops->first();

        $ozmanCategories = $ozmanShop?->categories ?? collect();

        return view('front.index', [
            'ozmanShop' => $ozmanShop,
            'shop' => $shop,
            'shops' => $shops,
            'agents' => $ozmanShop?->agents ?? collect(),
            'distributors' => $ozmanShop?->distributors ?? collect(),
            'ozmanCategories' => $ozmanCategories,
            'frontData' => $this->frontData($shops, $ozmanCategories),
        ]);
    }

    public function dashboardPreview(Request $request): View
    {
        $selectedShop = $this->previewShop($request);

        $shop = $selectedShop->load([
            'social',
            'agents' => fn($query) => $query->where('is_active', true)->latest(),
            'distributors' => fn($query) => $query->where('is_active', true)->latest(),
            'categories' => fn($query) => $query
                ->where('is_active', true)
                ->with(['products' => fn($productQuery) => $productQuery
                    ->where('is_active', true)
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
            'frontData' => $this->frontData($shops, $ozmanCategories),
            'isDashboardPreview' => true,
        ]);
    }

    private function previewShop(Request $request): Shop
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $shopId = $request->integer('shop_id');

        if ($user->isSuperAdmin()) {
            return Shop::query()
                ->where('slug', '!=', 'ozman')
                ->when($shopId, fn($query) => $query->whereKey($shopId))
                ->latest()
                ->firstOrFail();
        }

        return $user->shops()
            ->when($shopId, fn($query) => $query->whereKey($shopId))
            ->firstOrFail();
    }

    private function frontData($shops, $ozmanCategories): array
    {
        $productsDb = [];
        foreach ($ozmanCategories as $category) {
            $productsDb[$category->name] = $category->products
                ->map(fn($product) => $this->productPayload($product))
                ->values()
                ->all();
        }

        $centersData = $shops->map(function (Shop $shop) use (&$productsDb) {
            return [
                'title' => $shop->name,
                'img' => $this->imageUrl($shop->logo ?: $shop->banner, 'images/logo.jpg'),
                'departments' => $shop->categories->map(function ($category) use (&$productsDb) {
                    $products = $category->products->map(fn($product) => $this->productPayload($product))->values()->all();
                    $productsDb[$category->name] = $products;

                    return [
                        'title' => $category->name,
                        'img' => $this->imageUrl($category->image, 'images/logo.jpg'),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $carouselProductsDb = $ozmanCategories
            ->mapWithKeys(fn($category) => [$category->name => [
                'name' => $category->name,
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
                'title' => $campaign->title,
            ])
            ->filter(fn($campaign) => filled($campaign['src']))
            ->values()
            ->all();

        $image = $this->imageUrl($product->main_image, 'images/1.jpg');

        return [
            'name' => $product->name,
            'price' => number_format((float) ($product->discount_price ?: $product->price), 2) . ' ₪',
            'img' => $image,
            'gallery' => $gallery ?: [$image],
            'video' => $this->imageUrl($product->video, ''),
            'campaigns' => $campaignMedia,
            'description' => $product->description,
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
}
