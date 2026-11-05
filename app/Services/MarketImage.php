<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Product photos for "المتجر الحر". Every upload is re-encoded with GD (max 1400px), so phone photos
 * shrink and embedded metadata such as GPS location never reaches the disk. Files live on the private disk.
 */
class MarketImage
{
    public const DIRECTORY = 'market/listings';
    public const MAX_SIDE = 1400;
    private const MAX_PIXELS = 25_000_000;

    public static function rules(): array
    {
        return [
            'images' => ['nullable', 'array', 'max:'.config('market.max_images', 6)],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public static function messages(): array
    {
        $type = __('الصور لازم تكون JPG أو PNG أو WebP.');

        return [
            'images.max' => __('بتقدر تضيف لحد :max صور.', ['max' => config('market.max_images', 6)]),
            'images.*.file' => $type, 'images.*.image' => $type, 'images.*.mimes' => $type,
            'images.*.max' => __('حجم كل صورة لازم يكون 8 ميجا أو أقل.'),
            'images.*.uploaded' => __('تعذّر رفع صورة. تأكد إن حجمها أقل من 8 ميجا.'),
        ];
    }

    public function store(UploadedFile $file): string
    {
        [$bytes, $extension] = $this->render($file);
        $path = self::DIRECTORY.'/'.Str::random(40).'.'.$extension;
        if (! Storage::disk('local')->put($path, $bytes)) {
            throw $this->failed();
        }

        return $path;
    }

    public function delete(array|string|null $paths): void
    {
        $paths = array_values(array_filter((array) $paths, fn ($p) => is_string($p) && str_starts_with($p, self::DIRECTORY.'/') && ! str_contains($p, '..')));
        if ($paths) {
            Storage::disk('local')->delete($paths);
        }
    }

    /** @return array{0: string, 1: string} */
    private function render(UploadedFile $file): array
    {
        if (! function_exists('imagecreatefromstring')) {
            Log::warning('Market image upload failed: the GD extension is not installed.');
            throw $this->failed();
        }
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages(['images' => __('الصور لازم تكون JPG أو PNG أو WebP.')]);
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['images' => __('أبعاد إحدى الصور كبيرة جداً. جرّب صورة أصغر.')]);
        }
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            throw $this->failed();
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $source = $this->orient($source, $file->getRealPath());
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));
        $webp = function_exists('imagewebp');
        $canvas = imagecreatetruecolor($w, $h);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $w, $h, $width, $height);
        unset($source);

        ob_start();
        $ok = $webp ? imagewebp($canvas, null, 80) : imagejpeg($canvas, null, 84);
        $bytes = (string) ob_get_clean();
        unset($canvas);
        if (! $ok || $bytes === '') {
            throw $this->failed();
        }

        return [$bytes, $webp ? 'webp' : 'jpg'];
    }

    private function orient(\GdImage $image, string $path): \GdImage
    {
        $orientation = function_exists('exif_read_data') ? (int) (@exif_read_data($path)['Orientation'] ?? 1) : 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }

    private function failed(): ValidationException
    {
        return ValidationException::withMessages(['images' => __('تعذّرت معالجة إحدى الصور. جرّب صورة ثانية.')]);
    }
}
