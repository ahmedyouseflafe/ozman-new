<?php

namespace App\Http\Controllers;

use App\Models\WebPushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebPushSubscriptionController extends Controller
{
    public function publicKey(WebPushService $webPush): JsonResponse
    {
        return response()->json(['public_key' => $webPush->publicKey()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'subscription.endpoint' => ['required', 'url:https', 'max:2048'],
            'subscription.keys.p256dh' => ['required', 'string', 'max:512'],
            'subscription.keys.auth' => ['required', 'string', 'max:255'],
            'subscription.contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ]);

        $user = $request->user();
        abort_unless($user?->isShopOwner(), 403);
        $shop = $user->shops()->whereKey($data['shop_id'])->where('is_active', true)->firstOrFail();
        $endpoint = $data['subscription']['endpoint'];
        $endpointHash = hash('sha256', $endpoint);

        WebPushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'endpoint' => $endpoint,
                'public_key' => $data['subscription']['keys']['p256dh'],
                'auth_token' => $data['subscription']['keys']['auth'],
                'content_encoding' => $data['subscription']['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'last_seen_at' => now(),
            ],
        );

        $request->session()->put('merchant_web_push_endpoint_hash', $endpointHash);

        return response()->json(['registered' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url:https', 'max:2048']]);
        $user = $request->user();
        abort_unless($user?->isShopOwner(), 403);

        WebPushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        $request->session()->forget('merchant_web_push_endpoint_hash');

        return response()->json(['deleted' => true]);
    }
}
