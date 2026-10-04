<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClothingTryOnTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_controls_ship_inside_the_page_without_a_separate_public_script_request(): void
    {
        $owner = User::factory()->create();
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Try-on shop',
            'slug' => 'try-on-shop', 'catalog_type' => 'clothing', 'is_active' => true]);

        $this->get(route('clothing.store', $shop))->assertOk()
            ->assertSee('id="demoTryOn"', false)
            ->assertSee('id="tryShowGuides"', false)
            ->assertSee('export function initClothingTryOn', false)
            ->assertDontSee('src="'.asset('clothing-tryon.js'), false)
            ->assertSee('ivory-navy-3', false);
    }

    public function test_demo_image_route_serves_the_new_transparent_png(): void
    {
        $response = $this->get(route('virtual-tryon.demo-garment', ['v' => 'ivory-navy-3']));
        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertSame(realpath(public_path('images/virtual-tryon/demo-ivory-navy-tee.png')), realpath($file));
        $header = file_get_contents($file, false, null, 0, 26);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($header, 0, 8));
        $this->assertSame(6, ord($header[25]), 'The shirt must retain RGBA transparency.');
    }
}
