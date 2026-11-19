<?php

namespace App\Services;

use App\Models\{Product, Product3dJob, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * A product's 3D model for "see it in your room": the owner uploads a .glb (from the factory or a designer), or has one made
 * from the product's photo (Meshy). A made model waits for the owner's approval before customers see it.
 */
class Product3d
{
    public const MAX_BYTES = 25 * 1024 * 1024;

    public function canHave(Product $product): bool
    {
        return in_array($product->shop?->catalog_type, config('services.meshy.shop_types', ['furniture_appliances']), true);
    }

    public function converterReady(): bool
    {
        return filled(config('services.meshy.key'));
    }

    public function url(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }

    /** Everything the owner's screens show about a product's model. */
    public function state(Product $product): array
    {
        $job = Product3dJob::where('product_id', $product->id)->latest('id')->first();
        if ($job && $job->isWorking()) {
            $job = $this->poll($job);
        }

        return [
            'available' => $this->canHave($product),
            'model' => $this->url($product->model_3d),
            'size' => $product->model_3d_size,
            'ar_url' => $product->model_3d ? route('products.ar', $product) : null,
            'can_convert' => $this->converterReady() && $this->canHave($product) && filled($product->main_image),
            'job' => $job && $job->status !== 'discarded' && $job->status !== 'approved' ? [
                'id' => $job->id, 'status' => $job->status, 'progress' => $job->progress,
                'preview' => $this->url($job->result_path), 'error' => $job->error,
                'started_at' => $job->created_at?->toIso8601String(),
            ] : null,
        ];
    }

    public function upload(Product $product, UploadedFile $file): void
    {
        $this->ensureAllowed($product);
        // A .glb starts with the bytes "glTF"; browsers send it with all sorts of types, so check the file itself.
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 4);
        if (strtolower($file->getClientOriginalExtension()) !== 'glb' || $head !== 'glTF') {
            throw ValidationException::withMessages(['model' => __('الملف لازم يكون نموذج ثلاثي الأبعاد بصيغة .glb')]);
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['model' => __('الملف أكبر من 25 ميغا. اطلب نسخة أخف من المصمم.')]);
        }
        $path = $file->storeAs('products/3d/'.$product->shop_id, Str::uuid().'.glb', 'public');
        $this->replaceModel($product, $path);
    }

    /** Real size in centimetres; the AR view scales the model to it. Empty values clear it. */
    public function setSize(Product $product, ?float $width, ?float $depth, ?float $height): void
    {
        $size = array_filter(['w' => $width, 'd' => $depth, 'h' => $height], fn ($v) => $v !== null && $v > 0);
        foreach ($size as $value) {
            if ($value > 2000) {
                throw ValidationException::withMessages(['size' => __('المقاس بالسنتيمتر، وأكبر قيمة 2000 سم.')]);
            }
        }
        $product->forceFill(['model_3d_size' => $size ?: null])->save();
    }

    public function remove(Product $product): void
    {
        $this->replaceModel($product, null);
    }

    /** Starts making a model from the product's main photo. */
    public function convert(Product $product, ?User $user): Product3dJob
    {
        $this->ensureAllowed($product);
        if (! $this->converterReady()) {
            throw ValidationException::withMessages(['convert' => __('تحويل الصور لـ 3D مش مفعّل لسا.')]);
        }
        $image = $this->imageUrl($product->main_image);
        if (! $image) {
            throw ValidationException::withMessages(['convert' => __('ارفع صورة للمنتج أول، على خلفية سادة إذا بتقدر.')]);
        }
        $working = Product3dJob::where('product_id', $product->id)->whereIn('status', ['queued', 'running'])->first();
        if ($working) {
            return $working;
        }
        $job = Product3dJob::create(['product_id' => $product->id, 'user_id' => $user?->id, 'status' => 'queued']);
        try {
            $response = Http::withToken(config('services.meshy.key'))->acceptJson()->timeout(30)
                ->post('https://api.meshy.ai/openapi/v1/image-to-3d', [
                    'image_url' => $image, 'should_texture' => true, 'enable_pbr' => true,
                    'target_polycount' => 30000, 'target_formats' => ['glb'],
                ]);
            if (! $response->successful() || ! $response->json('result')) {
                throw new RuntimeException((string) ($response->json('message') ?: 'HTTP '.$response->status()));
            }
            $job->update(['provider_task_id' => $response->json('result'), 'status' => 'running']);
        } catch (Throwable $e) {
            report($e);
            $job->update(['status' => 'failed', 'error' => Str::limit(__('خدمة التحويل ما ردّت. جرّب كمان شوي.'), 490)]);
        }

        return $job->fresh();
    }

    /** Asks the service how a job is doing; keeps the finished model in our storage. */
    public function poll(Product3dJob $job): Product3dJob
    {
        if (! $job->isWorking() || ! $job->provider_task_id || ! $this->converterReady()) {
            if ($job->status === 'queued' && $job->created_at?->lt(now()->subMinutes(10))) {
                $job->update(['status' => 'failed', 'error' => __('التحويل ما بلّش. جرّب مرة ثانية.')]);
            }

            return $job;
        }
        // Not more than once every few seconds per job, however many screens ask.
        if ($job->updated_at && $job->updated_at->gt(now()->subSeconds(5)) && $job->status === 'running' && $job->progress > 0) {
            return $job;
        }
        try {
            $response = Http::withToken(config('services.meshy.key'))->acceptJson()->timeout(20)
                ->get('https://api.meshy.ai/openapi/v1/image-to-3d/'.$job->provider_task_id);
            if (! $response->successful()) {
                throw new RuntimeException('HTTP '.$response->status());
            }
            $status = (string) $response->json('status');
            if ($status === 'SUCCEEDED' && ($glb = $response->json('model_urls.glb'))) {
                $file = Http::timeout(120)->get($glb);
                if (! $file->successful() || substr($file->body(), 0, 4) !== 'glTF') {
                    throw new RuntimeException('Model download failed.');
                }
                $path = 'products/3d/'.$job->product->shop_id.'/'.Str::uuid().'.glb';
                Storage::disk('public')->put($path, $file->body());
                $job->update(['status' => 'ready', 'progress' => 100, 'result_path' => $path]);
            } elseif (in_array($status, ['FAILED', 'CANCELED'], true)) {
                $job->update(['status' => 'failed', 'error' => __('ما زبط التحويل من هالصورة. جرّب صورة أوضح للقطعة لحالها على خلفية سادة.')]);
            } else {
                $job->forceFill(['progress' => min(99, (int) $response->json('progress', $job->progress)), 'updated_at' => now()])->save();
            }
        } catch (Throwable $e) {
            report($e);
            if ($job->created_at?->lt(now()->subMinutes(30))) {
                $job->update(['status' => 'failed', 'error' => __('التحويل طوّل كتير. جرّب مرة ثانية.')]);
            }
        }

        return $job->fresh();
    }

    /** The owner liked the made model: customers see it from now on. */
    public function approve(Product3dJob $job): void
    {
        abort_unless($job->status === 'ready' && $job->result_path, 422, __('النموذج مش جاهز للاعتماد.'));
        $this->replaceModel($job->product, $job->result_path);
        $job->update(['status' => 'approved']);
    }

    public function discard(Product3dJob $job): void
    {
        if ($job->status === 'ready' && $job->result_path) {
            Storage::disk('public')->delete($job->result_path);
        }
        $job->update(['status' => 'discarded']);
    }

    private function replaceModel(Product $product, ?string $path): void
    {
        $old = $product->model_3d;
        $product->forceFill(['model_3d' => $path])->save();
        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }
    }

    private function ensureAllowed(Product $product): void
    {
        if (! $this->canHave($product)) {
            throw ValidationException::withMessages(['model' => __('نماذج الـ 3D متاحة لمحلات الأثاث.')]);
        }
    }

    private function imageUrl(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        return Str::startsWith($image, ['http://', 'https://']) ? $image : asset(Str::startsWith($image, 'storage/') ? $image : 'storage/'.$image);
    }
}
