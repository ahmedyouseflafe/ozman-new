<?php
namespace Tests\Feature;

use App\Models\{Shop,User,Category,Product,FrontOrder};
use App\Services\MealChoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MealChoicesTest extends TestCase
{
    use RefreshDatabase;
    private function meal(): Product
    {
        $shop=Shop::create(['user_id'=>User::factory()->create(['role'=>'shop_owner','is_active'=>true])->id,'name'=>'Test','slug'=>uniqid(),'catalog_type'=>'restaurant','is_active'=>true,'is_accepting_orders'=>true]);
        $category=Category::create(['shop_id'=>$shop->id,'name'=>'Rolls','slug'=>uniqid(),'is_active'=>true]);
        return Product::create(['shop_id'=>$shop->id,'category_id'=>$category->id,'name'=>'Ship','slug'=>uniqid(),'price'=>100,'quantity'=>50,'is_active'=>true]);
    }
    private function group(int $count=4): array
    {
        return ['id'=>'rolls','name'=>'Rolls','min'=>$count,'max'=>$count,'allow_repeat'=>true,'options'=>[['id'=>'salmon','name'=>'Salmon','price'=>2]]];
    }
    public function test_owner_can_save_edit_remove_groups_and_other_owner_cannot(): void
    {
        $meal=$this->meal();$this->actingAs($meal->shop->user);
        $url=route('products.update',$meal);
        $data=['shop_id'=>$meal->shop_id,'category_id'=>$meal->category_id,'name'=>$meal->name,'price'=>100,'is_active'=>1,'meal_choice_groups'=>[$this->group()]];
        $this->put($url,$data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(4,$meal->fresh()->catalog_attributes['meal_choice_groups'][0]['max']);
        $this->get(route('products.edit',$meal))->assertOk()->assertSee('mealChoiceEditor',false)->assertSee('data-choice-preset="7"',false);
        $data['meal_choice_groups']=[[...$this->group(7),'source'=>'menu','options'=>[]]];
        $this->put($url,$data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('menu',$meal->fresh()->catalog_attributes['meal_choice_groups'][0]['source']);
        $data['meal_choice_groups']=[];
        $this->put($url,$data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame([],$meal->fresh()->catalog_attributes['meal_choice_groups']);
        $other=$this->meal();$this->actingAs($other->shop->user)->put($url,$data)->assertForbidden();
    }
    public function test_exact_counts_repeat_rules_and_invalid_selections(): void
    {
        $service=app(MealChoiceService::class);
        foreach([4,5,7] as $count){
            $groups=$service->normalize([$this->group($count)]);
            $good=[['group_id'=>'rolls','options'=>[['option_id'=>'salmon','qty'=>$count]]]];
            $this->assertSame((float)($count*2),$service->resolve($groups,$good)['extra']);
            foreach([$count-1,$count+1,0,-1] as $bad){
                try{$service->resolve($groups,[['group_id'=>'rolls','options'=>[['option_id'=>'salmon','qty'=>$bad]]]]);$this->fail('Accepted wrong count');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
            }
        }
        $this->expectException(ValidationException::class);
        $service->normalize([[...$this->group(),'allow_repeat'=>false]]);
    }
    public function test_menu_source_is_live_scoped_and_excludes_inactive_and_self(): void
    {
        $meal=$this->meal();$other=$this->meal();$service=app(MealChoiceService::class);
        $meal->update(['catalog_attributes'=>['meal_choice_groups'=>$service->normalize([[...$this->group(),'source'=>'menu','options'=>[]]])]]);
        $this->assertSame([],$service->forProduct($meal)[0]['options']);
        $roll=$meal->replicate();$roll->slug=uniqid();$roll->name='Fresh Roll';$roll->save();
        $this->assertSame('product-'.$roll->id,$service->forProduct($meal)[0]['options'][0]['id']);
        $this->assertSame(0,$service->forProduct($meal)[0]['options'][0]['price']);
        $roll->update(['is_active'=>false]);$this->assertSame([],$service->forProduct($meal)[0]['options']);
        $roll->update(['is_active'=>true]);$roll->category->update(['is_active'=>false]);$this->assertSame([],$service->forProduct($meal)[0]['options']);
    }
    public function test_order_requires_choices_and_prices_them_on_server_and_preserves_summary(): void
    {
        $meal=$this->meal();$meal->update(['catalog_attributes'=>['meal_choice_groups'=>[$this->group()]]]);
        $payload=['order_type'=>'pickup','customer_name'=>'Test','customer_phone'=>'0591234567','items'=>[['product_id'=>$meal->id,'qty'=>2]]];
        $url=route('restaurant.orders.store',$meal->shop_id);
        $this->postJson($url,$payload)->assertUnprocessable();
        $payload['items'][0]['choices']=[['group_id'=>'rolls','options'=>[['option_id'=>'salmon','qty'=>4,'price'=>0,'name'=>'Spoof']]]];
        $result=$this->postJson($url,$payload)->assertOk();
        $order=FrontOrder::findOrFail($result->json('order_id'));
        $this->assertSame('216.00',$order->total);
        $this->assertSame('Salmon',$order->items[0]['choices'][0]['options'][0]['name']);
        $this->actingAs($meal->shop->user)->get(route('restaurant.orders.show',[$meal->shop,$order]))->assertOk()->assertSee('4× Salmon');
        $payload['items'][0]['choices'][0]['options'][0]['option_id']='foreign';
        $this->postJson($url,$payload)->assertUnprocessable();
        $this->get(route('restaurant.menu',$meal->shop))->assertOk()->assertSee('mealChoices',false);
    }
}
