<?php

namespace App\Http\Controllers;

use App\Models\{Shop, Product};
use App\Services\CosmeticsGuestRewardService;
use App\Rules\ValidPhoneNumber;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\URL;

class CosmeticsCheckoutController extends Controller
{
    public function store(Request $request, Shop $shop, CosmeticsGuestRewardService $rewards): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'cosmetics' && $shop->slug === 'elegance-perfumes', 404);
        foreach (['customer_phone', 'customer_whatsapp'] as $field) {
            if (is_string($request->input($field))) {
                $phone = strtr($request->input($field), array_combine(
                    preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY), str_split('01234567890123456789')
                ));
                $request->merge([$field => preg_replace('/^00/', '', preg_replace('/[\s()+-]/u', '', $phone))]);
            }
        }
        $data = $request->validate([
            'registration_token' => ['nullable','string','regex:/\A[a-f0-9]{64}\z/'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/\A[0-9]+\z/', new ValidPhoneNumber()],
            'customer_whatsapp' => ['required', 'string', 'max:30', 'regex:/\A[0-9]+\z/', new ValidPhoneNumber()],
            'customer_address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'skip_signup_reward' => ['sometimes', 'boolean'],
            'items' => ['required','array','min:1','max:100'],
            'items.*.product_id' => ['required','integer','distinct'],
            'items.*.qty' => ['required','integer','min:1','max:100'],
        ]);
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
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $order = $rewards->checkout($shop,$data['registration_token'] ?? null,[
            'shop_id'=>$shop->id,'order_number'=>'COS-'.strtoupper((string)\Illuminate\Support\Str::ulid()),
            'customer_name'=>$data['customer_name'],'customer_phone'=>$data['customer_phone'],'customer_whatsapp'=>$data['customer_whatsapp'],
            'customer_address'=>$data['customer_address'],'latitude'=>$latitude,'longitude'=>$longitude,
            'map_link'=>$latitude !== null && $longitude !== null ? 'https://www.google.com/maps?q='.$latitude.','.$longitude : null,
            'items'=>$items,'subtotal'=>round($subtotal,2),'discount'=>0,'total'=>round($subtotal,2),
            'order_channel'=>'whatsapp','status'=>'new','payment_status'=>'whatsapp_order',
        ], (bool) ($data['skip_signup_reward'] ?? false));
        $message = 'طلب '.$order->order_number.' — '.$shop->name."\n";
        $message .= 'الاسم: '.$order->customer_name."\n".'رقم التواصل: '.$order->customer_phone."\n"
            .'واتساب: '.$order->customer_whatsapp."\n".'العنوان: '.$order->customer_address."\n";
        if ($order->map_link) $message .= 'الموقع: '.$order->map_link."\n";
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
