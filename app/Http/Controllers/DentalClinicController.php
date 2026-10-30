<?php
namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\View\View;

class DentalClinicController extends Controller
{
    public function show(Shop $shop): View
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'dental', 404);
        $categories = $shop->categories()->where('is_active', true)
            ->with(['products' => fn ($q) => $q->where('is_active', true)->where('shop_id', $shop->id)->orderBy('name')])
            ->orderBy('name')->get();
        $phone = preg_replace('/[^+0-9]/', '', $shop->phone ?? '');
        $whatsapp = preg_replace('/\D/', '', $shop->whatsapp ?? '');
        if (str_starts_with($whatsapp, '00')) $whatsapp = substr($whatsapp, 2);
        // Do not guess a country code for local numbers.
        $whatsappUrl = $whatsapp && !str_starts_with($whatsapp, '0')
            ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode('مرحبًا، أرغب بالاستفسار عن زيارة العيادة.') : null;
        $mapQuery = $shop->latitude !== null && $shop->longitude !== null
            ? $shop->latitude.','.$shop->longitude : trim(($shop->city ?? '').' '.($shop->address ?? ''));
        $mapUrl = $mapQuery ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($mapQuery) : null;
        return view('front.dental_clinic', compact('shop', 'categories', 'phone', 'whatsappUrl', 'mapUrl'));
    }
}
