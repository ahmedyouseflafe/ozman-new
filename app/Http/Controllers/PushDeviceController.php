<?php

namespace App\Http\Controllers;

use App\Models\PushDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'in:android,ios'],
        ]);

        PushDevice::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()?->id,
                'platform' => $data['platform'] ?? 'android',
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['registered' => true]);
    }
}
