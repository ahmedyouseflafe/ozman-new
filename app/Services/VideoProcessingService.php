<?php

namespace App\Services;

use App\Jobs\ProcessUploadedVideo;
use Illuminate\Database\Eloquent\Model;

class VideoProcessingService
{
    public function queue(Model $model, string $mediaField = 'media'): void
    {
        $path = $model->{$mediaField};
        if (! is_string($path) || ! str_starts_with($path, 'storage/')) {
            return;
        }

        $model->forceFill([
            'video_status' => 'processing',
            'video_poster' => null,
            'video_error' => null,
        ])->save();

        ProcessUploadedVideo::dispatch($model::class, (int) $model->getKey(), $mediaField, $path)
            ->afterCommit();
    }
}
