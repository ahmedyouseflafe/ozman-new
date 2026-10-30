<?php
namespace Tests\Feature;

use App\Models\{Shop, User, Category, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentalClinicTest extends TestCase
{
    use RefreshDatabase;

    private function clinic(): Shop
    {
        return Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'عيادة الابتسامة','slug'=>'smile-clinic',
            'catalog_type'=>'dental','is_active'=>true,'phone'=>'0501234567','whatsapp'=>'972501234567','address'=>'شارع العيادة 12']);
    }

    public function test_clinic_type_links_and_services_are_connected(): void
    {
        $shop=$this->clinic();
        $this->assertArrayHasKey('dental',config('catalog_types'));
        $this->assertSame('dental.clinic',$shop->publicRouteName());
        $this->get(route('front.shop.slug',$shop))->assertRedirect(route('dental.clinic',$shop));
        $category=Category::create(['shop_id'=>$shop->id,'name'=>'العناية','slug'=>'care','is_active'=>true]);
        foreach ([true,false] as $active) Product::create(['shop_id'=>$shop->id,'category_id'=>$category->id,'name'=>$active?'فحص الأسنان':'خدمة مخفية','slug'=>$active?'exam':'hidden','price'=>100,'is_active'=>$active]);
        $response=$this->get($shop->publicUrl())->assertOk()->assertSee('عيادة الابتسامة')->assertSee('فحص الأسنان')->assertDontSee('خدمة مخفية')->assertSee('https://wa.me/972501234567',false);
        if(getenv('OZMAN_DENTAL_PREVIEW')==='1') file_put_contents(storage_path('app/dental-preview.html'),$response->getContent());
        $shop->update(['is_active'=>false]);$this->get($shop->publicUrl())->assertNotFound();
    }

    public function test_empty_clinic_has_honest_fallback_and_other_shop_types_are_rejected(): void
    {
        $shop=$this->clinic();$shop->update(['phone'=>null,'whatsapp'=>null,'address'=>null]);
        $this->get($shop->publicUrl())->assertOk()->assertSee('ستُضاف بيانات التواصل')->assertDontSee('wa.me',false)->assertDontSee('tel:',false);
        $shop->update(['catalog_type'=>'general']);$this->get(route('dental.clinic',$shop))->assertNotFound();
    }
}
