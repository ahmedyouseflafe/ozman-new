<?php

namespace Tests\Feature;

use App\Models\{Shop, User, Product, RewardWheel, ShopSignupReward, FrontOrder};
use App\Services\ShopSignupRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSignupWheelTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_configure_own_wheel_without_changing_global_or_other_shop_wheels(): void
    {
        $shop = $this->shop('bankai-sushi'); $other = $this->shop('other');
        $global = RewardWheel::create(['key' => RewardWheel::CUSTOMER_SIGNUP_DISCOUNTS, 'title' => 'Global', 'is_active' => true]);
        $this->actingAs($shop->user)->get(route('reward-wheels.shop-signup.edit', $shop))->assertOk()->assertSee('عجلة التسجيل');
        $this->assertDatabaseCount('reward_wheels', 1); // Viewing is not creation or activation.
        $this->put(route('reward-wheels.shop-signup.update', $shop), $this->settings())->assertRedirect();
        $wheel = RewardWheel::where('shop_id', $shop->id)->sole();
        $this->assertSame(2, $wheel->segments()->count());
        $this->assertTrue($wheel->is_active);
        $this->assertSame('Global', $global->fresh()->title);
        $this->get(route('reward-wheels.shop-signup.edit', $other))->assertForbidden();
        $this->put(route('reward-wheels.shop-signup.update', $other), $this->settings())->assertForbidden();
        $this->get(route('reward-wheels.customer-signup.edit'))->assertForbidden();
        $this->get(route('restaurant.dashboard', $shop))->assertSee(route('reward-wheels.shop-signup.edit', $shop), false);
    }

    public function test_wheel_validation_rejects_invalid_discounts_and_insufficient_active_segments(): void
    {
        $shop = $this->shop('bankai-sushi');$this->actingAs($shop->user);
        foreach ([['discount_value'=>101], ['discount_value'=>-1], ['discount_type'=>'free_shipping'], ['color'=>'red'], ['win_quota'=>-1], ['win_quota'=>3]] as $bad) {
            $settings=$this->settings();$settings['segments'][0]=[...$settings['segments'][0],...$bad];
            $this->putJson(route('reward-wheels.shop-signup.update',$shop),$settings)->assertUnprocessable();
        }
        $settings=$this->settings();$settings['segments'][0]['is_active']=false;
        $this->putJson(route('reward-wheels.shop-signup.update',$shop),$settings)->assertUnprocessable();
        $this->assertDatabaseCount('reward_wheels',0);
    }

    public function test_registration_and_spin_are_once_and_survive_reload_and_admin_edits(): void
    {
        $shop=$this->shop('bankai-sushi');$this->wheel($shop);
        $this->register($shop);
        $this->register($shop);
        $this->assertDatabaseCount('shop_signup_rewards',1);
        $url=route('restaurant.signup-reward',$shop);
        $this->postJson($url,['registration_token'=>str_repeat('a',64)])->assertOk()->assertJsonPath('reward.selected_index',null);
        $first=$this->postJson($url,['registration_token'=>str_repeat('a',64),'spin'=>true,'selected_index'=>500])->assertOk()->json('reward');
        $this->postJson($url,['registration_token'=>str_repeat('a',64),'spin'=>true])->assertJsonPath('reward.selected_index',$first['selected_index']);
        $wheel=RewardWheel::where('shop_id',$shop->id)->sole();$wheel->update(['is_active'=>false]);$wheel->segments()->delete();
        $this->postJson($url,['registration_token'=>str_repeat('a',64)])->assertJsonPath('reward.segments',$first['segments']);
        $this->register($shop,str_repeat('b',64),'00970591234567');
        $this->assertDatabaseCount('shop_signup_rewards',1);
        $this->postJson($url,['registration_token'=>str_repeat('b',64),'spin'=>true])->assertJsonPath('reward',null);
        $this->postJson($url,['registration_token'=>str_repeat('c',64),'spin'=>true])->assertNotFound();
    }

    public function test_only_new_registrations_receive_enabled_shop_wheels_and_other_restaurants_can_opt_in(): void
    {
        $shop=$this->shop('bankai-sushi');$this->register($shop);
        $this->wheel($shop);$this->register($shop);
        $this->assertDatabaseCount('shop_signup_rewards',0);
        $this->register($shop,str_repeat('b',64),'0591234568');$this->assertDatabaseCount('shop_signup_rewards',1);
        $other=$this->shop('second-restaurant');$this->wheel($other,false);
        $this->get(route('restaurant.menu',$other))->assertOk()->assertSee('id="bankaiWelcome"',false)->assertSee('second-restaurant');
        $this->register($other);$this->assertDatabaseCount('shop_signup_rewards',1);
        $this->postJson(route('restaurant.signup-reward',$other),['registration_token'=>str_repeat('b',64),'spin'=>true])->assertNotFound();
    }

    public function test_first_order_applies_server_discount_once_and_ignores_client_totals(): void
    {
        $shop=$this->shop('bankai-sushi');$this->wheel($shop);$this->register($shop);
        $service=app(ShopSignupRewardService::class);$service->state($shop,str_repeat('a',64),true);
        $product=Product::create(['category_id'=>\App\Models\Category::create(['shop_id'=>$shop->id,'name'=>'Meals','slug'=>'meals','is_active'=>true])->id,'shop_id'=>$shop->id,'name'=>'Roll','slug'=>'test-roll','price'=>100,'quantity'=>50,'is_active'=>true]);
        $payload=['registration_token'=>str_repeat('a',64),'order_type'=>'pickup','customer_name'=>'Customer','customer_phone'=>'0591234567',
            'discount'=>100,'total'=>0,'items'=>[['product_id'=>$product->id,'qty'=>2,'price'=>1]]];
        $result=$this->postJson(route('restaurant.orders.store',$shop),$payload)->assertOk();
        $order=FrontOrder::findOrFail($result->json('order_id'));
        $this->assertSame('200.00',$order->subtotal);$this->assertSame('20.00',$order->discount);$this->assertSame('180.00',$order->total);
        $this->assertSame($order->id,ShopSignupReward::sole()->front_order_id);
        $result=$this->postJson(route('restaurant.orders.store',$shop),$payload)->assertOk();
        $this->assertSame('0.00',FrontOrder::findOrFail($result->json('order_id'))->discount);
        $this->postJson(route('restaurant.signup-reward',$shop),['registration_token'=>str_repeat('a',64),'spin'=>true])->assertJsonPath('reward',null);
    }

    public function test_pending_spin_wrong_phone_invalid_order_and_other_shop_cannot_spend_reward(): void
    {
        $shop=$this->shop('bankai-sushi');$this->wheel($shop);$this->register($shop);
        $product=Product::create(['category_id'=>\App\Models\Category::create(['shop_id'=>$shop->id,'name'=>'Meals','slug'=>'meals','is_active'=>true])->id,'shop_id'=>$shop->id,'name'=>'Roll','slug'=>'test-roll','price'=>100,'quantity'=>50,'is_active'=>true]);
        $payload=['registration_token'=>str_repeat('a',64),'order_type'=>'pickup','customer_name'=>'Customer','customer_phone'=>'0591234567','items'=>[['product_id'=>$product->id,'qty'=>1]]];
        $url=route('restaurant.orders.store',$shop);
        $this->postJson($url,$payload)->assertUnprocessable();
        app(ShopSignupRewardService::class)->state($shop,str_repeat('a',64),true);
        $this->postJson($url,[...$payload,'customer_phone'=>'0591234568'])->assertUnprocessable();
        $this->postJson($url,[...$payload,'items'=>[['product_id'=>9999,'qty'=>1]]])->assertUnprocessable();
        $this->assertNull(ShopSignupReward::sole()->redeemed_at);$this->assertDatabaseCount('front_orders',0);
        $this->postJson($url,[...$payload,'registration_token'=>str_repeat('c',64)])->assertNotFound();
    }

    public function test_fixed_discount_is_capped_and_cancellation_does_not_restore_reward(): void
    {
        $shop=$this->shop('bankai-sushi');$this->wheel($shop,true,'amount',200);$this->register($shop);
        $service=app(ShopSignupRewardService::class);$service->state($shop,str_repeat('a',64),true);
        $order=$service->placeOrder($shop,str_repeat('a',64),['shop_id'=>$shop->id,'order_number'=>'CAP-1','customer_name'=>'Test','customer_phone'=>'0591234567','subtotal'=>30,'discount'=>0,'total'=>30]);
        $this->assertSame('0.00',$order->total);$this->assertSame('30.00',$order->discount);
        $order->update(['status'=>'cancelled']);$this->assertNull($service->state($shop,str_repeat('a',64),true));
    }

    private function shop(string $slug): Shop
    {
        return Shop::create(['user_id'=>User::factory()->create(['role'=>'shop_owner','is_active'=>true])->id,'name'=>$slug,'slug'=>$slug,'catalog_type'=>'restaurant','is_active'=>true,'is_accepting_orders'=>true]);
    }
    private function settings(): array
    {
        return ['title'=>'Welcome gift','is_active'=>true,'win_quota_total'=>2,'segments'=>[
            ['label'=>'10% A','discount_type'=>'percent','discount_value'=>10,'color'=>'#00cfe8','is_active'=>true,'win_quota'=>1],
            ['label'=>'10% B','discount_type'=>'percent','discount_value'=>10,'color'=>'#7000ff','is_active'=>true,'win_quota'=>1],
        ]];
    }
    private function wheel(Shop $shop,bool $active=true,string $type='percent',int $value=10): void
    {
        $wheel=RewardWheel::create(['shop_id'=>$shop->id,'key'=>'shop_signup_'.$shop->id,'wheel_type'=>RewardWheel::TYPE_CUSTOMER_SIGNUP,'title'=>'Welcome gift','is_active'=>$active]);
        foreach($this->settings()['segments'] as $segment)$wheel->segments()->create([...$segment,'discount_type'=>$type,'discount_value'=>$value]);
    }
    private function register(Shop $shop,?string $token=null,string $phone='0591234567'): void
    {
        $this->postJson(route('restaurant.customer-registration.store',$shop),['registration_token'=>$token??str_repeat('a',64),'name'=>'Customer','whatsapp'=>$phone,'address'=>'Test address','location_deferred'=>true])->assertOk();
    }
}
