<?php

namespace Tests\Feature;

use Tests\TestCase;

class LanguageNavigationTest extends TestCase
{
    public function test_assets_do_not_replace_the_previous_page_without_a_referer(): void
    {
        $this->withSession(['_previous' => ['url' => url('/cosmetics/elegance-perfumes')]])
            ->get('/front-assets/shop-stories.css')->assertOk()
            ->assertSessionHas('_previous.url', url('/cosmetics/elegance-perfumes'));

        $this->get('/lang/ar?source=device')->assertRedirect('/cosmetics/elegance-perfumes');
    }

    public function test_device_language_returns_to_explicit_store_despite_stale_asset_history(): void
    {
        $this->withSession(['_previous' => ['url' => url('/front-assets/shop-stories.css')]])
            ->get('/lang/he?source=device&return_to='.urlencode('/cosmetics/elegance-perfumes?category=2#products'))
            ->assertRedirect('/cosmetics/elegance-perfumes?category=2#products')
            ->assertSessionHas('locale', 'he');
    }

    public function test_language_switch_rejects_external_and_asset_destinations(): void
    {
        foreach (['https://evil.test/', '//evil.test/', '/%2f%2fevil.test', '/front-assets/shop-stories.css', '/merchant-pwa-sw.js', '/lang/ar', '/a/../front-assets/shop-stories.css'] as $destination) {
            $this->withSession(['_previous' => ['url' => url('/front-assets/shop-stories.css')]])
                ->get('/lang/en?return_to='.urlencode($destination))->assertRedirect('/');
        }
    }

    public function test_manual_language_selection_keeps_the_preference_cookie(): void
    {
        $this->get('/lang/he?return_to=/shop-app/elegance-perfumes/launch')
            ->assertRedirect('/shop-app/elegance-perfumes/launch')
            ->assertCookie('ozman_public_locale', 'he');
    }
}
