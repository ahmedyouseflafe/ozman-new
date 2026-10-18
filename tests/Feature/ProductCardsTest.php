<?php

namespace Tests\Feature;

use App\Models\{Category, Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCardsTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $type = 'sweets', ?User $owner = null): Product
    {
        $owner ??= User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Store '.$type, 'slug' => uniqid('store-'), 'catalog_type' => $type, 'is_active' => true]);
        $category = Category::create(['shop_id' => $shop->id, 'name' => 'Original', 'slug' => uniqid('category-'), 'is_active' => true]);
        return Product::create(['shop_id' => $shop->id, 'category_id' => $category->id, 'name' => 'Product '.$type, 'slug' => uniqid('product-'), 'price' => 35, 'discount_price' => 30, 'quantity' => 12, 'is_active' => true]);
    }

    public function test_every_catalog_uses_cards_and_admin_shop_filter_is_honored(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        foreach (array_keys(config('catalog_types')) as $type) {
            $product = $this->product($type);
            $response = $this->actingAs($admin)->get(route('products', ['shop_id' => $product->shop_id]))
                ->assertOk()->assertSee('id="productCards"', false)->assertDontSee('id="prodTable"', false)
                ->assertSee('data-move-category', false)->assertSee(route('products.move-category', $product), false);
            $this->assertCount(1, $response->viewData('products'));
            $this->assertSame($product->id, $response->viewData('products')->first()->id);
        }
    }

    public function test_move_changes_only_category_and_quick_settings_work_for_non_restaurants(): void
    {
        foreach (['sweets', 'cosmetics', 'general', 'restaurant'] as $type) {
            $product = $this->product($type);
            $target = Category::create(['shop_id' => $product->shop_id, 'name' => 'New section', 'slug' => uniqid('new-'), 'is_active' => true]);
            $this->actingAs($product->shop->user)->patchJson(route('products.move-category', $product), [
                'category_id' => $target->id, 'price' => 999, 'quantity' => 0, 'is_active' => false,
            ])->assertOk()->assertJsonPath('category_id', $target->id)->assertJsonPath('category_name', 'New section');
            $product->refresh();
            $this->assertSame($target->id, $product->category_id);
            $this->assertEquals(35, $product->price);
            $this->assertEquals(30, $product->discount_price);
            $this->assertEquals(12, $product->quantity);
            $this->assertTrue($product->is_active);
            $this->patchJson(route('products.quick-settings', $product), ['price' => 40, 'is_active' => false])->assertOk();
            $this->assertEquals(40, $product->fresh()->price);
            $this->assertFalse($product->fresh()->is_active);
        }
    }

    public function test_cross_shop_and_invalid_moves_are_rejected_even_for_same_owner(): void
    {
        $product = $this->product();
        $other = $this->product('sweets', $product->shop->user);
        $this->actingAs($product->shop->user);
        foreach ([$other->category_id, 999999, null, 'invalid'] as $target) {
            $this->patchJson(route('products.move-category', $product), ['category_id' => $target])
                ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        }
        $this->assertSame($product->category_id, $product->fresh()->category_id);
        $this->get(route('products', ['shop_id' => $product->shop_id]))->assertOk()
            ->assertDontSee('value="'.$other->category_id.'"', false);
        $outsider = $this->product();
        $this->actingAs($outsider->shop->user)->patchJson(route('products.move-category', $product), ['category_id' => $product->category_id])->assertForbidden();
    }

    public function test_view_only_employee_cannot_move_or_quick_edit(): void
    {
        $product = $this->product();
        $employee = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $employee->employeePermissions()->create(['permission' => 'products.view']);
        $this->actingAs($employee)->get(route('products'))->assertOk()->assertDontSee('data-move-category aria-label', false);
        $this->patchJson(route('products.move-category', $product), ['category_id' => $product->category_id])->assertForbidden();
        $this->patchJson(route('products.quick-settings', $product), ['price' => 40, 'is_active' => true])->assertForbidden();
        $employee->employeePermissions()->create(['permission' => 'products.edit']);
        $employee->unsetRelation('employeePermissions');
        $this->patchJson(route('products.move-category', $product), ['category_id' => $product->category_id])->assertOk();
    }
}
