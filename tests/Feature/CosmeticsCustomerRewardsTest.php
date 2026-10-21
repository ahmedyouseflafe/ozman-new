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
        $this->get($shop->publicUrl())->assertOk()->assertDontSee('id="bankaiWelcome"', false)->assertSee('purchaseWheelTrigger');
        $this->postJson(route('restaurant.customer-registration.store',$shop),['registration_token'=>$token,'name'=>'Customer','whatsapp'=>'0591234567','address'=>'City','location_deferred'=>true])->assertOk();
        $this->postJson(route('restaurant.signup-reward',$shop),['registration_token'=>$token,'spin'=>true])->assertOk();
        $payload=['registration_token'=>$token,'customer_name'=>'Customer','customer_phone'=>'0591234567','customer_whatsapp'=>'0591234567','customer_address'=>'City','items'=>[['product_id'=>$product->id,'qty'=>1,'price'=>1]],'total'=>1];
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
    public function test_customer_details_required_and_unavailable_products_rejected(): void
    {
        [$shop,$product]=$this->fixture();
        $this->postJson(route('cosmetics.orders.store',$shop),['items'=>[['product_id'=>$product->id,'qty'=>1]]])->assertUnprocessable();
        $token=str_repeat('b',64);
        $this->postJson(route('restaurant.customer-registration.store',$shop),['registration_token'=>$token,'name'=>'Customer','whatsapp'=>'0591234567','address'=>'City','location_deferred'=>true])->assertOk();
        $product->update(['is_active'=>false]);
        $this->postJson(route('cosmetics.orders.store',$shop),['registration_token'=>$token,'customer_name'=>'Customer','customer_phone'=>'0591234567','customer_whatsapp'=>'0591234567','customer_address'=>'City','items'=>[['product_id'=>$product->id,'qty'=>1]]])->assertUnprocessable();
        $this->assertDatabaseCount('front_orders',0);
        $other=Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'Other','slug'=>'other','catalog_type'=>'cosmetics','is_active'=>true]);
        $this->postJson(route('cosmetics.orders.store',$other),['registration_token'=>$token,'items'=>[['product_id'=>$product->id,'qty'=>1]]])->assertNotFound();
    }

    private function details(Product $product): array
    {
        return ['customer_name'=>'Guest Customer','customer_phone'=>'0591234567','customer_whatsapp'=>'0597654321',
            'customer_address'=>'City, street 12','items'=>[['product_id'=>$product->id,'qty'=>1]]];
    }

    public function test_guest_spins_without_registration_and_places_order_without_location(): void
    {
        [$shop,$product]=$this->fixture();$token=str_repeat('c',64);
        $this->get($shop->publicUrl())->assertOk()->assertDontSee('id="bankaiWelcome"',false)
            ->assertDontSee('id="bankaiProfileEdit"',false)->assertSee('name="customer_address"',false);
        $url=route('restaurant.signup-reward',$shop);
        $this->postJson($url,['registration_token'=>$token])->assertOk()->assertJsonPath('reward.selected_index',null);
        $spin=$this->postJson($url,['registration_token'=>$token,'spin'=>true])->assertOk()->json('reward');
        $this->postJson($url,['registration_token'=>$token,'spin'=>true])->assertOk()->assertJson(['reward'=>$spin]);
        $this->assertDatabaseCount('visitor_registrations',0);
        $this->assertDatabaseCount('shop_signup_rewards',1);
        $response=$this->postJson(route('cosmetics.orders.store',$shop),['registration_token'=>$token,...$this->details($product),'total'=>1])->assertOk()->assertJsonPath('total',90);
        $order=FrontOrder::findOrFail($response->json('order_id'));
        $this->assertNull($order->latitude);$this->assertNull($order->longitude);$this->assertNull($order->map_link);
        $this->assertSame('0591234567',$order->customer_phone);
        $this->assertSame('0597654321',$order->customer_whatsapp);
        $this->assertSame('City, street 12',$order->customer_address);
        $message=rawurldecode($response->json('whatsapp_url'));
        foreach (['Guest Customer','0591234567','0597654321','City, street 12'] as $value) $this->assertStringContainsString($value,$message);
        $purchase=$this->postJson($response->json('purchase_wheel.spin_url'))->assertOk();
        $this->assertStringContainsString('0597654321',rawurldecode($purchase->json('whatsapp_url')));
        $this->postJson($url,['registration_token'=>$token])->assertOk()->assertJsonPath('reward',null);
        $this->postJson(route('cosmetics.orders.store',$shop),['registration_token'=>$token,...$this->details($product)])->assertOk()->assertJsonPath('total',100);
    }

    public function test_wheel_only_reset_backs_up_rewards_preserves_orders_and_issues_new_welcome_to_old_browser(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        [$shop,$product]=$this->fixture();$token=str_repeat('a',64);
        $url=route('restaurant.signup-reward',$shop);
        $this->postJson(route('restaurant.customer-registration.store',$shop),['registration_token'=>$token,'name'=>'Customer','whatsapp'=>'0591234567','address'=>'City','location_deferred'=>true])->assertOk();
        $first=$this->postJson($url,['registration_token'=>$token,'spin'=>true])->assertOk()->json('reward.welcome_id');
        $this->postJson(route('cosmetics.orders.store',$shop),$this->details($product))->assertOk();
        $registrations=\App\Models\VisitorRegistration::count();
        $other=Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'Other','slug'=>'other-reset','is_active'=>true]);
        $otherReward=\App\Models\ShopSignupReward::create(['shop_id'=>$other->id,'phone_hash'=>hash('sha256','other'),'title'=>'Other reward','segments'=>[]]);
        $this->artisan('shop:reset-customer-registrations',['slug'=>$shop->slug,'--wheel-only'=>true])->assertExitCode(0);
        $this->assertDatabaseHas('shop_signup_rewards',['id'=>$first]);
        $this->artisan('shop:reset-customer-registrations',['slug'=>$shop->slug,'--wheel-only'=>true,'--execute'=>true])->assertExitCode(0);
        $this->assertDatabaseCount('visitor_registrations',$registrations);
        $this->assertDatabaseCount('front_orders',1);
        $this->assertDatabaseHas('shop_signup_rewards',['id'=>$otherReward->id]);
        $this->assertDatabaseMissing('shop_signup_rewards',['id'=>$first]);
        $backups=\Illuminate\Support\Facades\Storage::disk('local')->files('registration-resets');
        $this->assertCount(1,$backups);
        $backup=json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get($backups[0]),true);
        $this->assertSame((int)$first,$backup['rewards'][0]['id']);
        $next=$this->postJson($url,['registration_token'=>$token])->assertOk()->assertJsonPath('reward.selected_index',null)->json('reward.welcome_id');
        $this->assertNotSame($first,$next);
    }

    public function test_checkout_accepts_optional_location_and_uses_server_coordinates_for_map_link(): void
    {
        [$shop,$product]=$this->fixture();
        $response=$this->postJson(route('cosmetics.orders.store',$shop),[
            ...$this->details($product),'latitude'=>31.5,'longitude'=>35.1,'map_link'=>'https://untrusted.invalid',
        ])->assertOk();
        $order=FrontOrder::findOrFail($response->json('order_id'));
        $this->assertSame('https://www.google.com/maps?q=31.5,35.1',$order->map_link);
        $this->assertStringContainsString($order->map_link,rawurldecode($response->json('whatsapp_url')));
    }

    public function test_missing_details_and_partial_or_invalid_location_cannot_create_orders(): void
    {
        [$shop,$product]=$this->fixture();
        foreach (['customer_name','customer_phone','customer_whatsapp','customer_address'] as $field) {
            $payload=$this->details($product);unset($payload[$field]);
            $this->postJson(route('cosmetics.orders.store',$shop),$payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        foreach ([['latitude'=>31],['longitude'=>35],['latitude'=>91,'longitude'=>35],['latitude'=>31,'longitude'=>181]] as $location) {
            $this->postJson(route('cosmetics.orders.store',$shop),[...$this->details($product),...$location])->assertUnprocessable();
        }
        $this->assertDatabaseCount('front_orders',0);
    }

    public function test_same_whatsapp_cannot_redeem_another_guest_reward_but_can_order_without_it(): void
    {
        [$shop,$product]=$this->fixture();
        foreach (['d','e'] as $letter) $this->postJson(route('restaurant.signup-reward',$shop),['registration_token'=>str_repeat($letter,64),'spin'=>true])->assertOk();
        $this->postJson(route('cosmetics.orders.store',$shop),['registration_token'=>str_repeat('d',64),...$this->details($product)])->assertOk()->assertJsonPath('total',90);
        $payload=['registration_token'=>str_repeat('e',64),...$this->details($product),'customer_whatsapp'=>'+970 597654321'];
        $this->postJson(route('cosmetics.orders.store',$shop),$payload)->assertUnprocessable()->assertJsonValidationErrors('welcome_reward');
        $this->assertDatabaseCount('front_orders',1);
        $this->postJson(route('cosmetics.orders.store',$shop),[...$payload,'skip_signup_reward'=>true])->assertOk()->assertJsonPath('total',100);
        $this->postJson(route('restaurant.signup-reward',$shop),['registration_token'=>str_repeat('e',64)])->assertOk()->assertJsonPath('reward',null);
    }

    public function test_invalid_products_do_not_consume_guest_reward_and_other_shops_keep_their_flow(): void
    {
        [$shop,$product]=$this->fixture();$token=str_repeat('f',64);
        $this->postJson(route('restaurant.signup-reward',$shop),['registration_token'=>$token,'spin'=>true])->assertOk();
        $other=Shop::create(['user_id'=>$shop->user_id,'name'=>'Other cosmetics','slug'=>'other-cosmetics','catalog_type'=>'cosmetics','is_active'=>true]);
        $foreign=$product->replicate();$foreign->shop_id=$other->id;$foreign->slug='foreign';$foreign->save();
        $payload=[...$this->details($product),'registration_token'=>$token,'items'=>[['product_id'=>$foreign->id,'qty'=>1]]];
        $this->postJson(route('cosmetics.orders.store',$shop),$payload)->assertUnprocessable();
        $this->assertDatabaseCount('front_orders',0);
        $this->assertNull(\App\Models\ShopSignupReward::first()->redeemed_at);
        $this->get($other->publicUrl())->assertOk()->assertDontSee('id="eleganceCheckoutForm"',false);
        $this->postJson(route('restaurant.signup-reward',$other),['registration_token'=>$token])->assertNotFound();
    }

    public function test_order_photo_share_uses_shop_product_snapshots_and_includes_return_link_after_wheel(): void
    {
        [$shop,$product]=$this->fixture();
        $product->update(['main_image'=>'products/perfume.jpg']);
        $second=$product->replicate();$second->name='Second perfume';$second->slug='second';$second->main_image=null;$second->save();
        $second->images()->create(['image'=>'storage/products/second.png']);
        $third=$product->replicate();$third->name='No photo';$third->slug='no-photo';$third->main_image=null;$third->save();
        $response=$this->postJson(route('cosmetics.orders.store',$shop),[
            ...$this->details($product),'items'=>[
                ['product_id'=>$product->id,'qty'=>2,'image_url'=>'https://untrusted.invalid/fake.jpg'],
                ['product_id'=>$second->id,'qty'=>1],['product_id'=>$third->id,'qty'=>1],
            ],
        ])->assertOk();
        $url=$response->json('order_share_url');
        $this->assertNotEmpty($url);
        $response->assertJsonPath('order_share.items.0.image_url',asset('storage/products/perfume.jpg'));
        $this->assertStringContainsString($shop->publicUrl(),$response->json('order_share.text'));
        $initialMessage = rawurldecode($response->json('whatsapp_url'));
        $this->assertStringStartsWith('https://wa.me/972501234567?text=', $response->json('whatsapp_url'));
        $this->assertStringContainsString("صورة المنتج: ".asset('storage/products/perfume.jpg'), $initialMessage);
        $this->assertStringContainsString("صورة المنتج: ".asset('storage/products/second.png'), $initialMessage);
        $this->assertSame(2, substr_count($initialMessage, 'صورة المنتج:'));
        $this->assertStringContainsString($shop->publicUrl(), $initialMessage);
        $this->get($shop->publicUrl())->assertOk()->assertDontSee('id="eleganceOrderShareDialog"',false)
            ->assertDontSee('id="eleganceSharePhotos"',false)->assertSee('إرسال الطلب على واتساب')
            ->assertDontSee('window.OzmanOrderPhotoShare',false)->assertSee('window.OzmanOrderWhatsapp',false);
        $product->update(['main_image'=>'products/replacement.jpg','name'=>'Changed after order']);
        $share=$this->getJson($url)->assertOk()->assertHeader('Cache-Control','no-store, private')
            ->assertJsonPath('shop_id',$shop->id)->assertJsonPath('shop_url',$shop->publicUrl())
            ->assertJsonPath('items.0.image_url',asset('storage/products/perfume.jpg'))
            ->assertJsonPath('items.0.name','Perfume')->assertJsonPath('items.0.qty',2)
            ->assertJsonPath('items.1.image_url',asset('storage/products/second.png'))
            ->assertJsonPath('items.2.image_url',null);
        $this->assertStringContainsString($shop->publicUrl(),$share->json('text'));
        $this->assertStringContainsString('0597654321',$share->json('text'));
        $this->assertStringNotContainsString('untrusted.invalid',json_encode($share->json()));
        $spin=$this->postJson($response->json('purchase_wheel.spin_url'))->assertOk();
        $this->assertStringContainsString('purchase prize',$spin->json('order_share.text'));
        $wheelMessage = rawurldecode($spin->json('whatsapp_url'));
        $this->assertStringContainsString(asset('storage/products/perfume.jpg'), $wheelMessage);
        $this->assertStringContainsString(asset('storage/products/second.png'), $wheelMessage);
        $this->assertStringContainsString($shop->publicUrl(), $wheelMessage);
        $this->getJson($spin->json('order_share_url'))->assertOk();
        $this->assertStringContainsString('purchase prize',$this->getJson($url)->json('text'));
        $this->assertDatabaseCount('front_orders',1);
    }

    public function test_order_photo_details_require_valid_signature_and_reject_cancelled_orders(): void
    {
        [$shop,$product]=$this->fixture();
        $response=$this->postJson(route('cosmetics.orders.store',$shop),$this->details($product))->assertOk();
        $order=FrontOrder::findOrFail($response->json('order_id'));
        $this->getJson(route('cosmetics.orders.share',$order))->assertForbidden();
        $this->getJson($response->json('order_share_url').'x')->assertForbidden();
        $expired=\Illuminate\Support\Facades\URL::temporarySignedRoute('cosmetics.orders.share',now()->subMinute(),['order'=>$order->id]);
        $this->getJson($expired)->assertForbidden();
        $order->update(['status'=>'cancelled']);
        $this->getJson($response->json('order_share_url'))->assertNotFound();
    }
}
