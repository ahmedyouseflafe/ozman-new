<?php

namespace App\Http\Controllers;

use App\Models\OfferPushSubscription;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferPushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'subscription.endpoint' => ['required', 'url:https', 'max:2048'],
            'subscription.keys.p256dh' => ['required', 'string', 'max:512'],
            'subscription.keys.auth' => ['required', 'string', 'max:255'],
            'subscription.contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ]);

        $sourceShopId = $data['source_shop_id'] ?? null;
        if ($sourceShopId !== null) {
            Shop::query()->whereKey($sourceShopId)->where('is_active', true)->firstOrFail();
        }

        $endpoint = $data['subscription']['endpoint'];
        $endpointHash = hash('sha256', $endpoint);

        OfferPushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => $request->user()?->id,
                'source_shop_id' => $sourceShopId,
                'endpoint' => $endpoint,
                'public_key' => $data['subscription']['keys']['p256dh'],
                'auth_token' => $data['subscription']['keys']['auth'],
                'content_encoding' => $data['subscription']['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'last_seen_at' => now(),
            ],
        );

        $request->session()->put('offer_web_push_endpoint_hash', $endpointHash);

        return response()->json(['registered' => true, 'audience' => 'all_offers']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url:https', 'max:2048']]);

        OfferPushSubscription::query()
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        $request->session()->forget('offer_web_push_endpoint_hash');

        return response()->json(['deleted' => true]);
    }
}
