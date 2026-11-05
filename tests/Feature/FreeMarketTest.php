<?php

namespace Tests\Feature;

use App\Models\MarketListing;
use App\Models\MarketOrder;
use App\Models\User;
use App\Notifications\MarketOrderUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FreeMarketTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role = 'customer', array $extra = []): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true, ...$extra]);
    }

    private function listingData(array $extra = []): array
    {
        return ['title' => 'جاكيت جلد', 'description' => 'مقاس M', 'price' => '120', 'category' => 'clothing', 'condition' => 'used',
            'city' => 'نابلس', 'quantity' => 2, 'images' => [UploadedFile::fake()->image('a.jpg', 1600, 1200), UploadedFile::fake()->image('b.png', 300, 300)], ...$extra];
    }

    private function listing(User $seller, array $extra = []): MarketListing
    {
        $listing = MarketListing::create(['user_id' => $seller->id, 'title' => 'سماعات', 'price' => 50, 'category' => 'electronics', 'condition' => 'new',
            'city' => 'رام الله', 'quantity' => 1, 'status' => 'active', ...$extra]);
        Storage::disk('local')->put($path = 'market/listings/'.uniqid().'.webp', 'x');
        $listing->images()->create(['path' => $path]);

        return $listing;
    }

    public function test_member_lists_a_product_that_shows_at_once_with_resized_photos(): void
    {
        Storage::fake('local');
        $seller = $this->member('promoter');

        $this->get(route('market.create'))->assertRedirect(route('community.login'));
        $this->actingAs($seller)->get(route('market.create'))->assertOk();
        $response = $this->actingAs($seller)->post(route('market.store'), $this->listingData());
        $listing = MarketListing::sole();
        $response->assertRedirect($listing->url());
        $this->assertSame('active', $listing->status);
        $this->assertCount(2, $listing->images);
        $path = $listing->images->first()->path;
        Storage::disk('local')->assertExists($path);
        [$w] = getimagesizefromstring(Storage::disk('local')->get($path));
        $this->assertSame(1400, $w);

        auth()->logout();
        $this->get(route('market.index'))->assertOk()->assertSee('جاكيت جلد')->assertSee('120 ₪');
        $this->get(route('market.index', ['category' => 'electronics']))->assertDontSee('جاكيت جلد');
        $this->get(route('market.index', ['q' => 'جاكيت', 'condition' => 'used', 'city' => 'نابلس']))->assertSee('جاكيت جلد');
        $this->get($listing->url())->assertOk()->assertSee('دخول / حساب جديد');
        $this->get(route('market.image', $listing->images->first()))->assertOk();
    }

    public function test_listing_rules_and_who_can_sell(): void
    {
        Storage::fake('local');
        $this->actingAs($this->member())->post(route('market.store'), $this->listingData(['images' => []]))->assertSessionHasErrors('images');
        $this->actingAs($this->member())->post(route('market.store'), $this->listingData(['price' => 0, 'category' => 'weapons']))->assertSessionHasErrors(['price', 'category']);
        $this->actingAs($this->member('shop_owner'))->post(route('market.store'), $this->listingData())->assertRedirect(route('market.index'));
        $this->assertSame(0, MarketListing::count());
    }

    public function test_hidden_and_removed_listings_are_private_and_only_the_owner_edits(): void
    {
        Storage::fake('local');
        $seller = $this->member();
        $other = $this->member();
        $listing = $this->listing($seller);

        $this->actingAs($other)->get(route('market.edit', $listing))->assertForbidden();
        $this->actingAs($other)->put(route('market.update', $listing), ['title' => 'x'])->assertForbidden();

        $this->actingAs($seller)->patch(route('market.toggle', $listing));
        $this->assertSame('hidden', $listing->fresh()->status);
        $this->actingAs($other)->get($listing->url())->assertNotFound();
        $this->actingAs($other)->get(route('market.image', $listing->images->first()))->assertNotFound();
        $this->actingAs($seller)->get($listing->url())->assertOk()->assertSee('تعديل المنتج');

        $admin = $this->member('super_admin');
        $this->actingAs($admin)->get(route('market.admin'))->assertOk()->assertSee('سماعات');
        $this->actingAs($admin)->patch(route('market.admin.moderate', $listing), ['action' => 'remove']);
        $this->assertSame('removed', $listing->fresh()->status);
        $this->actingAs($seller)->patch(route('market.toggle', $listing))->assertForbidden();
        $this->actingAs($seller)->get(route('market.admin'))->assertForbidden();

        $this->actingAs($admin)->delete(route('market.admin.destroy', $listing));
        $this->assertModelMissing($listing);
        Storage::disk('local')->assertMissing($listing->images->first()->path);
    }

    public function test_order_flow_takes_stock_on_accept_and_returns_it_on_cancel(): void
    {
        Storage::fake('local');
        Notification::fake();
        $seller = $this->member();
        $buyer = $this->member();
        $listing = $this->listing($seller, ['quantity' => 2, 'price' => 50]);
        $form = ['quantity' => 2, 'buyer_name' => 'Omar', 'buyer_phone' => '0599 123 456', 'city' => 'جنين', 'address' => 'شارع الجامعة', 'unit_price' => 1, 'total' => 1];

        $this->actingAs($seller)->post(route('market.order', $listing), $form)->assertForbidden();
        $this->actingAs($buyer)->post(route('market.order', $listing), [...$form, 'quantity' => 3])->assertSessionHasErrors('quantity');
        $this->actingAs($buyer)->post(route('market.order', $listing), $form)->assertRedirect(route('market.purchases'));
        $order = MarketOrder::sole();
        $this->assertSame('100.00', (string) $order->total);
        $this->assertSame('pending', $order->status);
        Notification::assertSentTo($seller, MarketOrderUpdate::class, fn ($n) => $n->event === 'new' && str_contains($n->toMail($seller)->subject, $order->order_number) && str_contains((string) $n->toMail($seller)->render(), 'سماعات'));

        $this->actingAs($buyer)->patch(route('market.orders.update', $order), ['action' => 'accept'])->assertForbidden();
        $this->actingAs($seller)->get(route('market.sales'))->assertOk()->assertSee('Omar')->assertSee('0599 123 456');
        $this->actingAs($seller)->patch(route('market.orders.update', $order), ['action' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $order->fresh()->status);
        $this->assertSame(0, $listing->fresh()->quantity);
        $this->assertSame('sold', $listing->fresh()->status);
        Notification::assertSentTo($buyer, MarketOrderUpdate::class, fn ($n) => $n->order->status === 'accepted' && str_contains((string) $n->toMail($buyer)->render(), 'قبله البائع'));
        $this->get(route('market.index'))->assertDontSee('سماعات');

        // The buyer can't cancel after the seller answered; the seller can, and the stock comes back.
        $this->actingAs($buyer)->patch(route('market.orders.cancel', $order))->assertSessionHasErrors('order');
        $this->actingAs($seller)->patch(route('market.orders.update', $order), ['action' => 'cancel']);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(2, $listing->fresh()->quantity);
        $this->assertSame('active', $listing->fresh()->status);
        $this->actingAs($seller)->patch(route('market.orders.update', $order), ['action' => 'complete'])->assertSessionHasErrors('order');
    }

    public function test_buyer_cancels_pending_order_and_seller_completes_another(): void
    {
        Storage::fake('local');
        Notification::fake();
        $seller = $this->member();
        $buyer = $this->member();
        $listing = $this->listing($seller, ['quantity' => 5]);
        $form = ['quantity' => 1, 'buyer_name' => 'Lina', 'buyer_phone' => '0599000000', 'city' => 'الخليل', 'address' => 'وسط البلد'];

        $this->actingAs($buyer)->post(route('market.order', $listing), $form);
        $first = MarketOrder::latest('id')->first();
        $this->actingAs($buyer)->patch(route('market.orders.cancel', $first))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $first->fresh()->status);
        Notification::assertSentTo($seller, MarketOrderUpdate::class, fn ($n) => $n->event === 'buyer_cancelled');

        $this->actingAs($buyer)->post(route('market.order', $listing), $form);
        $second = MarketOrder::latest('id')->first();
        $this->actingAs($seller)->patch(route('market.orders.update', $second), ['action' => 'accept']);
        $this->actingAs($seller)->patch(route('market.orders.update', $second), ['action' => 'complete']);
        $this->assertSame('completed', $second->fresh()->status);
        $this->assertSame(4, $listing->fresh()->quantity);
        $this->actingAs($buyer)->get(route('market.purchases'))->assertOk()->assertSee('تم التسليم');
    }

    public function test_entry_links_and_the_main_market_page_still_work(): void
    {
        $this->get(route('front.home'))->assertOk();
        $this->assertStringEndsWith('/market', route('front.home'));
        $this->get(route('promoters.index'))->assertSee(route('market.index'), false);
    }
}
