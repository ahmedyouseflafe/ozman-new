<?php

namespace App\Http\Controllers;

use App\Models\{Shop, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Http};
use Illuminate\Support\Str;

class ClothingPhotoTryOnController extends Controller
{
    public static function enabled(Shop $shop): bool
    {
        return filled(config('services.fashn.key')) && in_array($shop->slug, config('services.fashn.shops', []), true);
    }

    public function create(Request $request, Shop $shop)
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'clothing',404);
        abort_unless(self::enabled($shop),503,'تجربة الصور غير مفعلة لهذا المحل بعد.');
        $data=$request->validate([
            'photo'=>['required','image','mimes:jpeg,png,webp','max:6144','dimensions:min_width=256,min_height=256,max_width=4096,max_height=4096'],
            'consent'=>['required','accepted'],
            'product_id'=>['nullable','integer'],
        ]);
        if (!empty($data['product_id'])) {
            $product=Product::where('shop_id',$shop->id)->where('is_active',true)->findOrFail($data['product_id']);
            abort_unless(filled($product->main_image),422,'صورة القطعة غير متاحة.');
            $path=$product->main_image;
            $garment=Str::startsWith($path,['http://','https://'])?$path:asset(Str::startsWith($path,'storage/')?$path:'storage/'.$path);
        } else {
            $garment='data:image/png;base64,'.base64_encode(file_get_contents(base_path('public/images/virtual-tryon/demo-ivory-navy-tee.png')));
        }
        $photo=$data['photo'];
        try {
            $response=Http::withToken(config('services.fashn.key'))->connectTimeout(10)->timeout(30)->post('https://api.fashn.ai/v1/run',[
                'model_name'=>'tryon-v1.6','inputs'=>[
                    'model_image'=>'data:'.$photo->getMimeType().';base64,'.base64_encode(file_get_contents($photo->getRealPath())),
                    'garment_image'=>$garment,'category'=>'tops','mode'=>'quality','num_samples'=>1,'return_base64'=>true,
                ],
            ]);
            $providerId=$response->json('id');
            if (!$response->successful() || !is_string($providerId) || !preg_match('/^[a-zA-Z0-9_-]{1,150}$/',$providerId)) return $this->unavailable();
        } catch (\Illuminate\Http\Client\ConnectionException $e) { return $this->unavailable(); }
        $job=(string)Str::uuid();
        $owner=$request->session()->get('photo_tryon_owner') ?: Str::random(64);
        $request->session()->put('photo_tryon_owner',$owner);
        Cache::put('photo-tryon:'.$job,['provider_id'=>$providerId,'shop_id'=>$shop->id,'session'=>hash('sha256',$owner)],now()->addMinutes(10));
        return response()->json(['job'=>$job],202)->header('Cache-Control','no-store');
    }

    public function status(Request $request, Shop $shop, string $job)
    {
        abort_unless($shop->is_active && self::enabled($shop),404);
        $record=Cache::get('photo-tryon:'.$job);
        abort_unless($record && $record['shop_id']===$shop->id && hash_equals($record['session'],hash('sha256',(string)$request->session()->get('photo_tryon_owner'))),404);
        try {
            $response=Http::withToken(config('services.fashn.key'))->connectTimeout(10)->timeout(20)->get('https://api.fashn.ai/v1/status/'.$record['provider_id']);
            if (!$response->successful()) return $this->unavailable();
        } catch (\Illuminate\Http\Client\ConnectionException $e) { return $this->unavailable(); }
        $state=$response->json('status');
        if ($state==='completed') {
            $image=$response->json('output.0');
            // Only image data is returned; no provider credentials or errors reach the browser.
            if (!is_string($image) || !preg_match('#^data:image/(png|jpeg|webp);base64,[A-Za-z0-9+/=]+$#',$image)) return $this->unavailable();
            return response()->json(['status'=>'completed','image'=>$image])->header('Cache-Control','no-store');
        }
        if ($state==='failed') return $this->unavailable();
        return response()->json(['status'=>'processing'])->header('Cache-Control','no-store');
    }

    private function unavailable()
    {
        return response()->json(['message'=>'تعذّر تجهيز النتيجة من خدمة الصور. حاول لاحقًا.'],502)->header('Cache-Control','no-store');
    }
}
