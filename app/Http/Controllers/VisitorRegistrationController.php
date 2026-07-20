<?php

namespace App\Http\Controllers;

use App\Models\DistributorMarketer;
use App\Models\Shop;
use App\Models\User;
use App\Models\VisitorRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VisitorRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'distributor_marketer_id' => ['nullable', 'integer', 'exists:distributor_marketers,id'],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'marketing_source' => ['nullable', 'string', 'max:40'],
            'type' => ['required', Rule::in(['customer', 'merchant'])],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'shop_name' => ['required_if:type,merchant', 'nullable', 'string', 'max:255'],
            'tax_file' => ['required_if:type,merchant', 'nullable', 'string', 'max:255'],
            'business_location' => ['required_if:type,merchant', 'nullable', 'string', 'max:1000'],
            'residence_address' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'map_link' => ['required', 'string', 'max:1000'],
        ]);

        $registration = DB::transaction(function () use ($validated) {
            $marketer = null;

            if (! empty($validated['distributor_marketer_id'])) {
                $marketer = DistributorMarketer::query()
                    ->whereKey($validated['distributor_marketer_id'])
                    ->where('is_active', true)
                    ->with('distributor')
                    ->first();

                if ($marketer?->distributor?->is_active) {
                    $validated['distributor_id'] = $marketer->distributor_id;
                    $validated['marketing_source'] = 'marketer';
                } else {
                    unset($validated['distributor_marketer_id']);
                }
            }

            $registration = VisitorRegistration::create([
                ...$validated,
                'status' => ($validated['type'] ?? null) === 'merchant' ? 'pending' : 'approved',
                'public_token' => Str::random(64),
                'approved_at' => ($validated['type'] ?? null) === 'merchant' ? null : now(),
            ]);

            return $registration;
        });

        return response()->json([
            'message' => __('تم حفظ بيانات التسجيل بنجاح'),
            'registration_id' => $registration->id,
            'shop_id' => $registration->shop_id,
            'type' => $registration->type,
            'status' => $registration->status,
            'registration_token' => $registration->public_token,
            'whatsapp_message' => $registration->type === 'merchant' ? $this->merchantWhatsappMessage($registration) : null,
        ], 201);
    }

    public function status(string $token): JsonResponse
    {
        $registration = VisitorRegistration::query()
            ->where('public_token', $token)
            ->where('type', 'merchant')
            ->firstOrFail();

        return response()->json([
            'status' => $registration->status,
            'approved' => $registration->status === 'approved',
        ]);
    }

    private function merchantWhatsappMessage(VisitorRegistration $registration): string
    {
        return implode("\n", [
            'طلب اعتماد صاحب متجر جديد في Ozman',
            'رقم الطلب: ' . $registration->id,
            'الاسم: ' . $registration->name,
            'الهاتف: ' . $registration->phone,
            'اسم المتجر: ' . ($registration->shop_name ?: '-'),
            'الملف الضريبي: ' . ($registration->tax_file ?: '-'),
            'مكان السكن: ' . $registration->residence_address,
            'موقع المحل: ' . ($registration->map_link ?: $registration->business_location ?: '-'),
            'الحالة: قيد المراجعة',
        ]);
    }

    private function createOrUpdateMerchantShop(VisitorRegistration $registration, DistributorMarketer $marketer): Shop
    {
        $phoneDigits = preg_replace('/\D+/', '', (string) $registration->phone);
        $ownerEmail = $phoneDigits
            ? "merchant-{$phoneDigits}@ozman.local"
            : 'merchant-' . Str::lower(Str::random(12)) . '@ozman.local';

        $owner = User::firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => $registration->name,
                'phone' => $registration->phone,
                'password' => Hash::make(Str::random(32)),
                'role' => 'shop_owner',
                'is_active' => true,
            ]
        );

        $shopName = $registration->shop_name ?: $registration->name;
        $shop = Shop::query()
            ->where('distributor_marketer_id', $marketer->id)
            ->where(function ($query) use ($registration, $shopName) {
                $query->where('name', $shopName);

                if (filled($registration->phone)) {
                    $query->orWhere('phone', $registration->phone);
                    $query->orWhere('whatsapp', $registration->phone);
                }
            })
            ->first();

        $data = [
            'user_id' => $owner->id,
            'distributor_marketer_id' => $marketer->id,
            'name' => $shopName,
            'phone' => $registration->phone,
            'whatsapp' => $registration->phone,
            'address' => $registration->business_location ?: $registration->residence_address,
            'latitude' => $registration->latitude,
            'longitude' => $registration->longitude,
            'is_active' => true,
        ];

        if ($shop) {
            $shop->update($data);
            return $shop;
        }

        return Shop::create([
            ...$data,
            'slug' => $this->uniqueShopSlug($shopName),
        ]);
    }

    private function uniqueShopSlug(string $value): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'merchant-shop';
        $slug = $base;
        $counter = 2;

        while (Shop::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
