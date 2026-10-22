<?php

namespace App\Http\Controllers;

use App\Models\{FrontOrder, Product, Shop};
use App\Rules\ValidPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogCheckoutController extends Controller
{
    public function store(Request $request, Shop $shop)
    {
        abort_unless($shop->is_active && in_array($shop->catalog_type, ['sweets', 'clothing', 'furniture_appliances'], true), 404);
        abort_unless($shop->is_accepting_orders, 422, 'المحل لا يستقبل طلبات حاليًا.');
        $number = preg_replace('/\D/', '', $shop->whatsapp ?: $shop->phone ?: '');
        abort_unless($number, 422, 'لا يوجد رقم واتساب للمحل.');
        $data = $request->validate([
            'request_token' => ['required', 'uuid'], 'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30', new ValidPhoneNumber()],
            'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'], 'items.*.color' => ['nullable', 'string', 'max:100'],
        ]);
        $token = hash('sha256', $shop->id.':'.$data['request_token']);
        $payloadHash = hash('sha256', json_encode([$data['customer_name'], $data['customer_phone'], $data['items']]));
        $order = DB::transaction(function () use ($shop, $data, $token, $payloadHash) {
            Shop::whereKey($shop->id)->lockForUpdate()->firstOrFail();
            $existing = FrontOrder::where('checkout_token_hash', $token)->first();
            if ($existing) {
                abort_unless(hash_equals($existing->checkout_payload_hash, $payloadHash), 409, 'تغيّرت تفاصيل الطلب. حدّث الصفحة وأعد المحاولة.');
                return $existing;
            }
            $products = Product::where('shop_id', $shop->id)->where('is_active', true)->whereIn('id', array_column($data['items'], 'product_id'))->get()->keyBy('id');
            $items = []; $subtotal = 0;
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']] ?? null;
                if (! $product) throw ValidationException::withMessages(['items' => 'أحد المنتجات غير متاح في هذا المحل. حدّث السلة.']);
                $options = [];
                foreach (['size' => 'sizes', 'color' => 'colors'] as $field => $attribute) {
                    if (empty($item[$field])) continue;
                    $allowed = data_get($product->catalog_attributes, $attribute, []);
                    if (! is_array($allowed)) $allowed = array_map('trim', explode(',', $allowed));
                    if (! in_array($item[$field], $allowed, true)) throw ValidationException::withMessages(['items' => 'خيار المقاس أو اللون لم يعد متاحًا.']);
                    $options[] = $item[$field];
                }
                $price = round((float) ($product->discount_price ?: $product->price), 2);
                $subtotal += (int) round($price * 100) * $item['qty'];
                $items[] = ['product_id' => $product->id, 'name' => $product->localized('name').($options ? ' · '.implode(' · ', $options) : ''), 'qty' => $item['qty'], 'price' => $price];
            }
            $order = new FrontOrder(['shop_id' => $shop->id, 'order_number' => 'SHOP-'.Str::ulid(),
                'customer_name' => $data['customer_name'], 'customer_phone' => $data['customer_phone'],
                'items' => $items, 'subtotal' => $subtotal / 100, 'discount' => 0, 'total' => $subtotal / 100,
                'order_channel' => 'whatsapp', 'status' => 'new', 'payment_status' => 'pending']);
            $order->checkout_token_hash = $token; $order->checkout_payload_hash = $payloadHash; $order->save();
            return $order;
        });
        $lines = ['طلب من '.$shop->name, 'رقم الطلب: '.$order->order_number, 'الاسم: '.$order->customer_name, 'رقم التواصل: '.$order->customer_phone, ''];
        foreach ($order->items as $item) $lines[] = $item['qty'].' × '.$item['name'].' — '.number_format($item['price'] * $item['qty'], 2).' ₪';
        $lines[] = 'المجموع: '.number_format((float) $order->total, 2).' ₪';
        $lines[] = 'للرجوع والتسوق: '.$shop->publicUrl();
        return response()->json(['order_number' => $order->order_number, 'whatsapp_url' => 'https://wa.me/'.$number.'?text='.rawurlencode(implode("\n", $lines))])->header('Cache-Control', 'private, no-store');
    }
}
