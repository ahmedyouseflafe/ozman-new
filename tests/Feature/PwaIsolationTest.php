<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_shop_types_can_publish_customer_and_merchant_apps_without_claiming_each_other(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $apps = [];

        foreach (array_keys(config('catalog_types')) as $index => $type) {
            $shop = Shop::create([
                'user_id' => $owner->id,
                // Repeated names and prefix-matching slugs must not merge apps.
                'name' => 'Same display name',
                'slug' => $index === 0 ? 'ozman' : 'ozman-'.$index,
                'catalog_type' => $type,
                'is_active' => true,
            ]);

            foreach (['shop-app', 'merchant-app'] as $kind) {
                $manifest = $this->get(route($kind.'.manifest', $shop))->assertOk()->json();
                $label = $kind.':'.$shop->slug;
                $this->assertNotSame('/', $manifest['scope'], $label);
                $this->assertStringEndsWith('/', $manifest['scope'], $label);
                foreach ([$manifest['start_url'], route($kind.'.install', $shop, false), ...array_column($manifest['shortcuts'], 'url')] as $url) {
                    $this->assertStringStartsWith($manifest['scope'], $url, $label);
                }
                foreach ($apps as $otherLabel => $other) {
                    $pair = $label.' versus '.$otherLabel;
                    $this->assertNotSame($other['id'], $manifest['id'], $pair);
                    $this->assertFalse(str_starts_with($manifest['scope'], $other['scope']), $pair);
                    $this->assertFalse(str_starts_with($other['scope'], $manifest['scope']), $pair);
                }
                $apps[$label] = $manifest;
            }
        }

        $this->assertCount(2 * count(config('catalog_types')), $apps);
    }

    public function test_opening_apps_in_sequence_keeps_the_requested_shop_even_with_another_shop_in_session(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shops = collect(['restaurant', 'cosmetics', 'general'])->map(fn ($type) => Shop::create([
            'user_id' => $owner->id,
            'name' => 'Store '.$type,
            'slug' => 'store-'.$type,
            'catalog_type' => $type,
            'is_active' => true,
        ]));

        $this->actingAs($owner)->withSession([
            'merchant_shop_id' => $shops->first()->id,
            'current_shop_id' => $shops->first()->id,
        ]);

        foreach ([$shops[0], $shops[1], $shops[2], $shops[0], $shops[2], $shops[1]] as $shop) {
            $response = $this->get(route('shop-app.launch', $shop))->assertOk()
                ->assertSee(route('shop-app.manifest', $shop), false)
                ->assertDontSee(route('merchant-app.manifest', $shop), false);
            foreach ($shops->except($shops->search($shop)) as $other) {
                $response->assertDontSee('rel="manifest" href="'.route('shop-app.manifest', $other).'"', false);
            }
        }
    }

    public function test_renaming_a_shop_preserves_both_app_identities_and_old_manifest_links(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create([
            'user_id' => $owner->id, 'name' => 'Original', 'slug' => 'original',
            'catalog_type' => 'restaurant', 'is_active' => true,
        ]);
        $previous = [];
        foreach (['shop-app', 'merchant-app'] as $kind) {
            $url = route($kind.'.manifest', $shop);
            $previous[$kind] = ['url' => $url, 'manifest' => $this->get($url)->assertOk()->json()];
        }
        $shop->update(['name' => 'Renamed', 'slug' => 'renamed']);
        $shop->slugRedirects()->create(['slug' => 'original']);

        foreach ($previous as $kind => $old) {
            $manifest = $this->get($old['url'])->assertOk()->json();
            $this->assertSame($old['manifest']['id'], $manifest['id']);
            $this->assertSame('Renamed', $manifest['name']);
            $this->assertSame(route($kind.'.launch', $shop, false), $manifest['start_url']);
            $this->assertStringStartsWith($manifest['scope'], $manifest['start_url']);
        }
    }
}
