<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopOutlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopOutletController extends Controller
{
    public function index(Shop $shop): View
    {
        $this->authorizeOutlets($shop);

        return view('admin.shop_outlets.index', [
            'shop' => $shop,
            'outlets' => $shop->outlets()->orderBy('region')->orderBy('sort_order')->orderBy('name')->get(),
            'editing' => null,
        ]);
    }

    public function store(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorizeOutlets($shop);
        $shop->outlets()->create($this->validated($request));

        return redirect()->route('shop-outlets.index', $shop)->with('status', 'تمت إضافة نقطة البيع.');
    }

    public function edit(Shop $shop, ShopOutlet $outlet): View
    {
        $this->authorizeOutlets($shop);

        return view('admin.shop_outlets.index', [
            'shop' => $shop,
            'outlets' => $shop->outlets()->orderBy('region')->orderBy('sort_order')->orderBy('name')->get(),
            'editing' => $outlet,
        ]);
    }

    public function update(Request $request, Shop $shop, ShopOutlet $outlet): RedirectResponse
    {
        $this->authorizeOutlets($shop);
        $outlet->update($this->validated($request));

        return redirect()->route('shop-outlets.index', $shop)->with('status', 'تم تحديث نقطة البيع.');
    }

    public function destroy(Shop $shop, ShopOutlet $outlet): RedirectResponse
    {
        $this->authorizeOutlets($shop);
        $outlet->delete();

        return redirect()->route('shop-outlets.index', $shop)->with('status', 'تم حذف نقطة البيع.');
    }

    private function authorizeOutlets(Shop $shop): void
    {
        $this->authorizeShopAccess($shop);
        abort_unless($shop->catalog_type === 'krembo', 404);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'map_url' => ['nullable', 'url:http,https', 'max:1000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], [], [
            'name' => 'اسم المحل',
            'region' => 'المنطقة',
            'address' => 'العنوان',
            'map_url' => 'رابط الخريطة',
            'latitude' => 'خط العرض',
            'longitude' => 'خط الطول',
            'phone' => 'الرقم',
            'sort_order' => 'الترتيب',
        ]);

        $data['region'] = trim($data['region']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
