<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ClothingPhotoTryOnController as SiteTryOn;
use App\Http\Controllers\Controller;
use App\Models\{Product, Shop};
use App\Services\WatchTryOnCatalog;
use Illuminate\Http\Request;

/** "Try it on me" from a photo (clothes, watches), through the website's own photo service. */
class TryOnController extends Controller
{
    /** Whether a product can be tried on in the app, and how (shirt or watch). */
    public static function modeFor(Product $product): ?string
    {
        $shop = $product->shop;
        if (! $shop || ! SiteTryOn::enabled($shop)) {
            return null;
        }
        if ($shop->catalog_type === 'clothing') {
            return 'clothing';
        }

        return $shop->catalog_type === 'cosmetics' && WatchTryOnCatalog::isWatchCategory($product->category) ? 'watch' : null;
    }

    public function create(Request $request, Product $product, SiteTryOn $site)
    {
        $mode = self::modeFor($product->loadMissing('shop', 'category'));
        abort_unless($mode, 404, 'تجربة الصور غير مفعلة لهذا المنتج.');
        $request->merge(['product_id' => $product->id]);

        return $site->create($request, $product->shop, $mode === 'watch');
    }

    public function status(Request $request, Shop $shop, string $job, SiteTryOn $site)
    {
        return $site->status($request, $shop, $job);
    }
}
