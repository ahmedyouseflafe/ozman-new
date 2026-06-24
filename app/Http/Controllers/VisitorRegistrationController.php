<?php

namespace App\Http\Controllers;

use App\Models\VisitorRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisitorRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
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

        $registration = VisitorRegistration::create($validated);

        return response()->json([
            'message' => __('تم حفظ بيانات التسجيل بنجاح'),
            'registration_id' => $registration->id,
        ], 201);
    }
}
