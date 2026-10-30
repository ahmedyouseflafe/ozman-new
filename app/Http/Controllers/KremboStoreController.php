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
        $video=$shop->activeScreens()->where('type','video')->whereNotNull('media')->first();
        $socialLinks=collect(['instagram'=>'Instagram','facebook'=>'Facebook','tiktok'=>'TikTok','youtube'=>'YouTube','snapchat'=>'Snapchat'])->map(function($label,$key) use ($shop) {
            $url=$shop->social?->{$key};
            return filter_var($url,FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url,PHP_URL_SCHEME)),['https','http'],true) ? ['label'=>$label,'url'=>$url] : null;
        })->filter();
        $outlets=Shop::query()->where('is_active',true)->where('id','!=',$shop->id)
            ->where(fn($q)=>$q->whereIn('distributor_id',$distributors->pluck('id'))
                ->orWhereHas('distributorMarketer',fn($m)=>$m->where('is_active',true)->whereIn('distributor_id',$distributors->pluck('id'))))
            ->orderBy('name')->get();
        return view('front.krembo_store',compact('shop','products','agents','distributors','phone','video','socialLinks','outlets'));
    }
    public function catalog(Shop $shop)
    {
        $this->available($shop);
        return app(FrontController::class)->index($shop);
    }
}
