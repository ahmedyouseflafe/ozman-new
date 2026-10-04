<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\VisitorRegistration;
use App\Rules\ValidPhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantCustomerRegistrationController extends Controller
{
    public function store(Request $request, Shop $shop): JsonResponse
    {
        abort_unless($shop->is_active && $shop->catalog_type === 'restaurant' && $shop->slug === 'bankai-sushi', 404);

        $request->validate(['whatsapp' => ['required', 'string', 'max:30']]);
        $phone = strtr((string) $request->input('whatsapp', ''), array_combine(
            preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789'),
        ));
        $phone = preg_replace('/[\s()+-]/u', '', $phone);
        $request->merge(['whatsapp' => preg_replace('/^00/', '', $phone)]);

        $data = $request->validate([
            'registration_token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/\A[0-9]+\z/', new ValidPhoneNumber()],
            'address' => ['required', 'string', 'max:500'],
            'location_deferred' => ['required', 'boolean'],
            'latitude' => [Rule::requiredIf(! $request->boolean('location_deferred')), 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [Rule::requiredIf(! $request->boolean('location_deferred')), 'nullable', 'numeric', 'between:-180,180'],
        ]);

        $deferred = (bool) $data['location_deferred'];
        $latitude = $deferred ? null : $data['latitude'];
        $longitude = $deferred ? null : $data['longitude'];

        // The browser's random secret makes retries idempotent. A phone number is
        // not proof of ownership and must never authorize editing another record.
        // Store a digest, so the public token cannot itself be used to edit details.
        $token = hash('sha256', 'restaurant-customer:'.$shop->id.':'.$data['registration_token']);
        $details = [
            'name' => $data['name'], 'phone' => $data['whatsapp'], 'residence_address' => $data['address'],
            'latitude' => $latitude, 'longitude' => $longitude,
            'map_link' => $deferred ? null : 'https://www.google.com/maps?q='.$latitude.','.$longitude,
        ];
        $registration = VisitorRegistration::firstOrCreate(['public_token' => $token], [
            'shop_id' => $shop->id, 'type' => 'customer', 'status' => 'approved',
            'marketing_source' => 'restaurant_welcome', 'approved_at' => now(),
            ...$details,
        ]);
        abort_unless($registration->type === 'customer' && (int) $registration->shop_id === (int) $shop->id
            && $registration->marketing_source === 'restaurant_welcome', 404);
        $registration->update($details);

        return response()->json(['registered' => true], 200, ['Cache-Control' => 'no-store']);
    }
}
