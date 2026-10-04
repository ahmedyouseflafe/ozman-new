<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantCustomerWelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bankai_welcome_replaces_the_separate_offer_prompt_in_each_language(): void
    {
        $shop = $this->shop('bankai-sushi');
        foreach (['ar' => 'أهلًا فيك في بانكاي', 'en' => 'Welcome to Bankai', 'he' => 'ברוכים הבאים לבנקאי'] as $locale => $title) {
            $this->withSession(['locale' => $locale])->get(route('restaurant.menu', $shop))
                ->assertOk()->assertSee('id="bankaiWelcome"', false)
                ->assertSee($title)->assertSee('id="bankaiEnablePush"', false)
                ->assertSee('"showPrompt":false', false)
                ->assertSee('ozman.restaurant.'.$shop->id.'.customer.v1', false);
        }
    }

    public function test_other_restaurants_keep_their_existing_menu_and_notification_prompt(): void
    {
        $shop = $this->shop('other-restaurant');
        $this->get(route('restaurant.menu', $shop))->assertOk()
            ->assertDontSee('id="bankaiWelcome"', false)
            ->assertDontSee('id="bankaiProfileEdit"', false)
            ->assertSee('"showPrompt":true', false);
    }

    private function shop(string $slug): Shop
    {
        return Shop::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Bankai Sushi', 'slug' => $slug,
            'catalog_type' => 'restaurant', 'is_active' => true,
        ]);
    }
}
