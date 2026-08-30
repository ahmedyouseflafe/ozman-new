<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTimeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_edit_accepts_existing_database_times_with_seconds(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Time Shop',
            'slug' => 'time-shop',
            'catalog_type' => 'general',
            'open_time' => '09:00:00',
            'close_time' => '22:30:00',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('shops.update', $shop), [
                'name' => 'Updated Time Shop',
                'catalog_type' => 'general',
                'open_time' => '09:00:00',
                'close_time' => '22:30:00',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('shops'));

        $shop->refresh();
        $this->assertSame('Updated Time Shop', $shop->name);
        $this->assertSame('09:00', substr($shop->open_time, 0, 5));
        $this->assertSame('22:30', substr($shop->close_time, 0, 5));
    }
}
