<?php
namespace Tests\Feature;

use App\Models\{Shop,User,FrontOrder,RewardWheel};
use App\Http\Controllers\RestaurantPurchaseWheelController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RestaurantPurchaseWheelTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $shop=Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'Bankai','slug'=>uniqid(),'catalog_type'=>'restaurant','is_active'=>true]);
        $order=FrontOrder::create(['shop_id'=>$shop->id,'order_number'=>uniqid(),'customer_name'=>'Test','items'=>[],'subtotal'=>75,'total'=>75,'order_channel'=>'restaurant','order_type'=>'pickup','status'=>'new','reward_label'=>'Signup gift']);
        $wheel=RewardWheel::create(['shop_id'=>$shop->id,'key'=>uniqid(),'title'=>'Purchase','wheel_type'=>'purchase_amount','min_order_total'=>50,'max_order_total'=>100,'is_active'=>true,'win_quota_total'=>2]);
        foreach([0,1] as $i)$wheel->segments()->create(['label'=>'Prize '.$i,'discount_type'=>'gift','discount_value'=>0,'win_quota'=>1,'color'=>'#00ccdd','sort_order'=>$i,'is_active'=>true]);
        return [$shop,$order,$wheel];
    }

    public function test_only_signed_eligible_orders_can_spin_once_without_overwriting_signup(): void
    {
        [$shop,$order,$wheel]=$this->fixture();
        $this->postJson(route('restaurant.purchase-wheel.spin',$order))->assertForbidden();
        $this->getJson(URL::signedRoute('restaurant.purchase-wheel.show',$order))->assertOk()->assertJsonCount(2,'wheel.segments');
        $url=URL::signedRoute('restaurant.purchase-wheel.spin',$order);
        $first=$this->postJson($url)->assertOk()->json('reward');
        $cycle=$wheel->fresh()->spin_cycle;
        $this->postJson($url)->assertOk()->assertJson(['reward'=>$first]);
        $this->assertSame($cycle,$wheel->fresh()->spin_cycle);
        $this->assertCount(1,$cycle);
        $this->assertSame('Signup gift',$order->fresh()->reward_label);
        $this->assertSame($first,json_decode($order->fresh()->purchase_reward,true));
        $this->getJson(URL::signedRoute('restaurant.purchase-wheel.show',$order))->assertJson(['reward'=>$first]);
        $html=view('admin.restaurant.partials.purchase_reward',['order'=>$order->fresh()])->render();
        $this->assertStringContainsString($first['label'],$html);
    }

    public function test_amount_shop_status_and_active_wheel_are_enforced(): void
    {
        [$shop,$order,$wheel]=$this->fixture();
        foreach([49,101] as $total){$order->update(['total'=>$total]);$this->postJson(URL::signedRoute('restaurant.purchase-wheel.spin',$order))->assertStatus(422);}
        foreach([50,100] as $total){$order->update(['total'=>$total]);$this->assertSame($wheel->id,RestaurantPurchaseWheelController::eligible($order)->id);}
        $order->update(['status'=>'cancelled']);
        $this->postJson(URL::signedRoute('restaurant.purchase-wheel.spin',$order))->assertStatus(422);
        $order->update(['status'=>'new']);$wheel->update(['is_active'=>false]);
        $this->assertNull(RestaurantPurchaseWheelController::eligible($order));
        $wheel->update(['is_active'=>true]);
        [$other]=$this->fixture();$order->update(['shop_id'=>$other->id]);
        $wheel->update(['min_order_total'=>1]);
        $this->assertNotSame($wheel->id,RestaurantPurchaseWheelController::eligible($order)->id);
    }

    public function test_locked_control_is_rendered_with_eligible_amounts(): void
    {
        [$shop]=$this->fixture();
        $html=view('front.partials.restaurant_purchase_wheel',['shop'=>$shop,'locale'=>'ar'])->render();
        $this->assertStringContainsString('class="purchase-wheel-lock"',$html);
        $this->assertStringContainsString('50',$html);
        $this->assertStringContainsString('purchaseWheelTrigger',$html);
    }

    public function test_saved_order_returns_signed_unlock_links_only_when_amount_qualifies(): void
    {
        [$shop]=$this->fixture();$shop->update(['is_accepting_orders'=>true]);
        $category=\App\Models\Category::create(['shop_id'=>$shop->id,'name'=>'Rolls','slug'=>uniqid(),'is_active'=>true]);
        $product=\App\Models\Product::create(['shop_id'=>$shop->id,'category_id'=>$category->id,'name'=>'Roll','slug'=>uniqid(),'price'=>75,'quantity'=>100,'is_active'=>true]);
        $payload=['order_type'=>'pickup','customer_name'=>'Test','customer_phone'=>'0591234567','items'=>[['product_id'=>$product->id,'qty'=>1]]];
        $response=$this->postJson(route('restaurant.orders.store',$shop),$payload)->assertOk();
        $this->getJson($response->json('purchase_wheel.url'))->assertOk();
        $this->postJson($response->json('purchase_wheel.spin_url'))->assertOk();
        $product->update(['price'=>10]);
        $this->postJson(route('restaurant.orders.store',$shop),$payload)->assertOk()->assertJsonPath('purchase_wheel',null);
    }
}

