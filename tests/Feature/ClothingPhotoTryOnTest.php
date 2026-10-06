<?php
namespace Tests\Feature;

use App\Models\{Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClothingPhotoTryOnTest extends TestCase
{
    use RefreshDatabase;
    private function shop(): Shop
    {
        return Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'Demo','slug'=>'photo-demo','catalog_type'=>'clothing','is_active'=>true]);
    }
    public function test_disabled_service_does_not_send_images(): void
    {
        Http::fake();$shop=$this->shop();config(['services.fashn.key'=>null]);
        $this->postJson(route('clothing.photo-tryon',$shop))->assertStatus(503);Http::assertNothingSent();
        $this->get(route('clothing.store',$shop))->assertOk()->assertSee('data-enabled="0"',false)->assertDontSee('id="photoTryConsent"',false);
    }
    public function test_validated_consent_and_photo_are_sent_and_result_is_private_to_session(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>'test-secret','services.fashn.shops'=>[$shop->slug]]);
        Http::fake(['*/run'=>Http::response(['id'=>'provider-demo']),'*/status/*'=>Http::response(['status'=>'completed','output'=>['data:image/png;base64,YWJj']])]);
        $url=route('clothing.photo-tryon',$shop);
        $this->postJson($url,['consent'=>true])->assertUnprocessable();
        $this->postJson($url,['photo'=>UploadedFile::fake()->image('photo.jpg',600,800)])->assertUnprocessable();Http::assertNothingSent();
        $result=$this->postJson($url,['photo'=>UploadedFile::fake()->image('photo.jpg',600,800),'consent'=>true])->assertStatus(202);
        Http::assertSent(fn($r)=>$r['model_name']==='tryon-v1.6' && str_starts_with($r['inputs']['model_image'],'data:image/jpeg;base64,') && $r['inputs']['return_base64']===true
            && $r['inputs']['garment_photo_type']==='flat-lay' && $r['inputs']['segmentation_free']===false && $r['inputs']['mode']==='quality');
        $status=route('clothing.photo-tryon.status',[$shop,$result->json('job')]);
        $this->getJson($status)->assertOk()->assertJsonPath('image','data:image/png;base64,YWJj')->assertHeader('Cache-Control','no-store, private');
        $this->app['session']->invalidate();$this->getJson($status)->assertNotFound();
    }
    public function test_provider_errors_are_safe_and_shop_must_be_enabled(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>'test-secret','services.fashn.shops'=>[]]);Http::fake();
        $url=route('clothing.photo-tryon',$shop);$this->postJson($url)->assertStatus(503);Http::assertNothingSent();
        config(['services.fashn.shops'=>[$shop->slug]]);Http::fake(['*'=>Http::response(['error'=>'private provider details'],500)]);
        $this->postJson($url,['photo'=>UploadedFile::fake()->image('photo.jpg',600,800),'consent'=>true])->assertStatus(502)->assertDontSee('private provider details');
    }

    public function test_polling_does_not_consume_generation_limit_and_seventh_generation_is_limited(): void
    {
        $shop=$this->shop();config(['services.fashn.key'=>'test-secret','services.fashn.shops'=>[$shop->slug]]);
        Http::fake(['*/run'=>Http::response(['id'=>'provider-demo']),'*/status/*'=>Http::response(['status'=>'processing'])]);
        $url=route('clothing.photo-tryon',$shop);
        for($attempt=0;$attempt<6;$attempt++) {
            $result=$this->postJson($url,['photo'=>UploadedFile::fake()->image('photo.jpg',600,800),'consent'=>true])->assertStatus(202);
            $status=route('clothing.photo-tryon.status',[$shop,$result->json('job')]);
            for($poll=0;$poll<8;$poll++) $this->getJson($status)->assertOk()->assertJsonPath('status','processing');
        }
        $this->postJson($url,['photo'=>UploadedFile::fake()->image('photo.jpg',600,800),'consent'=>true])->assertStatus(429);
        Http::assertSentCount(54); // Six generations plus 48 status requests; blocked request never reaches FASHN.
    }
}
