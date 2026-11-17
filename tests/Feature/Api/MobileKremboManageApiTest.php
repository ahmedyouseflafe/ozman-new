<?php

namespace Tests\Feature\Api;

use App\Models\{ApiToken, Distributor, DistributorMarketer, EmployeePermission, Shop, ShopOutlet, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Krembo's points of sale (the company) and a distributor's marketers, managed in the app. */
class MobileKremboManageApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(User $user): array
    {
        [, $plain] = ApiToken::issue($user);

        return ['Authorization' => 'Bearer '.$plain, 'Accept' => 'application/json'];
    }

    public function test_the_company_manages_its_points_of_sale(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $company = Shop::create(['user_id' => $owner->id, 'name' => 'Krembo', 'slug' => 'krembo-'.uniqid(), 'catalog_type' => 'krembo', 'is_active' => true]);
        $h = $this->auth($owner);

        $this->postJson("/api/v1/merchant/shops/{$company->id}/outlets", ['name' => 'سوبرماركت النور'], $h)->assertStatus(422)->assertJsonValidationErrors('region');
        $outlet = $this->postJson("/api/v1/merchant/shops/{$company->id}/outlets", ['name' => 'سوبرماركت النور', 'region' => 'رام الله', 'phone' => '0599111222',
            'latitude' => 31.9, 'longitude' => 35.2, 'is_active' => true], $h)->assertCreated()->assertJsonPath('outlet.is_active', true)->json('outlet');
        $this->postJson("/api/v1/merchant/outlets/{$outlet['id']}", ['name' => 'سوبرماركت النور الجديد', 'region' => 'البيرة', 'is_active' => false], $h)
            ->assertOk()->assertJsonPath('outlet.region', 'البيرة')->assertJsonPath('outlet.is_active', false);
        $this->getJson("/api/v1/merchant/shops/{$company->id}/outlets", $h)->assertOk()->assertJsonCount(1, 'data');

        $stranger = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        Shop::create(['user_id' => $stranger->id, 'name' => 'Other', 'slug' => 'o-'.uniqid(), 'catalog_type' => 'krembo', 'is_active' => true]);
        $this->deleteJson("/api/v1/merchant/outlets/{$outlet['id']}", [], $this->auth($stranger))->assertForbidden();

        $this->deleteJson("/api/v1/merchant/outlets/{$outlet['id']}", [], $h)->assertOk();
        $this->assertSame(0, ShopOutlet::count());

        $sweets = Shop::create(['user_id' => $owner->id, 'name' => 'Sweets', 'slug' => 's-'.uniqid(), 'catalog_type' => 'sweets', 'is_active' => true]);
        $this->getJson("/api/v1/merchant/shops/{$sweets->id}/outlets", $h)->assertNotFound();
    }

    public function test_a_distributor_adds_edits_and_removes_its_marketers(): void
    {
        $owner = User::factory()->create(['role' => 'shop_owner', 'is_active' => true]);
        $company = Shop::create(['user_id' => $owner->id, 'name' => 'Krembo', 'slug' => 'krembo-'.uniqid(), 'catalog_type' => 'krembo', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'distributor', 'is_active' => true]);
        EmployeePermission::create(['user_id' => $user->id, 'permission' => 'distributors.marketers']);
        $distributor = Distributor::create(['shop_id' => $company->id, 'user_id' => $user->id, 'name' => 'موزع الشمال', 'is_active' => true]);
        $h = $this->auth($user);

        $marketer = $this->postJson("/api/v1/me/network/distributors/{$distributor->id}/marketers", ['name' => 'سامي', 'phone' => '0599333444', 'commission_rate' => 5], $h)
            ->assertCreated()->assertJsonPath('marketer.commission_rate', 5)->assertJsonPath('marketer.is_active', true)->json('marketer');
        $this->assertNotEmpty($marketer['code']);
        $this->getJson('/api/v1/me/network', $h)->assertJsonPath('distributors.0.marketers.0.name', 'سامي');

        $this->postJson("/api/v1/me/network/marketers/{$marketer['id']}", ['is_active' => false], $h)->assertOk()
            ->assertJsonPath('marketer.is_active', false)->assertJsonPath('marketer.name', 'سامي')->assertJsonPath('marketer.commission_rate', 5);
        $this->postJson("/api/v1/me/network/marketers/{$marketer['id']}", ['name' => 'سامي خليل', 'commission_rate' => 7.5, 'is_active' => true], $h)->assertOk()
            ->assertJsonPath('marketer.commission_rate', 7.5);

        // Another distributor can't touch them, and a distributor without the permission can't add any.
        $other = User::factory()->create(['role' => 'distributor', 'is_active' => true]);
        EmployeePermission::create(['user_id' => $other->id, 'permission' => 'distributors.marketers']);
        Distributor::create(['shop_id' => $company->id, 'user_id' => $other->id, 'name' => 'موزع الجنوب', 'is_active' => true]);
        $this->deleteJson("/api/v1/me/network/marketers/{$marketer['id']}", [], $this->auth($other))->assertForbidden();
        $this->postJson("/api/v1/me/network/distributors/{$distributor->id}/marketers", ['name' => 'x'], $this->auth($other))->assertForbidden();
        $bare = User::factory()->create(['role' => 'distributor', 'is_active' => true]);
        $mine = Distributor::create(['shop_id' => $company->id, 'user_id' => $bare->id, 'name' => 'موزع', 'is_active' => true]);
        $this->postJson("/api/v1/me/network/distributors/{$mine->id}/marketers", ['name' => 'x'], $this->auth($bare))->assertForbidden();

        $this->deleteJson("/api/v1/me/network/marketers/{$marketer['id']}", [], $h)->assertOk();
        $this->assertSame(0, DistributorMarketer::count());
    }
}
