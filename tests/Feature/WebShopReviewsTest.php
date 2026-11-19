<?php

namespace Tests\Feature;

use App\Models\{FrontOrder, Shop, ShopReview, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Reviews written in the app show on the shop's website. */
class WebShopReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_reviews_page_shows_the_summary_first_names_and_replies_but_not_hidden_reviews(): void
    {
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'Sweets', 'slug' => 'sweets', 'catalog_type' => 'sweets', 'is_active' => true]);
        $review = fn (int $rating, array $extra = []) => ShopReview::create(['shop_id' => $shop->id, 'user_id' => User::factory()->create(['name' => 'Lina Haddad'])->id,
            'front_order_id' => FrontOrder::create(['shop_id' => $shop->id, 'order_number' => 'R-'.uniqid(), 'customer_name' => 'x', 'items' => [], 'subtotal' => 1, 'discount' => 0, 'total' => 1, 'status' => 'completed'])->id,
            'rating' => $rating, 'is_visible' => true, ...$extra]);
        $review(5, ['comment' => 'أطيب كنافة', 'reply' => 'شكراً إلك']);
        $review(4);
        $review(1, ['comment' => 'Hidden one', 'is_visible' => false]);

        $this->get(route('shop.reviews', $shop))->assertOk()
            ->assertSee('4.5')->assertSee('Lina H.')->assertDontSee('Haddad')
            ->assertSee('أطيب كنافة')->assertSee('شكراً إلك')->assertDontSee('Hidden one');

        // The storefront shows the rating badge that links here.
        $this->get($shop->publicUrl())->assertOk()->assertSee(route('shop.reviews', $shop), false);
    }

    public function test_a_shop_without_reviews_has_no_badge_and_a_closed_shop_has_no_page(): void
    {
        $shop = Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner'])->id, 'name' => 'New', 'slug' => 'new-shop', 'catalog_type' => 'sweets', 'is_active' => true]);
        $this->get($shop->publicUrl())->assertOk()->assertDontSee('oz-shop-rating"', false);
        $this->get(route('shop.reviews', $shop))->assertOk()->assertSee('لسا ما في تقييمات');

        $shop->update(['is_active' => false]);
        $this->get(route('shop.reviews', $shop))->assertNotFound();
    }
}
