<?php

namespace Tests\Feature;

use App\Models\LoyaltyPointEntry;
use App\Models\PromoterGift;
use App\Models\PromoterGiftSend;
use App\Models\PromoterProfile;
use App\Models\Shop;
use App\Models\User;
use App\Services\LoyaltyPoints;
use App\Services\PromoterLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PromoterGiftsTest extends TestCase
{
    use RefreshDatabase;

    private function profile(string $slug = 'rima'): PromoterProfile
    {
        return PromoterProfile::create([
            'user_id' => User::factory()->create(['role' => 'promoter', 'is_active' => true])->id,
            'slug' => $slug, 'display_name' => Str::title($slug), 'is_active' => true,
        ]);
    }

    private function shop(string $name): Shop
    {
        return Shop::create(['user_id' => User::factory()->create(['role' => 'shop_owner', 'is_active' => true])->id,
            'name' => $name, 'slug' => Str::slug($name).'-'.Str::random(4), 'is_active' => true]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true]);
    }

    /** Confirmed points earned at a shop, `$daysAgo` days ago (oldest is spent first). */
    private function earn(User $user, ?Shop $shop, int $points, int $daysAgo, string $status = LoyaltyPointEntry::CONFIRMED): void
    {
        $entry = LoyaltyPointEntry::create(['user_id' => $user->id, 'shop_id' => $shop?->id, 'points' => $points,
            'type' => LoyaltyPointEntry::TYPE_EARN, 'status' => $status]);
        $entry->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
    }

    private function gift(string $name): PromoterGift
    {
        return PromoterGift::where('name', $name)->firstOrFail();
    }

    public function test_default_gift_catalog_is_seeded(): void
    {
        $this->assertSame(['وردة' => 10, 'قهوة' => 50, 'هدية' => 100, 'تاج' => 500],
            PromoterGift::available()->pluck('points', 'name')->all());
    }

    public function test_a_gift_spends_the_oldest_shop_points_first_and_raises_the_promoter(): void
    {
        $profile = $this->profile();
        $customer = $this->customer();
        $kingApparel = $this->shop('King Apparel');
        $jadaa = $this->shop('Jadaa');
        $this->earn($customer, $jadaa, 30, 2);        // newer
        $this->earn($customer, $kingApparel, 50, 10); // older → spent first
        $this->earn($customer, $jadaa, 40, 1, LoyaltyPointEntry::PENDING); // pending never counts

        $this->actingAs($customer)->postJson(route('promoters.gifts.store', $profile), ['gift_id' => $this->gift('وردة')->id])
            ->assertOk()->assertJson(['sent' => true, 'balance' => 70, 'gift_points' => 10]);

        // 10 more: still from King Apparel (40 left there).
        $loyalty = app(LoyaltyPoints::class);
        $this->assertSame(40, $loyalty->shopBalance($customer, $kingApparel));
        $this->assertSame(30, $loyalty->shopBalance($customer, $jadaa));

        // 50-point coffee: 40 from King Apparel, the remaining 10 from Jadaa.
        $this->actingAs($customer)->postJson(route('promoters.gifts.store', $profile), ['gift_id' => $this->gift('قهوة')->id])
            ->assertOk()->assertJson(['balance' => 20, 'gift_points' => 60]);
        $this->assertSame(0, $loyalty->shopBalance($customer, $kingApparel));
        $this->assertSame(20, $loyalty->shopBalance($customer, $jadaa));
        $this->assertSame(20, $loyalty->balance($customer));
        $this->assertSame(40, $loyalty->pendingPoints($customer));

        $coffee = PromoterGiftSend::latest('id')->first();
        $this->assertSame(['☕', 'قهوة', 50], [$coffee->gift_icon, $coffee->gift_name, $coffee->points]);
        $this->assertEqualsCanonicalizing([-40, -10], LoyaltyPointEntry::where('promoter_gift_send_id', $coffee->id)->pluck('points')->all());
        $this->assertSame(60, $profile->fresh()->gift_points);
    }

    public function test_insufficient_or_pending_points_cannot_pay_for_a_gift(): void
    {
        $profile = $this->profile();
        $customer = $this->customer();
        $this->earn($customer, $this->shop('A'), 40, 3);
        $this->earn($customer, $this->shop('B'), 500, 1, LoyaltyPointEntry::PENDING);

        $this->actingAs($customer)->postJson(route('promoters.gifts.store', $profile), ['gift_id' => $this->gift('قهوة')->id])
            ->assertStatus(422)->assertJsonValidationErrors('gift');

        $this->assertSame(0, PromoterGiftSend::count());
        $this->assertSame(0, LoyaltyPointEntry::where('type', LoyaltyPointEntry::TYPE_GIFT)->count());
        $this->assertSame(40, app(LoyaltyPoints::class)->balance($customer));
        $this->assertSame(0, $profile->fresh()->gift_points);
    }

    public function test_only_signed_in_customers_can_gift_and_only_active_gifts_and_pages(): void
    {
        $profile = $this->profile();
        $rose = $this->gift('وردة');

        $this->postJson(route('promoters.gifts.store', $profile), ['gift_id' => $rose->id])
            ->assertStatus(401)->assertJsonStructure(['login']);

        foreach (['shop_owner', 'promoter', 'super_admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]))
                ->postJson(route('promoters.gifts.store', $profile), ['gift_id' => $rose->id])->assertForbidden();
        }

        $customer = $this->customer();
        $this->earn($customer, null, 1000, 1);
        $rose->update(['is_active' => false]);
        $this->actingAs($customer)->postJson(route('promoters.gifts.store', $profile), ['gift_id' => $rose->id])->assertStatus(422);

        $hidden = $this->profile('hidden');
        $hidden->update(['is_active' => false]);
        $this->actingAs($customer)->postJson(route('promoters.gifts.store', $hidden), ['gift_id' => $this->gift('قهوة')->id])->assertNotFound();

        $otherPost = $this->profile('other')->posts()->create(['kind' => 'post', 'type' => 'image', 'media' => 'promoters/posts/x.jpg', 'is_active' => true]);
        $this->actingAs($customer)->postJson(route('promoters.gifts.store', $profile), ['gift_id' => $this->gift('قهوة')->id, 'post_id' => $otherPost->id])
            ->assertStatus(422)->assertJsonValidationErrors('post_id');

        $this->assertSame(0, PromoterGiftSend::count());
    }

    public function test_profile_shows_level_gifts_and_counts_one_visit_per_session_per_day(): void
    {
        $profile = $this->profile();
        $customer = $this->customer();
        $this->earn($customer, null, 60, 1);

        $this->actingAs($customer)->get($profile->publicUrl())->assertOk()
            ->assertSee('id="promoterGiftDialog"', false)->assertSee('data-signed-in="1"', false)
            ->assertSee('🥉 برونزي')->assertSee('⭐ 60');
        $this->actingAs($customer)->get($profile->publicUrl())->assertOk();
        $this->assertSame(1, $profile->fresh()->page_views);

        $this->travel(1)->days();
        $this->actingAs($customer)->get($profile->publicUrl());
        $this->assertSame(2, $profile->fresh()->page_views);

        // The promoter's own visits and link-preview bots never count.
        $this->actingAs($profile->user)->get($profile->publicUrl())->assertOk()->assertDontSee('id="promoterGiftDialog"', false);
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->withHeader('User-Agent', 'WhatsApp/2.23')->get($profile->publicUrl());
        $this->assertSame(2, $profile->fresh()->page_views);

        // Guests see the gifts with a sign-in link.
        $this->flushSession();
        $this->get($profile->publicUrl())->assertSee('data-signed-in="0"', false)->assertSee('سجّل دخول عشان تهدي');
    }

    public function test_levels_and_directory_order_by_reach(): void
    {
        $level = app(PromoterLevel::class);
        $this->assertSame('bronze', $level->forScore(0)['key']);
        $this->assertSame('silver', $level->forScore(500)['key']);
        $this->assertSame(50, $level->forScore(1250)['progress']);
        $this->assertSame('diamond', $level->forScore(10000)['key']);
        $this->assertNull($level->forScore(10000)['next']);

        $quiet = $this->profile('quiet');
        $popular = $this->profile('popular');
        $popular->update(['gift_points' => 450]);
        $popular->followers()->attach($this->customer()); // +10 → 460
        $quiet->update(['page_views' => 30]);

        $this->assertSame(460, $level->score($popular->fresh()));
        $this->get(route('promoters.index'))->assertOk()->assertSeeInOrder(['Popular', 'Quiet'])->assertSee('الأكثر وصول');
        $quiet->forceFill(['created_at' => now()->addMinute()])->save();
        $this->get(route('promoters.index', ['sort' => 'latest']))->assertOk()->assertSeeInOrder(['Quiet', 'Popular']);

        $popular->update(['gift_points' => 600]);
        $this->assertSame('silver', $popular->fresh()->level()['key']);
    }

    public function test_only_super_admins_manage_the_gift_catalog(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'promoter', 'is_active' => true]))->get(route('promoter.gifts.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('promoter.gifts.index'))->assertOk()->assertSee('🌹');
        $this->actingAs($admin)->post(route('promoter.gifts.store'), ['name' => 'باقة', 'icon' => '💐', 'points' => 250, 'sort_order' => 5, 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('promoter_gifts', ['name' => 'باقة', 'points' => 250, 'is_active' => true]);

        $crown = $this->gift('تاج');
        $this->actingAs($admin)->patch(route('promoter.gifts.update', $crown), ['name' => 'تاج', 'icon' => '👑', 'points' => 800, 'is_active' => 0])->assertRedirect();
        $this->assertSame([800, false], [$crown->fresh()->points, $crown->fresh()->is_active]);
    }

    public function test_account_page_lists_points_per_shop(): void
    {
        $customer = $this->customer();
        $this->earn($customer, $this->shop('King Apparel'), 50, 3);
        $this->earn($customer, $this->shop('Jadaa'), 30, 1);

        $this->actingAs($customer)->get(route('community.account'))->assertOk()
            ->assertSee('نقاطك حسب المحل')->assertSeeInOrder(['King Apparel', '50', 'Jadaa', '30']);
    }
}
