<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarExplorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_explorer_is_public_and_separate_from_demo_listings(): void
    {
        $this->get('/cars')->assertOk()
            ->assertSee('data-car-explorer', false)
            ->assertSee('نموذج تفاعلي مبسّط، مستقل عن السيارات المعروضة للبيع.')
            ->assertSee('data-car-view="inside"', false)
            ->assertSee('data-part="hood"', false)
            ->assertSee(route('front.assets', 'car-explorer.js'), false);
    }

    public function test_explorer_modules_are_served_with_valid_javascript_types(): void
    {
        foreach (['car-explorer.js', 'car-explorer-model.js', 'caravan-demo.js', 'caravan-model.js'] as $file) {
            $this->get('/front-assets/'.$file)->assertOk()
                ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get('/front-assets/car-explorer-private.js')->assertNotFound();
    }
}
