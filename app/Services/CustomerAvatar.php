<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Optional profile photos for community (customer) accounts.
 * Every upload is re-encoded with GD as a small square, so phone photos and any embedded metadata never reach the disk.
 */
class CustomerAvatar
{
    public const DIRECTORY = 'customers/avatars';
    public const SIZE = 400;
    // Decoding costs ~5 bytes per pixel; this keeps the worst case well under common memory limits.
    private const MAX_PIXELS = 25_000_000;

    public static function rules(bool $required = false): array
    {
        return ['avatar' => [$required ? 'required' : 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']];
    }

    public static function messages(): array
    {
        $type = __('الصورة لازم تكون JPG أو PNG أو WebP.');

        return [
            'avatar.required' => __('اختر صورة أولاً.'),
            'avatar.uploaded' => __('تعذّر رفع الصورة. تأكد إن حجمها أقل من 5 ميجا.'),
            'avatar.file' => $type,
            'avatar.image' => $type,
            'avatar.mimes' => $type,
            'avatar.max' => __('حجم الصورة لازم يكون 5 ميجا أو أقل.'),
        ];
    }

    /** Resize, crop and save an upload; returns the path on the public disk. */
    public function store(UploadedFile $file): string
    {
        [$bytes, $extension] = $this->render($file);
        $path = self::DIRECTORY.'/'.Str::random(40).'.'.$extension;
        if (! Storage::disk('public')->put($path, $bytes)) {
            throw $this->failed();
        }

        return $path;
    }

    public function replace(User $user, UploadedFile $file): void
    {
        $old = $user->avatar_path;
        $user->update(['avatar_path' => $this->store($file)]);
        $this->delete($old);
    }

    public function remove(User $user): void
    {
        $old = $user->avatar_path;
        $user->update(['avatar_path' => null]);
        $this->delete($old);
    }

    public function delete(?string $path): void
    {
        // Only ever delete files this service created.
        if ($path && str_starts_with($path, self::DIRECTORY.'/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @return array{0: string, 1: string} encoded bytes and file extension */
    private function render(UploadedFile $file): array
    {
        if (! function_exists('imagecreatefromstring')) {
            Log::warning('Customer avatar upload skipped: the GD extension is not installed.');
            throw $this->failed();
        }
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages(['avatar' => __('الصورة لازم تكون JPG أو PNG أو WebP.')]);
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['avatar' => __('أبعاد الصورة كبيرة جداً. جرّب صورة أصغر.')]);
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
        $side = min($width, $height);
        $size = min($side, self::SIZE);
        $webp = function_exists('imagewebp');
        $canvas = imagecreatetruecolor($size, $size);
        if ($webp) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        } else {
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        }
        // Center square crop.
        imagecopyresampled($canvas, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), $size, $size, $side, $side);
        unset($source);

        ob_start();
        $ok = $webp ? imagewebp($canvas, null, 82) : imagejpeg($canvas, null, 85);
        $bytes = (string) ob_get_clean();
        unset($canvas);
        if (! $ok || $bytes === '') {
            throw $this->failed();
        }

        return [$bytes, $webp ? 'webp' : 'jpg'];
    }

    /** Phone cameras store rotation in EXIF instead of rotating the pixels. */
    private function orient(\GdImage $image, string $path): \GdImage
    {
        $orientation = function_exists('exif_read_data') ? (int) (@exif_read_data($path)['Orientation'] ?? 1) : 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if (! $angle) {
            return $image;
        }
        $rotated = imagerotate($image, $angle, 0);
        if (! $rotated) {
            return $image;
        }

        return $rotated;
    }

    private function failed(): ValidationException
    {
        return ValidationException::withMessages(['avatar' => __('تعذّرت معالجة الصورة. جرّب صورة ثانية.')]);
    }
}
