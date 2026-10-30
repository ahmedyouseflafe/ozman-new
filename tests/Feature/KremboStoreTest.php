<?php

namespace Tests\Feature;

use App\Models\{Shop, User, Category, Product, Agent, Distributor};
use App\Services\ShopOwnerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KremboStoreTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $slug = 'krembo-company'): Shop
    {
        return Shop::create(['user_id'=>User::factory()->create(['role'=>'shop_owner'])->id,
            'name'=>'شركة الكريمبو','slug'=>$slug,'catalog_type'=>'krembo','is_active'=>true,'show_ozman_products'=>false]);
    }

    public function test_store_lists_only_its_active_products_and_network(): void
    {
        $shop=$this->company();$other=$this->company('other-company');
        foreach ([$shop,$other] as $company) {
            $category=Category::create(['shop_id'=>$company->id,'name'=>'عبوات الكريمبو','slug'=>'packs-'.$company->id,'is_active'=>true]);
            Product::create(['shop_id'=>$company->id,'category_id'=>$category->id,'name'=>$company->is($shop)?'كريمبو الشوكولاتة':'Foreign product','slug'=>'product-'.$company->id,'price'=>25,'is_active'=>true]);
            Agent::create(['shop_id'=>$company->id,'name'=>$company->is($shop)?'وكيل الشركة':'Foreign agent','is_active'=>true]);
            Distributor::create(['shop_id'=>$company->id,'name'=>$company->is($shop)?'موزع الشركة':'Foreign distributor','is_active'=>true]);
        }
        Agent::create(['shop_id'=>$shop->id,'name'=>'Hidden agent','is_active'=>false]);
        $shop->screens()->create(['type'=>'video','media'=>'videos/krembo.mp4','is_active'=>true,'needs_shop_assignment'=>false,'title'=>'Brand video']);
        $shop->social()->create(['instagram'=>'https://www.instagram.com/example/','facebook'=>'javascript:alert(1)']);
        Shop::create(['user_id'=>$shop->user_id,'name'=>'Local outlet','slug'=>'local-outlet','is_active'=>true,'distributor_id'=>$shop->distributors()->first()->id,'latitude'=>32,'longitude'=>35]);
        $response=$this->get($shop->publicUrl())->assertOk()->assertSee('كريمبو الشوكولاتة')->assertSee('وكيل الشركة')->assertSee('موزع الشركة')
            ->assertDontSee('Foreign product')->assertDontSee('Foreign agent')->assertDontSee('Foreign distributor')->assertDontSee('Hidden agent');
        $response->assertSee('خدود الجدع')->assertSee('videos/krembo.mp4')->assertSee('Local outlet')
            ->assertSee('query=32%2C35',false)->assertSee('https://www.instagram.com/example/',false)->assertDontSee('javascript:alert');
        $this->get(route('front.shop.slug',$shop))->assertRedirect($shop->publicUrl());
        if (getenv('OZMAN_KREMBO_PREVIEW')==='1') file_put_contents(storage_path('app/krembo-preview.html'),$response->getContent());
        $this->get(route('krembo.catalog',$shop))->assertOk()->assertViewHas('ozmanShop',fn($value)=>$value->is($shop))
            ->assertViewHas('agents',fn($value)=>$value->every(fn($agent)=>$agent->shop_id===$shop->id));
        $distributor=$shop->distributors()->first();
        $this->get(route('front.distributor',$distributor))->assertOk()
            ->assertViewHas('ozmanShop',fn($value)=>$value->is($shop))
            ->assertViewHas('marketingContext',fn($value)=>$value['distributor_id']===$distributor->id);
        $shop->update(['is_active'=>false]);
        $this->get(route('krembo.store',$shop))->assertNotFound();
        $this->get(route('krembo.catalog',$shop))->assertNotFound();
    }

    public function test_type_has_network_permissions_and_rejects_other_types(): void
    {
        $shop=$this->company();
        $owner=app(ShopOwnerAccountService::class)->resolve($shop);
        foreach (['agents.view','agents.create','distributors.view','distributors.create'] as $permission) {
            $this->assertTrue($owner->employeePermissions()->where('permission',$permission)->exists());
        }
        $this->assertArrayHasKey('krembo',config('catalog_types'));
        $shop->update(['catalog_type'=>'general']);
        $this->get(route('krembo.store',$shop))->assertNotFound();
        $this->get(route('krembo.catalog',$shop))->assertNotFound();
    }
}
