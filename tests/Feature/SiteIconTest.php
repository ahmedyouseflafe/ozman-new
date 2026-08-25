<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_has_ozman_favicon(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="icon" type="image/png" href="'.asset('ozman-favicon.png').'?v=3">', false);
    }

    public function test_login_page_has_ozman_favicon(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ozman-favicon.png?v=3', false)
            ->assertDontSee('laravel.svg', false);
    }
}
