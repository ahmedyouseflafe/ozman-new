<?php

namespace Tests\Feature;

use App\Models\{Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fulla introduces herself in words that fit who opened Ozman. */
class FullaIntroTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_gets_the_welcome_with_a_sign_up_button(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="ozFullaIntro"', false)->assertSee('data-kind="guest"', false)
            ->assertSee(route('community.login'), false)->assertSee('أهلاً فيك بأوزمان! أنا فُلّة 🌸');
    }

    public function test_a_customer_is_greeted_by_first_name(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true, 'name' => 'Lina Saleh']);
        $this->actingAs($customer)->get('/')->assertOk()
            ->assertSee('data-kind="customer"', false)->assertSee('أهلين Lina! أنا فُلّة 🌸')->assertSee('«زي المرة الماضية»');
    }

    public function test_a_shop_owner_meets_the_shop_assistant_on_the_dashboard_and_admins_do_not(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true, 'name' => 'Samer Haddad']);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Grill', 'slug' => 'grill-'.uniqid(), 'catalog_type' => 'restaurant', 'is_active' => true]);

        $this->actingAs($owner)->get('/lost-demand')->assertOk()
            ->assertSee('data-kind="merchant"', false)->assertSee('أهلين Samer! أنا فُلّة، مساعدتك بالمحل 🌸')
            ->assertSee(route('shop-assistant.index', $shop), false);

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get('/lost-demand')->assertOk()->assertDontSee('id="ozFullaIntro"', false);
    }
}
