<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Shop, StorePointCode, User};
use App\Services\{LoyaltyPoints, StorePoints};
use Illuminate\Http\{JsonResponse, Request};

/** Points at the till: the shop makes a one-time QR for an amount, the customer scans it. */
class StorePointsController extends Controller
{
    public function store(Request $request, Shop $shop, StorePoints $points): JsonResponse
    {
        $this->authorizeShop($request->user(), $shop);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01', 'max:'.StorePoints::MAX_AMOUNT]]);

        return response()->json(['code' => $points->present($points->create($shop, $request->user(), (float) $data['amount']), true)], 201);
    }

    /** The shop's screen asks every few seconds whether the customer scanned. */
    public function show(Request $request, StorePointCode $code, StorePoints $points): JsonResponse
    {
        $this->authorizeShop($request->user(), Shop::findOrFail($code->shop_id));

        return response()->json(['code' => $points->present($code)]);
    }

    public function destroy(Request $request, StorePointCode $code, StorePoints $points): JsonResponse
    {
        $this->authorizeShop($request->user(), Shop::findOrFail($code->shop_id));
        if ($code->status() === 'open') {
            $code->update(['cancelled_at' => now()]);
        }

        return response()->json(['code' => $points->present($code->fresh())]);
    }

    public function claim(Request $request, StorePoints $points, LoyaltyPoints $loyalty): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:2000']]);
        $code = $points->claim($request->user(), self::token($data['token']));

        return response()->json([
            'points' => $code->points,
            'amount' => (float) $code->amount,
            'shop' => ['id' => $code->shop->id, 'name' => $code->shop->name],
            'balance' => $loyalty->balance($request->user()),
            'message' => __('انضافت :points نقطة من :shop 🎉', ['points' => $code->points, 'shop' => $code->shop->name]),
        ]);
    }

    /** Accepts the bare token or the whole scanned link (…/points/claim/TOKEN). */
    public static function token(string $scanned): string
    {
        $scanned = trim($scanned);

        return preg_match('~/points/claim/([A-Za-z0-9]{20,64})~', $scanned, $m) ? $m[1] : $scanned;
    }

    private function authorizeShop(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
    }
}
