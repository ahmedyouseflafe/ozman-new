<?php

namespace App\Services\Products;

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

/** A product's files: its main photo (resized and optimised), gallery, video, and deleting old files. */
class ProductMedia
{
    public function storeGalleryImages(Request $request, Product $product): void
    {
        foreach ($request->file('images', []) as $image) {
            $storedPath = $this->storeProductImage(
                $image,
                'products/gallery',
                $product->shop?->catalog_type === 'electronics'
            );

            ProductImage::create([
                'product_id' => $product->id,
                'image' => $storedPath,
            ]);
        }
    }

    public function deleteGalleryImages(Request $request, Product $product): void
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

    public function storeUpload(Request $request, string $field, string $directory): string
    {
        $path = $request->file($field)->store($directory, 'public');

        return 'storage/' . $path;
    }

    public function storeProductImage(UploadedFile $file, string $directory, bool $removeWhiteBackground): string
    {
        if (! $removeWhiteBackground || ! function_exists('imagecreatefromstring')) {
            return 'storage/' . $file->store($directory, 'public');
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            return 'storage/' . $file->store($directory, 'public');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        imagealphablending($source, false);
        imagesavealpha($source, true);

        $visited = array_fill(0, $width * $height, false);
        $queue = new \SplQueue();
        $enqueue = function (int $x, int $y) use (&$visited, $queue, $width, $height, $source): void {
            if ($x < 0 || $y < 0 || $x >= $width || $y >= $height) {
                return;
            }
            $index = ($y * $width) + $x;
            if ($visited[$index]) {
                return;
            }
            $rgba = imagecolorat($source, $x, $y);
            $red = ($rgba >> 16) & 0xff;
            $green = ($rgba >> 8) & 0xff;
            $blue = $rgba & 0xff;
            if (min($red, $green, $blue) < 225 || max($red, $green, $blue) - min($red, $green, $blue) > 28) {
                return;
            }
            $visited[$index] = true;
            $queue->enqueue([$x, $y]);
        };

        for ($x = 0; $x < $width; $x++) {
            $enqueue($x, 0);
            $enqueue($x, $height - 1);
        }
        for ($y = 0; $y < $height; $y++) {
            $enqueue(0, $y);
            $enqueue($width - 1, $y);
        }

        while (! $queue->isEmpty()) {
            [$x, $y] = $queue->dequeue();
            $rgba = imagecolorat($source, $x, $y);
            $red = ($rgba >> 16) & 0xff;
            $green = ($rgba >> 8) & 0xff;
            $blue = $rgba & 0xff;
            $whiteness = min($red, $green, $blue);
            $alpha = max(0, min(127, (int) round(($whiteness - 220) / 35 * 127)));
            imagesetpixel($source, $x, $y, imagecolorallocatealpha($source, $red, $green, $blue, $alpha));
            $enqueue($x - 1, $y);
            $enqueue($x + 1, $y);
            $enqueue($x, $y - 1);
            $enqueue($x, $y + 1);
        }

        $path = trim($directory, '/') . '/' . Str::uuid() . '.png';
        ob_start();
        imagepng($source, null, 7);
        $png = ob_get_clean();
        imagedestroy($source);
        Storage::disk('public')->put($path, $png);

        return 'storage/' . $path;
    }

    public function deleteUpload(?string $path): void
    {
        if (!$path) {
            return;
        }

        $path = Str::of($path)->replaceStart('storage/', '')->toString();
        Storage::disk('public')->delete($path);
    }
}
