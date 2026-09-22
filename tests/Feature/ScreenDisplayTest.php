<?php

namespace Tests\Feature;

use App\Models\MainScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_screen_has_a_muted_sound_toggle_control(): void
    {
        MainScreen::create([
            'title' => 'فيديو العرض',
            'type' => 'video',
            'media' => 'screens/videos/demo.mp4',
            'duration' => 10,
            'placement' => 'top',
            'is_active' => true,
        ]);

        $this->get(route('display.main'))
            ->assertOk()
            ->assertSee('data-sound-toggle', false)
            ->assertSee('ti-volume-off', false)
            ->assertSee('muted playsinline autoplay loop', false)
            ->assertSee('syncVideoSound', false);
    }
}
