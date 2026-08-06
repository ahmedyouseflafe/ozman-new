<?php

namespace Tests\Feature;

use App\Jobs\ProcessUploadedVideo;
use App\Models\MainScreen;
use App\Services\VideoProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_video_is_marked_processing_and_queued(): void
    {
        Queue::fake();
        Storage::fake('public');
        Storage::disk('public')->put('screens/videos/source.mp4', 'video-content');

        $screen = MainScreen::create([
            'title' => 'Video screen',
            'type' => 'video',
            'media' => 'storage/screens/videos/source.mp4',
            'duration' => 10,
            'is_active' => true,
        ]);

        app(VideoProcessingService::class)->queue($screen);

        $this->assertSame('processing', $screen->fresh()->video_status);
        Queue::assertPushed(ProcessUploadedVideo::class, fn(ProcessUploadedVideo $job) =>
            $job->modelClass === MainScreen::class
            && $job->modelId === $screen->id
            && $job->sourcePath === 'storage/screens/videos/source.mp4'
        );
    }

    public function test_processing_video_is_hidden_from_public_queries(): void
    {
        MainScreen::create([
            'title' => 'Processing',
            'type' => 'video',
            'media' => 'storage/processing.mp4',
            'video_status' => 'processing',
            'duration' => 10,
            'is_active' => true,
        ]);
        $ready = MainScreen::create([
            'title' => 'Ready',
            'type' => 'video',
            'media' => 'storage/ready.mp4',
            'video_status' => 'ready',
            'duration' => 10,
            'is_active' => true,
        ]);

        $this->assertSame([$ready->id], MainScreen::query()->publiclyReady()->pluck('id')->all());
    }
}
