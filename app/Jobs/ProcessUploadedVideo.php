<?php

namespace App\Jobs;

use App\Models\Advertisement;
use App\Models\MainScreen;
use App\Models\Product;
use App\Models\ProductCampaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class ProcessUploadedVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(
        public string $modelClass,
        public int $modelId,
        public string $mediaField,
        public string $sourcePath,
    ) {
        $this->onQueue('media');
    }

    public function handle(): void
    {
        $model = $this->findModel();
        if (! $model || $model->{$this->mediaField} !== $this->sourcePath) {
            return;
        }

        $disk = Storage::disk('public');
        $relativeSource = Str::of($this->sourcePath)->replaceStart('storage/', '')->toString();
        if (! $disk->exists($relativeSource)) {
            throw new RuntimeException('ملف الفيديو الأصلي غير موجود.');
        }

        $directory = trim(dirname($relativeSource), '.\\/');
        $baseName = pathinfo($relativeSource, PATHINFO_FILENAME);
        $token = Str::random(8);
        $outputRelative = ($directory ? $directory.'/' : '').$baseName.'-'.$token.'.web.mp4';
        $posterRelative = ($directory ? $directory.'/' : '').$baseName.'-'.$token.'.poster.jpg';
        $source = $disk->path($relativeSource);
        $output = $disk->path($outputRelative);
        $poster = $disk->path($posterRelative);

        try {
            $this->run([
                config('media.ffmpeg'), '-y', '-i', $source,
                '-vf', 'scale=-2:min('.config('media.video_height').',ih)',
                '-c:v', 'libx264', '-preset', 'medium', '-crf', (string) config('media.video_crf'),
                '-movflags', '+faststart', '-c:a', 'aac', '-b:a', '96k', $output,
            ]);
            $this->run([
                config('media.ffmpeg'), '-y', '-ss', '00:00:01', '-i', $output,
                '-frames:v', '1', '-q:v', '3', $poster,
            ]);

            if (! is_file($output) || filesize($output) === 0) {
                throw new RuntimeException('لم ينتج FFmpeg ملف فيديو صالحاً.');
            }

            $model->forceFill([
                $this->mediaField => 'storage/'.$outputRelative,
                'video_status' => 'ready',
                'video_poster' => is_file($poster) ? 'storage/'.$posterRelative : null,
                'video_error' => null,
            ])->save();

            if ($relativeSource !== $outputRelative) {
                $disk->delete($relativeSource);
            }
        } catch (Throwable $exception) {
            $disk->delete([$outputRelative, $posterRelative]);
            $model->forceFill([
                'video_status' => 'failed',
                'video_error' => Str::limit($exception->getMessage(), 1000),
            ])->save();
            throw $exception;
        }
    }

    private function findModel(): MainScreen|Advertisement|Product|ProductCampaign|null
    {
        $allowed = [MainScreen::class, Advertisement::class, Product::class, ProductCampaign::class];
        if (! in_array($this->modelClass, $allowed, true)) {
            throw new RuntimeException('نوع الوسائط غير مدعوم.');
        }

        return $this->modelClass::query()->find($this->modelId);
    }

    private function run(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->mustRun();
    }
}
