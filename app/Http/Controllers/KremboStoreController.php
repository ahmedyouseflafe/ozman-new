<?php
namespace App\Http\Controllers;
use App\Models\Shop;
use App\Models\ShopOutlet;

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
        // Hero video: TikTok link from the shop form, then a screen video, then the newest featured product video.
        $tiktokVideoId=Shop::tiktokVideoId($shop->hero_video_url);
        $video=$tiktokVideoId ? null : $shop->activeScreens()->where('type','video')->whereNotNull('media')->first();
        $productVideo=$tiktokVideoId || $video ? null : $shop->products()->where('is_active',true)->where('is_featured',true)
            ->whereNotNull('video')->where('video','!=','')->latest()->value('video');
        $socialLinks=collect(['instagram'=>'Instagram','facebook'=>'Facebook','tiktok'=>'TikTok','youtube'=>'YouTube','snapchat'=>'Snapchat'])->map(function($label,$key) use ($shop) {
            $url=$shop->social?->{$key};
            return filter_var($url,FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url,PHP_URL_SCHEME)),['https','http'],true) ? ['label'=>$label,'url'=>$url] : null;
        })->filter();
        $outletGroups=$this->outletGroups($shop,$distributors);
        return view('front.krembo_store',compact('shop','products','agents','distributors','phone','video','tiktokVideoId','productVideo','socialLinks','outletGroups'));
    }
    /** Where to buy, grouped by region: the shop's own outlets plus shops served by its distributors (website and app). */
    public function outletGroups(Shop $shop,$distributors=null)
    {
        $distributors??=$shop->distributors()->where('is_active',true)
            ->where(fn($q)=>$q->whereNull('agent_id')->orWhereHas('agent',fn($a)=>$a->where('shop_id',$shop->id)->where('is_active',true)))->get();
        $outlets=Shop::query()->where('is_active',true)->where('id','!=',$shop->id)
            ->where(fn($q)=>$q->whereIn('distributor_id',$distributors->pluck('id'))
                ->orWhereHas('distributorMarketer',fn($m)=>$m->where('is_active',true)->whereIn('distributor_id',$distributors->pluck('id'))))
            ->orderBy('name')->get();
        $otherRegion='مناطق أخرى';
        return $outlets->map(fn(Shop $outlet)=>[
                'name'=>$outlet->name,'region'=>trim((string)$outlet->city) ?: $otherRegion,'address'=>$outlet->address,
                'latitude'=>$outlet->latitude,'longitude'=>$outlet->longitude,'phone'=>null,'sort'=>0,
                'map'=>$this->mapUrl($outlet->latitude,$outlet->longitude,$outlet->address),
            ])
            ->concat($shop->outlets()->where('is_active',true)->get()->map(fn(ShopOutlet $outlet)=>[
                'name'=>$outlet->name,'region'=>$outlet->region,'address'=>$outlet->address,
                'latitude'=>$outlet->latitude,'longitude'=>$outlet->longitude,'phone'=>preg_replace('/[^+0-9]/','',(string)$outlet->phone) ?: null,
                'sort'=>$outlet->sort_order,'map'=>$outlet->mapUrl(),
            ]))
            ->sortBy([['sort','asc'],['name','asc']])
            ->groupBy('region')
            ->sortKeysUsing(fn($a,$b)=>[$a===$otherRegion,$a]<=>[$b===$otherRegion,$b]);
    }
    private function mapUrl($latitude,$longitude,?string $address): ?string
    {
        $destination=is_numeric($latitude) && is_numeric($longitude) ? $latitude.','.$longitude : $address;
        return filled($destination) ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($destination) : null;
    }
    public function catalog(Shop $shop)
    {
        $this->available($shop);
        return app(FrontController::class)->index($shop);
    }
}
