<?php

namespace Tests\Feature;

use App\Models\{Shop, User, Category, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WatchPhotoTryOnTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): Shop
    {
        return Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'Elegance','slug'=>'elegance-perfumes','catalog_type'=>'cosmetics','is_active'=>true]);
    }

    private function watch(Shop $shop, string $image = 'storage/watches/real-watch.jpg'): Product
    {
        $category=Category::firstOrCreate(['shop_id'=>$shop->id,'slug'=>'watches-'.$shop->id],['name'=>'ساعات رجالية','is_active'=>true]);
        return Product::create(['shop_id'=>$shop->id,'category_id'=>$category->id,'slug'=>uniqid('watch-'),'name'=>'Gold and silver watch','price'=>250,'quantity'=>5,'is_active'=>true,'main_image'=>$image]);
    }

    public function test_empty_watch_catalog_has_no_demo_and_real_products_appear_automatically(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>null]);Http::fake();
        $this->get(route('cosmetics.store',$shop))->assertOk()->assertDontSee('id="watchCatalogSelect"',false)->assertDontSee('virtual-tryon/demo-watch');
        $watch=$this->watch($shop);
        $response=$this->get(route('cosmetics.store',$shop))->assertOk()->assertSee('جرّب الساعة على إيدك')->assertSee('data-kind="watch"',false)->assertSee('data-enabled="0"',false)
            ->assertSee('data-product-id="'.$watch->id.'"',false)->assertDontSee('virtual-tryon/demo-watch')->assertDontSee('4 أرصدة');
        $this->assertSame([$watch->id],$response->viewData('watchProducts')->pluck('id')->all());
        $this->postJson(route('watch.photo-tryon',$shop))->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_watch_uses_selected_product_reference_max_and_private_result(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>'fake','services.fashn.shops'=>[$shop->slug]]);
        $watch=$this->watch($shop);
        Http::fake(['*/run'=>Http::response(['id'=>'watch-job']),'*/status/*'=>Http::response(['status'=>'completed','output'=>['data:image/png;base64,YWJj']])]);
        $url=route('watch.photo-tryon',$shop);
        $this->postJson($url,['photo'=>UploadedFile::fake()->image('wrist.jpg',800,1000),'product_id'=>$watch->id])->assertUnprocessable();
        Http::assertNothingSent();
        $job=$this->postJson($url,['photo'=>UploadedFile::fake()->image('wrist.jpg',800,1000),'consent'=>true,'quality'=>'standard','product_id'=>$watch->id])->assertStatus(202)->json('job');
        Http::assertSent(fn($r)=>$r['model_name']==='tryon-max' && $r['inputs']['product_image']===asset($watch->main_image) && $r['inputs']['resolution']==='2k' && $r['inputs']['generation_mode']==='quality' && $r['inputs']['num_images']===1 && !isset($r['inputs']['category']) && str_contains($r['inputs']['prompt'],'bare wrist') && str_contains($r['inputs']['prompt'],'display cushion') && !str_contains($r['inputs']['prompt'],'blue dial'));
        $second=$this->watch($shop,'https://example.test/another-watch.jpg');
        $this->postJson($url,['photo'=>UploadedFile::fake()->image('wrist.jpg',800,1000),'consent'=>true,'product_id'=>$second->id])->assertStatus(202);
        Http::assertSent(fn($r)=>$r['inputs']['product_image']===$second->main_image);
        $status=route('watch.photo-tryon.status',[$shop,$job]);
        $this->getJson($status)->assertOk()->assertJsonPath('status','completed');
        $this->app['session']->invalidate();$this->getJson($status)->assertNotFound();
    }

    public function test_invalid_products_are_rejected_before_any_provider_request(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>'fake','services.fashn.shops'=>[$shop->slug]]);Http::fake();
        $other=Shop::create(['user_id'=>$shop->user_id,'name'=>'Other','slug'=>'other','catalog_type'=>'cosmetics','is_active'=>true]);
        $foreign=$this->watch($other);
        $inactive=$this->watch($shop);$inactive->update(['is_active'=>false]);
        $noImage=$this->watch($shop,'');
        $perfume=$this->watch($shop);$perfume->update(['category_id'=>Category::create(['shop_id'=>$shop->id,'name'=>'عطور','slug'=>'perfumes','is_active'=>true])->id]);
        $hidden=$this->watch($shop);
        $hidden->update(['category_id'=>Category::create(['shop_id'=>$shop->id,'name'=>'ساعات نسائية','slug'=>'hidden-watches','is_active'=>false])->id]);
        $payload=fn($id)=>['photo'=>UploadedFile::fake()->image('wrist.jpg',600,800),'consent'=>true,'product_id'=>$id];
        foreach ([[$foreign->id,404],[$inactive->id,404],[$noImage->id,422],[$perfume->id,422],[$hidden->id,422],[null,422]] as [$id,$status]) {
            $this->postJson(route('watch.photo-tryon',$shop),$payload($id))->assertStatus($status);
        }
        $this->assertCount(0,$this->get(route('cosmetics.store',$shop))->viewData('watchProducts'));
        Http::assertNothingSent();
    }

    public function test_watch_reference_cannot_be_replaced_by_a_customer_upload(): void
    {
        $shop=$this->shop();$watch=$this->watch($shop);config(['services.fashn.key'=>'fake','services.fashn.shops'=>[$shop->slug]]);Http::fake();
        $this->postJson(route('watch.photo-tryon',$shop),[
            'photo'=>UploadedFile::fake()->image('wrist.jpg',600,800),'consent'=>true,'product_id'=>$watch->id,
            'garment'=>UploadedFile::fake()->image('unrelated.jpg',600,800),
        ])->assertUnprocessable()->assertJsonValidationErrors('garment');
        Http::assertNothingSent();
    }

    public function test_clothing_shop_cannot_use_watch_endpoint(): void
    {
        $shop=$this->shop();$shop->update(['catalog_type'=>'clothing']);Http::fake();
        $this->postJson(route('watch.photo-tryon',$shop))->assertNotFound();Http::assertNothingSent();
    }
}
