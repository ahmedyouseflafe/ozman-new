<?php

namespace Tests\Feature;

use App\Models\{Category, Product, Shop, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfumeProductOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_elegance_groups_brands_and_editions_without_changing_other_sections_or_shops(): void
    {
        $shop = Shop::create(['user_id'=>User::factory()->create()->id,'name'=>'Elegance Perfumes','slug'=>'elegance-perfumes','catalog_type'=>'cosmetics','is_active'=>true]);
        $perfumes = Category::create(['shop_id'=>$shop->id,'name'=>'عطور رجالية','slug'=>'perfumes','is_active'=>true]);
        $watches = Category::create(['shop_id'=>$shop->id,'name'=>'ساعات رجالية','slug'=>'watches','is_active'=>true]);
        $entries = [
            ['Emporio Armani Stronger With You Parfum','Emporio Armani'],
            ['Dior Sauvage Elixir','Dior'],
            ['Stronger With You Absolutely','Emporio Armani'],
            ['GIORGIO ARMANI ACQUA DI GIÒ Eau de Parfum','Giorgio Armani'],
            ['Dior Sauvage Eau de Parfum',' DIOR '],
            ['Giorgio Armani Acqua di Giò Elixir','Giorgio Armani'],
            ['Stronger With You Intensely','Emporio Armani'],
        ];
        $ids=[];
        foreach ($entries as $i=>[$name,$brand]) {
            $ids[]=Product::create(['shop_id'=>$shop->id,'category_id'=>$perfumes->id,'name'=>$name,'slug'=>'p-'.$i,'price'=>100,'is_active'=>true,'catalog_attributes'=>['brand'=>$brand]])->id;
        }
        foreach (['Zulu watch','Alpha watch'] as $i=>$name) Product::create(['shop_id'=>$shop->id,'category_id'=>$watches->id,'name'=>$name,'slug'=>'w-'.$i,'price'=>100,'is_active'=>true,'created_at'=>now()->subDays($i)]);
        $response=$this->get(route('cosmetics.store',$shop))->assertOk();
        $categories=$response->viewData('categories');
        $this->assertSame([$ids[3],$ids[5],$ids[2],$ids[6],$ids[0],$ids[4],$ids[1]],$categories->firstWhere('id',$perfumes->id)->products->pluck('id')->all());
        $this->assertSame(['Zulu watch','Alpha watch'],$categories->firstWhere('id',$watches->id)->products->pluck('name')->all());
        $shop->update(['name'=>'Other shop','slug'=>'other-shop']);
        Product::whereKey($ids[1])->update(['created_at'=>now()->addMinute()]);
        $other=$this->get(route('cosmetics.store',$shop))->assertOk()->viewData('categories');
        $this->assertSame($ids[1],$other->firstWhere('id',$perfumes->id)->products->first()->id);
    }
}
