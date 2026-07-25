<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorMarketer;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorStorefrontListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_and_marketer_links_show_all_directly_assigned_shops(): void
    {
        $owner = User::create([
            'name' => 'Distributor Owner',
            'email' => 'listing-distributor@example.com',
            'password' => 'secret123',
            'role' => 'distributor',
            'is_active' => true,
        ]);
        $baseShop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Base Store',
            'slug' => 'base-store',
            'is_active' => true,
        ]);
        $distributor = Distributor::create([
            'shop_id' => $baseShop->id,
            'user_id' => $owner->id,
            'name' => 'Main Distributor',
            'is_active' => true,
        ]);
        $shopA = $this->assignedShop($distributor, 'Assigned A', 'assigned-a');
        $shopB = $this->assignedShop($distributor, 'Assigned B', 'assigned-b');
        $marketer = DistributorMarketer::create([
            'distributor_id' => $distributor->id,
            'name' => 'Link Marketer',
            'tracking_code' => 'link-marketer',
            'is_active' => true,
        ]);

        foreach ([route('front.distributor', $distributor), route('front.marketer', $marketer)] as $url) {
            $response = $this->get($url)->assertOk();
            $centerIds = collect($response->viewData('frontData')['centersData'])->pluck('id');

            $this->assertTrue($centerIds->contains($baseShop->id));
            $this->assertTrue($centerIds->contains($shopA->id));
            $this->assertTrue($centerIds->contains($shopB->id));
        }

        $marketerResponse = $this->get(route('front.marketer', $marketer));
        $this->assertSame('marketer', $marketerResponse->viewData('marketingContext')['source']);
        $this->assertSame($marketer->id, $marketerResponse->viewData('marketingContext')['marketer_id']);
        $this->assertSame($distributor->id, $marketerResponse->viewData('marketingContext')['distributor_id']);
    }

    private function assignedShop(Distributor $distributor, string $name, string $slug): Shop
    {
        $owner = User::create([
            'name' => $name,
            'email' => "{$slug}@example.com",
            'password' => 'secret123',
            'role' => 'shop_owner',
            'is_active' => true,
        ]);

        return Shop::create([
            'user_id' => $owner->id,
            'distributor_id' => $distributor->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }
}
