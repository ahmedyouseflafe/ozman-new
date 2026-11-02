<?php

namespace Tests\Feature;

use App\Models\LoyaltyPointEntry;
use App\Models\PromoterGift;
use App\Models\PromoterGiftSend;
use App\Models\PromoterPost;
use App\Models\PromoterProfile;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromoterPageImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function profile(string $slug = 'rima'): PromoterProfile
    {
        return PromoterProfile::create([
            'user_id' => User::factory()->create(['role' => 'promoter', 'is_active' => true])->id,
            'slug' => $slug, 'display_name' => Str::title($slug), 'is_active' => true,
        ]);
    }

    private function shop(string $name, bool $active = true): Shop
    {
        return Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id,
            'name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)), 'catalog_type' => 'clothing', 'is_active' => $active]);
    }

    private function promoterPost(PromoterProfile $profile, ?Shop $shop = null, array $extra = []): PromoterPost
    {
        return $profile->posts()->create(['kind' => 'post', 'type' => 'image', 'media' => 'promoters/posts/'.Str::random(6).'.jpg',
            'shop_id' => $shop?->id, 'is_active' => true, ...$extra]);
    }

    public function test_shop_visits_are_counted_once_per_session_and_redirect_to_the_shop(): void
    {
        $profile = $this->profile();
        $shop = $this->shop('King Apparel');
        $post = $this->promoterPost($profile, $shop);

        $this->get(route('promoters.shop-visit', $post))->assertRedirect($shop->publicUrl());
        $this->get(route('promoters.shop-visit', $post))->assertRedirect($shop->publicUrl());
        $this->assertSame(1, $post->fresh()->shop_clicks);

        $this->flushSession();
        $this->get(route('promoters.shop-visit', $post));
        $this->assertSame(2, $post->fresh()->shop_clicks);

        // The promoter's own clicks and link-preview bots don't count.
        $this->flushSession();
        $this->actingAs($profile->user)->get(route('promoters.shop-visit', $post))->assertRedirect();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->withHeader('User-Agent', 'facebookexternalhit/1.1')->get(route('promoters.shop-visit', $post));
        $this->assertSame(2, $post->fresh()->shop_clicks);

        // Hidden posts, posts without a shop, and closed shops give 404.
        $this->get(route('promoters.shop-visit', $this->promoterPost($profile, $shop, ['is_active' => false])))->assertNotFound();
        $this->get(route('promoters.shop-visit', $this->promoterPost($profile)))->assertNotFound();
        $this->get(route('promoters.shop-visit', $this->promoterPost($profile, $this->shop('Closed', false))))->assertNotFound();
    }

    public function test_profile_lists_shops_visits_and_top_supporters_by_first_name(): void
    {
        $profile = $this->profile();
        $king = $this->shop('King Apparel');
        $jadaa = $this->shop('Jadaa');
        $this->promoterPost($profile, $king, ['shop_clicks' => 4]);
        $latestKing = $this->promoterPost($profile, $king, ['shop_clicks' => 3]);
        $this->promoterPost($profile, $jadaa, ['shop_clicks' => 2]);
        $this->promoterPost($profile, $this->shop('Hidden shop'), ['is_active' => false, 'shop_clicks' => 10]);

        $lina = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Lina Haddad']);
        $omar = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Omar Saleh']);
        foreach ([[$lina, 100], [$omar, 50], [$lina, 10]] as [$user, $points]) {
            PromoterGiftSend::create(['promoter_profile_id' => $profile->id, 'user_id' => $user->id, 'gift_name' => 'x', 'gift_icon' => '🎁', 'points' => $points]);
        }

        $response = $this->get($profile->publicUrl())->assertOk()
            ->assertSee('المحلات اللي بحكي عنها')->assertSeeInOrder(['Jadaa', 'King Apparel'])
            ->assertSee(route('promoters.shop-visit', $latestKing), false)->assertDontSee('Hidden shop')
            ->assertSee('أكثر الداعمين')->assertSeeInOrder(['Lina', '110', 'Omar', '50'])
            ->assertDontSee('Haddad')->assertDontSee('Saleh')
            ->assertSee('id="viewerGift"', false);
        // 4 + 3 + 2 + 10 (the hidden post's earlier visits still count for the page).
        $response->assertSee('<strong>19</strong>', false);
    }

    public function test_a_gift_from_the_post_viewer_is_tied_to_that_post(): void
    {
        $profile = $this->profile();
        $post = $this->promoterPost($profile);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        LoyaltyPointEntry::create(['user_id' => $customer->id, 'points' => 100, 'type' => LoyaltyPointEntry::TYPE_MANUAL, 'status' => LoyaltyPointEntry::CONFIRMED, 'note' => 'test']);

        $this->actingAs($customer)->postJson(route('promoters.gifts.store', $profile), ['gift_id' => PromoterGift::where('name', 'وردة')->value('id'), 'post_id' => $post->id])->assertOk();
        $this->assertSame($post->id, PromoterGiftSend::sole()->promoter_post_id);
    }

    public function test_directory_shows_latest_stories_and_one_row_header(): void
    {
        $profile = $this->profile();
        $post = $this->promoterPost($profile);
        $this->promoterPost($this->profile('hidden'), null)->profile->update(['is_active' => false]);

        $this->get(route('promoters.index'))->assertOk()
            ->assertSee('hero-stories', false)->assertSee(route('promoters.media', ['post' => $post, 'part' => 'media']), false)
            ->assertSee('تعرّف على المروّجين')->assertSee('دخول / حساب جديد')->assertSee('دخول المروّج')
            ->assertSee('creator-stats', false);

        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($customer)->get(route('promoters.index'))->assertOk()
            ->assertSee('account-button', false)->assertDontSee('دخول المروّج')->assertDontSee('دخول / حساب جديد');
    }
}
