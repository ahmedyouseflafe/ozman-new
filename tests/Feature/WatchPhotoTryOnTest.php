<?php

namespace Tests\Feature;

use App\Models\{Shop, User};
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

    public function test_demo_is_visible_without_enabling_paid_generation(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>null]);Http::fake();
        $this->get(route('cosmetics.store',$shop))->assertOk()->assertSee('جرّب الساعة على إيدك')->assertSee('data-kind="watch"',false)->assertSee('4 أرصدة')->assertSee('data-enabled="0"',false);
        $this->postJson(route('watch.photo-tryon',$shop))->assertStatus(503);
        Http::assertNothingSent();
        $this->get(route('virtual-tryon.demo-watch'))->assertOk()->assertHeader('content-type','image/png');
    }

    public function test_watch_uses_demo_reference_max_and_private_result(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>'fake','services.fashn.shops'=>[$shop->slug]]);
        Http::fake(['*/run'=>Http::response(['id'=>'watch-job']),'*/status/*'=>Http::response(['status'=>'completed','output'=>['data:image/png;base64,YWJj']])]);
        $url=route('watch.photo-tryon',$shop);
        $this->postJson($url,['photo'=>UploadedFile::fake()->image('wrist.jpg',800,1000)])->assertUnprocessable();
        Http::assertNothingSent();
        $job=$this->postJson($url,['photo'=>UploadedFile::fake()->image('wrist.jpg',800,1000),'consent'=>true,'quality'=>'standard'])->assertStatus(202)->json('job');
        $reference='data:image/png;base64,'.base64_encode(file_get_contents(base_path('public/images/virtual-tryon/demo-blue-watch.png')));
        Http::assertSent(fn($r)=>$r['model_name']==='tryon-max' && $r['inputs']['product_image']===$reference && $r['inputs']['resolution']==='2k' && $r['inputs']['generation_mode']==='quality' && $r['inputs']['num_images']===1 && !isset($r['inputs']['category']) && str_contains($r['inputs']['prompt'],'bare wrist'));
        $status=route('watch.photo-tryon.status',[$shop,$job]);
        $this->getJson($status)->assertOk()->assertJsonPath('status','completed');
        $this->app['session']->invalidate();$this->getJson($status)->assertNotFound();
    }

    public function test_clothing_shop_cannot_use_watch_endpoint(): void
    {
        $shop=$this->shop();$shop->update(['catalog_type'=>'clothing']);Http::fake();
        $this->postJson(route('watch.photo-tryon',$shop))->assertNotFound();Http::assertNothingSent();
    }
}
