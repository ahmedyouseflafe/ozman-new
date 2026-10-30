<?php
namespace App\Http\Controllers;
use App\Models\Shop;

class KremboStoreController extends Controller
{
    private function available(Shop $shop): void { abort_unless($shop->is_active && $shop->catalog_type === 'krembo',404); }
    public function show(Shop $shop)
    {
        $this->available($shop);
        $products=$shop->products()->where('is_active',true)->whereHas('category',fn($q)=>$q->where('shop_id',$shop->id)->where('is_active',true))->with('category')->orderBy('name')->paginate(12);
        $agents=$shop->agents()->where('is_active',true)->orderBy('name')->get();
        $distributors=$shop->distributors()->where('is_active',true)
            ->where(fn($q)=>$q->whereNull('agent_id')->orWhereHas('agent',fn($a)=>$a->where('shop_id',$shop->id)->where('is_active',true)))
            ->orderBy('name')->get();
        $phone=preg_replace('/[^+0-9]/','',$shop->phone ?? '');
        return view('front.krembo_store',compact('shop','products','agents','distributors','phone'));
    }
    public function catalog(Shop $shop)
    {
        $this->available($shop);
        return app(FrontController::class)->index($shop);
    }
}
