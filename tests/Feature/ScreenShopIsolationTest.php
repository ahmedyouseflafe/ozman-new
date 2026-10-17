<?php

namespace Tests\Feature;

use App\Models\{Advertisement, MainScreen, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScreenShopIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $slug, string $type = 'cosmetics', ?User $owner = null): Shop
    {
        return Shop::create(['user_id' => ($owner ?? User::factory()->create(['role' => 'shop_owner', 'is_active' => true]))->id,
            'name' => $slug, 'slug' => $slug, 'catalog_type' => $type, 'is_active' => true]);
    }

    private function screen(?Shop $shop, string $title, array $extra = []): MainScreen
    {
        return MainScreen::create([...$this->data($title), 'shop_id' => $shop?->id, 'user_id' => $shop?->user_id, ...$extra]);
    }

    private function data(string $title = 'Own screen'): array
    {
        return ['title' => $title, 'type' => 'youtube', 'media' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'placement' => 'top', 'duration' => 10, 'is_active' => true];
    }

    public function test_owner_creation_uses_current_shop_and_cannot_publish_globally_or_to_another_shop(): void
    {
        $shop = $this->shop('elegance-perfumes');
        $other = $this->shop('other');
        $this->actingAs($shop->user)->get(route('screens.create'))->assertOk()->assertSee('elegance-perfumes')->assertDontSee('واجهة أوزمان العامة');
        $this->post(route('screens.store'), $this->data())->assertRedirect();
        $this->assertDatabaseHas('main_screens', ['shop_id' => $shop->id, 'user_id' => $shop->user_id, 'title' => 'Own screen']);
        $this->postJson(route('screens.store'), [...$this->data(), 'shop_id' => null])->assertUnprocessable();
        $this->postJson(route('screens.store'), [...$this->data(), 'shop_id' => $other->id])->assertForbidden();
        $this->assertDatabaseCount('main_screens', 1);
    }

    public function test_other_shop_records_are_inaccessible_even_when_created_by_the_same_user(): void
    {
        $shop = $this->shop('first'); $other = $this->shop('second');
        $own = $this->screen($shop, 'Visible own');
        $foreign = $this->screen($other, 'Hidden foreign', ['user_id' => $shop->user_id]);
        $global = $this->screen(null, 'Hidden global');
        $this->actingAs($shop->user)->get(route('screens'))->assertOk()->assertSee($own->title)->assertDontSee($foreign->title)->assertDontSee($global->title);
        foreach ([$foreign, $global] as $screen) {
            $this->get(route('screens.show', $screen))->assertForbidden();
            $this->get(route('screens.edit', $screen))->assertForbidden();
            $this->putJson(route('screens.update', $screen), $this->data())->assertForbidden();
            $this->delete(route('screens.destroy', $screen))->assertForbidden();
        }
        $this->get(route('screens', ['shop_id' => $other->id]))->assertForbidden();
        $this->putJson(route('screens.update', $own), [...$this->data(), 'shop_id' => $other->id])->assertForbidden();
        $this->assertDatabaseCount('main_screens', 3);
    }

    public function test_uploaded_image_stays_in_its_shop_and_disabled_screens_stay_hidden(): void
    {
        Storage::fake('public');
        $shop = $this->shop('image-shop');
        $this->actingAs($shop->user)->post(route('screens.store'), [
            ...$this->data('Uploaded shop image'), 'type' => 'image', 'media' => null,
            'media_file' => UploadedFile::fake()->image('shop.png'),
        ])->assertRedirect();
        $screen = MainScreen::firstOrFail();
        $this->assertSame($shop->id, $screen->shop_id);
        Storage::disk('public')->assertExists(substr($screen->media, strlen('storage/')));
        $this->get(route('cosmetics.store', $shop))->assertOk()->assertSee($screen->media);
        $this->get(route('display.main'))->assertOk()->assertDontSee($screen->media);
        $this->put(route('screens.update', $screen), [
            'title' => $screen->title, 'type' => 'image', 'placement' => 'top', 'is_active' => false,
        ])->assertRedirect();
        $this->get(route('cosmetics.store', $shop))->assertOk()->assertDontSee($screen->media);
    }

    public function test_multi_shop_owner_uses_selected_dashboard_shop_and_lists_only_that_shop(): void
    {
        $first = $this->shop('first'); $second = $this->shop('second', 'general', $first->user);
        $this->screen($first, 'First content');
        $this->actingAs($first->user)->withSession(['current_shop_id' => $second->id])
            ->post(route('screens.store'), $this->data('Second content'))->assertRedirect();
        $this->assertDatabaseHas('main_screens', ['shop_id' => $second->id, 'title' => 'Second content']);
        $this->get(route('screens'))->assertOk()->assertSee('Second content')->assertDontSee('First content');
    }

    public function test_admin_can_choose_a_shop_or_explicit_platform_scope(): void
    {
        $shop = $this->shop('elegance-perfumes');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->withSession(['current_shop_id' => $shop->id])
            ->post(route('screens.store'), $this->data('Admin shop screen'))->assertRedirect();
        $this->post(route('screens.store'), [...$this->data('Platform screen'), 'shop_id' => null])->assertRedirect();
        $this->assertDatabaseHas('main_screens', ['title' => 'Admin shop screen', 'shop_id' => $shop->id]);
        $this->assertDatabaseHas('main_screens', ['title' => 'Platform screen', 'shop_id' => null]);
        $screen = MainScreen::where('title', 'Admin shop screen')->firstOrFail();
        $this->get(route('screens.edit', $screen))->assertOk()->assertSee('screenShopId');
        $this->get(route('screens.show', $screen))->assertOk();
    }

    public function test_platform_and_shop_displays_are_isolated_and_ads_are_preserved_without_id_collisions(): void
    {
        $shop = $this->shop('elegance-perfumes'); $other = $this->shop('other');
        $own = $this->screen($shop, 'Elegance display');
        $this->screen($other, 'Foreign display');
        $this->screen(null, 'Platform display');
        $this->screen(null, 'Unassigned display', ['needs_shop_assignment' => true]);
        Advertisement::create([...$this->data('Existing ad'), 'shop_id' => $shop->id]);
        $this->get(route('display.main'))->assertOk()->assertSee('Platform display')->assertDontSee('Elegance display')->assertDontSee('Foreign display')->assertDontSee('Unassigned display');
        $this->get(route('display.shop', $shop))->assertOk()->assertSee('Elegance display')->assertSee('Existing ad')->assertDontSee('Platform display')->assertDontSee('Foreign display');
        $shop->update(['is_active' => false]);
        $this->get(route('display.shop', $shop))->assertNotFound();
        $shop->delete();
        $this->assertDatabaseMissing('main_screens', ['id' => $own->id]);
    }

    public function test_specialized_storefronts_show_only_own_top_and_bottom_screens(): void
    {
        $foreign = $this->shop('foreign');
        $this->screen($foreign, 'Foreign content');
        $this->screen(null, 'Platform content');
        foreach (['cosmetics' => 'cosmetics.store', 'restaurant' => 'restaurant.menu', 'clothing' => 'clothing.store',
            'furniture_appliances' => 'furniture.store', 'sweets' => 'sweets.store', 'advertising_services' => 'advertising.store',
            'real_estate' => 'real-estate.company', 'electronics' => 'electronics.store'] as $type => $route) {
            $shop = $this->shop('store-'.$type, $type);
            $this->screen($shop, 'Top '.$type);
            $this->screen($shop, 'Bottom '.$type, ['placement' => 'bottom']);
            $this->screen($shop, 'Disabled content', ['is_active' => false]);
            $this->get(route($route, $shop))->assertOk()->assertSee('Top '.$type)->assertSee('Bottom '.$type)
                ->assertDontSee('Foreign content')->assertDontSee('Platform content')->assertDontSee('Disabled content');
        }
    }

    public function test_home_payload_keeps_each_screen_under_its_shop_not_ozman(): void
    {
        $ozman = $this->shop('ozman', 'general'); $shop = $this->shop('other', 'general');
        $this->screen($shop, 'Shop content'); $this->screen(null, 'Platform content');
        $response = $this->get(route('front.home'))->assertOk();
        $response->assertViewHas('ozmanScreens', fn ($screens) => $screens->pluck('title')->all() === ['Platform content']);
        $data = $response->viewData('frontData');
        $this->assertStringNotContainsString('Shop content', json_encode($response->viewData('ozmanScreens')));
        // The full directory may carry other shops' payloads, but never assigns them to Ozman.
        $centers = collect($data['centersData']);
        $this->assertSame([], $centers->firstWhere('id', $ozman->id)['display_items']);
        $this->assertSame(['Shop content'], array_column($centers->firstWhere('id', $shop->id)['display_items'], 'title'));
        $response->assertViewHas('shop', fn ($selected) => $selected->id === $ozman->id && $selected->activeScreens->isEmpty());
        $this->get(route('front.shop', $shop))->assertOk()->assertSee('Shop content');
    }

    public function test_migration_assigns_unambiguous_legacy_screens_and_quarantines_ambiguous_ones(): void
    {
        $one = $this->shop('one'); $many = $this->shop('many'); $this->shop('many-two', 'general', $many->user);
        $admin = User::factory()->create(['role' => 'super_admin']);
        $migration = require database_path('migrations/2026_10_17_000001_scope_main_screens_to_shops.php');
        $migration->down();
        foreach ([$one->user_id, $many->user_id, $admin->id] as $id) {
            DB::table('main_screens')->insert([...$this->data('Legacy '.$id), 'user_id' => $id]);
        }
        $migration->up();
        $this->assertDatabaseHas('main_screens', ['user_id' => $one->user_id, 'shop_id' => $one->id, 'is_active' => true]);
        $this->assertDatabaseHas('main_screens', ['user_id' => $many->user_id, 'shop_id' => null, 'needs_shop_assignment' => true]);
        $this->assertSame(['Legacy '.$admin->id], MainScreen::forPlatform()->pluck('title')->all());
        $this->actingAs($many->user)->get(route('screens', ['shop_id' => '']))->assertOk()->assertSee('بانتظار تحديد المحل');
        $legacy = MainScreen::where('user_id', $many->user_id)->firstOrFail();
        $this->put(route('screens.update', $legacy), [...$this->data('Reassigned'), 'shop_id' => $many->id])->assertRedirect();
        $this->assertDatabaseHas('main_screens', ['id' => $legacy->id, 'shop_id' => $many->id, 'needs_shop_assignment' => false]);
    }
}
