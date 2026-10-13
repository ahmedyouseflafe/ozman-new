<?php
namespace Tests\Feature;

use App\Models\{Shop,User,Product,RewardWheel,FrontOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CosmeticsCustomerRewardsTest extends TestCase
{
    use RefreshDatabase;
    private function fixture(): array
    {
        $shop=Shop::create(['user_id'=>User::factory()->create(['role'=>'shop_owner','is_active'=>true])->id,'name'=>'Elegance Perfumes','slug'=>'elegance-perfumes','catalog_type'=>'cosmetics','whatsapp'=>'972501234567','is_active'=>true]);
        $category=\App\Models\Category::create(['shop_id'=>$shop->id,'name'=>'Perfumes','slug'=>'perfumes','is_active'=>true]);
        $product=Product::create(['category_id'=>$category->id,'shop_id'=>$shop->id,'name'=>'Perfume','slug'=>'perfume','price'=>100,'quantity'=>10,'is_active'=>true]);
        foreach(['signup','purchase'] as $type){
            $wheel=RewardWheel::create(['shop_id'=>$shop->id,'key'=>$type==='signup'?'shop_signup_'.$shop->id:'purchase_test','title'=>$type,'wheel_type'=>$type==='signup'?'customer_signup':'purchase_amount','min_order_total'=>50,'is_active'=>true,'win_quota_total'=>2]);
            foreach([0,1] as $index)$wheel->segments()->create(['label'=>$type.' prize','discount_type'=>$type==='signup'?'percent':'gift','discount_value'=>$type==='signup'?10:0,'color'=>'#cc7788','win_quota'=>1,'sort_order'=>$index,'is_active'=>true]);
        }
        return [$shop,$product];
    }
    public function test_registration_signup_discount_checkout_and_purchase_prize(): void
    {
        [$shop,$product]=$this->fixture();$token=str_repeat('a',64);
        $this->get($shop->publicUrl())->assertOk()->assertSee('bankaiWelcome')->assertSee('purchaseWheelTrigger');
        $this->postJson(route('restaurant.customer-registration.store',$shop),['registration_token'=>$token,'name'=>'Customer','whatsapp'=>'0591234567','address'=>'City','location_deferred'=>true])->assertOk();
        $this->postJson(route('restaurant.signup-reward',$shop),['registration_token'=>$token,'spin'=>true])->assertOk();
        $payload=['registration_token'=>$token,'items'=>[['product_id'=>$product->id,'qty'=>1,'price'=>1]],'total'=>1];
        $response=$this->postJson(route('cosmetics.orders.store',$shop),$payload)->assertOk()->assertJsonPath('total',90);
        $order=FrontOrder::findOrFail($response->json('order_id'));
        $this->assertSame('100.00',$order->subtotal);
        $url=$response->json('purchase_wheel.spin_url');
        $reward=$this->postJson($url)->assertOk()->json('reward');
        $this->postJson($url)->assertOk()->assertJson(['reward'=>$reward]);
        $this->assertSame('signup prize',$order->fresh()->reward_label);
        $this->postJson(route('cosmetics.orders.store',$shop),$payload)->assertOk()->assertJsonPath('total',100);
        $this->actingAs($shop->user)->get(route('reward-wheels.shop-signup.edit',$shop))->assertOk();
    }
    public function test_registration_required_and_foreign_products_rejected(): void
    {
        [$shop,$product]=$this->fixture();
        $this->postJson(route('cosmetics.orders.store',$shop),['items'=>[['product_id'=>$product->id,'qty'=>1]]])->assertUnprocessable();
        $token=str_repeat('b',64);
        $this->postJson(route('restaurant.customer-registration.store',$shop),['registration_token'=>$token,'name'=>'Customer','whatsapp'=>'0591234567','address'=>'City','location_deferred'=>true])->assertOk();
        $product->update(['is_active'=>false]);
        $this->postJson(route('cosmetics.orders.store',$shop),['registration_token'=>$token,'items'=>[['product_id'=>$product->id,'qty'=>1]]])->assertUnprocessable();
        $this->assertDatabaseCount('front_orders',0);
        $other=Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'Other','slug'=>'other','catalog_type'=>'cosmetics','is_active'=>true]);
        $this->postJson(route('cosmetics.orders.store',$other),['registration_token'=>$token,'items'=>[['product_id'=>$product->id,'qty'=>1]]])->assertNotFound();
    }
}
