<?php

namespace App\Http\Controllers;

use App\Models\{Product, Product3dJob};
use App\Services\Product3d;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;

/**
 * A product's 3D model, for the owner (website dashboard and app): upload a .glb, set the real size, make one from the
 * product's photo and approve it. Customers see it on the product's AR page.
 */
class Product3dController extends Controller
{
    public function __construct(private Product3d $models) {}

    /** The dashboard page. */
    public function page(Request $request, Product $product): View
    {
        $this->authorizeOwner($request, $product);

        return view('admin.products.model_3d', ['product' => $product->load('shop'), 'state' => $this->models->state($product)]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwner($request, $product);

        return $this->state($product);
    }

    public function upload(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwner($request, $product);
        $request->validate(['model' => ['required', 'file', 'max:'.(Product3d::MAX_BYTES / 1024)]], ['model.max' => __('الملف أكبر من 25 ميغا. اطلب نسخة أخف من المصمم.')]);
        $this->models->upload($product, $request->file('model'));

        return $this->state($product->fresh(), __('انرفع النموذج.'));
    }

    public function size(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwner($request, $product);
        $data = $request->validate([
            'width' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'depth' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:2000'],
        ]);
        $this->models->setSize($product, $data['width'] ?? null, $data['depth'] ?? null, $data['height'] ?? null);

        return $this->state($product->fresh(), __('انحفظت المقاسات.'));
    }

    public function convert(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwner($request, $product);
        $this->models->convert($product, $request->user());

        return $this->state($product->fresh(), __('بلّش التحويل. بياخد تقريباً دقيقتين.'));
    }

    public function approve(Request $request, Product3dJob $job): JsonResponse
    {
        $this->authorizeOwner($request, $job->product);
        $this->models->approve($job);

        return $this->state($job->product->fresh(), __('صار النموذج ظاهر للزباين.'));
    }

    public function discard(Request $request, Product3dJob $job): JsonResponse
    {
        $this->authorizeOwner($request, $job->product);
        $this->models->discard($job);

        return $this->state($job->product->fresh());
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwner($request, $product);
        $this->models->remove($product);

        return $this->state($product->fresh(), __('انحذف النموذج.'));
    }

    /** The customer's page: the piece in 3D, and "see it in your room" on phones that can. */
    public function ar(Product $product): View
    {
        $product->loadMissing('shop');
        abort_unless($product->is_active && $product->shop?->is_active && $product->model_3d, 404);

        return view('front.product_ar', ['product' => $product, 'model' => $this->models->url($product->model_3d), 'size' => $product->model_3d_size]);
    }

    private function state(Product $product, ?string $message = null): JsonResponse
    {
        return response()->json(['model_3d' => $this->models->state($product)] + ($message ? ['message' => $message] : []), 200, ['Cache-Control' => 'no-store']);
    }

    private function authorizeOwner(Request $request, ?Product $product): void
    {
        $user = $request->user();
        abort_unless($product && $user, 404);
        abort_unless($user->isSuperAdmin() || in_array((int) $product->shop_id, $user->accessibleShopIds(), true), 403);
        abort_if(! $user->isSuperAdmin() && ! $user->isShopOwner() && ! $user->canAccessRouteName('products.edit'), 403);
    }
}
