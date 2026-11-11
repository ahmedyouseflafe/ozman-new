<?php

namespace App\Services;

use App\Http\Controllers\Api\V1\Presenter;
use App\Models\AdminNotification;
use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PushDevice;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Orders from the app for every shop that is not a restaurant (clothing, cosmetics, electronics,
 * sweets, furniture…). Prices, sizes, colours and stock are checked here, never taken from the app.
 * The order lands in the shop's normal orders page, and the shop owner gets a notification.
 */
class AppShopOrderService
{
    public function __construct(private FirebaseMessagingService $firebase)
    {
    }

    /**
     * @param  array{customer_name:string, customer_phone:string, order_type:string, customer_address?:?string,
     *               customer_notes?:?string, items:array<int, array{product_id:int, qty:int, variant_id?:?int, size?:?string, color?:?string}>}  $data
     */
    public function place(Shop $shop, User $user, array $data, ?string $pushToken): FrontOrder
    {
        $order = DB::transaction(function () use ($shop, $user, $data, $pushToken) {
            $products = Product::query()
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->whereIn('id', array_column($data['items'], 'product_id'))
                ->with('variants')
                ->get()
                ->keyBy('id');

            $items = [];
            $subtotalCents = 0;
            foreach ($data['items'] as $row) {
                $product = $products->get($row['product_id']);
                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'أحد المنتجات لم يعد متاحًا في هذا المحل. حدّث السلة.']);
                }
                $quantity = (int) $row['qty'];
                [$price, $details, $variantId] = $this->priceAndOptions($shop, $product, $row, $quantity);
                $subtotalCents += (int) round($price * 100) * $quantity;
                $items[] = array_filter([
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'name' => $product->localized('name').($details ? ' · '.implode(' · ', $details) : ''),
                    'price' => $price,
                    'qty' => $quantity,
                    'line_total' => round($price * $quantity, 2),
                    'image_url' => Presenter::media($product->main_image),
                ], fn ($value) => $value !== null);
            }

            $notes = trim(implode("\n", array_filter([
                $data['order_type'] === 'delivery' ? 'طريقة الاستلام: توصيل للعنوان' : 'طريقة الاستلام: استلام من المحل',
                $data['customer_notes'] ?? null,
            ])));
            $subtotal = $subtotalCents / 100;

            return FrontOrder::create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'order_number' => 'APP-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_whatsapp' => $data['customer_phone'],
                'customer_address' => $data['order_type'] === 'delivery' ? ($data['customer_address'] ?? null) : null,
                'customer_notes' => $notes,
                'items' => $items,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'order_channel' => 'app',
                'customer_push_token' => $pushToken,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'status' => 'new',
            ]);
        });

        $this->tellTheShop($shop, $order);

        return $order;
    }

    /** @return array{0: float, 1: list<string>, 2: ?int} */
    private function priceAndOptions(Shop $shop, Product $product, array $row, int $quantity): array
    {
        $price = (float) ($product->discount_price && $product->discount_price < $product->price ? $product->discount_price : $product->price);
        $details = [];
        $variantId = null;

        $variants = $product->variants->where('is_active', true);
        if ($variants->isNotEmpty()) {
            $variant = $variants->firstWhere('id', (int) ($row['variant_id'] ?? 0));
            if (! $variant) {
                throw ValidationException::withMessages(['items' => 'اختار الخيار (المقاس/اللون/السعة) لـ «'.$product->localized('name').'».']);
            }
            // Electronics keep real stock per option on the website, so the app respects it too.
            if ($shop->catalog_type === 'electronics') {
                $locked = ProductVariant::query()->lockForUpdate()->find($variant->id);
                if ($locked->quantity < $quantity) {
                    throw ValidationException::withMessages(['items' => 'الكمية المطلوبة غير متوفرة لـ «'.$product->localized('name').'».']);
                }
                $locked->decrement('quantity', $quantity);
            }
            if ($variant->price !== null && (float) $variant->price > 0) {
                $price = (float) $variant->price;
            }
            $details = array_values(array_filter([
                $variant->size, $variant->color_name ?: $variant->color, $variant->storage, $variant->ram,
            ]));
            $variantId = $variant->id;
        }

        foreach (['size' => 'sizes', 'color' => 'colors'] as $field => $attribute) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            if (! in_array($value, Presenter::optionList($product, $attribute), true)) {
                throw ValidationException::withMessages(['items' => 'خيار المقاس أو اللون لم يعد متاحًا.']);
            }
            $details[] = $value;
        }

        return [round($price, 2), $details, $variantId];
    }

    /** The shop owner hears about the order on the phone app, the browser and the dashboard bell. */
    private function tellTheShop(Shop $shop, FrontOrder $order): void
    {
        $title = 'طلب جديد من التطبيق · '.$shop->name;
        $body = "الطلب {$order->order_number} من {$order->customer_name} بقيمة {$order->total} شيكل";
        $url = route('shop-orders.show', [$shop, $order]);
        $data = ['type' => 'shop_order', 'shop_id' => $shop->id, 'order_id' => $order->id, 'order_number' => $order->order_number];

        try {
            AdminNotification::create([
                'shop_id' => $shop->id, 'type' => 'front_order_created', 'title' => $title, 'message' => $body,
                'subject_type' => FrontOrder::class, 'subject_id' => $order->id, 'url' => $url,
                'data' => ['order_number' => $order->order_number],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($shop->user_id) {
            try {
                $tokens = PushDevice::query()->where('user_id', $shop->user_id)->pluck('token');
                if ($tokens->isNotEmpty()) {
                    $this->firebase->sendToTokens($tokens, $title, $body, $url, $data);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            app(WebPushService::class)->sendToShop($shop, $title, $body, $url, $data);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** Tells the customer's phone that the shop changed the order's status. */
    public function notifyCustomer(FrontOrder $order): void
    {
        if (! filled($order->customer_push_token) || ! $order->shop) {
            return;
        }
        $title = match ($order->status) {
            'preparing' => 'المحل بلّش يجهّز طلبك 📦',
            'ready' => 'طلبك جاهز 🎉',
            'out_for_delivery' => 'طلبك طلع للتوصيل 🛵',
            'delivered', 'completed' => 'وصل طلبك ✅',
            'cancelled' => 'تم إلغاء الطلب',
            default => 'تحديث على طلبك',
        };
        $body = "طلبك {$order->order_number} من {$order->shop->name}: {$order->statusLabel()}.";

        try {
            $this->firebase->sendToTokens([$order->customer_push_token], $title, $body, $order->shop->publicUrl(), [
                'type' => 'shop_order_status', 'shop_id' => $order->shop_id, 'order_id' => $order->id,
                'order_number' => $order->order_number, 'status' => $order->status,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
