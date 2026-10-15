<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweetsCategoryGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cups_appear_before_plates_with_a_matching_active_pane(): void
    {
        $shop = Shop::create([
            'user_id' => User::factory()->create()->id, 'name' => 'Sweets',
            'slug' => 'sweets-groups', 'catalog_type' => 'sweets', 'is_active' => true,
        ]);
        foreach (['الكيك مستطيل', 'المشروبات', 'آيس كوفي', 'الكعك والكروسان', 'Cupcakes', 'حلى الكاسات'] as $index => $name) {
            Category::create(['shop_id' => $shop->id, 'name' => $name, 'slug' => 'sweets-category-'.$index, 'is_active' => true]);
        }

        $response = $this->get(route('sweets.store', $shop))->assertOk();
        $response->assertViewHas('cupCategories', fn ($items) => $items->pluck('name')->sort()->values()->all() === collect(['المشروبات', 'آيس كوفي', 'حلى الكاسات'])->sort()->values()->all());
        $response->assertViewHas('plateCategories', fn ($items) => $items->count() === 3);
        $response->assertSeeInOrder(['data-category-group="cups"', 'data-category-group="plates"'], false);
        $first = $response->viewData('categories')->first();
        $response->assertSee('class="category active" type="button" data-tab="'.$first->id.'"', false);
        $response->assertSee('data-pane="'.$first->id.'" ><header', false);
    }
}
