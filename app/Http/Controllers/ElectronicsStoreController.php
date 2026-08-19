<?php

namespace App\Http\Controllers;

use App\Models\FrontOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Rules\ValidPhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ElectronicsStoreController extends Controller
{
    public function index(Request $request, Shop $shop): View
    {
        $this->ensureStore($shop);
        $query = Product::query()
            ->with(['category', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
            ->where('shop_id', $shop->id)
            ->where('is_active', true);

        $this->applyFilters($query, $request);
        $products = $query->orderByDesc('is_featured')->latest()->paginate(24)->withQueryString();
        $filterSource = Product::query()->where('shop_id', $shop->id)->where('is_active', true);
        $compareIds = collect($request->session()->get($this->compareKey($shop), []))->map(fn ($id) => (int) $id)->take(3);

        return view('front.electronics.index', [
            'shop' => $shop,
            'products' => $products,
            'categories' => $shop->categories()->where('is_active', true)->get(),
            'brands' => (clone $filterSource)->get()->pluck('catalog_attributes.brand')->filter()->unique()->sort()->values(),
            'storages' => ProductVariant::query()->whereHas('product', fn ($q) => $q->where('shop_id', $shop->id)->where('is_active', true))
                ->whereNotNull('storage')->distinct()->orderBy('storage')->pluck('storage'),
            'comparisonProducts' => Product::query()->where('shop_id', $shop->id)->whereIn('id', $compareIds)->get()
                ->sortBy(fn ($product) => $compareIds->search($product->id))->values(),
        ]);
    }

    public function toggleCompare(Request $request, Shop $shop, Product $product): RedirectResponse
    {
        $this->ensureStore($shop);
        abort_unless($product->shop_id === $shop->id && $product->is_active, 404);
        $key = $this->compareKey($shop);
        $ids = collect($request->session()->get($key, []))->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->contains($product->id)) {
            $ids = $ids->reject(fn ($id) => $id === $product->id)->values();
        } elseif ($ids->count() < 3) {
            $ids->push($product->id);
        }
        $request->session()->put($key, $ids->all());

        return back()->with('compare_message', $ids->contains($product->id) ? 'تمت إضافة الجهاز للمقارنة.' : 'تمت إزالة الجهاز من المقارنة.');
    }

    public function clearCompare(Request $request, Shop $shop): RedirectResponse
    {
        $this->ensureStore($shop);
        $request->session()->forget($this->compareKey($shop));

        return back();
    }

    public function compare(Request $request, Shop $shop): View|RedirectResponse
    {
        $this->ensureStore($shop);
        $ids = collect($request->session()->get($this->compareKey($shop), []))->map(fn ($id) => (int) $id)->take(3);
        $products = Product::query()->with(['variants' => fn ($query) => $query->where('is_active', true)])
            ->where('shop_id', $shop->id)->where('is_active', true)->whereIn('id', $ids)->get()
            ->sortBy(fn ($product) => $ids->search($product->id))->values();
        if ($products->count() < 2) {
            return redirect()->route('electronics.store', $shop)->with('compare_message', 'اختر جهازين على الأقل للمقارنة.');
        }

        return view('front.electronics.compare', compact('shop', 'products'));
    }

    public function show(Shop $shop, Product $product): View
    {
        $this->ensureStore($shop);
        abort_unless($product->shop_id === $shop->id && $product->is_active, 404);
        $product->load(['category', 'images', 'variants' => fn ($query) => $query->where('is_active', true)->orderBy('storage')->orderBy('color')]);
        $related = Product::query()->with('variants')->where('shop_id', $shop->id)->where('is_active', true)
            ->whereKeyNot($product->id)->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))->limit(4)->get();

        return view('front.electronics.show', compact('shop', 'product', 'related'));
    }

    public function order(Request $request, Shop $shop): JsonResponse
    {
        $this->ensureStore($shop);
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:60', new ValidPhoneNumber],
            'customer_address' => ['required', 'string', 'max:1000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $order = DB::transaction(function () use ($data, $shop) {
            $items = [];
            $subtotal = 0.0;
            foreach ($data['items'] as $row) {
                $variant = ProductVariant::query()->with('product')->lockForUpdate()->find($row['variant_id']);
                abort_unless($variant && $variant->is_active && $variant->product?->is_active && $variant->product->shop_id === $shop->id, 422, 'أحد خيارات الأجهزة غير متاح.');
                $quantity = (int) $row['qty'];
                abort_if($variant->quantity < $quantity, 422, 'الكمية المطلوبة غير متوفرة لأحد الخيارات.');
                $unitPrice = (float) ($variant->price ?? $variant->product->discount_price ?? $variant->product->price);
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineTotal;
                $items[] = [
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'name' => $variant->product->name,
                    'brand' => data_get($variant->product->catalog_attributes, 'brand'),
                    'model' => data_get($variant->product->catalog_attributes, 'model'),
                    'condition' => data_get($variant->product->catalog_attributes, 'condition'),
                    'storage' => $variant->storage,
                    'ram' => $variant->ram,
                    'color' => $variant->color,
                    'color_name' => $variant->color_name ?: $variant->color,
                    'sku' => $variant->sku,
                    'price' => $unitPrice,
                    'qty' => $quantity,
                    'line_total' => $lineTotal,
                ];
                $variant->decrement('quantity', $quantity);
                $variant->product->update(['quantity' => $variant->product->variants()->where('is_active', true)->sum('quantity')]);
            }

            return FrontOrder::create([
                'shop_id' => $shop->id,
                'order_number' => 'ELC-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_address' => $data['customer_address'],
                'customer_notes' => $data['customer_notes'] ?? null,
                'items' => $items,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'order_channel' => 'electronics',
                'payment_status' => 'pending',
                'status' => 'new',
            ]);
        });

        return response()->json(['ok' => true, 'order_id' => $order->id, 'order_number' => $order->order_number]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query->when($request->filled('q'), fn ($q) => $q->where(fn ($nested) => $nested
            ->where('name', 'like', '%' . $request->string('q') . '%')
            ->orWhere('catalog_attributes->brand', 'like', '%' . $request->string('q') . '%')
            ->orWhere('catalog_attributes->model', 'like', '%' . $request->string('q') . '%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('brand'), fn ($q) => $q->where('catalog_attributes->brand', $request->string('brand')))
            ->when($request->filled('condition'), fn ($q) => $q->where('catalog_attributes->condition', $request->string('condition')))
            ->when($request->filled('min_price'), fn ($q) => $q->whereHas('variants', fn ($v) => $v
                ->where('is_active', true)->where('quantity', '>', 0)
                ->where('product_variants.price', '>=', $request->float('min_price'))))
            ->when($request->filled('max_price'), fn ($q) => $q->whereHas('variants', fn ($v) => $v
                ->where('is_active', true)->where('quantity', '>', 0)
                ->where('product_variants.price', '<=', $request->float('max_price'))))
            ->when($request->filled('storage'), fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('storage', $request->string('storage'))->where('quantity', '>', 0)));
    }

    private function ensureStore(Shop $shop): void
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'electronics', 404);
    }

    private function compareKey(Shop $shop): string
    {
        return 'electronics_compare_'.$shop->id;
    }
}
