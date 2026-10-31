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

    public function test_hero_video_prefers_tiktok_then_screen_then_featured_product(): void
    {
        $shop=$this->company();
        $category=Category::create(['shop_id'=>$shop->id,'name'=>'عبوات','slug'=>'packs','is_active'=>true]);
        Product::create(['shop_id'=>$shop->id,'category_id'=>$category->id,'name'=>'مميز','slug'=>'featured','price'=>10,'is_active'=>true,'is_featured'=>true,'video'=>'storage/products/videos/featured.mp4']);
        $this->get($shop->publicUrl())->assertOk()->assertSee('products/videos/featured.mp4')->assertDontSee('قريبًا… حكايتنا بالصورة');

        $shop->screens()->create(['type'=>'video','media'=>'videos/screen.mp4','is_active'=>true,'needs_shop_assignment'=>false,'title'=>'Screen']);
        $this->get($shop->publicUrl())->assertSee('videos/screen.mp4')->assertDontSee('products/videos/featured.mp4');

        $shop->update(['hero_video_url'=>'https://www.tiktok.com/@krembo/video/7301234567890123456']);
        $this->get($shop->publicUrl())->assertSee('https://www.tiktok.com/embed/v2/7301234567890123456',false)->assertDontSee('videos/screen.mp4');
    }

    public function test_tiktok_video_id_accepts_only_full_video_links(): void
    {
        $this->assertSame('7301234567890123456',Shop::tiktokVideoId('https://www.tiktok.com/@user.name/video/7301234567890123456?lang=ar'));
        $this->assertSame('7301234567890123456',Shop::tiktokVideoId('https://www.tiktok.com/embed/v2/7301234567890123456'));
        $this->assertNull(Shop::tiktokVideoId('https://vm.tiktok.com/ZMabc/'));
        $this->assertNull(Shop::tiktokVideoId('https://evil.example/tiktok.com/@u/video/7301234567890123456'));
        $this->assertNull(Shop::tiktokVideoId(null));
    }

    public function test_manual_outlets_are_grouped_by_region_and_empty_notice_shows_once(): void
    {
        $shop=$this->company();
        $response=$this->get($shop->publicUrl())->assertOk();
        $this->assertSame(1,substr_count($response->getContent(),'نقاط البيع ستُضاف قريبًا'));

        $shop->outlets()->create(['name'=>'سوبرماركت النور','region'=>'جنين','latitude'=>32.46,'longitude'=>35.3,'phone'=>'059-111 2222','is_active'=>true]);
        $shop->outlets()->create(['name'=>'بقالة الحي','region'=>'نابلس','map_url'=>'https://maps.app.goo.gl/abc','is_active'=>true]);
        $shop->outlets()->create(['name'=>'Hidden outlet','region'=>'جنين','is_active'=>false]);
        $this->get($shop->publicUrl())->assertOk()
            ->assertSeeInOrder(['جنين','سوبرماركت النور','نابلس','بقالة الحي'])
            ->assertSee('https://maps.app.goo.gl/abc',false)->assertSee('tel:0591112222',false)
            ->assertSee('افتح على الخريطة')->assertDontSee('Hidden outlet')->assertDontSee('نقاط البيع ستُضاف قريبًا');
    }

    public function test_owner_manages_only_their_own_outlets(): void
    {
        $shop=$this->company();$other=$this->company('other-company');
        $foreign=$other->outlets()->create(['name'=>'Foreign','region'=>'رام الله','is_active'=>true]);
        $owner=$shop->user;

        $this->actingAs($owner)->post(route('shop-outlets.store',$shop),['name'=>'محل جديد','region'=>'طولكرم','is_active'=>'1'])
            ->assertRedirect(route('shop-outlets.index',$shop));
        $this->assertDatabaseHas('shop_outlets',['shop_id'=>$shop->id,'name'=>'محل جديد','region'=>'طولكرم','is_active'=>true]);
        $this->actingAs($owner)->post(route('shop-outlets.store',$shop),['name'=>'بدون منطقة','latitude'=>'32'])
            ->assertSessionHasErrors(['region','longitude']);

        $this->actingAs($owner)->get(route('shop-outlets.index',$other))->assertForbidden();
        $this->actingAs($owner)->delete(route('shop-outlets.destroy',[$shop,$foreign]))->assertNotFound();
        $this->assertDatabaseHas('shop_outlets',['id'=>$foreign->id]);
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
