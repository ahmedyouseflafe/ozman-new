<?php

namespace App\Http\Controllers;

use App\Models\{Shop, StorePointCode, User};
use App\Services\StorePoints;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Points at the till, on the dashboard: the same one-time QR the app makes, for shops that keep a computer or
 * tablet at the counter. Same rules as the app (StorePoints): 10 minutes, once, never for the shop's own people.
 */
class StorePointsTillController extends Controller
{
    public function page(Request $request): View
    {
        $this->authorizeRoute($request);
        $shops = $this->shops($request->user());
        abort_if($shops->isEmpty(), 404);
        $shop = $shops->firstWhere('id', $request->integer('shop_id')) ?? $shops->first();

        return view('admin.store_points.till', ['shops' => $shops, 'shop' => $shop, 'minutes' => StorePoints::MINUTES, 'max' => StorePoints::MAX_AMOUNT]);
    }

    public function store(Request $request, Shop $shop, StorePoints $points): JsonResponse
    {
        $this->authorizeRoute($request);
        $this->authorizeShop($request->user(), $shop);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01', 'max:'.StorePoints::MAX_AMOUNT]]);

        return response()->json(['code' => $points->present($points->create($shop, $request->user(), (float) $data['amount']), true)], 201);
    }

    public function show(Request $request, StorePointCode $code, StorePoints $points): JsonResponse
    {
        $this->authorizeRoute($request);
        $this->authorizeShop($request->user(), Shop::findOrFail($code->shop_id));

        return response()->json(['code' => $points->present($code)], 200, ['Cache-Control' => 'no-store']);
    }

    public function destroy(Request $request, StorePointCode $code, StorePoints $points): JsonResponse
    {
        $this->authorizeRoute($request);
        $this->authorizeShop($request->user(), Shop::findOrFail($code->shop_id));
        if ($code->status() === 'open') {
            $code->update(['cancelled_at' => now()]);
        }

        return response()->json(['code' => $points->present($code->fresh())]);
    }

    /** Shop owners always reach their own till; employees and others need the "points at the till" permission. */
    private function authorizeRoute(Request $request): void
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopOwner() || $user->canAccessRouteName($request->route()?->getName()), 403);
    }

    /** The shops this account works for (all of them for the Ozman admin). */
    private function shops(User $user): Collection
    {
        return Shop::where('is_active', true)
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->accessibleShopIds()))
            ->orderBy('name')->get(['id', 'name', 'catalog_type']);
    }

    private function authorizeShop(User $user, Shop $shop): void
    {
        abort_unless($user->isSuperAdmin() || in_array($shop->id, $user->accessibleShopIds(), true), 403);
    }
}
