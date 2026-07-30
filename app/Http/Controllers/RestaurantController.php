<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Shop;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function dashboard(Request $request, Shop $shop): View
    {
        $this->authorizeShop($request, $shop);
        abort_unless($shop->catalog_type === 'restaurant', 404);

        return view('admin.restaurant.dashboard', [
            'shop' => $shop,
            'tables' => $shop->restaurantTables()->latest()->get(),
            'orders' => FrontOrder::with('restaurantTable')->where('shop_id', $shop->id)
                ->whereNotNull('order_type')->latest()->limit(100)->get(),
        ]);
    }

    public function storeTable(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeShop($request, $shop);
        abort_unless($shop->catalog_type === 'restaurant', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $data['code'] = Str::lower(Str::random(32));
        $data['is_active'] = true;
        $shop->restaurantTables()->create($data);
        return back()->with('status', 'تمت إضافة الطاولة وإنشاء QR خاص بها.');
    }

    public function destroyTable(Request $request, RestaurantTable $table): RedirectResponse
    {
        $this->authorizeShop($request, $table->shop);
        $table->delete();
        return back()->with('status', 'تم حذف الطاولة.');
    }

    public function tableQr(RestaurantTable $table): Response
    {
        abort_unless($table->is_active && $table->shop?->catalog_type === 'restaurant', 404);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(500, 2), new SvgImageBackEnd())))
            ->writeString(route('restaurant.table', [$table->shop, $table->code]));
        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function menu(Shop $shop, ?string $tableCode = null): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'restaurant', 404);
        $table = $tableCode ? $shop->restaurantTables()->where('code', $tableCode)->where('is_active', true)->firstOrFail() : null;
        $products = Product::with('category')->where('shop_id', $shop->id)->where('is_active', true)->get();
        return view('front.restaurant_menu', compact('shop', 'table', 'products'));
    }

    public function storeOrder(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'restaurant', 404);
        $data = $request->validate([
            'order_type' => ['required', 'in:dine_in,delivery,pickup'],
            'table_code' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.addons' => ['nullable', 'array', 'max:30'],
            'items.*.addons.*' => ['string', 'max:100'],
            'items.*.excluded' => ['nullable', 'array', 'max:30'],
            'items.*.excluded.*' => ['string', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $table = null;
        if ($data['order_type'] === 'dine_in') {
            $table = $shop->restaurantTables()->where('code', $data['table_code'] ?? '')->where('is_active', true)->firstOrFail();
        } elseif (!filled($data['customer_phone'])) {
            return response()->json(['message' => 'رقم الهاتف مطلوب لطلبات التوصيل والاستلام.'], 422);
        }
        if ($data['order_type'] === 'delivery' && !filled($data['customer_address'])) {
            return response()->json(['message' => 'عنوان التوصيل مطلوب.'], 422);
        }

        $products = Product::where('shop_id', $shop->id)->where('is_active', true)
            ->whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
        $items = [];
        $subtotal = 0;
        foreach ($data['items'] as $row) {
            $product = $products->get((int) $row['product_id']);
            abort_unless($product, 422, 'إحدى الوجبات غير متاحة.');
            $attributes = $product->catalog_attributes ?? [];
            $sizePrices = $this->pricedOptions($attributes['meal_size_prices'] ?? []);
            $addonPrices = $this->pricedOptions($attributes['addon_prices'] ?? []);
            $unit = (float) ($product->discount_price ?: $product->price);
            $size = $row['size'] ?? null;
            if ($size && array_key_exists($size, $sizePrices)) $unit = $sizePrices[$size];
            $addons = collect($row['addons'] ?? [])->filter(fn($name) => array_key_exists($name, $addonPrices))->values();
            $unit += $addons->sum(fn($name) => $addonPrices[$name]);
            $line = round($unit * (int) $row['qty'], 2);
            $subtotal += $line;
            $items[] = [
                'product_id' => $product->id, 'name' => $product->name, 'price' => $unit,
                'qty' => (int) $row['qty'], 'size' => $size, 'addons' => $addons->all(),
                'excluded' => array_values($row['excluded'] ?? []), 'notes' => $row['notes'] ?? null, 'line_total' => $line,
            ];
        }

        $order = FrontOrder::create([
            'shop_id' => $shop->id, 'restaurant_table_id' => $table?->id,
            'order_number' => 'RST-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
            'customer_name' => $data['customer_name'], 'customer_phone' => $data['customer_phone'] ?? null,
            'customer_address' => $data['customer_address'] ?? null, 'customer_notes' => $data['customer_notes'] ?? null,
            'items' => $items, 'subtotal' => $subtotal, 'total' => $subtotal, 'discount' => 0,
            'order_channel' => 'restaurant', 'order_type' => $data['order_type'],
            'payment_status' => 'pending', 'status' => 'new',
        ]);
        return response()->json(['ok' => true, 'order_id' => $order->id, 'order_number' => $order->order_number]);
    }

    public function status(Request $request, FrontOrder $order): RedirectResponse
    {
        abort_unless($order->shop && $order->order_type, 404);
        $this->authorizeShop($request, $order->shop);
        $data = $request->validate(['status' => ['required', 'in:new,preparing,ready,completed,cancelled']]);
        $order->update($data);
        return back()->with('status', 'تم تحديث حالة الطلب.');
    }

    private function pricedOptions(array $values): array
    {
        return collect($values)->mapWithKeys(function ($value) {
            [$name, $price] = array_pad(explode(':', (string) $value, 2), 2, null);
            $name = trim($name);
            return $name !== '' && is_numeric($price) ? [$name => max(0, (float) $price)] : [];
        })->all();
    }

    private function authorizeShop(Request $request, Shop $shop): void
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true)), 403);
    }
}
