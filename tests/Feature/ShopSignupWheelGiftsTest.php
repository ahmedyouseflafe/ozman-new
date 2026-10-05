<?php

namespace Tests\Feature;

use App\Models\{Shop, User, RewardWheel, ShopSignupReward, ShopSignupWheelCycle, Product, Category, FrontOrder};
use App\Services\ShopSignupRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopSignupWheelGiftsTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $slug = 'bankai-sushi'): Shop
    {
        return Shop::create(['user_id'=>User::factory()->create(['role'=>'shop_owner','is_active'=>true])->id,
            'name'=>$slug,'slug'=>$slug,'catalog_type'=>'restaurant','is_active'=>true,'is_accepting_orders'=>true,'whatsapp'=>'0591234567']);
    }

    private function settings(): array
    {
        return ['title'=>'Welcome gifts','is_active'=>true,'win_quota_total'=>4,'segments'=>[
            ['label'=>'هدية رول','discount_type'=>'gift','discount_value'=>999,'color'=>'#087e8b','win_quota'=>1,'is_active'=>true],
            ['label'=>'خصم 10%','discount_type'=>'percent','discount_value'=>10,'color'=>'#5540b5','win_quota'=>3,'is_active'=>true],
            ['label'=>'لن تظهر','discount_type'=>'amount','discount_value'=>20,'color'=>'#05765f','win_quota'=>0,'is_active'=>true],
        ]];
    }

    private function register(Shop $shop, int $number): string
    {
        $token=hash('sha256','customer-'.$number);
        $this->postJson(route('restaurant.customer-registration.store',$shop),[
            'registration_token'=>$token,'name'=>'Test customer','whatsapp'=>'059123'.str_pad((string)$number,4,'0',STR_PAD_LEFT),
            'address'=>'Test address','location_deferred'=>true,
        ])->assertOk();
        return $token;
    }

    public function test_exact_distribution_repeats_and_retries_do_not_consume_another_slot(): void
    {
        $shop=$this->shop();$this->actingAs($shop->user);
        $this->put(route('reward-wheels.shop-signup.update',$shop),$this->settings())->assertSessionHasNoErrors();
        $service=app(ShopSignupRewardService::class);
        for($round=0;$round<2;$round++) {
            $indices=[];
            for($i=1;$i<=4;$i++) {
                $token=$this->register($shop,$round*4+$i);
                $before=ShopSignupWheelCycle::sole()->remaining;
                $service->state($shop,$token,false);
                $this->assertSame($before,ShopSignupWheelCycle::sole()->remaining);
                $result=$service->state($shop,$token,true);$indices[]=$result['selected_index'];
                $after=ShopSignupWheelCycle::sole()->remaining;
                $this->assertSame($result,$service->state($shop,$token,true));
                $this->assertSame($after,ShopSignupWheelCycle::sole()->remaining);
            }
            sort($indices);$this->assertSame([0,1,1,1],$indices);
            $this->assertSame([0,0,0],ShopSignupWheelCycle::sole()->remaining);
        }
    }

    public function test_editing_or_disabling_keeps_previous_entitlements_on_their_original_distribution(): void
    {
        $shop=$this->shop();$this->actingAs($shop->user);$settings=$this->settings();
        $this->put(route('reward-wheels.shop-signup.update',$shop),$settings)->assertSessionHasNoErrors();
        $first=$this->register($shop,1);$oldCycle=ShopSignupWheelCycle::sole();
        $settings['segments'][0]['win_quota']=4;$settings['segments'][1]['win_quota']=0;
        $this->put(route('reward-wheels.shop-signup.update',$shop),$settings)->assertSessionHasNoErrors();
        $second=$this->register($shop,2);
        $this->assertDatabaseCount('shop_signup_wheel_cycles',2);
        $service=app(ShopSignupRewardService::class);
        $this->assertSame(0,$service->state($shop,$second,true)['selected_index']);
        $this->assertSame([1,3,0],$oldCycle->fresh()->remaining);
        $settings['is_active']=false;
        $this->put(route('reward-wheels.shop-signup.update',$shop),$settings)->assertSessionHasNoErrors();
        $service->state($shop,$first,true);
        $this->assertSame(3,array_sum($oldCycle->fresh()->remaining));
        $this->register($shop,3);$this->assertDatabaseCount('shop_signup_rewards',2);
    }

    public function test_gift_upload_preservation_order_and_cross_shop_image_rejection(): void
    {
        Storage::fake('public');$shop=$this->shop();$this->actingAs($shop->user);
        $settings=$this->settings();$settings['segments'][0]['win_quota']=4;$settings['segments'][1]['win_quota']=0;
        $settings['segments'][0]['gift_image']=UploadedFile::fake()->image('roll.png');
        $url=route('reward-wheels.shop-signup.update',$shop);
        $this->post($url,['_method'=>'PUT',...$settings])->assertSessionHasNoErrors();
        $wheel=RewardWheel::where('shop_id',$shop->id)->sole();$gift=$wheel->segments()->where('discount_type','gift')->sole();
        $this->assertNull($gift->discount_value);$this->assertStringStartsWith('storage/reward-gifts/',$gift->gift_image);
        Storage::disk('public')->assertExists(substr($gift->gift_image,8));
        unset($settings['segments'][0]['gift_image']);$settings['segments'][0]['existing_gift_image']=$gift->gift_image;
        $this->put($url,$settings)->assertSessionHasNoErrors();
        $this->assertSame($gift->gift_image,$wheel->segments()->where('discount_type','gift')->sole()->gift_image);
        $other=$this->shop('other');$this->actingAs($other->user);
        $this->putJson(route('reward-wheels.shop-signup.update',$other),$settings)->assertUnprocessable();
        $this->actingAs($shop->user);
        $token=$this->register($shop,1);$service=app(ShopSignupRewardService::class);
        $reward=$service->state($shop,$token,true);
        $this->assertSame(asset($gift->gift_image),$reward['segments'][0]['gift_image']);
        $product=Product::create(['category_id'=>Category::create(['shop_id'=>$shop->id,'name'=>'Meals','slug'=>'meals','is_active'=>true])->id,
            'shop_id'=>$shop->id,'name'=>'Roll','slug'=>'roll','price'=>100,'quantity'=>50,'is_active'=>true]);
        $payload=['registration_token'=>$token,'order_type'=>'pickup','customer_name'=>'Test customer','customer_phone'=>'0591230001','items'=>[['product_id'=>$product->id,'qty'=>1]],'reward_label'=>'Forged','reward_gift_image'=>'https://example.test/fake.png'];
        $response=$this->postJson(route('restaurant.orders.store',$shop),$payload)->assertOk();
        $order=FrontOrder::findOrFail($response->json('order_id'));
        $this->assertSame('0.00',$order->discount);$this->assertSame('100.00',$order->total);
        $this->assertSame('gift',$order->reward_discount_type);$this->assertSame('هدية رول',$order->reward_label);
        $this->assertSame($gift->gift_image,$order->reward_gift_image);
        $this->assertStringContainsString('هدية رول',urldecode($response->json('whatsapp_url')));
        $this->get(route('restaurant.orders.show',[$shop,$order]))->assertOk()->assertSee('هدية التسجيل')->assertSee(asset($gift->gift_image),false);
        $this->assertNull($service->state($shop,$token,true));
        $response=$this->postJson(route('restaurant.orders.store',$shop),$payload)->assertOk();
        $this->assertNull(FrontOrder::findOrFail($response->json('order_id'))->reward_gift_image);
    }

    public function test_invalid_totals_and_non_image_uploads_are_rejected(): void
    {
        $shop=$this->shop();$this->actingAs($shop->user);$url=route('reward-wheels.shop-signup.update',$shop);
        $settings=$this->settings();$settings['win_quota_total']=5;
        $this->putJson($url,$settings)->assertUnprocessable();
        $settings=$this->settings();$settings['segments'][0]['gift_image']=UploadedFile::fake()->create('payload.html',1,'text/html');
        $this->postJson($url,['_method'=>'PUT',...$settings])->assertUnprocessable();
        $settings=$this->settings();$settings['segments'][0]['existing_gift_image']='https://example.test/image.png';
        $this->putJson($url,$settings)->assertUnprocessable();
        $this->assertDatabaseCount('reward_wheels',0);
    }

    public function test_legacy_entitlement_without_cycle_can_still_spin_and_redeem(): void
    {
        $shop=$this->shop();$this->actingAs($shop->user);
        $this->put(route('reward-wheels.shop-signup.update',$shop),$this->settings())->assertSessionHasNoErrors();
        $token=$this->register($shop,1);$reward=ShopSignupReward::sole();
        $reward->update(['cycle_id'=>null,'segments'=>[
            ['label'=>'Old discount','discount_type'=>'percent','discount_value'=>10,'color'=>'#087e8b'],
            ['label'=>'Old discount B','discount_type'=>'percent','discount_value'=>10,'color'=>'#5540b5'],
        ]]);
        $service=app(ShopSignupRewardService::class);$service->state($shop,$token,true);
        $order=$service->placeOrder($shop,$token,['shop_id'=>$shop->id,'order_number'=>'LEGACY','customer_name'=>'Test','customer_phone'=>'0591230001','subtotal'=>100,'discount'=>0,'total'=>100]);
        $this->assertSame('90.00',$order->total);
        $this->assertSame([1,3,0],ShopSignupWheelCycle::sole()->remaining);
    }
}
