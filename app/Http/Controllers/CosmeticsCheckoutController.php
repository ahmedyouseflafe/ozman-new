<?php

namespace App\Http\Controllers;

use App\Models\{Shop, Product};
use App\Services\ShopSignupRewardService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\URL;

class CosmeticsCheckoutController extends Controller
{
    public function store(Request $request, Shop $shop, ShopSignupRewardService $rewards): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'cosmetics' && $shop->slug === 'elegance-perfumes', 404);
        $data = $request->validate([
            'registration_token' => ['required','string','regex:/\A[a-f0-9]{64}\z/'],
            'items' => ['required','array','min:1','max:100'],
            'items.*.product_id' => ['required','integer','distinct'],
            'items.*.qty' => ['required','integer','min:1','max:100'],
        ]);
        $profile = $rewards->registration($shop, $data['registration_token']);
        $products = Product::where('shop_id',$shop->id)->where('is_active',true)
            ->whereIn('id',array_column($data['items'],'product_id'))->get()->keyBy('id');
        $items = []; $subtotal = 0;
        foreach ($data['items'] as $item) {
            $product = $products->get($item['product_id']);
            abort_unless($product,422,'أحد المنتجات غير متاح. حدّث السلة.');
            $price = (float) ($product->discount_price ?? $product->price);
            $items[] = ['product_id'=>$product->id,'name'=>$product->name,'price'=>$price,'qty'=>$item['qty']];
            $subtotal += $price * $item['qty'];
        }
        $order = $rewards->placeOrder($shop,$data['registration_token'],[
            'shop_id'=>$shop->id,'order_number'=>'COS-'.strtoupper((string)\Illuminate\Support\Str::ulid()),
            'customer_name'=>$profile->name,'customer_phone'=>$profile->phone,'customer_whatsapp'=>$profile->phone,
            'customer_address'=>$profile->residence_address,'latitude'=>$profile->latitude,'longitude'=>$profile->longitude,'map_link'=>$profile->map_link,
            'items'=>$items,'subtotal'=>round($subtotal,2),'discount'=>0,'total'=>round($subtotal,2),
            'order_channel'=>'whatsapp','status'=>'new','payment_status'=>'whatsapp_order',
        ]);
        $message = 'طلب '.$order->order_number.' — '.$shop->name."\n";
        \App\Models\AdminNotification::create([
            'shop_id'=>$shop->id,'type'=>'front_order_created','title'=>'طلب جديد من الواجهة',
            'message'=>$order->customer_name.' — '.$order->total.' ₪',
            'subject_type'=>\App\Models\FrontOrder::class,'subject_id'=>$order->id,
            'url'=>route('front-orders.index'),'data'=>['order_number'=>$order->order_number],
        ]);
        foreach ($items as $item) $message .= $item['name'].' × '.$item['qty']."\n";
        $message .= 'المجموع: '.$order->total.' ₪';
        if ($order->reward_label) $message .= "\n".'هدية التسجيل: '.$order->reward_label;
        $phone = preg_replace('/\D+/','',$shop->whatsapp ?: $shop->phone);
        return response()->json([
            'ok'=>true,'order_id'=>$order->id,'order_number'=>$order->order_number,'total'=>(float)$order->total,
            'whatsapp_url'=>$phone ? 'https://wa.me/'.$phone.'?text='.rawurlencode($message) : null,
            'purchase_wheel'=>RestaurantPurchaseWheelController::eligible($order) ? [
                'url'=>URL::signedRoute('restaurant.purchase-wheel.show',$order),
                'spin_url'=>URL::signedRoute('restaurant.purchase-wheel.spin',$order),
            ] : null,
        ]);
    }
}
