<?php

namespace Tests\Feature;

use App\Models\{Category, FrontOrder, Product, Shop, User};
use App\Services\Assistant\CustomerAssistantTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fulla remembers the customer's usual orders and can prepare a basket from one or several shops. */
class AssistantBasketTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $slug, string $type = 'restaurant'): array
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Shop '.$slug, 'slug' => $slug, 'catalog_type' => $type, 'is_active' => true, 'is_accepting_orders' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Main', 'slug' => 'main-'.$slug, 'is_active' => true]);

        return [$shop, $category];
    }

    private function product(Shop $shop, Category $category, string $name, float $price, bool $active = true): Product
    {
        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => $name, 'slug' => 'p-'.uniqid(), 'price' => $price, 'quantity' => 5, 'rating' => 0, 'is_active' => $active]);
    }

    private function order(User $user, Shop $shop, string $number, array $items, string $status = 'completed'): FrontOrder
    {
        return FrontOrder::create(['shop_id' => $shop->id, 'user_id' => $user->id, 'order_number' => $number, 'customer_name' => 'Lina', 'customer_phone' => '0599000111',
            'items' => $items, 'subtotal' => 50, 'total' => 50, 'status' => $status]);
    }

    public function test_the_usuals_come_from_the_customers_own_orders_per_shop(): void
    {
        [$grill, $cat] = $this->shop('grill');
        $shawarma = $this->product($grill, $cat, 'شاورما', 20);
        $old = $this->product($grill, $cat, 'قديم', 5, false);
        $lina = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->order($lina, $grill, 'A-1', [['product_id' => $shawarma->id, 'name' => 'شاورما', 'qty' => 1], ['product_id' => $old->id, 'name' => 'قديم', 'qty' => 1]]);
        $this->order($lina, $grill, 'A-2', [['product_id' => $shawarma->id, 'name' => 'شاورما', 'qty' => 2]]);
        $this->order($lina, $grill, 'A-3', [['product_id' => $shawarma->id, 'name' => 'شاورما', 'qty' => 9]], 'cancelled');
        $this->order($other, $grill, 'B-1', [['product_id' => $shawarma->id, 'name' => 'شاورما', 'qty' => 5]]);

        $cards = [];
        $result = app(CustomerAssistantTools::class)->run('my_usuals', [], $lina, $cards);

        $this->assertCount(1, $result['shops']);
        $usual = $result['shops'][0];
        $this->assertSame(2, $usual['orders']);
        $this->assertSame('A-2', $usual['last_order']['number']);
        $this->assertSame(['شاورما', 2, 2, true], [$usual['items'][0]['name'], $usual['items'][0]['times'], $usual['items'][0]['usual_qty'], $usual['items'][0]['still_sold']]);
        $this->assertFalse(collect($usual['items'])->firstWhere('name', 'قديم')['still_sold']);
        $this->assertSame([], $cards);
    }

    public function test_a_basket_from_two_shops_is_prepared_but_not_ordered(): void
    {
        [$grill, $grillCat] = $this->shop('grill');
        [$sweets, $sweetsCat] = $this->shop('sweets', 'sweets');
        $shawarma = $this->product($grill, $grillCat, 'شاورما', 20);
        $knafeh = $this->product($sweets, $sweetsCat, 'كنافة', 15);
        $gone = $this->product($sweets, $sweetsCat, 'قطايف', 10, false);
        $lina = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $cards = [];
        $result = app(CustomerAssistantTools::class)->run('propose_basket', ['items' => [
            ['product_id' => $shawarma->id, 'qty' => 2], ['product_id' => $knafeh->id], ['product_id' => $gone->id],
        ]], $lina, $cards);

        $this->assertSame(2, $result['shops']);
        $this->assertCount(2, $result['ready']);
        $this->assertSame(['قطايف'], $result['skipped']);
        $this->assertSame(0, FrontOrder::count());

        $shown = CustomerAssistantTools::cardsFor($cards);
        $this->assertSame([$shawarma->id, $knafeh->id], collect($shown['basket']['lines'])->pluck('product.id')->all());
        $this->assertSame(2, $shown['basket']['lines'][0]['qty']);
        $this->assertSame($sweets->id, $shown['basket']['lines'][1]['shop']['id']);
        $this->assertSame(['قطايف'], $shown['basket']['skipped']);
    }

    public function test_repeating_an_order_works_only_for_the_customers_own_order_and_skips_custom_meals(): void
    {
        [$grill, $cat] = $this->shop('grill');
        $burger = $this->product($grill, $cat, 'برغر', 30);
        $lina = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $other = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->order($lina, $grill, 'A-1', [['product_id' => $burger->id, 'name' => 'برغر', 'qty' => 3], ['product_id' => $burger->id, 'name' => 'برغر مع إضافات', 'qty' => 1, 'addons' => ['جبنة']]]);
        $this->order($other, $grill, 'B-1', [['product_id' => $burger->id, 'name' => 'برغر', 'qty' => 1]]);
        $tools = app(CustomerAssistantTools::class);

        $cards = [];
        $this->assertArrayHasKey('error', $tools->run('propose_basket', ['order_number' => 'B-1'], $lina, $cards));
        $this->assertSame([], $cards);

        $result = $tools->run('propose_basket', ['order_number' => 'A-1'], $lina, $cards);
        $this->assertSame([['name' => 'برغر', 'qty' => 3, 'price' => 30.0, 'shop_id' => $grill->id]], $result['ready']);
        $this->assertSame(['برغر مع إضافات'], $result['skipped']);
        $this->assertSame([['qty' => 3, 'product_id' => $burger->id]], $cards['basket']['items']);

        // A second call in the same reply (another shop's item) adds to the same basket.
        [$sweets, $sweetsCat] = $this->shop('sweets', 'sweets');
        $knafeh = $this->product($sweets, $sweetsCat, 'كنافة', 15);
        $tools->run('propose_basket', ['items' => [['product_id' => $knafeh->id]]], $lina, $cards);
        $this->assertSame([$burger->id, $knafeh->id], array_column($cards['basket']['items'], 'product_id'));
        $this->assertSame(['برغر مع إضافات'], $cards['basket']['skipped']);
    }
}
